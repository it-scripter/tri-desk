<?php

namespace App\Http\Controllers;

use App\Domain\Competition\Data\OptionChoice;
use App\Domain\Competition\Enums\Gender;
use App\Domain\Competition\Enums\RaceFieldScope;
use App\Domain\Competition\Exceptions\RegistrationRejected;
use App\Domain\Competition\Models\Competition;
use App\Domain\Competition\Models\Race;
use App\Domain\Competition\Models\RaceField;
use App\Domain\Competition\Models\RaceOption;
use App\Domain\Competition\Models\Registration;
use App\Domain\Competition\Services\PriceCalculator;
use App\Domain\Competition\Services\RegistrationAvailability;
use App\Domain\Competition\Services\RegistrationService;
use App\Domain\Messaging\Mail\RegistrationConfirmation;
use App\Http\Registration\RegistrationDraft;
use App\Http\Registration\RegistrationTestMode;
use App\Http\Registration\RejectionField;
use App\Support\CompetitionContext;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * The public registration form (specification W-10): race, persons, options,
 * consents, summary. Each step validates on the server and keeps its input in
 * the draft; the registration service has the last word on every rule.
 *
 * Payment follows with package 2c - until then the form ends with the
 * reserved place and the confirmation mail (W-15).
 *
 * In the test mode the same steps run for the edition under test, judged at
 * the moment under test, and store a test registration.
 */
class RegistrationFormController extends Controller
{
    /**
     * Seconds that must pass between opening the form and submitting it.
     * Bots post at once; no person fills in the persons step that fast
     * (specification 9: spam protection without captcha).
     */
    private const MIN_SECONDS = 5;

    public function __construct(
        private readonly CompetitionContext $context,
        private readonly RegistrationService $registrations,
        private readonly PriceCalculator $prices,
        private readonly RegistrationAvailability $availability,
    ) {}

    /**
     * The race step. A link from the competition page may name the race
     * (?wettbewerb=slug); an open race is then chosen right away and the
     * form starts with the persons. Anything else shows the list.
     */
    public function race(Request $request): View|RedirectResponse
    {
        $edition = $this->context->edition();
        $at = $this->at($request);
        $races = $edition !== null ? $this->availability->openRaces($edition, $at) : collect();

        $preselected = $races->firstWhere('slug', $request->query('wettbewerb'));

        if ($preselected instanceof Race && ! $this->availability->isSoldOut($preselected)) {
            $this->draft($request)->start($preselected);

            return redirect()->route('competition.registration.people');
        }

        return view('registration.race', [
            'races' => $races,
            'open' => $edition !== null && $this->availability->isEditionOpen($edition, $at),
            'tiers' => $races->mapWithKeys(fn (Race $race): array => [$race->id => $this->prices->tierFor($race, $at)]),
            'soldOut' => $races->mapWithKeys(fn (Race $race): array => [$race->id => $this->availability->isSoldOut($race)]),
        ]);
    }

    public function chooseRace(Request $request): RedirectResponse
    {
        $edition = $this->context->requireEdition();

        $validated = $request->validate([
            'race' => ['required', 'integer', Rule::exists('races', 'id')
                ->where('edition_id', $edition->id)
                ->where('is_public', true)],
        ]);

        $race = Race::query()->whereKey($validated['race'])->firstOrFail();

        if (! $this->availability->isRaceOpen($race, $this->at($request))) {
            return redirect()->route('competition.registration.race')
                ->withErrors(['registration' => __('registration_form.race.none')]);
        }

        $this->draft($request)->start($race);

        return redirect()->route('competition.registration.people');
    }

    public function people(Request $request): View|RedirectResponse
    {
        $race = $this->draftRace($request);

        if ($race === null) {
            return $this->restart();
        }

        return view('registration.people', [
            'race' => $race,
            'draft' => $this->draft($request),
            'slots' => $this->slots($race),
            'required' => $this->requiredSlots($race),
            'genders' => $race->allowedGenders(),
            'participantFields' => $race->fields->where('scope', RaceFieldScope::Participant)->values(),
            'registrationFields' => $race->fields->where('scope', RaceFieldScope::Registration)->values(),
            'hasOptions' => RaceOption::query()->offeredFor($race)->exists(),
            // Minority is judged at the moment of registration (W-17).
            'registeredAt' => $this->at($request),
        ]);
    }

