<?php

namespace App\Domain\Access\Enums;

/**
 * The roles from the specification, chapter 3. Role names are stored as plain
 * strings by spatie/laravel-permission; this enum keeps them in one place.
 */
enum RoleName: string
{
    case SuperAdmin = 'super-admin';
    case Orga = 'orga';
    case Redaktion = 'redaktion';
    case Teamleitung = 'teamleitung';
    case Helfende = 'helfende';
    case Teilnehmende = 'teilnehmende';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super-Admin',
            self::Orga => 'Orga-Team',
            self::Redaktion => 'Redaktion',
            self::Teamleitung => 'Teamleitung',
            self::Helfende => 'Helfende',
            self::Teilnehmende => 'Teilnehmende',
        };
    }

    /**
     * All role names mapped to their German label, for select inputs.
     *
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn (self $role): string => $role->label(), self::cases()),
        );
    }
}
