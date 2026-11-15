<?php

namespace App\Domain\Competition\Enums;

/**
 * How an age group scheme scores entries with the gender "d" (W-05): in age
 * groups of their own, or in the male or female groups as the person chose
 * at registration.
 */
enum DiverseScoring: string
{
    case Separate = 'separate';
    case AsChosen = 'as_chosen';

    public function label(): string
    {
        return match ($this) {
            self::Separate => 'Eigene Wertung',
            self::AsChosen => 'Wertung nach Wahl (männlich oder weiblich)',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn (self $scoring): string => $scoring->label(), self::cases()),
        );
    }
}
