<?php

namespace App\Domain\Competition\Services;

use App\Domain\Competition\Data\OptionChoice;
use App\Domain\Competition\Data\ParticipantData;
use App\Domain\Competition\Data\RegistrationData;
use App\Domain\Competition\Data\RegistrationResult;
use App\Domain\Competition\Enums\DiverseScoring;
use App\Domain\Competition\Enums\Gender;
use App\Domain\Competition\Enums\RaceFieldScope;
use App\Domain\Competition\Enums\RegistrationStatus;
use App\Domain\Competition\Exceptions\DuplicateRegistration;
use App\Domain\Competition\Exceptions\RegistrationRejected;
use App\Domain\Competition\Models\PriceTier;
use App\Domain\Competition\Models\Race;
use App\Domain\Competition\Models\RaceOption;
use App\Domain\Competition\Models\RaceOptionVariant;
use App\Domain\Competition\Models\Registration;
use App\Domain\Competition\Models\RegistrationOption;
use App\Domain\Competition\Models\RegistrationParticipant;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Takes registrations (specification 4.2) and keeps their lifecycle (W-22).
 *
 * The place is reserved inside a transaction that locks the race row, and
 * the rows of the booked options, before counting: two people registering
 * for the last place at the same moment are serialised, so a race is never
 * overbooked (specification 9, load at registration start).
 *
 * Rules checked before anything is stored:
 * - registration window, price tier and race visibility
 * - team size (W-12), allowed gender (W-05), age limits by birth year
 * - consent of a parent or guardian for minors, judged to the day of
 *   registration (W-17)
 * - classification of relays and teams (W-18)
 * - duplicates by name and birth date (W-16)
 */
class RegistrationService
{
    public function __construct(
        private readonly PriceCalculator $prices,
        private readonly AgeGroupCalculator $ageGroups,
        private readonly TeamClassifier $classifier,
    ) {}

    /**
     * @throws RegistrationRejected
     */
    public function register(RegistrationData $data): RegistrationResult
    {
        $race = $data->race->loadMissing(['edition', 'editionDay', 'ageGroupScheme.ageGroups', 'fields']);

        // Two clocks: the rules (window, price tier, minority) are judged at
        // the moment of the data, which the test mode may set; places, the
        // payment window and consent times always run on the real clock.
        $now = CarbonImmutable::now();
        $at = $data->at ?? $now;

        $this->precheck($data);
        $tier = $this->tier($race, $data->byOrga, $at);

        $classification = $race->isTeamRace()
            ? $this->classifier->classify($race, $data->participants)
            : null;

        $this->checkOptions($race, $data->options);
        $this->assertConsents($data);

        $token = Str::random(48);

        $registration = DB::transaction(function () use ($race, $data, $tier, $classification, $token, $now, $at): Registration {
            $this->reservePlace($race, $now);
            $this->reserveOptions($data->options, $now);

            $amount = $tier->amount_cents + array_sum(array_map(
                fn (OptionChoice $choice): int => $choice->option->price_cents,
                $data->options,
            ));

            // A free entry has nothing to wait for (W-22).
            $free = $amount === 0;

            $registration = Registration::create([
                'race_id' => $race->id,
                'price_tier_id' => $tier->id,
                'amount_cents' => $amount,
                'status' => $free ? RegistrationStatus::Confirmed : RegistrationStatus::Reserved,
                'reserved_until' => $free ? null : $now->addHours($race->edition->payment_window_hours),
                'confirmed_at' => $free ? $now : null,
                'contact_first_name' => $data->contactFirstName,
                'contact_last_name' => $data->contactLastName,
                'contact_email' => $data->contactEmail,
                'team_name' => $race->isTeamRace() ? $data->teamName : null,
                'team_classification' => $classification,
                'extra' => $this->answers($race, RaceFieldScope::Registration, $data->extra),
                'hide_name_in_lists' => $data->hideNameInLists,
                'terms_accepted_at' => $data->termsAccepted ? $now : null,
                'terms_version' => $data->termsAccepted ? __('registration_form.consents.terms.version') : null,
                'privacy_acknowledged_at' => $data->privacyAcknowledged ? $now : null,
                'privacy_version' => $data->privacyAcknowledged ? __('registration_form.consents.privacy.version') : null,
                'token_hash' => hash('sha256', $token),
                'entered_by_orga' => $data->byOrga,
                'is_test' => $data->isTest,
            ]);

            foreach ($data->participants as $position => $participant) {
                $this->storeParticipant($registration, $race, $participant, $position, $now, $at);
            }

            foreach ($data->options as $choice) {
                RegistrationOption::create([
                    'registration_id' => $registration->id,
                    'race_option_id' => $choice->option->id,
                    'race_option_variant_id' => $choice->variant?->id,
                    'price_cents' => $choice->option->price_cents,
                ]);
            }

            return $registration;
        });

        return new RegistrationResult($registration, $token);
    }

