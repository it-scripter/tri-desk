<?php

namespace App\Domain\Competition\Enums;

/**
 * How a race is entered: by one person, by a relay team taking turns, or by a
 * team scored together (specification W-02).
 */
enum RaceType: string
{
    case Individual = 'individual';
    case Relay = 'relay';
    case Team = 'team';

    public function label(): string
    {
        return match ($this) {
            self::Individual => 'Einzel',
            self::Relay => 'Staffel',
            self::Team => 'Team',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn (self $type): string => $type->label(), self::cases()),
        );
    }
}
