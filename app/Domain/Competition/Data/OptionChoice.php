<?php

namespace App\Domain\Competition\Data;

use App\Domain\Competition\Models\RaceOption;
use App\Domain\Competition\Models\RaceOptionVariant;

/**
 * An option chosen at registration, with the variant where the option has
 * some, e.g. the shirt size (W-03).
 */
final readonly class OptionChoice
{
    public function __construct(
        public RaceOption $option,
        public ?RaceOptionVariant $variant = null,
    ) {}
}