    /**
     * Every rule on race and persons, without options, consents or storing
     * anything. The form runs it after the person step, so problems show up
     * where they were made instead of at the very end.
     *
     * @throws RegistrationRejected
     */
    public function precheck(RegistrationData $data): void
    {
        $race = $data->race->loadMissing(['edition', 'editionDay', 'ageGroupScheme.ageGroups', 'fields']);
        $at = $data->at ?? CarbonImmutable::now();

        $this->assertOpen($race, $data->byOrga, $at);
        $this->tier($race, $data->byOrga, $at);
        $this->assertTeamSize($race, $data->participants);

        foreach ($data->participants as $index => $participant) {
            $this->assertParticipant($race, $participant, $index, $at);
            $this->assertFields($race, RaceFieldScope::Participant, $participant->extra, $index);
        }

        $this->assertFields($race, RaceFieldScope::Registration, $data->extra);

        if ($race->isTeamRace()) {
            $this->classifier->classify($race, $data->participants);
        }

        $this->assertNoDuplicate($race, $data);
    }

    /**
     * @param  array<int, OptionChoice>  $options
     *
     * @throws RegistrationRejected
     */
    public function checkOptions(Race $race, array $options): void
    {
        $this->assertOptions($race, $options);
    }

    /**
     * Payment received or direct debit mandate given (W-22). Called by the
     * payment package (2c). An expired or cancelled registration cannot be
     * revived this way - its place may long be taken.
     *
     * @throws RegistrationRejected
     */
    public function confirm(Registration $registration): void
    {
        if ($registration->status === RegistrationStatus::Confirmed) {
            return;
        }

        if ($registration->status !== RegistrationStatus::Reserved) {
            throw new RegistrationRejected('not_reserved');
        }

        $registration->update([
            'status' => RegistrationStatus::Confirmed,
            'confirmed_at' => now(),
            'reserved_until' => null,
        ]);
    }

    /**
     * Cancels a registration and frees its place. A refund, where the
     * cancellation rules grant one (W-04), is the payment package's business.
     */
    public function cancel(Registration $registration): void
    {
        if (in_array($registration->status, [RegistrationStatus::Cancelled, RegistrationStatus::Expired], true)) {
            return;
        }

        $registration->update([
            'status' => RegistrationStatus::Cancelled,
            'cancelled_at' => now(),
            'reserved_until' => null,
        ]);
    }

    /**
     * Releases every reservation whose payment window ran out (W-22). Run
     * from the scheduler; returns how many were released.
     */
    public function expireOverdue(): int
    {
        $count = 0;

        // Row by row rather than a mass update, so every release is in the
        // audit log (B-03). lazyById, not each: the update changes the very
        // column the query filters on, which would make offset paging skip
        // rows.
        Registration::query()
            ->where('status', RegistrationStatus::Reserved)
            ->where('reserved_until', '<=', now())
            ->lazyById()
            ->each(function (Registration $registration) use (&$count): void {
                $registration->update(['status' => RegistrationStatus::Expired]);
                $count++;
            });

        return $count;
    }

    /**
     * @throws RegistrationRejected
     */
    private function assertOpen(Race $race, bool $byOrga, CarbonImmutable $now): void
    {
        $edition = $race->edition;

        if ($edition->isClosed()) {
            throw new RegistrationRejected('edition_closed');
        }

        // The orga team may enter late, e.g. on race day; the window only
        // binds the public form.
        if ($byOrga) {
            return;
        }

        if (! $race->is_public) {
            throw new RegistrationRejected('not_public');
        }

        if ($edition->registration_opens_at === null || $now->lt($edition->registration_opens_at)) {
            throw new RegistrationRejected('not_open_yet');
        }

        if ($edition->registration_closes_at !== null && $now->gt($edition->registration_closes_at)) {
            throw new RegistrationRejected('closed');
        }
    }

    /**
     * @throws RegistrationRejected
     */
    private function tier(Race $race, bool $byOrga, CarbonImmutable $now): PriceTier
    {
        return $this->prices->tierFor($race, $now, $byOrga)
            ?? throw new RegistrationRejected('no_price');
    }

