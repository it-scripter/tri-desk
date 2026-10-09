<?php

use App\Domain\Competition\Enums\Discipline;
use App\Domain\Competition\Enums\EditionStatus;
use App\Domain\Competition\Enums\RaceType;
use App\Domain\Competition\Models\Competition;
use App\Domain\Competition\Models\Edition;
use App\Domain\Competition\Models\EditionDay;
use App\Domain\Competition\Models\Race;
use App\Integrations\Results\Models\WkMapping;
use Database\Seeders\CompetitionSeeder;
use Database\Seeders\HistoricEditionSeeder;
use Database\Seeders\Volkstriathlon2027Seeder;
use Spatie\Activitylog\Models\Activity;

/**
 * W-01, W-33, W-37: the checked wk mapping of the legacy result database is
 * taken into the portal. The figures come from docs/data/wk-zuordnung.csv and
 * match the specification: 98 wk values, 27.520 entries, 98 rows over 55
 * editions and 93 races.
 */
beforeEach(function () {
    $this->seed(CompetitionSeeder::class);
    $this->seed(HistoricEditionSeeder::class);
});

it('takes over every wk value with its entry count', function () {
    expect(WkMapping::query()->count())->toBe(98)
        ->and((int) WkMapping::query()->sum('expected_entries'))->toBe(27520);
});

it('creates the three competitions', function () {
    expect(Competition::query()->pluck('slug')->sort()->values()->all())
        ->toBe(['berlinwomen', 'schueler-duathlon', 'volkstriathlon']);
});

it('creates one edition per competition and year plus the cancelled one', function () {
    // 55 editions from the CSV, plus the 32nd Volkstriathlon which has no
    // results because it was called off.
    expect(Edition::query()->count())->toBe(56)
        ->and(Edition::query()->where('status', EditionStatus::Archived)->count())->toBe(55)
        ->and(Edition::query()->where('status', EditionStatus::Cancelled)->count())->toBe(1);
});

it('creates one race per edition and race name', function () {
    expect(Race::query()->count())->toBe(93);
});

it('records the cancelled 32nd Volkstriathlon', function () {
    $edition = Edition::query()->where('number', 32)->sole();

    expect($edition->year)->toBe(2020)
        ->and($edition->status)->toBe(EditionStatus::Cancelled)
        ->and($edition->races()->count())->toBe(0)
        ->and($edition->note)->toContain('Corona');
});

it('leaves the Volkstriathlon numbers a complete sequence', function () {
    $numbers = Edition::query()
        ->whereRelation('competition', 'slug', 'volkstriathlon')
        ->whereNotNull('number')
        ->orderBy('number')
        ->pluck('number');

    expect($numbers->all())->toBe(range(1, 37));
});

it('names the BerlinWoMen editions BerlinMan up to 2024', function () {
    $competition = Competition::query()->where('slug', 'berlinwomen')->sole();

    $editions = $competition->editions()->orderBy('year')->get();

    expect($editions->every(fn (Edition $edition): bool => $edition->display_name === 'BerlinMan'))->toBeTrue()
        ->and($competition->editions()->where('year', 2024)->sole()->title())->toBe('BerlinMan 2024')
        ->and($competition->editions()->where('year', 1992)->sole()->title())->toBe('BerlinMan 1992');
});

it('merges the three MiniMan 2021 classes into one race', function () {
    $race = Race::query()
        ->whereRelation('edition', 'year', 2021)
        ->where('name', 'MiniMan')
        ->sole();

    expect($race->wkMappings()->count())->toBe(3)
        ->and($race->wkMappings()->pluck('wk')->sort()->values()->all())->toBe([
            'MiniMan 2021 SchA JugB',
            'MiniMan 2021 SchB',
            'MiniMan 2021 SchC',
        ])
        ->and((int) $race->wkMappings()->sum('expected_entries'))->toBe(53);
});

