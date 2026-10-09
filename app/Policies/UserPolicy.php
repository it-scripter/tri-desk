<?php

namespace App\Policies;

use App\Domain\Competition\Models\Edition;
use App\Models\User;

/**
 * Accounts and roles are managed by the organiser (specification 6.2). An orga
 * team may look people up - it has to appoint team leaders - but may not
 * change accounts.
 *
 * Which personal data a team leader gets to see is a question of the volunteer
 * module (specification 3, "Teamleitungen sehen nur Daten, die sie fuer die
 * Einsatzplanung brauchen") and is decided there, in phase 4.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionInAnyScope('user.viewAny', Edition::class);
    }

    public function view(User $user, User $subject): bool
    {
        return $user->is($subject)
            || $user->hasPermissionInAnyScope('user.view', Edition::class);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionFor('user.create');
    }

    public function update(User $user, User $subject): bool
    {
        return $user->hasPermissionFor('user.update');
    }

    public function delete(User $user, User $subject): bool
    {
        // Nobody deletes their own account through the admin panel; volunteers
        // delete or anonymise theirs in the portal (H-07).
        return ! $user->is($subject) && $user->hasPermissionFor('user.delete');
    }
}
