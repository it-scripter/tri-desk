<?php

namespace App\Policies;

use App\Domain\Competition\Models\Edition;
use App\Domain\Competition\Models\RaceField;
use App\Models\User;

/**
 * Additional fields are part of the race (specification W-11) and follow its
 * permissions; see RacePolicy.
 */
class RaceFieldPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionInAnyScope('race.view', Edition::class);
    }

    public function view(User $user, RaceField $field): bool
    {
        return $user->hasPermissionFor('race.view', $field->race->edition);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionInAnyScope('race.update', Edition::class);
    }

    public function update(User $user, RaceField $field): bool
    {
        return $this->manage($user, $field);
    }

    public function delete(User $user, RaceField $field): bool
    {
        return $this->manage($user, $field);
    }

    private function manage(User $user, RaceField $field): bool
    {
        $edition = $field->race->edition;

        if ($edition->isClosed()) {
            return false;
        }

        return $user->hasPermissionFor('race.update', $edition);
    }
}
