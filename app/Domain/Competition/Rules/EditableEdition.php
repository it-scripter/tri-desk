<?php

namespace App\Domain\Competition\Rules;

use App\Domain\Competition\Models\Edition;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Gate;

/**
 * Guards the edition chosen in a form. A policy can only judge an existing
 * record, so creating a race would otherwise slip past the read-only rule for
 * archived and cancelled editions (see EditionPolicy::update).
 */
class EditableEdition implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_numeric($value)) {
            return;
        }

        $edition = Edition::query()->find($value);

        if ($edition === null) {
            return;
        }

        if (Gate::denies('update', $edition)) {
            $fail(__('edition.validation.not_editable'));
        }
    }
}
