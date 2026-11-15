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

    /**
     * An archived or cancelled edition is a record, not a workspace. The
     * super admin still passes through Gate::before, so correcting historic
     * data stays possible - just not by an orga team by accident.
     */
    public function update(User $user, Edition $edition): bool
    {
        if ($edition->isClosed()) {
            return false;
        }

        return $user->hasPermissionFor('edition.update', $edition);
    }

    /**
     * Copying a previous year is the organiser's job (specification 6.2,
     * W-01). The source may well be archived - last year's edition usually
     * is - so a closed edition is no reason to refuse.
     */
    public function copy(User $user, Edition $edition): bool
    {
        return $user->hasPermissionFor('edition.copy', $edition);
    }

    /**
     * Playing the public registration form through as a test, before or
     * while registration is open. Whoever manages the registrations of the
     * edition may; a closed edition takes no more registrations, not even
     * tests.
     */
    public function testRegistration(User $user, Edition $edition): bool
    {
        if ($edition->isClosed()) {
            return false;
        }

        return $user->hasPermissionFor('registration.manage', $edition);
    }

    public function delete(User $user, Edition $edition): bool
    {
        return $user->hasPermissionFor('edition.delete', $edition);
    }
}
