<?php

use App\Domain\Competition\Models\AgeGroupScheme;
use App\Domain\Competition\Models\Edition;
use App\Domain\Competition\Services\PriceCalculator;
use Database\Seeders\CompetitionSeeder;
use Database\Seeders\DemoPricingSeeder;
use Database\Seeders\Volkstriathlon2027Seeder;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Activity;

/**
 * W-03 to W-05: the example data of the local environment. It must be
 * consistent - tiers without overlap, every race with a scheme - and may be
 * re-run without duplicating rows.
 */
beforeEach(function () {
    $this->seed(CompetitionSeeder::class);
    $this->seed(Volkstriathlon2027Seeder::class);
    $this->seed(DemoPricingSeeder::class);
});

it('prices every race of the 2027 edition by date', function () {
    $edition = Edition::query()->where('year', 2027)->sole();
    $calculator = new PriceCalculator;

    foreach ($edition->races as $race) {
        expect($race->ageGroupScheme)->not->toBeNull()
            ->and($calculator->tierFor($race, Carbon::parse('2027-02-15 10:00'))?->name)->toBe('Early Bird')
            ->and($calculator->tierFor($race, Carbon::parse('2027-06-27 08:00')))->toBeNull()
            ->and($calculator->tierFor($race, Carbon::parse('2027-06-27 08:00'), byOrga: true)?->name)->toBe('Nachmeldung');
    }

    expect($edition->raceOptions)->toHaveCount(3)
        ->and($edition->cancellationRules)->toHaveCount(2);
});

it('can be re-run without duplicates and without audit entries', function () {
    $this->seed(DemoPricingSeeder::class);

    $edition = Edition::query()->where('year', 2027)->sole();

    expect(AgeGroupScheme::query()->count())->toBe(1)
        ->and($edition->raceOptions()->count())->toBe(3)
        ->and($edition->races->first()->priceTiers()->count())->toBe(3)
        ->and(Activity::query()->count())->toBe(0);
});
