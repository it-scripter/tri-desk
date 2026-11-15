<?php

namespace App\Policies;

use App\Domain\Competition\Models\Edition;
use App\Domain\Competition\Models\RaceOption;
use App\Models\User;

/**
 * Additional options belong to an edition and follow its permissions
 * (specification W-03). Their variants are edited within the option, so they
 * need no policy of their own.
 */
class RaceOptionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionInAnyScope('price.view', Edition::class);
    }

    public function view(User $user, RaceOption $option): bool
    {
        return $user->hasPermissionFor('price.view', $option->edition);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionInAnyScope('price.manage', Edition::class);
    }

    public function update(User $user, RaceOption $option): bool
    {
        return $this->manage($user, $option->edition);
    }

    public function delete(User $user, RaceOption $option): bool
    {
        return $this->manage($user, $option->edition);
    }

    private function manage(User $user, Edition $edition): bool
    {
        if ($edition->isClosed()) {
            return false;
        }

        return $user->hasPermissionFor('price.manage', $edition);
    }
}
