<?php

namespace App\Domain\Competition\Support;

use App\Domain\Competition\Enums\RaceFieldScope;
use App\Domain\Competition\Enums\RaceFieldType;

/**
 * Ready-made additional fields named in the specification (W-11). The orga
 * team takes one over with a click and may adjust it; free fields can be
 * added next to them.
 */
final class RaceFieldCatalogue
{
    /**
     * @return array<string, array{label: string, type: RaceFieldType, scope: RaceFieldScope, help: string|null}>
     */
    public static function entries(): array
    {
        return [
            'club' => [
                'label' => 'Verein',
                'type' => RaceFieldType::Text,
                'scope' => RaceFieldScope::Participant,
                'help' => null,
            ],
            'licence_number' => [
                'label' => 'Lizenznummer',
                'type' => RaceFieldType::Text,
                'scope' => RaceFieldScope::Participant,
                'help' => 'Startlizenz des Landesverbands, falls vorhanden.',
            ],
            'start_pass_number' => [
                'label' => 'Startpassnummer',
                'type' => RaceFieldType::Text,
                'scope' => RaceFieldScope::Participant,
                'help' => null,
            ],
            'expected_time' => [
                'label' => 'Erwartete Zeit',
                'type' => RaceFieldType::Time,
                'scope' => RaceFieldScope::Registration,
                'help' => 'Im Format hh:mm:ss, zum Beispiel 01:15:00. Hilft bei der Einteilung der Startwellen.',
            ],
            'emergency_contact' => [
                'label' => 'Notfallkontakt (Name und Telefon)',
                'type' => RaceFieldType::Text,
                'scope' => RaceFieldScope::Registration,
                'help' => 'Wen wir im Notfall am Wettkampftag erreichen können.',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return array_map(fn (array $entry): string => $entry['label'], self::entries());
    }
}
