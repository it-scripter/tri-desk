<?php

namespace App\Policies;

use App\Domain\Access\Models\RoleAssignment;
use App\Models\User;

/**
 * Granting roles is the most sensitive operation in the system, so it needs
 * the dedicated role.assign permission, which only the organiser's admins
 * hold (specification 3, 6.2). Every grant and revocation is logged (B-03).
 */
class RoleAssignmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionFor('role.assign');
    }

    public function view(User $user, RoleAssignment $assignment): bool
    {
        return $user->hasPermissionFor('role.assign');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionFor('role.assign');
    }

    public function update(User $user, RoleAssignment $assignment): bool
    {
        return $user->hasPermissionFor('role.assign');
    }

    public function delete(User $user, RoleAssignment $assignment): bool
    {
        return $user->hasPermissionFor('role.assign');
    }
}