    public function storePeople(Request $request): RedirectResponse
    {
        $race = $this->draftRace($request);

        if ($race === null) {
            return $this->restart();
        }

        // Honeypot: a field people never see. Whoever fills it in is a bot.
        if (filled($request->input('website'))) {
            $this->draft($request)->forget();

            return redirect()->route('competition.registration.race')
                ->withErrors(['registration' => __('registration_form.errors.spam')]);
        }

        $validated = $request->validate($this->peopleRules($race), [], $this->peopleAttributes($race));

        $participants = array_values(array_filter(
            $validated['participants'],
            fn (array $person): bool => filled($person['first_name'] ?? null),
        ));

        $draft = $this->draft($request);
        $draft->put([
            'contact' => $validated['contact'],
            'team_name' => $validated['team_name'] ?? null,
            'participants' => $participants,
            'extra' => $validated['extra'] ?? [],
        ]);

        try {
            $this->registrations->precheck($draft->toData($race, $this->test($request)));
        } catch (RegistrationRejected $rejection) {
            throw ValidationException::withMessages([RejectionField::for($rejection) => $rejection->getMessage()]);
        }

        return redirect()->route(RaceOption::query()->offeredFor($race)->exists()
            ? 'competition.registration.options'
            : 'competition.registration.consents');
    }

    public function options(Request $request): View|RedirectResponse
    {
        $race = $this->draftRace($request);

        if ($race === null || ! $this->draft($request)->has('participants')) {
            return $this->restart();
        }

        return view('registration.options', [
            'race' => $race,
            'draft' => $this->draft($request),
            'options' => RaceOption::query()->offeredFor($race)->with('variants')->orderBy('sort')->get(),
        ]);
    }

    public function storeOptions(Request $request): RedirectResponse
    {
        $race = $this->draftRace($request);

        if ($race === null || ! $this->draft($request)->has('participants')) {
            return $this->restart();
        }

        $offered = RaceOption::query()->offeredFor($race)->with('variants')->get()->keyBy('id');

        $validated = $request->validate([
            'options' => ['array'],
            'options.*.selected' => ['nullable', 'boolean'],
            'options.*.variant' => ['nullable', 'integer'],
        ]);

        $chosen = [];
        $choices = [];

        foreach ($validated['options'] ?? [] as $id => $input) {
            $option = $offered->get((int) $id);

            if ($option === null || ! ($input['selected'] ?? false)) {
                continue;
            }

            $variant = $option->variants->firstWhere('id', (int) ($input['variant'] ?? 0));
            $chosen[$option->id] = $variant?->id;
            $choices[] = new OptionChoice($option, $variant);
        }

        try {
            $this->registrations->checkOptions($race, $choices);
        } catch (RegistrationRejected $rejection) {
            throw ValidationException::withMessages(['options' => $rejection->getMessage()]);
        }

        $this->draft($request)->put(['options' => $chosen]);

        return redirect()->route('competition.registration.consents');
    }

    public function consents(Request $request): View|RedirectResponse
    {
        $race = $this->draftRace($request);

        if ($race === null || ! $this->draft($request)->has('participants')) {
            return $this->restart();
        }

        return view('registration.consents', [
            'race' => $race,
            'draft' => $this->draft($request),
            'rules' => $race->edition->cancellationRules,
            'hasOptions' => RaceOption::query()->offeredFor($race)->exists(),
        ]);
    }

