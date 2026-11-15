<?php

namespace App\Domain\Competition\Services;

use App\Domain\Competition\Data\ParticipantData;
use App\Domain\Competition\Enums\DiverseScoring;
use App\Domain\Competition\Enums\Gender;
use App\Domain\Competition\Enums\TeamClassification;
use App\Domain\Competition\Exceptions\RegistrationRejected;
use App\Domain\Competition\Models\Race;

/**
 * Classification of a relay or team from its members (specification W-18).
 *
 * - male / female: every member is scored as "m" / as "w"
 * - mixed: the minimum per gender of the race is reached
 * - a member with "d" counts as the age group scheme says: scored
 *   separately (or no scheme) puts the team into open, "as chosen" counts
 *   the member as the gender picked
 * - open catches every team that fits no offered classification; without
 *   open the line-up is refused
 */
class TeamClassifier
{
    /**
     * @param  array<int, ParticipantData>  $members
     *
     * @throws RegistrationRejected
     */
    public function classify(Race $race, array $members): TeamClassification
    {
        $offered = $race->offeredTeamClassifications();
        $fits = $this->fit($race, $members);

        if ($fits !== null && in_array($fits, $offered, true)) {
            return $fits;
        }

        if (in_array(TeamClassification::Open, $offered, true)) {
            return TeamClassification::Open;
        }

        throw new RegistrationRejected('no_classification');
    }

    /**
     * The classification the line-up fits, before looking at what the race
     * offers. Null when it fits none but open.
     *
     * @param  array<int, ParticipantData>  $members
     */
    private function fit(Race $race, array $members): ?TeamClassification
    {
        $separate = $race->ageGroupScheme === null
            || $race->ageGroupScheme->diverse_scoring === DiverseScoring::Separate;

        $male = 0;
        $female = 0;

        foreach ($members as $member) {
            $gender = $member->gender === Gender::Diverse
                ? ($separate ? null : $member->scoredAs)
                : $member->gender;

            if ($gender === Gender::Male) {
                $male++;
            } elseif ($gender === Gender::Female) {
                $female++;
            } else {
                return null;
            }
        }

        return match (true) {
            $female === 0 => TeamClassification::Male,
            $male === 0 => TeamClassification::Female,
            $male >= $race->mixed_min_male && $female >= $race->mixed_min_female => TeamClassification::Mixed,
            default => null,
        };
    }
}
