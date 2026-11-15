<?php

namespace App\Filament\Shared;

use Filament\Forms\Components\TextInput;

/**
 * Amounts are stored as integer cents (CLAUDE.md) but entered in euros. The
 * field shows the cents as euros and hands cents back on save, so no float
 * ever reaches the database.
 */
class MoneyInput
{
    public static function make(string $name): TextInput
    {
        // Not numeric(): that rejects the German decimal comma ("45,50").
        // The pattern allows both separators and at most two decimals.
        return TextInput::make($name)
            ->inputMode('decimal')
            ->rules(['regex:/^\d+([.,]\d{1,2})?$/'])
            ->suffix('€')
            ->formatStateUsing(fn (mixed $state): ?string => is_numeric($state)
                ? number_format(((int) $state) / 100, 2, ',', '')
                : null)
            ->dehydrateStateUsing(fn (mixed $state): ?int => self::toCents($state));
    }

    /**
     * Euros as typed - "45", "45.5", "45,50" - to cents. Parsed from the
     * string rather than multiplied as a float, so 0.29 € stays 29 cents.
     */
    public static function toCents(mixed $state): ?int
    {
        if (! is_scalar($state) || trim((string) $state) === '') {
            return null;
        }

        $value = str_replace(',', '.', trim((string) $state));
        [$euros, $fraction] = array_pad(explode('.', $value, 2), 2, '');

        return ((int) $euros) * 100 + (int) str_pad(substr($fraction, 0, 2), 2, '0');
    }
}
