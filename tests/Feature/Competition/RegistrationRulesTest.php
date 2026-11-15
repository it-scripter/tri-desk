<?php

use App\Domain\Competition\Data\RegistrationData;
use App\Domain\Competition\Enums\Gender;
use App\Domain\Competition\Exceptions\DuplicateRegistration;
use App\Domain\Competition\Exceptions\RegistrationRejected;
use App\Domain\Competition\Models\AgeGroupScheme;
use App\Domain\Competition\Models\PriceTier;
use App\Domain\Competition\Models\Race;
use App\Domain\Competition\Services\RegistrationService;
use Carbon\CarbonImmutable;

/**
 * Rules on the persons of a registration: duplicates (W-16), consent for
 * minors (W-17), age limits by birth year and the gender the race allows
 * (W-05).
 */
beforeEach(function () {
    $this->travelTo('2027-03-01 12:00:00');
    $this->service = app(RegistrationService::class);
});

it('W-16: refuses a duplicate in the public form', function () {
    $race = openRace();
    $this->service->register(registrationFor($race, [participant('Erika', 'Muster', '1987-05-10')]));

    $this->service->register(registrationFor($race, [participant('erika', 'MUSTER ', '1987-05-10')]));
})->throws(DuplicateRegistration::class, 'bereits angemeldet');

it('W-16: lets the orga team save a duplicate once it confirmed the warning', function () {
    $race = openRace();
    $this->service->register(registrationFor($race, [participant()]));

    expect(fn () => $this->service->register(registrationFor($race, [participant()], byOrga: true)))
        ->toThrow(DuplicateRegistration::class);

    $registration = $this->service->register(registrationFor($race, [participant()], byOrga: true, allowDuplicate: true))->registration;

    expect($registration->exists)->toBeTrue();
});

it('W-16: ignores other races, other birth dates and cancelled entries', function () {
    $race = openRace();
    $other = Race::factory()->for($race->edition)->create();
    PriceTier::factory()->for($other)->create();

    $first = $this->service->register(registrationFor($race, [participant()]))->registration;

    $this->service->register(registrationFor($other, [participant()]));
    $this->service->register(registrationFor($race, [participant(birthDate: '1988-05-10')]));

    $this->service->cancel($first);
    $again = $this->service->register(registrationFor($race, [participant()]))->registration;

    expect($again->exists)->toBeTrue();
});

it('W-16: finds a duplicate within a relay', function () {
    $relay = openRace(['type' => 'relay']);
    $this->service->register(registrationFor($relay, [participant('Anna', 'A'), participant('Berta', 'B')]));

    $this->service->register(registrationFor($relay, [participant('Clara', 'C'), participant('Berta', 'B')]));
})->throws(DuplicateRegistration::class, 'Berta B');

it('W-17: requires guardian name and consent for a minor', function () {
    $race = openRace();

    // 17 at registration on 01.03.2027.
    $this->service->register(registrationFor($race, [participant(birthDate: '2009-06-28')]));
})->throws(RegistrationRejected::class, 'erziehungsberechtigten');

it('W-17: stores the consent with time and text version', function () {
    $race = openRace();

    $registration = $this->service->register(registrationFor($race, [
        participant(birthDate: '2012-04-01', guardian: 'Max Muster', consent: true),
    ]))->registration;

    $child = $registration->participants->sole();

    expect($child->guardian_name)->toBe('Max Muster')
        ->and($child->guardian_consented_at->format('Y-m-d H:i'))->toBe('2027-03-01 12:00')
        ->and($child->guardian_consent_version)->toBe(__('registration.guardian_consent.version'));
});

it('W-17: judges minority at the moment of registration, not on race day', function () {
    $race = openRace();

    // 17 at registration on 01.03.2027, 18 on race day 27.06.2027: the
    // contract is made at registration, so the consent is needed.
    expect(fn () => $this->service->register(registrationFor($race, [participant(birthDate: '2009-06-27')])))
        ->toThrow(RegistrationRejected::class, 'erziehungsberechtigten');

    // Turns 18 on the day of registration itself: of age.
    $registration = $this->service->register(registrationFor($race, [participant('Lea', 'Mai', '2009-03-01')]))->registration;

    expect($registration->participants->sole()->guardian_name)->toBeNull();
});

