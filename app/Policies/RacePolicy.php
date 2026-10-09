<?php

namespace App\Policies;

use App\Domain\Competition\Models\Edition;
use App\Domain\Competition\Models\Race;
use App\Models\User;

/**
 * Races belong to an edition, so the permission is checked against that
 * edition - the orga team of the 2027 edition may add a race there and
 * nowhere else (specification W-02).
 *
 * Races of a closed edition are read only; see EditionPolicy::update.
 */
class RacePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionInAnyScope('race.viewAny', Edition::class);
    }

    public function view(User $user, Race $race): bool
    {
        return $user->hasPermissionFor('race.view', $race->edition);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionInAnyScope('race.create', Edition::class);
    }

    public function update(User $user, Race $race): bool
    {
        if ($race->edition->isClosed()) {
            return false;
        }

        return $user->hasPermissionFor('race.update', $race->edition);
    }

    public function delete(User $user, Race $race): bool
    {
        if ($race->edition->isClosed()) {
            return false;
        }

        return $user->hasPermissionFor('race.delete', $race->edition);
    }
}
