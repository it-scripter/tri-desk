<?php

namespace App\Policies;

use App\Domain\Competition\Models\Edition;
use App\Domain\Competition\Models\PriceTier;
use App\Models\User;

/**
 * Price tiers belong to a race and through it to an edition, so they follow
 * the permissions of that edition (specification W-03). A closed edition is
 * read only; see EditionPolicy::update.
 */
class PriceTierPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionInAnyScope('price.view', Edition::class);
    }

    public function view(User $user, PriceTier $tier): bool
    {
        return $user->hasPermissionFor('price.view', $tier->race->edition);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionInAnyScope('price.manage', Edition::class);
    }

    public function update(User $user, PriceTier $tier): bool
    {
        return $this->manage($user, $tier->race->edition);
    }

    public function delete(User $user, PriceTier $tier): bool
    {
        return $this->manage($user, $tier->race->edition);
    }

    private function manage(User $user, Edition $edition): bool
    {
        if ($edition->isClosed()) {
            return false;
        }

        return $user->hasPermissionFor('price.manage', $edition);
    }
}
