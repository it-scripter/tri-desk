<?php

namespace App\Policies;

use App\Domain\Competition\Models\AgeGroup;
use App\Domain\Competition\Models\Edition;
use App\Models\User;

/**
 * Age groups are part of their scheme and, like it, maintained by the super
 * admin only (specification W-05); see AgeGroupSchemePolicy.
 */
class AgeGroupPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionInAnyScope('race.view', Edition::class);
    }

    public function view(User $user, AgeGroup $group): bool
    {
        return $user->hasPermissionInAnyScope('race.view', Edition::class);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, AgeGroup $group): bool
    {
        return false;
    }

    public function delete(User $user, AgeGroup $group): bool
    {
        return false;
    }
}
