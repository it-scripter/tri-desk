<?php

namespace App\Policies;

use App\Domain\Competition\Models\Edition;
use App\Domain\Competition\Models\Registration;
use App\Models\User;

/**
 * Registrations belong to a race and through it to an edition; the orga team
 * of that edition sees and manages them (specification 3). Registrations are
 * cancelled, never deleted - the record is needed for payments and the
 * audit log. Test registrations of the orga team are the exception.
 */
class RegistrationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionInAnyScope('registration.view', Edition::class);
    }

    public function view(User $user, Registration $registration): bool
    {
        return $user->hasPermissionFor('registration.view', $registration->race->edition);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionInAnyScope('registration.manage', Edition::class);
    }

    public function update(User $user, Registration $registration): bool
    {
        return $this->manage($user, $registration);
    }

    public function cancel(User $user, Registration $registration): bool
    {
        return $this->manage($user, $registration);
    }

    /**
     * Only test registrations may be deleted; a real one is cancelled and
     * kept for payments and the audit log.
     */
    public function delete(User $user, Registration $registration): bool
    {
        return $registration->is_test && $this->manage($user, $registration);
    }

    private function manage(User $user, Registration $registration): bool
    {
        $edition = $registration->race->edition;

        if ($edition->isClosed()) {
            return false;
        }

        return $user->hasPermissionFor('registration.manage', $edition);
    }
}
