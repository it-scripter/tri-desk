<?php

namespace App\Console\Commands;

use App\Domain\Competition\Services\RegistrationService;
use Illuminate\Console\Command;

/**
 * Releases reserved places whose payment window ran out (specification
 * W-22). Scheduled every five minutes; on shared hosting the scheduler runs
 * from cron, there is no permanent worker.
 */
class ExpireRegistrations extends Command
{
    protected $signature = 'registrations:expire';

    protected $description = 'Gibt reservierte Plätze frei, deren Zahlungsfrist abgelaufen ist (W-22)';

    public function handle(RegistrationService $registrations): int
    {
        $count = $registrations->expireOverdue();

        $this->info(trans_choice('registration.expired_count', $count, ['count' => $count]));

        return self::SUCCESS;
    }
}