    /**
     * @param  array<int, ParticipantData>  $participants
     *
     * @throws RegistrationRejected
     */
    private function assertTeamSize(Race $race, array $participants): void
    {
        $count = count($participants);

        if (! $race->isTeamRace()) {
            if ($count !== 1) {
                throw new RegistrationRejected('individual_size');
            }

            return;
        }

        // A relay or team without bounds needs at least two members.
        $min = $race->team_size_min ?? 2;
        $max = $race->team_size_max;

        if ($count < $min || ($max !== null && $count > $max)) {
            throw new RegistrationRejected('team_size', null, [
                'min' => $min,
                'max' => $max ?? '-',
            ]);
        }
    }

    /**
     * @throws RegistrationRejected
     */
    private function assertParticipant(Race $race, ParticipantData $participant, int $index, CarbonImmutable $at): void
    {
        if (! in_array($participant->gender, $race->allowedGenders(), true)) {
            throw new RegistrationRejected('gender_not_allowed', $index);
        }

        if ($participant->gender === Gender::Diverse
            && $race->ageGroupScheme?->diverse_scoring === DiverseScoring::AsChosen
            && ! in_array($participant->scoredAs, [Gender::Male, Gender::Female], true)) {
            throw new RegistrationRejected('scored_as_required', $index);
        }

        // Age limits follow the birth year, like the age groups (W-05).
        $age = $this->ageGroups->ageInYear($participant->birthDate->year, $race->edition->year);

        if ($race->min_age !== null && $age < $race->min_age) {
            throw new RegistrationRejected('too_young', $index, ['age' => $race->min_age]);
        }

        if ($race->max_age !== null && $age > $race->max_age) {
            throw new RegistrationRejected('too_old', $index, ['age' => $race->max_age]);
        }

        if ($this->isMinor($participant, $at)
            && (blank($participant->guardianName) || ! $participant->guardianConsent)) {
            throw new RegistrationRejected('guardian_required', $index);
        }
    }

    /**
     * Minority is a legal question and judged to the day, at the moment of
     * registration (W-17): that is when the contract is made, and a minor
     * needs the consent of a guardian for it (§§ 106 ff. BGB) - even when
     * of age by race day. Whoever is a minor on race day was one at
     * registration too, so this one moment covers both.
     */
    private function isMinor(ParticipantData $participant, CarbonImmutable $at): bool
    {
        return $participant->birthDate->diffInYears($at) < 18;
    }

    /**
     * @param  array<int, OptionChoice>  $options
     *
     * @throws RegistrationRejected
     */
    private function assertOptions(Race $race, array $options): void
    {
        foreach ($options as $choice) {
            if (! $choice->option->isOfferedFor($race)) {
                throw new RegistrationRejected('option_not_offered', null, ['option' => $choice->option->name]);
            }

            $hasVariants = $choice->option->variants()->exists();

            if ($hasVariants && $choice->variant === null) {
                throw new RegistrationRejected('variant_required', null, ['option' => $choice->option->name]);
            }

            if ($choice->variant !== null && $choice->variant->race_option_id !== $choice->option->id) {
                throw new RegistrationRejected('option_not_offered', null, ['option' => $choice->option->name]);
            }
        }
    }

    /**
     * Terms and privacy notice are required in the public form (W-10). The
     * orga team enters on behalf of people who agreed elsewhere, e.g. on
     * paper at the late entry desk.
     *
     * @throws RegistrationRejected
     */
    private function assertConsents(RegistrationData $data): void
    {
        if ($data->byOrga) {
            return;
        }

        if (! $data->termsAccepted || ! $data->privacyAcknowledged) {
            throw new RegistrationRejected('consent_required');
        }
    }

    /**
     * Required additional fields must be answered (W-11). The form checks the
     * types; this is the server side guard against a form that skipped them.
     *
     * @param  array<string, mixed>  $answers
     *
     * @throws RegistrationRejected
     */
    private function assertFields(Race $race, RaceFieldScope $scope, array $answers, ?int $index = null): void
    {
        foreach ($race->fields->where('scope', $scope) as $field) {
            if ($field->is_required && blank($answers[$field->key] ?? null)) {
                throw new RegistrationRejected('field_required', $index, ['field' => $field->label]);
            }
        }
    }

