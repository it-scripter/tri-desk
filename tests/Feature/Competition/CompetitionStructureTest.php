<?php

use App\Domain\Competition\Enums\Discipline;
use App\Domain\Competition\Enums\EditionStatus;
use App\Domain\Competition\Enums\RaceType;
use App\Domain\Competition\Models\Competition;
use App\Domain\Competition\Models\Edition;
use App\Domain\Competition\Models\EditionDay;
use App\Domain\Competition\Models\Race;

/**
 * W-01: competition with editions per year, W-02: races per edition with a
 * free discipline sequence, specification 1.5: an edition may span days.
 */
it('builds a competition with editions, days and races', function () {
    $competition = Competition::factory()->create(['name' => 'Volkstriathlon', 'slug' => 'volkstriathlon']);

    $edition = Edition::factory()->for($competition)->create([
        'year' => 2027,
        'number' => 38,
        'slug' => '2027',
    ]);

    $day = EditionDay::factory()->for($edition)->create(['date' => '2027-06-27']);

    Race::factory()->for($edition)->create([
        'edition_day_id' => $day->id,
        'name' => 'Volkstriathlon',
    ]);

    expect($competition->editions)->toHaveCount(1)
        ->and($edition->days)->toHaveCount(1)
        ->and($edition->races)->toHaveCount(1)
        ->and($edition->races->first()->editionDay->is($day))->toBeTrue()
        ->and($competition->races()->count())->toBe(1);
});

it('names an edition by its display name and number', function () {
    $competition = Competition::factory()->create(['name' => 'BerlinWoMen']);

    $historic = Edition::factory()->for($competition)->create([
        'year' => 2024,
        'display_name' => 'BerlinMan',
        'slug' => '2024',
    ]);

    $upcoming = Edition::factory()->for($competition)->create([
        'year' => 2028,
        'slug' => '2028',
    ]);

    $numbered = Edition::factory()->for($competition)->create([
        'year' => 2027,
        'number' => 38,
        'slug' => '2027',
    ]);

    expect($historic->title())->toBe('BerlinMan 2024')
        ->and($upcoming->title())->toBe('BerlinWoMen 2028')
        ->and($numbered->titleWithNumber())->toBe('38. BerlinWoMen 2027')
        ->and($upcoming->titleWithNumber())->toBe('BerlinWoMen 2028');
});

it('keeps only one edition per competition current', function () {
    $competition = Competition::factory()->create();

    $first = Edition::factory()->for($competition)->current()->create(['year' => 2026, 'slug' => '2026']);
    $second = Edition::factory()->for($competition)->current()->create(['year' => 2027, 'slug' => '2027']);

    expect($second->fresh()->is_current)->toBeTrue()
        ->and($first->fresh()->is_current)->toBeFalse();
});

it('does not clear the current edition of another competition', function () {
    $one = Edition::factory()->current()->create(['year' => 2027, 'slug' => '2027']);
    $other = Edition::factory()->current()->create(['year' => 2027, 'slug' => '2027']);

    expect($one->fresh()->is_current)->toBeTrue()
        ->and($other->fresh()->is_current)->toBeTrue();
});

it('falls back to the most recent open edition when none is marked current', function () {
    $competition = Competition::factory()->create();

    Edition::factory()->for($competition)->archived()->create(['year' => 2026, 'slug' => '2026']);
    $planned = Edition::factory()->for($competition)->create(['year' => 2027, 'slug' => '2027']);
    Edition::factory()->for($competition)->cancelled()->create(['year' => 2028, 'slug' => '2028']);

    expect($competition->currentEdition()?->is($planned))->toBeTrue();
});

it('prefers the edition marked current over the most recent one', function () {
    $competition = Competition::factory()->create();

    $marked = Edition::factory()->for($competition)->current()->create(['year' => 2025, 'slug' => '2025']);
    Edition::factory()->for($competition)->create(['year' => 2027, 'slug' => '2027']);

    expect($competition->currentEdition()?->is($marked))->toBeTrue();
});

it('stores a free discipline sequence per race', function () {
    $quadrathlon = Race::factory()->quadrathlon()->create(['name' => 'Quadrathlon']);
    $duathlon = Race::factory()->duathlon()->create(['name' => 'Schüler-Duathlon']);
    $relay = Race::factory()->relay()->create(['name' => 'Mitteldistanz-Staffel']);

    expect($quadrathlon->fresh()->hasDiscipline(Discipline::Canoe))->toBeTrue()
        ->and($quadrathlon->fresh()->disciplineSequenceLabel())->toBe('Schwimmen – Rad – Kanu – Lauf')
        ->and($duathlon->fresh()->disciplineSequenceLabel())->toBe('Lauf – Rad – Lauf')
        ->and($duathlon->fresh()->hasDiscipline(Discipline::Swim))->toBeFalse()
        ->and($relay->fresh()->type)->toBe(RaceType::Relay);
});

it('rejects reserved slugs that would collide with a panel path', function () {
    expect(Competition::isReservedSlug('admin'))->toBeTrue()
        ->and(Competition::isReservedSlug('Orga'))->toBeTrue()
        ->and(Competition::isReservedSlug('team'))->toBeTrue()
        ->and(Competition::isReservedSlug('volkstriathlon'))->toBeFalse();
});

it('marks archived and cancelled editions as closed', function () {
    expect(EditionStatus::Archived->isClosed())->toBeTrue()
        ->and(EditionStatus::Cancelled->isClosed())->toBeTrue()
        ->and(EditionStatus::RegistrationOpen->isClosed())->toBeFalse();
});

it('describes a race day with whatever is known', function () {
    $edition = Edition::factory()->create();

    $both = EditionDay::factory()->for($edition)->create(['date' => '2027-06-27', 'label' => 'Sonntag', 'sort' => 0]);
    $labelOnly = EditionDay::factory()->for($edition)->create(['date' => null, 'label' => 'Samstag', 'sort' => 1]);
    $dateOnly = EditionDay::factory()->for($edition)->create(['date' => '2013-05-24', 'label' => null, 'sort' => 2]);
    $neither = EditionDay::factory()->for($edition)->create(['date' => null, 'label' => null, 'sort' => 3]);

    expect($both->title())->toBe('Sonntag, 27.06.2027')
        ->and($labelOnly->title())->toBe('Samstag')
        ->and($dateOnly->title())->toBe('24.05.2013')
        ->and($neither->title())->toBe('Wettkampftag 4');
});
