<?php

namespace App\Domain\Competition\Exceptions;

use App\Domain\Competition\Models\Race;
use App\Domain\Competition\Models\RaceOption;
use RuntimeException;

/**
 * A price cannot be quoted: no tier is valid at the moment, or an option is
 * not offered for the race.
 */
class PriceNotAvailable extends RuntimeException
{
    public static function noTier(Race $race): self
    {
        return new self("Für den Wettbewerb {$race->name} gilt zu diesem Zeitpunkt keine Preisstufe.");
    }

    public static function optionNotOffered(RaceOption $option, Race $race): self
    {
        return new self("Die Option {$option->name} wird für den Wettbewerb {$race->name} nicht angeboten.");
    }
}
