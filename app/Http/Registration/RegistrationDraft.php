<?php

namespace App\Http\Registration;

use App\Domain\Competition\Data\OptionChoice;
use App\Domain\Competition\Data\ParticipantData;
use App\Domain\Competition\Data\RegistrationData;
use App\Domain\Competition\Enums\Gender;
use App\Domain\Competition\Models\Competition;
use App\Domain\Competition\Models\Race;
use App\Domain\Competition\Models\RaceOption;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Session\Session;

/**
 * The registration form in progress (specification W-10). Every step is a
 * plain form posted to the server; what was entered so far waits in the
 * session, one draft per competition, until the last step submits it.
 *
 * Nothing of it reaches the database before that last step.
 */
final class RegistrationDraft
{
    public function __construct(
        private readonly Session $session,
        private readonly Competition $competition,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $draft = $this->session->get($this->key(), []);

        return is_array($draft) ? $draft : [];
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return data_get($this->all(), $key, $default);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function put(array $values): void
    {
        $this->session->put($this->key(), array_replace($this->all(), $values));
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->all());
    }

    public function forget(): void
    {
        $this->session->forget($this->key());
    }

    /**
     * Starts a draft for a race. Choosing another race starts over - persons
     * and options of one race do not fit another.
     */
    public function start(Race $race): void
    {
        if ($this->get('race_id') === $race->id) {
            return;
        }

        $this->session->put($this->key(), [
            'race_id' => $race->id,
            'started_at' => now()->getTimestamp(),
        ]);
    }

    /**
     * The race of the draft, as long as it still belongs to the current
     * edition of the competition and is public.
     */
    public function race(?int $editionId): ?Race
    {
        $raceId = $this->get('race_id');

        if (! is_int($raceId) || $editionId === null) {
            return null;
        }

        return Race::query()
            ->where('edition_id', $editionId)
            ->where('is_public', true)
            ->find($raceId);
    }

    /**
     * The draft as registration data. Options and consents are taken only
     * once their steps were done; before that they are empty.
     */
    public function toData(Race $race): RegistrationData
    {
        /** @var array<int, array<string, mixed>> $participants */
        $participants = $this->get('participants', []);

        return new RegistrationData(
            race: $race,
            contactFirstName: $this->string('contact.first_name'),
            contactLastName: $this->string('contact.last_name'),
            contactEmail: $this->string('contact.email'),
            participants: array_values(array_map(self::participant(...), $participants)),
            options: $this->options($race),
            teamName: $this->get('team_name'),
            extra: $this->answers('extra'),
            termsAccepted: (bool) $this->get('consents.terms', false),
            privacyAcknowledged: (bool) $this->get('consents.privacy', false),
            hideNameInLists: (bool) $this->get('consents.hide_name', false),
        );
    }

    /**
     * @param  array<string, mixed>  $person
     */
    public static function participant(array $person): ParticipantData
    {
        $scoredAs = $person['scored_as'] ?? null;

        return new ParticipantData(
            firstName: (string) ($person['first_name'] ?? ''),
            lastName: (string) ($person['last_name'] ?? ''),
            birthDate: CarbonImmutable::parse((string) ($person['birth_date'] ?? 'now')),
            gender: Gender::from((string) ($person['gender'] ?? Gender::Male->value)),
            scoredAs: is_string($scoredAs) && $scoredAs !== '' ? Gender::from($scoredAs) : null,
            guardianName: filled($person['guardian_name'] ?? null) ? (string) $person['guardian_name'] : null,
            guardianConsent: (bool) ($person['guardian_consent'] ?? false),
            extra: is_array($person['extra'] ?? null) ? $person['extra'] : [],
        );
    }

    /**
     * Chosen options; one that disappeared since, e.g. switched off by the
     * orga team, is dropped quietly - the summary shows what remains.
     *
     * @return array<int, OptionChoice>
     */
    public function options(Race $race): array
    {
        /** @var array<int|string, int|null> $chosen */
        $chosen = $this->array('options');

        if ($chosen === []) {
            return [];
        }

        $options = RaceOption::query()
            ->offeredFor($race)
            ->with('variants')
            ->whereKey(array_keys($chosen))
            ->get();

        return $options->map(fn (RaceOption $option): OptionChoice => new OptionChoice(
            $option,
            $option->variants->firstWhere('id', $chosen[$option->id] ?? null),
        ))->values()->all();
    }

    /**
     * Answers to additional fields, keyed by the field key.
     *
     * @return array<string, mixed>
     */
    private function answers(string $key): array
    {
        $answers = [];

        foreach ($this->array($key) as $field => $value) {
            if (is_string($field)) {
                $answers[$field] = $value;
            }
        }

        return $answers;
    }

    private function string(string $key): string
    {
        $value = $this->get($key);

        return is_string($value) ? $value : '';
    }

    /**
     * @return array<int|string, mixed>
     */
    private function array(string $key): array
    {
        $value = $this->get($key);

        return is_array($value) ? $value : [];
    }

    private function key(): string
    {
        return 'registration_draft.'.$this->competition->id;
    }
}
