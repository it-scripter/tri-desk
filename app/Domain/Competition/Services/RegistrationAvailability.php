<?php

namespace App\Domain\Competition\Services;

use App\Domain\Competition\Models\Edition;
use App\Domain\Competition\Models\Race;
use Illuminate\Support\Collection;

/**
 * Whether the public can register right now - one answer for the competition
 * page, which links to the form, and the form itself (W-10). Without it the
 * page could offer a registration the form then refuses.
 *
 * A race is open when its edition is in the registration window and the race
 * is public with a price tier valid now. The binding check on limits and
 * rules stays with the registration service; this only decides what to
 * offer.
 */
class RegistrationAvailability
{
    public function __construct(
        private readonly PriceCalculator $prices,
    ) {}

    public function isEditionOpen(Edition $edition): bool
    {
        return ! $edition->isClosed()
            && $edition->registration_opens_at !== null
            && now()->gte($edition->registration_opens_at)
            && ($edition->registration_closes_at === null || now()->lte($edition->registration_closes_at));
    }

    public function isRaceOpen(Race $race): bool
    {
        return $race->is_public
            && $this->isEditionOpen($race->edition)
            && $this->prices->tierFor($race, now()) !== null;
    }

    /**
     * The races the public form offers.
     *
     * @return Collection<int, Race>
     */
    public function openRaces(Edition $edition): Collection
    {
        if (! $this->isEditionOpen($edition)) {
            return collect();
        }

        return $edition->races()
            ->where('is_public', true)
            ->get()
            ->filter(fn (Race $race): bool => $this->prices->tierFor($race, now()) !== null)
            ->values();
    }

    /**
     * Only a hint for what to show; the binding check happens under the lock
     * when the registration is stored.
     */
    public function isSoldOut(Race $race): bool
    {
        return $race->participant_limit !== null
            && $race->registrations()->holdingPlace()->count() >= $race->participant_limit;
    }
}
