<?php

namespace App\Policies;

use App\Domain\Competition\Models\AgeGroupScheme;
use App\Domain\Competition\Models\Edition;
use App\Models\User;

/**
 * Age group schemes are shared by every competition (specification W-05), so
 * only the super admin maintains them - through Gate::before, which is why
 * the write abilities return false here. Whoever edits races may read them,
 * to pick the scheme of a race.
 */
class AgeGroupSchemePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionInAnyScope('race.view', Edition::class);
    }

    public function view(User $user, AgeGroupScheme $scheme): bool
    {
        return $user->hasPermissionInAnyScope('race.view', Edition::class);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, AgeGroupScheme $scheme): bool
    {
        return false;
    }

    public function delete(User $user, AgeGroupScheme $scheme): bool
    {
        return false;
    }
}
