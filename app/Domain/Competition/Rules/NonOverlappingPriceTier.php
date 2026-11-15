<?php

namespace App\Domain\Competition\Rules;

use App\Domain\Competition\Models\PriceTier;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Carbon;

/**
 * Keeps the price tiers of a race apart (specification W-03): at any moment
 * at most one tier applies, so the price is never ambiguous. Validated on the
 * start of the period; the end is handed in.
 */
class NonOverlappingPriceTier implements ValidationRule
{
    public function __construct(
        private readonly int $raceId,
        private readonly mixed $validUntil,
        private readonly ?int $ignoreId = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $from = filled($value) ? Carbon::parse($value) : null;
        $until = filled($this->validUntil) ? Carbon::parse($this->validUntil) : null;

        $overlaps = PriceTier::query()
            ->where('race_id', $this->raceId)
            ->when($this->ignoreId !== null, fn ($query) => $query->whereKeyNot($this->ignoreId))
            ->overlapping($from, $until)
            ->exists();

        if ($overlaps) {
            $fail(__('price.validation.overlap'));
        }
    }
}
