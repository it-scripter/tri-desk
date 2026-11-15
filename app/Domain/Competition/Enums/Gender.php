<?php

namespace App\Domain\Competition\Enums;

/**
 * Gender as entered at registration. "d" is only offered where the race allows
 * it (Race::allows_diverse) - championships are run without it.
 */
enum Gender: string
{
    case Male = 'm';
    case Female = 'w';
    case Diverse = 'd';

    public function label(): string
    {
        return match ($this) {
            self::Male => 'männlich',
            self::Female => 'weiblich',
            self::Diverse => 'divers',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn (self $gender): string => $gender->label(), self::cases()),
        );
    }
}
