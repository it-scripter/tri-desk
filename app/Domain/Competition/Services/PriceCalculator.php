<?php

namespace App\Domain\Competition\Services;

use App\Domain\Competition\Exceptions\PriceNotAvailable;
use App\Domain\Competition\Models\PriceTier;
use App\Domain\Competition\Models\Race;
use App\Domain\Competition\Models\RaceOption;
use Carbon\CarbonInterface;

/**
 * Prices of an entry at a given moment (specification W-03): the price tier
 * valid then plus the chosen options, all in cents.
 *
 * The tier price is the price of one entry - a relay or team pays it once,
 * not per member.
 */
class PriceCalculator
{
    /**
     * The tier valid at the given moment. Tiers marked orga_only, e.g. late
     * entry on race day, are only considered when the orga team enters.
     */
    public function tierFor(Race $race, CarbonInterface $at, bool $byOrga = false): ?PriceTier
    {
        return $race->priceTiers()
            ->validAt($at)
            ->when(! $byOrga, fn ($query) => $query->where('orga_only', false))
            ->first();
    }

    /**
     * Total of an entry in cents.
     *
     * @param  iterable<RaceOption>  $options
     *
     * @throws PriceNotAvailable
     */
    public function total(Race $race, CarbonInterface $at, iterable $options = [], bool $byOrga = false): int
    {
        $tier = $this->tierFor($race, $at, $byOrga) ?? throw PriceNotAvailable::noTier($race);

        $total = $tier->amount_cents;

        foreach ($options as $option) {
            if (! $option->isOfferedFor($race)) {
                throw PriceNotAvailable::optionNotOffered($option, $race);
            }

            $total += $option->price_cents;
        }

        return $total;
    }
}
