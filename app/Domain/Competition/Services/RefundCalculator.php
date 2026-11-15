<?php

namespace App\Domain\Competition\Services;

use App\Domain\Competition\Models\CancellationRule;
use App\Domain\Competition\Models\Edition;
use Carbon\CarbonInterface;

/**
 * Refunds on cancellation (specification W-04). The earliest step of the
 * edition's cancellation rules that is still open applies; once the last step
 * has passed, nothing is refunded.
 */
class RefundCalculator
{
    public function ruleAt(Edition $edition, CarbonInterface $at): ?CancellationRule
    {
        return $edition->cancellationRules()
            ->where('valid_until', '>=', $at)
            ->first();
    }

    public function refundFor(Edition $edition, int $paidCents, CarbonInterface $at): int
    {
        return $this->ruleAt($edition, $at)?->refundFor($paidCents) ?? 0;
    }
}
