<?php

use App\Domain\Competition\Data\ParticipantData;
use App\Domain\Competition\Enums\TeamClassification;
use App\Domain\Competition\Exceptions\RegistrationRejected;
use App\Domain\Competition\Models\AgeGroupScheme;
use App\Domain\Competition\Services\RegistrationService;
use App\Domain\Competition\Services\TeamClassifier;

/**
 * W-18: the classification of relays and teams follows from the members,
 * rule by rule as written in section 4.2 of the specification.
 */
beforeEach(function () {
    $this->travelTo('2027-03-01 12:00:00');
    $this->classifier = new TeamClassifier;

    $this->relay = openRace([
        'type' => 'relay',
        'allows_diverse' => true,
        'team_classifications' => ['male', 'female', 'mixed', 'open'],
    ]);
});

/**
 * @return array<int, ParticipantData>
 */
function team(string ...$genders): array
{
    return array_map(
        fn (int $i, string $gender) => participant("P{$i}", 'Team', gender: $gender),
        array_keys($genders),
        $genders,
    );
}

it('rule 3: scores all male and all female teams as such', function () {
    expect($this->classifier->classify($this->relay, team('m', 'm', 'm')))->toBe(TeamClassification::Male)
        ->and($this->classifier->classify($this->relay, team('w', 'w', 'w')))->toBe(TeamClassification::Female);
});

it('rule 4: scores a team as mixed from one male and one female member by default', function () {
    expect($this->classifier->classify($this->relay, team('m', 'w', 'w')))->toBe(TeamClassification::Mixed);
});

it('rule 4: follows a stricter minimum of the race', function () {
    $this->relay->update(['mixed_min_male' => 2, 'mixed_min_female' => 2]);

    expect($this->classifier->classify($this->relay, team('m', 'm', 'w', 'w')))->toBe(TeamClassification::Mixed)
        ->and($this->classifier->classify($this->relay, team('m', 'w', 'w', 'w')))->toBe(TeamClassification::Open);
});

it('rule 5: puts a team with a diverse member into open when d is scored separately', function () {
    expect($this->classifier->classify($this->relay, team('m', 'w', 'd')))->toBe(TeamClassification::Open);
});

it('rule 5: puts a team with a diverse member into open when the race has no scheme', function () {
    expect($this->relay->age_group_scheme_id)->toBeNull()
        ->and($this->classifier->classify($this->relay, team('w', 'w', 'd')))->toBe(TeamClassification::Open);
});

it('rule 5: counts a diverse member as the gender chosen when the scheme says so', function () {
    $this->relay->update(['age_group_scheme_id' => AgeGroupScheme::factory()->scoredAsChosen()->create()->id]);
    $race = $this->relay->fresh();

    $members = [participant('A', 'A', gender: 'w'), participant('B', 'B', gender: 'w'), participant('C', 'C', gender: 'd', scoredAs: 'w')];
    $mixed = [participant('A', 'A', gender: 'm'), participant('B', 'B', gender: 'w'), participant('C', 'C', gender: 'd', scoredAs: 'm')];

    expect($this->classifier->classify($race, $members))->toBe(TeamClassification::Female)
        ->and($this->classifier->classify($race, $mixed))->toBe(TeamClassification::Mixed);
});

it('rule 6: falls back to open when the fitting classification is not offered', function () {
    $this->relay->update(['team_classifications' => ['mixed', 'open']]);

    expect($this->classifier->classify($this->relay->fresh(), team('m', 'm', 'm')))->toBe(TeamClassification::Open);
});

it('rule 7: refuses a line-up that fits nothing offered when open is not offered', function () {
    $this->relay->update(['team_classifications' => ['male', 'female']]);

    $this->classifier->classify($this->relay->fresh(), team('m', 'w', 'w'));
})->throws(RegistrationRejected::class, 'keine angebotene Wertung');

it('rule 1: offers every classification when the race lists none', function () {
    $this->relay->update(['team_classifications' => null]);

    expect($this->classifier->classify($this->relay->fresh(), team('m', 'w')))->toBe(TeamClassification::Mixed);
});

it('rule 2: stores the classification computed at registration', function () {
    $registration = app(RegistrationService::class)->register(registrationFor($this->relay, team('m', 'w', 'w')))->registration;

    expect($registration->team_classification)->toBe(TeamClassification::Mixed);
});

it('rule 8: gives members of a relay no age group', function () {
    $scheme = AgeGroupScheme::factory()->create();
    $scheme->ageGroups()->create(['code' => 'Alle']);
    $this->relay->update(['age_group_scheme_id' => $scheme->id]);

    $registration = app(RegistrationService::class)->register(registrationFor($this->relay->fresh(), team('m', 'w')))->registration;

    expect($registration->participants->pluck('age_group_id')->filter())->toBeEmpty();
});
