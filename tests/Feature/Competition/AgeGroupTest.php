<?php

use App\Domain\Competition\Enums\Gender;
use App\Domain\Competition\Enums\TeamClassification;
use App\Domain\Competition\Models\AgeGroupScheme;
use App\Domain\Competition\Models\Edition;
use App\Domain\Competition\Models\Race;
use App\Domain\Competition\Services\AgeGroupCalculator;

/**
 * W-05: age group schemes, the group derived from the birth year. As the DTU
 * does it, the age reached in the year of the race counts.
 */
beforeEach(function () {
    $this->scheme = AgeGroupScheme::factory()->create(['name' => 'Fünfjahresklassen', 'slug' => 'fuenfjahresklassen']);

    $this->scheme->ageGroups()->createMany([
        ['code' => 'MJ', 'gender' => 'm', 'max_age' => 19, 'sort' => 0],
        ['code' => 'M20', 'gender' => 'm', 'min_age' => 20, 'max_age' => 39, 'sort' => 1],
        ['code' => 'M40', 'gender' => 'm', 'min_age' => 40, 'sort' => 2],
        ['code' => 'WJ', 'gender' => 'w', 'max_age' => 19, 'sort' => 3],
        ['code' => 'W20', 'gender' => 'w', 'min_age' => 20, 'sort' => 4],
        ['code' => 'D', 'gender' => 'd', 'sort' => 5],
    ]);

    $this->calculator = new AgeGroupCalculator;
});

it('counts the age reached in the year of the race', function () {
    expect($this->calculator->ageInYear(1987, 2027))->toBe(40);
});

it('places entries at the bounds of a group', function (int $birthYear, Gender $gender, string $code) {
    expect($this->calculator->groupFor($this->scheme, $birthYear, 2027, $gender)?->code)->toBe($code);
})->with([
    'turns 19 in 2027' => [2008, Gender::Male, 'MJ'],
    'turns 20 in 2027' => [2007, Gender::Male, 'M20'],
    'turns 39 in 2027' => [1988, Gender::Male, 'M20'],
    'turns 40 in 2027' => [1987, Gender::Male, 'M40'],
    'woman, 55' => [1972, Gender::Female, 'W20'],
]);

it('scores diverse entries separately by default', function () {
    expect($this->calculator->groupFor($this->scheme, 1990, 2027, Gender::Diverse)?->code)->toBe('D');
});

it('scores diverse entries in the group chosen when the scheme says so', function () {
    $this->scheme->update(['diverse_scoring' => 'as_chosen']);

    expect($this->calculator->groupFor($this->scheme, 1987, 2027, Gender::Diverse, Gender::Female)?->code)->toBe('W20')
        ->and($this->calculator->groupFor($this->scheme, 1987, 2027, Gender::Diverse, Gender::Male)?->code)->toBe('M40')
        ->and($this->calculator->groupFor($this->scheme, 1987, 2027, Gender::Diverse))->toBeNull();
});

it('lets a group without gender take everyone', function () {
    $kids = AgeGroupScheme::factory()->create();
    $kids->ageGroups()->create(['code' => 'Kids', 'min_age' => 6, 'max_age' => 13]);

    expect($this->calculator->groupFor($kids, 2017, 2027, Gender::Female)?->code)->toBe('Kids')
        ->and($this->calculator->groupFor($kids, 2010, 2027, Gender::Male))->toBeNull();
});

it('takes the year from the edition of the race', function () {
    $race = Race::factory()
        ->for(Edition::factory()->create(['year' => 2027, 'slug' => '2027']))
        ->create(['age_group_scheme_id' => $this->scheme->id]);

    expect($this->calculator->groupForRace($race, 1987, Gender::Male)?->code)->toBe('M40');
});

it('returns no group for a race without a scheme', function () {
    $race = Race::factory()->create();

    expect($this->calculator->groupForRace($race, 1987, Gender::Male))->toBeNull();
});

it('offers the gender d only where the race allows it', function () {
    $championship = Race::factory()->create(['allows_diverse' => false]);
    $open = Race::factory()->create(['allows_diverse' => true]);

    expect($championship->allowedGenders())->toBe([Gender::Male, Gender::Female])
        ->and($open->allowedGenders())->toBe([Gender::Male, Gender::Female, Gender::Diverse]);
});

it('keeps the team classifications a relay may be scored in', function () {
    $relay = Race::factory()->relay()->create([
        'team_classifications' => [TeamClassification::Male, TeamClassification::Female, TeamClassification::Mixed],
    ]);

    expect($relay->fresh()->team_classifications->all())
        ->toBe([TeamClassification::Male, TeamClassification::Female, TeamClassification::Mixed]);
});
