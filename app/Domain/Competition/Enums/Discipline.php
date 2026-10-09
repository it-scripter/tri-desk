<?php

namespace App\Domain\Competition\Enums;

/**
 * Disciplines a race can be made of, in any order (specification 1.5):
 * triathlon swim-bike-run, duathlon run-bike-run, aquabike swim-bike,
 * swim & run swim-run. Canoe only occurs in the historic BerlinMan quadrathlon
 * and is kept so those old results stay representable.
 */
enum Discipline: string
{
    case Swim = 'swim';
    case Bike = 'bike';
    case Run = 'run';
    case Canoe = 'canoe';

    public function label(): string
    {
        return match ($this) {
            self::Swim => 'Schwimmen',
            self::Bike => 'Rad',
            self::Run => 'Lauf',
            self::Canoe => 'Kanu',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn (self $discipline): string => $discipline->label(), self::cases()),
        );
    }
}