it('W-17: judges minority at the moment under test in the test mode', function () {
    $race = openRace();

    // 17 on 01.03.2027, but 18 at the moment under test.
    $registration = $this->service->register(new RegistrationData(
        race: $race,
        contactFirstName: 'Erika',
        contactLastName: 'Muster',
        contactEmail: 'erika@example.org',
        participants: [participant(birthDate: '2009-04-01')],
        termsAccepted: true,
        privacyAcknowledged: true,
        at: CarbonImmutable::parse('2027-04-15 10:00'),
        isTest: true,
    ))->registration;

    expect($registration->participants->sole()->guardian_name)->toBeNull();
});

it('W-17: stores no guardian data for adults', function () {
    $registration = $this->service->register(registrationFor(openRace(), [
        participant(guardian: 'Unnötig', consent: true),
    ]))->registration;

    expect($registration->participants->sole()->guardian_name)->toBeNull()
        ->and($registration->participants->sole()->guardian_consented_at)->toBeNull();
});

it('keeps age limits by birth year', function () {
    $race = openRace(['min_age' => 18]);

    // Turns 18 in December 2027 - old enough by birth year.
    $registration = $this->service->register(registrationFor($race, [
        participant(birthDate: '2009-12-31', guardian: 'Max Muster', consent: true),
    ]))->registration;

    expect($registration->exists)->toBeTrue()
        ->and(fn () => $this->service->register(registrationFor($race, [participant('Kim', 'Jung', '2010-01-01', guardian: 'X', consent: true)])))
        ->toThrow(RegistrationRejected::class, 'Mindestalter');
});

it('W-05: offers d only where the race allows it', function () {
    $championship = openRace(['allows_diverse' => false]);

    $this->service->register(registrationFor($championship, [participant(gender: 'd')]));
})->throws(RegistrationRejected::class, 'divers');

it('W-05: asks a diverse starter for the scoring gender where the scheme scores as chosen', function () {
    $scheme = AgeGroupScheme::factory()->scoredAsChosen()->create();
    $scheme->ageGroups()->createMany([
        ['code' => 'W20', 'gender' => 'w', 'min_age' => 20, 'sort' => 0],
        ['code' => 'M20', 'gender' => 'm', 'min_age' => 20, 'sort' => 1],
    ]);
    $race = openRace(['allows_diverse' => true, 'age_group_scheme_id' => $scheme->id]);

    expect(fn () => $this->service->register(registrationFor($race, [participant(gender: 'd')])))
        ->toThrow(RegistrationRejected::class, 'Wertung');

    $registration = $this->service->register(registrationFor($race, [participant(gender: 'd', scoredAs: 'w')]))->registration;
    $starter = $registration->participants->sole();

    expect($starter->scored_as)->toBe(Gender::Female)
        ->and($starter->ageGroup?->code)->toBe('W20');
});

it('W-17: knows whether a minor can register at a given moment', function () {
    $race = openRace();
    $march = CarbonImmutable::parse('2027-03-01');

    expect($race->admitsMinors($march))->toBeTrue();

    // The youngest admitted person is born 31.12.2009 - 17 in March.
    $race->update(['min_age' => 18]);
    expect($race->fresh()->admitsMinors($march))->toBeTrue();

    // The youngest admitted person is born 31.12.2008 - 18 in March.
    $race->update(['min_age' => 19]);
    expect($race->fresh()->admitsMinors($march))->toBeFalse();

    // Registration opening in December of the year before: 17 again.
    expect($race->fresh()->admitsMinors(CarbonImmutable::parse('2026-12-01')))->toBeTrue();

    // From 31.12.2027 on, a minimum of 18 admits only adults.
    $race->update(['min_age' => 18]);
    expect($race->fresh()->admitsMinors(CarbonImmutable::parse('2027-12-31')))->toBeFalse();
});