    /**
     * Only answers to fields the race has are kept; anything else a form sent
     * along is dropped. Empty answers are not stored.
     *
     * @param  array<string, mixed>  $answers
     * @return array<string, mixed>|null
     */
    private function answers(Race $race, RaceFieldScope $scope, array $answers): ?array
    {
        $kept = [];

        foreach ($race->fields->where('scope', $scope) as $field) {
            $value = $answers[$field->key] ?? null;

            if (filled($value)) {
                $kept[$field->key] = is_string($value) ? trim($value) : $value;
            }
        }

        return $kept === [] ? null : $kept;
    }

    /**
     * Same first name, last name and birth date in the same race (W-16).
     * The collation compares names without regard to case; cancelled,
     * expired and test registrations do not count.
     *
     * @throws DuplicateRegistration
     */
    private function assertNoDuplicate(Race $race, RegistrationData $data): void
    {
        if ($data->byOrga && $data->allowDuplicate) {
            return;
        }

        foreach ($data->participants as $index => $participant) {
            $exists = RegistrationParticipant::query()
                ->where('first_name', trim($participant->firstName))
                ->where('last_name', trim($participant->lastName))
                ->whereDate('birth_date', $participant->birthDate)
                ->whereHas('registration', fn ($query) => $query
                    ->where('race_id', $race->id)
                    ->holdingPlace())
                ->exists();

            if ($exists) {
                throw new DuplicateRegistration($index, trim($participant->firstName).' '.trim($participant->lastName));
            }
        }
    }

    /**
     * Locks the race and counts the places taken. Runs inside the
     * transaction, so a concurrent registration waits until this one is
     * stored or rolled back.
     *
     * @throws RegistrationRejected
     */
    private function reservePlace(Race $race, CarbonImmutable $now): void
    {
        $locked = Race::query()->lockForUpdate()->findOrFail($race->id);

        if ($locked->participant_limit === null) {
            return;
        }

        $taken = Registration::query()
            ->where('race_id', $race->id)
            ->holdingPlace($now)
            ->count();

        if ($taken >= $locked->participant_limit) {
            throw new RegistrationRejected('sold_out');
        }
    }

    /**
     * Same as reservePlace, for the quotas of options and variants.
     *
     * @param  array<int, OptionChoice>  $options
     *
     * @throws RegistrationRejected
     */
    private function reserveOptions(array $options, CarbonImmutable $now): void
    {
        foreach ($options as $choice) {
            $option = RaceOption::query()->lockForUpdate()->findOrFail($choice->option->id);

            if ($option->quota !== null) {
                $taken = RegistrationOption::query()
                    ->where('race_option_id', $option->id)
                    ->whereHas('registration', fn ($query) => $query->holdingPlace($now))
                    ->count();

                if ($taken >= $option->quota) {
                    throw new RegistrationRejected('option_sold_out', null, ['option' => $option->name]);
                }
            }

            if ($choice->variant === null) {
                continue;
            }

            $variant = RaceOptionVariant::query()->lockForUpdate()->findOrFail($choice->variant->id);

            if ($variant->quota !== null) {
                $taken = RegistrationOption::query()
                    ->where('race_option_variant_id', $variant->id)
                    ->whereHas('registration', fn ($query) => $query->holdingPlace($now))
                    ->count();

                if ($taken >= $variant->quota) {
                    throw new RegistrationRejected('option_sold_out', null, ['option' => "{$option->name} {$variant->name}"]);
                }
            }
        }
    }

    private function storeParticipant(
        Registration $registration,
        Race $race,
        ParticipantData $participant,
        int $position,
        CarbonImmutable $now,
        CarbonImmutable $at,
    ): void {
        $minor = $this->isMinor($participant, $at);

        // Relays and teams have no age groups (W-18); single starters get
        // theirs from the scheme of the race.
        $ageGroup = $race->isTeamRace()
            ? null
            : $this->ageGroups->groupForRace($race, $participant->birthDate->year, $participant->gender, $participant->scoredAs);

        RegistrationParticipant::create([
            'registration_id' => $registration->id,
            'position' => $position,
            'first_name' => trim($participant->firstName),
            'last_name' => trim($participant->lastName),
            'birth_date' => $participant->birthDate,
            'gender' => $participant->gender,
            'scored_as' => $participant->gender === Gender::Diverse ? $participant->scoredAs : null,
            'age_group_id' => $ageGroup?->id,
            'extra' => $this->answers($race, RaceFieldScope::Participant, $participant->extra),
            'guardian_name' => $minor ? $participant->guardianName : null,
            'guardian_consented_at' => $minor ? $now : null,
            'guardian_consent_version' => $minor ? __('registration.guardian_consent.version') : null,
        ]);
    }
}
