<?php

namespace App\Policies;

use App\Domain\Competition\Models\Competition;
use App\Domain\Competition\Models\Edition;
use App\Models\User;

/**
 * Competitions are created and retired by the organiser (specification 6.2).
 * An orga team only ever sees the competition its own edition belongs to -
 * it needs the name and the slug, not the right to change them.
 */
class CompetitionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionInAnyScope('competition.viewAny', Edition::class);
    }

    public function view(User $user, Competition $competition): bool
    {
        if ($user->hasPermissionFor('competition.view')) {
            return true;
        }

        return $competition->editions()
            ->whereIn('id', $user->scopeIdsWithPermission(Edition::class, 'competition.view'))
            ->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionFor('competition.create');
    }

    public function update(User $user, Competition $competition): bool
    {
        return $user->hasPermissionFor('competition.update');
    }

    public function delete(User $user, Competition $competition): bool
    {
        return $user->hasPermissionFor('competition.delete');
    }

    public function restore(User $user, Competition $competition): bool
    {
        return $user->hasPermissionFor('competition.delete');
    }

    public function forceDelete(User $user, Competition $competition): bool
    {
        return false;
    }
}
