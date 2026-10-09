<?php

namespace App\Domain\Competition\Rules;

use App\Domain\Competition\Models\Competition;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Keeps a competition from claiming a slug that belongs to the application
 * itself (/admin, /orga, /team ...). The route pattern already refuses to
 * match those, so such a competition would simply never be reachable.
 */
class NotReservedSlug implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && Competition::isReservedSlug($value)) {
            $fail(__('competition.validation.reserved_slug'));
        }
    }
}
