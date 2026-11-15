<?php

namespace App\Policies;

use App\Domain\Competition\Models\CancellationRule;
use App\Domain\Competition\Models\Edition;
use App\Models\User;

/**
 * Cancellation rules belong to an edition and follow its permissions
 * (specification W-04).
 */
class CancellationRulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionInAnyScope('cancellation.view', Edition::class);
    }

    public function view(User $user, CancellationRule $rule): bool
    {
        return $user->hasPermissionFor('cancellation.view', $rule->edition);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionInAnyScope('cancellation.manage', Edition::class);
    }

    public function update(User $user, CancellationRule $rule): bool
    {
        return $this->manage($user, $rule->edition);
    }

    public function delete(User $user, CancellationRule $rule): bool
    {
        return $this->manage($user, $rule->edition);
    }

    private function manage(User $user, Edition $edition): bool
    {
        if ($edition->isClosed()) {
            return false;
        }

        return $user->hasPermissionFor('cancellation.manage', $edition);
    }
}
