<?php

namespace App\Domain\Competition\Services;

use App\Domain\Competition\Enums\Gender;
use App\Domain\Competition\Models\AgeGroup;
use App\Domain\Competition\Models\AgeGroupScheme;
use App\Domain\Competition\Models\Race;

/**
 * Age group from the birth year (specification W-05). As the DTU does it, the
 * age counts that is reached in the year of the race - the birthday itself
 * does not matter.
 */
class AgeGroupCalculator
{
    public function ageInYear(int $birthYear, int $year): int
    {
        return $year - $birthYear;
    }

    /**
     * The first group of the scheme, in its order, that fits age and gender.
     * Null when none fits, e.g. a diverse entry without a choice in a scheme
     * that scores "as chosen".
     */
    public function groupFor(
        AgeGroupScheme $scheme,
        int $birthYear,
        int $year,
        Gender $gender,
        ?Gender $scoredAs = null,
    ): ?AgeGroup {
        $age = $this->ageInYear($birthYear, $year);
        $scoringGender = $scheme->scoringGender($gender, $scoredAs);

        if ($scoringGender === null) {
            return null;
        }

        return $scheme->ageGroups
            ->first(fn (AgeGroup $group): bool => $group->matches($age, $scoringGender));
    }

    /**
     * The group within a race, by the scheme the race is scored with and the
     * year of its edition.
     */
    public function groupForRace(Race $race, int $birthYear, Gender $gender, ?Gender $scoredAs = null): ?AgeGroup
    {
        $scheme = $race->ageGroupScheme;

        if ($scheme === null) {
            return null;
        }

        return $this->groupFor($scheme, $birthYear, $race->edition->year, $gender, $scoredAs);
    }
}
