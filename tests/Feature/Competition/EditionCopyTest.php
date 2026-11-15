<?php

use App\Domain\Access\Enums\RoleName;
use App\Domain\Competition\Enums\EditionStatus;
use App\Domain\Competition\Enums\TeamClassification;
use App\Domain\Competition\Models\AgeGroupScheme;
use App\Domain\Competition\Models\CancellationRule;
use App\Domain\Competition\Models\Competition;
use App\Domain\Competition\Models\Edition;
use App\Domain\Competition\Models\EditionDay;
use App\Domain\Competition\Models\PriceTier;
use App\Domain\Competition\Models\Race;
use App\Domain\Competition\Models\RaceOption;
use App\Domain\Competition\Services\EditionCopier;
use App\Integrations\Results\Models\WkMapping;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Activity;

/**
 * W-01, B-01: an edition is copied from the previous year - race days, races,
 * prices, options and cancellation rules. Every date moves by the distance
 * between the old and the new race day.
 */
beforeEach(function () {
    $this->competition = Competition::factory()->create(['name' => 'Volkstriathlon', 'slug' => 'volkstriathlon']);

    // 27.06.2027 is a Sunday, 25.06.2028 as well: 364 days apart.
    $this->source = Edition::factory()->for($this->competition)->current()->create([
        'year' => 2027,
        'number' => 38,
        'slug' => '2027',
        'display_name' => 'Sondername',
        'status' => EditionStatus::Archived,
        'note' => 'Nur für 2027',
        'starts_on' => '2027-06-27',
        'ends_on' => '2027-06-27',
        'registration_opens_at' => '2027-02-15 10:00:00',
        'registration_closes_at' => '2027-06-20 23:59:00',
        'transfer_deadline_at' => '2027-06-13 23:59:00',
    ]);

    $this->day = EditionDay::factory()->for($this->source)->create(['date' => '2027-06-27', 'label' => 'Sonntag']);

    $this->scheme = AgeGroupScheme::factory()->create();

    $this->race = Race::factory()->for($this->source)->relay()->create([
        'name' => 'Staffel',
        'slug' => 'staffel',
        'edition_day_id' => $this->day->id,
        'age_group_scheme_id' => $this->scheme->id,
        'allows_diverse' => true,
        'team_classifications' => [TeamClassification::Mixed],
        'participant_limit' => 80,
    ]);
    $this->kids = Race::factory()->for($this->source)->create(['name' => 'Kids', 'slug' => 'kids']);

    PriceTier::factory()->for($this->race)->create([
        'name' => 'Early Bird',
        'amount_cents' => 9000,
        'valid_from' => '2027-02-15 10:00:00',
        'valid_until' => '2027-03-31 23:59:00',
    ]);

    $this->chip = RaceOption::factory()->for($this->source)->create(['name' => 'Chip-Miete', 'price_cents' => 300]);
    $this->chip->races()->attach($this->race);
    $this->shirt = RaceOption::factory()->for($this->source)->create(['name' => 'Funktionsshirt']);
    $this->shirt->variants()->createMany([['name' => 'S', 'sort' => 0], ['name' => 'M', 'sort' => 1]]);

    CancellationRule::factory()->for($this->source)->create([
        'valid_until' => '2027-04-30 23:59:00',
        'refund_percent' => 100,
        'fee_cents' => 500,
    ]);

    WkMapping::factory()->create(['race_id' => $this->race->id]);

    $this->copier = new EditionCopier;
});

function copyTo2028(EditionCopier $copier, Edition $source, ?string $startsOn = '2028-06-25'): Edition
{
    return $copier->copy($source, 2028, 39, '2028', $startsOn !== null ? Carbon::parse($startsOn) : null);
}

it('creates a planned edition that is not the current one', function () {
    $copy = copyTo2028($this->copier, $this->source);

    expect($copy->competition_id)->toBe($this->competition->id)
        ->and($copy->year)->toBe(2028)
        ->and($copy->number)->toBe(39)
        ->and($copy->status)->toBe(EditionStatus::Planned)
        ->and($copy->is_current)->toBeFalse()
        ->and($copy->display_name)->toBeNull()
        ->and($copy->note)->toBeNull()
        ->and($this->source->fresh()->is_current)->toBeTrue();
});