it('merges the two spellings of the Jedermensch-Sprint 2008', function () {
    $race = Race::query()
        ->whereRelation('edition', 'year', 2008)
        ->where('name', 'Jedermensch-Sprint')
        ->sole();

    expect($race->wkMappings()->count())->toBe(2)
        ->and((int) $race->wkMappings()->sum('expected_entries'))->toBe(21);
});

it('gives the 25th Volkstriathlon two days and keeps the race open', function () {
    $edition = Edition::query()->where('number', 25)->sole();
    $race = $edition->races()->sole();

    expect($edition->days)->toHaveCount(2)
        ->and($edition->days->pluck('label')->all())->toBe(['Samstag', 'Sonntag'])
        ->and($edition->days->every(fn (EditionDay $day): bool => $day->date === null))->toBeTrue()
        // The race spans both days, so the day is carried by the wk mapping.
        ->and($race->edition_day_id)->toBeNull()
        ->and($race->wkMappings()->whereNotNull('edition_day_id')->count())->toBe(2);
});

it('gives the Schueler-Duathlon 2013 its two dates', function () {
    $edition = Edition::query()
        ->whereRelation('competition', 'slug', 'schueler-duathlon')
        ->where('year', 2013)
        ->sole();

    expect($edition->days->pluck('date')->map(fn ($date) => $date->format('d.m.Y'))->all())
        ->toBe(['24.05.2013', '28.05.2013'])
        ->and($edition->starts_on->format('d.m.Y'))->toBe('24.05.2013')
        ->and($edition->ends_on->format('d.m.Y'))->toBe('28.05.2013');
});

it('keeps the canoe leg of the historic quadrathlon', function () {
    $races = Race::query()->where('name', 'Quadrathlon')->get();

    expect($races)->toHaveCount(4)
        ->and($races->every(fn (Race $race): bool => $race->hasDiscipline(Discipline::Canoe)))->toBeTrue()
        ->and($races->first()->disciplineSequenceLabel())->toBe('Schwimmen – Rad – Kanu – Lauf');
});

it('runs the school duathlon as run bike run', function () {
    $races = Race::query()->where('name', 'Schüler-Duathlon')->get();

    expect($races)->toHaveCount(2)
        ->and($races->every(fn (Race $race): bool => $race->disciplineSequenceLabel() === 'Lauf – Rad – Lauf'))->toBeTrue();
});

it('marks the relay as a relay', function () {
    $races = Race::query()->where('name', 'Mitteldistanz-Staffel')->get();

    expect($races)->toHaveCount(2)
        ->and($races->every(fn (Race $race): bool => $race->type === RaceType::Relay))->toBeTrue();
});

it('can be re-run without creating duplicates', function () {
    $this->seed(HistoricEditionSeeder::class);

    expect(WkMapping::query()->count())->toBe(98)
        ->and(Edition::query()->count())->toBe(56)
        ->and(Race::query()->count())->toBe(93)
        // Five days: both days of the 25th Volkstriathlon plus the three
        // dates of the Schueler-Duathlon in 2013 and 2014.
        ->and(EditionDay::query()->count())->toBe(5);
});

it('adds the upcoming edition as the current one', function () {
    $this->seed(Volkstriathlon2027Seeder::class);

    $edition = Edition::query()->where('year', 2027)->sole();

    expect($edition->number)->toBe(38)
        ->and($edition->status)->toBe(EditionStatus::Planned)
        ->and($edition->is_current)->toBeTrue()
        ->and($edition->titleWithNumber())->toBe('38. Volkstriathlon 2027')
        ->and($edition->starts_on->format('d.m.Y'))->toBe('27.06.2027')
        ->and($edition->registration_opens_at->format('d.m.Y'))->toBe('15.02.2027')
        ->and($edition->races()->pluck('name')->all())->toBe(['Volkstriathlon', 'Volxtri4kids'])
        ->and($edition->competition->currentEdition()?->is($edition))->toBeTrue();
});

it('leaves no audit entries behind when seeding', function () {
    // Seeding is not a change anybody made, so it must not fill the log.
    expect(Activity::query()->count())->toBe(0);
});
