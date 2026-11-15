<?php

use App\Domain\Competition\Data\OptionChoice;
use App\Domain\Competition\Enums\RegistrationStatus;
use App\Domain\Competition\Exceptions\RegistrationRejected;
use App\Domain\Competition\Models\PriceTier;
use App\Domain\Competition\Models\Race;
use App\Domain\Competition\Models\RaceOption;
use App\Domain\Competition\Models\Registration;
use App\Domain\Competition\Services\RegistrationService;
use Illuminate\Support\Facades\Artisan;
use Spatie\Activitylog\Models\Activity;

/**
 * Registration and its lifecycle: places are reserved transactionally and
 * never overbooked (specification 9), unpaid places expire after the
 * payment window of the edition (W-22), price and options are frozen at the
 * moment of registration (W-03), relays enter with all members (W-12).
 */
beforeEach(function () {
    $this->travelTo('2027-03-01 12:00:00');
    $this->service = app(RegistrationService::class);
});

it('reserves a place for the payment window of the edition', function () {
    $race = openRace();

    $result = $this->service->register(registrationFor($race));
    $registration = $result->registration;

    expect($registration->status)->toBe(RegistrationStatus::Reserved)
        ->and($registration->amount_cents)->toBe(4500)
        ->and($registration->reserved_until->format('Y-m-d H:i'))->toBe('2027-03-03 12:00')
        ->and($registration->participants)->toHaveCount(1)
        ->and($registration->participants->first()->fullName())->toBe('Erika Muster');
});

it('stores only the hash of the magic link token', function () {
    $result = $this->service->register(registrationFor(openRace()));

    expect($result->token)->toHaveLength(48)
        ->and($result->registration->token_hash)->toBe(hash('sha256', $result->token))
        ->and($result->registration->toArray())->not->toHaveKey('token_hash');
});

it('confirms a free entry at once', function () {
    $registration = $this->service->register(registrationFor(openRace(amountCents: 0)))->registration;

    expect($registration->status)->toBe(RegistrationStatus::Confirmed)
        ->and($registration->reserved_until)->toBeNull()
        ->and($registration->confirmed_at)->not->toBeNull();
});

it('freezes the price and the options at the moment of registration', function () {
    $race = openRace();
    $shirt = RaceOption::factory()->for($race->edition)->create(['price_cents' => 1800]);

    $registration = $this->service->register(registrationFor($race, options: [new OptionChoice($shirt)]))->registration;

    $race->priceTiers()->update(['amount_cents' => 9900]);
    $shirt->update(['price_cents' => 2500]);

    $registration->refresh();

    expect($registration->amount_cents)->toBe(4500 + 1800)
        ->and($registration->options->sole()->price_cents)->toBe(1800);
});

it('never sells more places than the race has', function () {
    $race = openRace(['participant_limit' => 2]);

    $this->service->register(registrationFor($race, [participant('Anna', 'Eins')]));
    $this->service->register(registrationFor($race, [participant('Berta', 'Zwei')]));

    expect(fn () => $this->service->register(registrationFor($race, [participant('Clara', 'Drei')])))
        ->toThrow(RegistrationRejected::class, 'ausgebucht');

    expect($race->registrations()->count())->toBe(2);
});

it('counts a relay as one place', function () {
    $race = openRace(['type' => 'relay', 'participant_limit' => 1, 'team_size_min' => 3, 'team_size_max' => 3]);

    $registration = $this->service->register(registrationFor($race, [
        participant('Anna', 'Schwimm'),
        participant('Berta', 'Rad'),
        participant('Clara', 'Lauf'),
    ], teamName: 'Die Drei'))->registration;

    expect($registration->participants->pluck('first_name')->all())->toBe(['Anna', 'Berta', 'Clara'])
        ->and($registration->team_name)->toBe('Die Drei')
        ->and(fn () => $this->service->register(registrationFor($race, [
            participant('Dora', 'A'), participant('Ella', 'B'), participant('Frida', 'C'),
        ])))->toThrow(RegistrationRejected::class, 'ausgebucht');
});

it('frees the places of cancelled and expired registrations', function () {
    $race = openRace(['participant_limit' => 1]);

    $first = $this->service->register(registrationFor($race, [participant('Anna', 'Eins')]))->registration;
    $this->service->cancel($first);

    $second = $this->service->register(registrationFor($race, [participant('Berta', 'Zwei')]))->registration;

    // The payment window has run out: the place counts as free even before
    // the expiry job has marked the reservation.
    $this->travelTo('2027-03-04 12:00:00');

    $third = $this->service->register(registrationFor($race, [participant('Clara', 'Drei')]))->registration;

    expect($first->fresh()->status)->toBe(RegistrationStatus::Cancelled)
        ->and($second->fresh()->status)->toBe(RegistrationStatus::Reserved)
        ->and($third->status)->toBe(RegistrationStatus::Reserved);
});

