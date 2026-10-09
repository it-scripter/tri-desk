<?php

namespace App\Policies;

use App\Domain\Competition\Models\Edition;
use App\Domain\Competition\Models\EditionDay;
use App\Models\User;

/**
 * Race days are part of the edition they belong to and follow its permissions
 * (specification 1.5).
 */
class EditionDayPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionInAnyScope('edition.viewAny', Edition::class);
    }

    public function view(User $user, EditionDay $day): bool
    {
        return $user->hasPermissionFor('edition.view', $day->edition);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionInAnyScope('edition.update', Edition::class);
    }

    public function update(User $user, EditionDay $day): bool
    {
        if ($day->edition->isClosed()) {
            return false;
        }

        return $user->hasPermissionFor('edition.update', $day->edition);
    }

    public function delete(User $user, EditionDay $day): bool
    {
        if ($day->edition->isClosed()) {
            return false;
        }

        return $user->hasPermissionFor('edition.update', $day->edition);
    }
}
