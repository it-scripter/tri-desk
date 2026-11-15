<?php

namespace App\Domain\Competition\Enums;

/**
 * Whether an additional field is asked once per registration or for every
 * person of it (W-11).
 */
enum RaceFieldScope: string
{
    case Registration = 'registration';
    case Participant = 'participant';

    public function label(): string
    {
        return match ($this) {
            self::Registration => 'Einmal je Anmeldung',
            self::Participant => 'Je Person',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn (self $scope): string => $scope->label(), self::cases()),
        );
    }
}
