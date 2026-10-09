<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Activitylog\Models\Activity;

/**
 * The audit log is readable for whoever holds the activity.view permission -
 * in practice the organiser's admins (specification 6.2). It is never
 * writable: entries are produced by the application itself.
 */
class ActivityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionFor('activity.view');
    }

    public function view(User $user, Activity $activity): bool
    {
        return $user->hasPermissionFor('activity.view');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Activity $activity): bool
    {
        return false;
    }

    public function delete(User $user, Activity $activity): bool
    {
        return false;
    }
}
