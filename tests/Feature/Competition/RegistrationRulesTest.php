<?php

use App\Domain\Competition\Enums\Gender;
use App\Domain\Competition\Exceptions\DuplicateRegistration;
use App\Domain\Competition\Exceptions\RegistrationRejected;
use App\Domain\Competition\Models\AgeGroupScheme;
use App\Domain\Competition\Models\PriceTier;
use App\Domain\Competition\Models\Race;
use App\Domain\Competition\Services\RegistrationService;

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

    // Turns 18 on 28.06.2027, the day after the race: still a minor.
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

it('W-17: judges minority to the day of the race', function () {
    $race = openRace();

    // Turns 18 on the race day itself: of age, no consent needed.
    $registration = $this->service->register(registrationFor($race, [participant(birthDate: '2009-06-27')]))->registration;

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

it('W-17: knows whether a minor can start at all', function () {
    $race = openRace();

    expect($race->admitsMinors())->toBeTrue();

    $race->update(['min_age' => 18]);
    expect($race->fresh()->admitsMinors())->toBeTrue();

    $race->update(['min_age' => 19]);
    expect($race->fresh()->admitsMinors())->toBeFalse();

    // Without a date, minority falls back to the birth year as well.
    $race->edition->update(['starts_on' => null, 'ends_on' => null]);
    $race->update(['min_age' => 18]);
    expect($race->fresh()->admitsMinors())->toBeFalse();
});