    public function storeConsents(Request $request): RedirectResponse
    {
        $race = $this->draftRace($request);

        if ($race === null || ! $this->draft($request)->has('participants')) {
            return $this->restart();
        }

        $validated = $request->validate([
            'terms' => ['accepted'],
            'privacy' => ['accepted'],
            'hide_name' => ['nullable', 'boolean'],
        ], [], [
            'terms' => __('registration_form.consents.terms.label'),
            'privacy' => __('registration_form.consents.privacy.label'),
        ]);

        $this->draft($request)->put(['consents' => [
            'terms' => true,
            'privacy' => true,
            'hide_name' => (bool) ($validated['hide_name'] ?? false),
        ]]);

        return redirect()->route('competition.registration.summary');
    }

    public function summary(Request $request): View|RedirectResponse
    {
        $race = $this->draftRace($request);
        $draft = $this->draft($request);

        if ($race === null || ! $draft->has('participants') || ! $draft->has('consents')) {
            return $this->restart();
        }

        $data = $draft->toData($race, $this->test($request));
        $tier = $this->prices->tierFor($race, $this->at($request));

        return view('registration.summary', [
            'race' => $race,
            'data' => $data,
            'tier' => $tier,
            'total' => $tier !== null ? $tier->amount_cents + array_sum(array_map(
                fn (OptionChoice $choice): int => $choice->option->price_cents,
                $data->options,
            )) : null,
            'hasOptions' => RaceOption::query()->offeredFor($race)->exists(),
        ]);
    }

    public function submit(Request $request): RedirectResponse
    {
        $race = $this->draftRace($request);
        $draft = $this->draft($request);

        if ($race === null || ! $draft->has('participants') || ! $draft->has('consents')) {
            return $this->restart();
        }

        $startedAt = $draft->get('started_at');

        if (! is_int($startedAt) || now()->getTimestamp() - $startedAt < self::MIN_SECONDS) {
            return redirect()->route('competition.registration.summary')
                ->withErrors(['registration' => __('registration_form.errors.spam')]);
        }

        try {
            $result = $this->registrations->register($draft->toData($race, $this->test($request)));
        } catch (RegistrationRejected $rejection) {
            return redirect()->route('competition.registration.summary')
                ->withErrors(['registration' => $rejection->getMessage()]);
        }

        Mail::to($result->registration->contact_email)
            ->queue(new RegistrationConfirmation($result->registration, $result->token));

        $draft->forget();

        return redirect()->route('competition.registration.done')
            ->with('registration_done', $result->registration->id);
    }

    public function done(Request $request): View|RedirectResponse
    {
        $id = $request->session()->get('registration_done');
        $registration = is_int($id) ? Registration::query()->with('race')->find($id) : null;

        if ($registration === null) {
            return redirect()->route('competition.registration.race');
        }

        // Keep it for a reload of this page, not longer.
        $request->session()->keep('registration_done');

        return view('registration.done', ['registration' => $registration]);
    }

    /**
     * Validation of the persons step: contact, team name, every slot of the
     * race and its additional fields (W-11). Slots beyond the required
     * number may stay empty; once a name is entered, the slot is complete.
     *
     * @return array<string, mixed>
     */
    private function peopleRules(Race $race): array
    {
        $genders = array_map(fn (Gender $gender): string => $gender->value, $race->allowedGenders());

        $rules = [
            'contact.first_name' => ['required', 'string', 'max:255'],
            'contact.last_name' => ['required', 'string', 'max:255'],
            'contact.email' => ['required', 'email', 'max:255'],
            'team_name' => [$race->isTeamRace() ? 'required' : 'nullable', 'string', 'max:255'],
            'participants' => ['required', 'array'],
        ];

        for ($i = 0; $i < $this->slots($race); $i++) {
            $required = $i < $this->requiredSlots($race)
                ? 'required'
                : "required_with:participants.{$i}.first_name,participants.{$i}.last_name";

            $rules["participants.{$i}.first_name"] = [$required, 'nullable', 'string', 'max:255'];
            $rules["participants.{$i}.last_name"] = [$required, 'nullable', 'string', 'max:255'];
            $rules["participants.{$i}.birth_date"] = [$required, 'nullable', 'date', 'before:today', 'after:1900-01-01'];
            $rules["participants.{$i}.gender"] = [$required, 'nullable', Rule::in($genders)];
            $rules["participants.{$i}.scored_as"] = ['nullable', Rule::in([Gender::Male->value, Gender::Female->value])];
            $rules["participants.{$i}.guardian_name"] = ['nullable', 'string', 'max:255'];
            $rules["participants.{$i}.guardian_consent"] = ['nullable', 'boolean'];

            foreach ($race->fields->where('scope', RaceFieldScope::Participant) as $field) {
                $rules["participants.{$i}.extra.{$field->key}"] = $this->fieldRules($field, $i < $this->requiredSlots($race) ? null : $i);
            }
        }

        foreach ($race->fields->where('scope', RaceFieldScope::Registration) as $field) {
            $rules["extra.{$field->key}"] = $field->rules();
        }

        return $rules;
    }

