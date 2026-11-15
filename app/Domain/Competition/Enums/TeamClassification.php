<?php

namespace App\Domain\Competition\Enums;

/**
 * Classifications a relay or team may be scored in. Which one a team falls
 * into follows from its members; the rules are settled with the registration
 * (package 2b, see specification chapter 11).
 */
enum TeamClassification: string
{
    case Male = 'male';
    case Female = 'female';
    case Mixed = 'mixed';
    case Open = 'open';

    public function label(): string
    {
        return match ($this) {
            self::Male => 'Männer',
            self::Female => 'Frauen',
            self::Mixed => 'Mixed',
            self::Open => 'Offen',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn (self $classification): string => $classification->label(), self::cases()),
        );
    }
}
