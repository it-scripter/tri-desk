<?php

use App\Domain\Competition\Exceptions\PriceNotAvailable;
use App\Domain\Competition\Models\Edition;
use App\Domain\Competition\Models\PriceTier;
use App\Domain\Competition\Models\Race;
use App\Domain\Competition\Models\RaceOption;
use App\Domain\Competition\Rules\NonOverlappingPriceTier;
use App\Domain\Competition\Services\PriceCalculator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

/**
 * W-03: price tiers by date (early bird, normal, late entry) and additional
 * options such as shirt, pasta party and chip rental.
 */
beforeEach(function () {
    $this->edition = Edition::factory()->create([
        'year' => 2027,
        'slug' => '2027',
        'registration_closes_at' => '2027-06-20 23:59:59',
    ]);
    $this->race = Race::factory()->for($this->edition)->create(['name' => 'Volkstriathlon']);

    PriceTier::factory()->for($this->race)->create([
        'name' => 'Early Bird',
        'amount_cents' => 3500,
        'valid_until' => '2027-03-31 23:59:59',
    ]);
    PriceTier::factory()->for($this->race)->create([
        'name' => 'Normal',
        'amount_cents' => 4500,
        'valid_from' => '2027-04-01 00:00:00',
        'valid_until' => '2027-06-20 23:59:59',
    ]);
    PriceTier::factory()->for($this->race)->orgaOnly()->create([
        'name' => 'Nachmeldung',
        'amount_cents' => 6000,
        'valid_from' => '2027-06-21 00:00:00',
    ]);

    $this->calculator = new PriceCalculator;
});

it('picks the tier valid at the moment of registration', function () {
    expect($this->calculator->tierFor($this->race, Carbon::parse('2027-02-15 10:00'))?->name)->toBe('Early Bird')
        ->and($this->calculator->tierFor($this->race, Carbon::parse('2027-03-31 23:59:59'))?->name)->toBe('Early Bird')
        ->and($this->calculator->tierFor($this->race, Carbon::parse('2027-04-01 00:00'))?->name)->toBe('Normal');
});

it('offers the late entry tier after the deadline to the orga team only', function () {
    $raceDay = Carbon::parse('2027-06-27 08:00');

    expect($this->calculator->tierFor($this->race, $raceDay))->toBeNull()
        ->and($this->calculator->tierFor($this->race, $raceDay, byOrga: true)?->name)->toBe('Nachmeldung');
});

it('refuses a price when no tier applies', function () {
    $this->calculator->total($this->race, Carbon::parse('2027-06-27 08:00'));
})->throws(PriceNotAvailable::class);

it('charges a relay once for the whole team', function () {
    $relay = Race::factory()->for($this->edition)->relay()->create();
    PriceTier::factory()->for($relay)->create(['amount_cents' => 9000]);

    expect($this->calculator->total($relay, Carbon::parse('2027-05-01')))->toBe(9000);
});

it('adds the chosen options to the tier price', function () {
    $shirt = RaceOption::factory()->for($this->edition)->create(['name' => 'Funktionsshirt', 'price_cents' => 1800]);
    $pasta = RaceOption::factory()->for($this->edition)->create(['name' => 'Pasta-Party', 'price_cents' => 850]);

    expect($this->calculator->total($this->race, Carbon::parse('2027-05-01'), [$shirt, $pasta]))->toBe(4500 + 1800 + 850);
});

it('offers an option restricted to some races only there', function () {
    $kids = Race::factory()->for($this->edition)->create(['name' => 'Volxtri4kids']);
    $chip = RaceOption::factory()->for($this->edition)->create(['name' => 'Chip-Miete', 'price_cents' => 300]);
    $chip->races()->attach($this->race);

    expect($chip->isOfferedFor($this->race))->toBeTrue()
        ->and($chip->isOfferedFor($kids))->toBeFalse()
        ->and(RaceOption::query()->offeredFor($kids)->count())->toBe(0);

    PriceTier::factory()->for($kids)->create(['amount_cents' => 1000]);

    expect(fn () => $this->calculator->total($kids, Carbon::parse('2027-05-01'), [$chip]))
        ->toThrow(PriceNotAvailable::class);
});

it('does not offer an inactive option or one of another edition', function () {
    RaceOption::factory()->for($this->edition)->inactive()->create();
    RaceOption::factory()->for(Edition::factory()->create(['slug' => '2026', 'year' => 2026]))->create();

    expect(RaceOption::query()->offeredFor($this->race)->count())->toBe(0);
});

it('keeps shirt sizes as variants of an option', function () {
    $shirt = RaceOption::factory()->for($this->edition)->create(['name' => 'Funktionsshirt', 'quota' => 300]);
    $shirt->variants()->createMany([
        ['name' => 'S', 'sort' => 0],
        ['name' => 'M', 'sort' => 1, 'quota' => 100],
    ]);

    expect($shirt->variants->pluck('name')->all())->toBe(['S', 'M'])
        ->and($shirt->variants->last()->quota)->toBe(100);
});

it('rejects a tier whose period overlaps another one of the race', function (?string $from, ?string $until, bool $valid) {
    $validator = Validator::make(
        ['valid_from' => $from],
        ['valid_from' => [new NonOverlappingPriceTier($this->race->id, $until)]],
    );

    expect($validator->passes())->toBe($valid);
})->with([
    'inside the normal tier' => ['2027-05-01 00:00', '2027-05-31 23:59', false],
    'open start' => [null, '2027-01-31 23:59', false],
    'open end' => ['2027-07-01 00:00', null, false],
]);

it('accepts a tier in a gap and ignores the tier being edited', function () {
    $race = Race::factory()->for($this->edition)->create();
    $tier = PriceTier::factory()->for($race)->create([
        'valid_from' => '2027-01-01 00:00',
        'valid_until' => '2027-03-31 23:59',
    ]);

    $gap = Validator::make(
        ['valid_from' => '2027-04-01 00:00'],
        ['valid_from' => [new NonOverlappingPriceTier($race->id, '2027-06-20 23:59')]],
    );
    $self = Validator::make(
        ['valid_from' => '2027-01-15 00:00'],
        ['valid_from' => [new NonOverlappingPriceTier($race->id, '2027-03-31 23:59', $tier->id)]],
    );

    expect($gap->passes())->toBeTrue()
        ->and($self->passes())->toBeTrue();
});