    /**
     * Rules of an additional field; in an optional slot a required field is
     * only required once the slot is used.
     *
     * @return array<int, mixed>
     */
    private function fieldRules(RaceField $field, ?int $optionalSlot): array
    {
        $rules = $field->rules();

        if ($optionalSlot !== null && $field->is_required) {
            $rules[0] = "required_with:participants.{$optionalSlot}.first_name";
            array_splice($rules, 1, 0, ['nullable']);
        }

        return $rules;
    }

    /**
     * German names of the fields for validation messages.
     *
     * @return array<string, string>
     */
    private function peopleAttributes(Race $race): array
    {
        $attributes = [
            'contact.first_name' => __('registration_form.people.first_name'),
            'contact.last_name' => __('registration_form.people.last_name'),
            'contact.email' => __('registration_form.people.email'),
            'team_name' => __('registration_form.people.team_name'),
        ];

        for ($i = 0; $i < $this->slots($race); $i++) {
            foreach (['first_name', 'last_name', 'birth_date', 'gender', 'scored_as', 'guardian_name'] as $name) {
                $attributes["participants.{$i}.{$name}"] = __("registration_form.people.{$name}");
            }

            foreach ($race->fields as $field) {
                $attributes["participants.{$i}.extra.{$field->key}"] = $field->label;
            }
        }

        foreach ($race->fields as $field) {
            $attributes["extra.{$field->key}"] = $field->label;
        }

        return $attributes;
    }

    /**
     * Form slots for persons: one for a single start, up to the maximum team
     * size for a relay - or the minimum, where the race sets no maximum.
     */
    private function slots(Race $race): int
    {
        if (! $race->isTeamRace()) {
            return 1;
        }

        return $race->team_size_max ?? max($race->team_size_min ?? 2, 2);
    }

    private function requiredSlots(Race $race): int
    {
        return $race->isTeamRace() ? ($race->team_size_min ?? 2) : 1;
    }

    /**
     * The test mode of this request, as applied by its middleware.
     */
    private function test(Request $request): ?RegistrationTestMode
    {
        $test = $request->attributes->get(RegistrationTestMode::class);

        return $test instanceof RegistrationTestMode ? $test : null;
    }

    /**
     * The moment the rules are judged at: now, or the moment under test.
     */
    private function at(Request $request): CarbonImmutable
    {
        return $this->test($request)->at ?? CarbonImmutable::now();
    }

    private function draftRace(Request $request): ?Race
    {
        return $this->draft($request)
            ->race($this->context->edition()?->id)
            ?->load(['edition.cancellationRules', 'editionDay', 'ageGroupScheme.ageGroups', 'fields']);
    }

    private function draft(Request $request): RegistrationDraft
    {
        $competition = $request->route('competition');

        return new RegistrationDraft(
            $request->session(),
            $competition instanceof Competition ? $competition : $this->context->requireCompetition(),
        );
    }

    private function restart(): RedirectResponse
    {
        return redirect()->route('competition.registration.race')
            ->withErrors(['registration' => __('registration_form.errors.expired')]);
    }
}