it('expires overdue reservations through the scheduled command', function () {
    $race = openRace();
    $overdue = Registration::factory()->for($race)->reserved('2027-03-01 11:00:00')->create();
    $pending = Registration::factory()->for($race)->reserved('2027-03-02 11:00:00')->create();
    $confirmed = Registration::factory()->for($race)->create();

    Artisan::call('registrations:expire');

    expect($overdue->fresh()->status)->toBe(RegistrationStatus::Expired)
        ->and($pending->fresh()->status)->toBe(RegistrationStatus::Reserved)
        ->and($confirmed->fresh()->status)->toBe(RegistrationStatus::Confirmed)
        ->and(Artisan::output())->toContain('Eine Reservierung verfallen');

    expect(Activity::query()->where('subject_type', $overdue->getMorphClass())->where('subject_id', $overdue->id)->where('event', 'updated')->exists())->toBeTrue();
});

it('confirms a reservation but not an expired one', function () {
    $race = openRace();
    $reserved = $this->service->register(registrationFor($race))->registration;
    $expired = Registration::factory()->for($race)->create(['status' => RegistrationStatus::Expired]);

    $this->service->confirm($reserved);

    expect($reserved->fresh()->status)->toBe(RegistrationStatus::Confirmed)
        ->and($reserved->fresh()->reserved_until)->toBeNull()
        ->and(fn () => $this->service->confirm($expired))->toThrow(RegistrationRejected::class);
});

it('never sells more of an option than its quota', function () {
    $race = openRace();
    $shirt = RaceOption::factory()->for($race->edition)->create(['name' => 'Funktionsshirt', 'quota' => 5]);
    $size = $shirt->variants()->create(['name' => 'M', 'quota' => 1]);

    $this->service->register(registrationFor($race, [participant('Anna', 'Eins')], [new OptionChoice($shirt, $size)]));

    expect(fn () => $this->service->register(registrationFor($race, [participant('Berta', 'Zwei')], [new OptionChoice($shirt, $size)])))
        ->toThrow(RegistrationRejected::class, 'Funktionsshirt M ist ausverkauft');
});

it('requires a variant where the option has some', function () {
    $race = openRace();
    $shirt = RaceOption::factory()->for($race->edition)->create(['name' => 'Funktionsshirt']);
    $shirt->variants()->create(['name' => 'M']);

    $this->service->register(registrationFor($race, options: [new OptionChoice($shirt)]));
})->throws(RegistrationRejected::class, 'Variante');

it('refuses an option of another race', function () {
    $race = openRace();
    $chip = RaceOption::factory()->for($race->edition)->create(['name' => 'Chip-Miete']);
    $chip->races()->attach(Race::factory()->for($race->edition)->create());

    $this->service->register(registrationFor($race, options: [new OptionChoice($chip)]));
})->throws(RegistrationRejected::class, 'nicht angeboten');

it('keeps the public form to the registration window', function () {
    $race = openRace();

    $this->travelTo('2027-02-15 09:59:00');
    expect(fn () => $this->service->register(registrationFor($race)))->toThrow(RegistrationRejected::class, 'noch nicht geöffnet');

    $this->travelTo('2027-06-21 00:00:00');
    expect(fn () => $this->service->register(registrationFor($race)))->toThrow(RegistrationRejected::class, 'Meldeschluss');
});

it('lets the orga team enter late at the late entry price', function () {
    $race = openRace();
    $race->priceTiers()->update(['valid_until' => '2027-06-20 23:59:00']);
    PriceTier::factory()->for($race)->orgaOnly()->create(['name' => 'Nachmeldung', 'amount_cents' => 6000, 'valid_from' => '2027-06-21 00:00:00']);

    $this->travelTo('2027-06-27 08:00:00');

    $registration = $this->service->register(registrationFor($race, byOrga: true))->registration;

    expect($registration->amount_cents)->toBe(6000)
        ->and($registration->entered_by_orga)->toBeTrue();
});

it('refuses a hidden race in the public form and a closed edition everywhere', function () {
    $hidden = openRace(['is_public' => false]);
    expect(fn () => $this->service->register(registrationFor($hidden)))->toThrow(RegistrationRejected::class, 'keine öffentliche');

    $closed = openRace();
    $closed->edition->update(['status' => 'archived']);
    expect(fn () => $this->service->register(registrationFor($closed->fresh(), byOrga: true)))->toThrow(RegistrationRejected::class, 'abgeschlossen');
});

it('keeps team sizes', function () {
    $relay = openRace(['type' => 'relay', 'team_size_min' => 3, 'team_size_max' => 3]);
    $single = openRace();

    expect(fn () => $this->service->register(registrationFor($relay, [participant('A', 'A'), participant('B', 'B')])))
        ->toThrow(RegistrationRejected::class, 'mindestens 3')
        ->and(fn () => $this->service->register(registrationFor($single, [participant('A', 'A'), participant('B', 'B')])))
        ->toThrow(RegistrationRejected::class, 'genau eine Person');
});
