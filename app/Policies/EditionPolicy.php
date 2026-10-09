<?php

namespace App\Policies;

use App\Domain\Competition\Models\Edition;
use App\Models\User;

/**
 * The edition is the unit roles are scoped to (specification chapter 3): an
 * orga team manages its own edition and must not see another one. Creating and
 * deleting editions stays with the organiser.
 */
class EditionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionInAnyScope('edition.viewAny', Edition::class);
    }

    public function view(User $user, Edition $edition): bool
    {
        return $user->hasPermissionFor('edition.view', $edition);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionFor('edition.create');
    }

    public function update(User $user, Edition $edition): bool
    {
        return $user->hasPermissionFor('edition.update', $edition);
    }

    public function delete(User $user, Edition $edition): bool
    {
        return $user->hasPermissionFor('edition.delete', $edition);
    }
}
