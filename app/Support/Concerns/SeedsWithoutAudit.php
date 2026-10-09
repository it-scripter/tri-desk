<?php

namespace App\Support\Concerns;

use Spatie\Activitylog\Support\ActivityLogStatus;

/**
 * Seeding is not a change anybody made, so it must not fill the audit log
 * (B-03) with hundreds of entries without a causer.
 *
 * The suppression sits in the seeder itself rather than relying on the
 * WithoutModelEvents trait of the DatabaseSeeder: a seeder is often called on
 * its own, from a test or from the console, and must behave the same way then.
 */
trait SeedsWithoutAudit
{
    protected function withoutAudit(callable $callback): void
    {
        $status = app(ActivityLogStatus::class);
        $wasEnabled = ! $status->disabled();

        $status->disable();

        try {
            $callback();
        } finally {
            if ($wasEnabled) {
                $status->enable();
            }
        }
    }
}
