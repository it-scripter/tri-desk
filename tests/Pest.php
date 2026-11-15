<?php

use App\Domain\Competition\Data\OptionChoice;
use App\Domain\Competition\Data\ParticipantData;
use App\Domain\Competition\Data\RegistrationData;
use App\Domain\Competition\Enums\Gender;
use App\Domain\Competition\Models\Edition;
use App\Domain\Competition\Models\PriceTier;
use App\Domain\Competition\Models\Race;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Feature tests boot the full application and run against the MariaDB test
| database configured in phpunit.xml. Unit tests stay free of the framework.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Registration helpers
|--------------------------------------------------------------------------
|
| Registrations are built from data objects in many tests; these keep the
| tests to what they are about.
|
*/

/**
 * @param  array<string, mixed>  $extra
 */
function participant(
    string $first = 'Erika',
    string $last = 'Muster',
    string $birthDate = '1987-05-10',
    string $gender = 'w',
    ?string $scoredAs = null,
    ?string $guardian = null,
    bool $consent = false,
    array $extra = [],
): ParticipantData {
    return new ParticipantData(
        firstName: $first,
        lastName: $last,
        birthDate: CarbonImmutable::parse($birthDate),
        gender: Gender::from($gender),
        scoredAs: $scoredAs !== null ? Gender::from($scoredAs) : null,
        guardianName: $guardian,
        guardianConsent: $consent,
        extra: $extra,
    );
}

/**
 * @param  array<int, ParticipantData>|null  $participants
 * @param  array<int, OptionChoice>  $options
 * @param  array<string, mixed>  $extra
 */
function registrationFor(
    Race $race,
    ?array $participants = null,
    array $options = [],
    bool $byOrga = false,
    bool $allowDuplicate = false,
    ?string $teamName = null,
    array $extra = [],
    bool $consents = true,
    bool $hideName = false,
): RegistrationData {
    return new RegistrationData(
        race: $race,
        contactFirstName: 'Erika',
        contactLastName: 'Muster',
        contactEmail: 'erika@example.org',
        participants: $participants ?? [participant()],
        options: $options,
        teamName: $teamName,
        byOrga: $byOrga,
        allowDuplicate: $allowDuplicate,
        extra: $extra,
        termsAccepted: $consents,
        privacyAcknowledged: $consents,
        hideNameInLists: $hideName,
    );
}

/**
 * An edition open for registration with one race and a price, as the
 * registration tests need it. Registration is open from 15.02.2027, the
 * race day is 27.06.2027; tests travel to 01.03.2027. The edition is the
 * current one of its competition, so the public form finds it.
 *
 * @param  array<string, mixed>  $race
 */
function openRace(array $race = [], int $amountCents = 4500): Race
{
    $edition = Edition::factory()->create([
        'year' => 2027,
        'slug' => '2027',
        'starts_on' => '2027-06-27',
        'ends_on' => '2027-06-27',
        'registration_opens_at' => '2027-02-15 10:00:00',
        'registration_closes_at' => '2027-06-20 23:59:00',
        'payment_window_hours' => 48,
        'is_current' => true,
    ]);

    $model = Race::factory()->for($edition)->create($race);

    PriceTier::factory()->for($model)->create(['amount_cents' => $amountCents]);

    return $model;
}

/**
 * Input of the persons step of the public form for a single starter.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function personInput(array $overrides = []): array
{
    return array_replace([
        'contact' => ['first_name' => 'Erika', 'last_name' => 'Muster', 'email' => 'erika@example.org'],
        'participants' => [[
            'first_name' => 'Erika',
            'last_name' => 'Muster',
            'birth_date' => '1987-05-10',
            'gender' => 'w',
        ]],
    ], $overrides);
}