it('shifts every date by the distance between the race days', function () {
    $copy = copyTo2028($this->copier, $this->source)->fresh();

    expect($copy->starts_on->format('Y-m-d'))->toBe('2028-06-25')
        ->and($copy->starts_on->isSunday())->toBeTrue()
        ->and($copy->registration_opens_at->format('Y-m-d H:i'))->toBe('2028-02-14 10:00')
        ->and($copy->registration_closes_at->format('Y-m-d H:i'))->toBe('2028-06-18 23:59')
        ->and($copy->transfer_deadline_at->format('Y-m-d H:i'))->toBe('2028-06-11 23:59')
        ->and($copy->days->first()->date->format('Y-m-d'))->toBe('2028-06-25')
        ->and($copy->cancellationRules->first()->valid_until->format('Y-m-d H:i'))->toBe('2028-04-28 23:59');

    $tier = $copy->races->firstWhere('slug', 'staffel')->priceTiers->first();

    expect($tier->valid_from->format('Y-m-d H:i'))->toBe('2028-02-14 10:00')
        ->and($tier->valid_until->format('Y-m-d H:i'))->toBe('2028-03-29 23:59');
});

it('copies races with their settings and their own race day', function () {
    $copy = copyTo2028($this->copier, $this->source);
    $relay = $copy->races()->where('slug', 'staffel')->sole();

    expect($copy->races)->toHaveCount(2)
        ->and($relay->id)->not->toBe($this->race->id)
        ->and($relay->edition_day_id)->toBe($copy->days->first()->id)
        ->and($relay->age_group_scheme_id)->toBe($this->scheme->id)
        ->and($relay->allows_diverse)->toBeTrue()
        ->and($relay->team_classifications->all())->toBe([TeamClassification::Mixed])
        ->and($relay->participant_limit)->toBe(80)
        ->and($relay->priceTiers->sole()->amount_cents)->toBe(9000);
});

it('points option restrictions at the copied races', function () {
    $copy = copyTo2028($this->copier, $this->source);

    $chip = $copy->raceOptions()->where('name', 'Chip-Miete')->sole();
    $shirt = $copy->raceOptions()->where('name', 'Funktionsshirt')->sole();
    $relay = $copy->races()->where('slug', 'staffel')->sole();

    expect($chip->races->pluck('id')->all())->toBe([$relay->id])
        ->and($shirt->races)->toBeEmpty()
        ->and($shirt->variants->pluck('name')->all())->toBe(['S', 'M'])
        ->and($this->chip->fresh()->races->pluck('id')->all())->toBe([$this->race->id]);
});

it('leaves the source untouched and takes no legacy wk mapping along', function () {
    $copy = copyTo2028($this->copier, $this->source);

    expect($this->source->races()->count())->toBe(2)
        ->and($this->source->raceOptions()->count())->toBe(2)
        ->and($this->source->fresh()->starts_on->format('Y-m-d'))->toBe('2027-06-27')
        ->and(WkMapping::query()->whereIn('race_id', $copy->races()->pluck('id'))->exists())->toBeFalse()
        ->and($this->race->wkMappings()->count())->toBe(1);
});

it('leaves the dates empty when the source has none', function () {
    $this->source->update(['starts_on' => null]);

    $copy = copyTo2028($this->copier, $this->source->fresh());

    expect($copy->starts_on)->toBeNull()
        ->and($copy->registration_opens_at)->toBeNull()
        ->and($copy->days->first()->date)->toBeNull()
        ->and($copy->races->firstWhere('slug', 'staffel')->priceTiers->first()->valid_from)->toBeNull()
        ->and($copy->cancellationRules)->toBeEmpty();
});

it('records the copy as a single audit entry naming the source', function () {
    $admin = User::factory()->create();
    Activity::query()->delete();

    $copy = $this->copier->copy($this->source, 2028, 39, '2028', Carbon::parse('2028-06-25'), $admin);

    $entries = Activity::query()->get();

    expect($entries)->toHaveCount(1)
        ->and($entries->first()->event)->toBe('copied')
        ->and($entries->first()->subject->is($copy))->toBeTrue()
        ->and($entries->first()->causer->is($admin))->toBeTrue()
        ->and($entries->first()->description)->toBe('aus 38. Sondername 2027 kopiert');
});

it('lets only the super admin copy an edition, archived or not', function () {
    $this->seed(RoleSeeder::class);

    $orga = User::factory()->create();
    $orga->assignRoleFor(RoleName::Orga, $this->source);
    $admin = User::factory()->create();
    $admin->assignRoleFor(RoleName::SuperAdmin);

    expect($orga->can('copy', $this->source))->toBeFalse()
        ->and($admin->can('copy', $this->source))->toBeTrue();
});
