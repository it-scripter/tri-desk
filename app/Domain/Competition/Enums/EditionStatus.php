<?php

namespace App\Domain\Competition\Enums;

/**
 * Lifecycle of an edition. Historic editions are archived, the 32nd
 * Volkstriathlon (2020/21) is cancelled - it was called off because of Corona.
 */
enum EditionStatus: string
{
    case Planned = 'planned';
    case RegistrationOpen = 'registration_open';
    case RegistrationClosed = 'registration_closed';
    case Running = 'running';
    case Archived = 'archived';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Planned => 'Geplant',
            self::RegistrationOpen => 'Anmeldung offen',
            self::RegistrationClosed => 'Anmeldung geschlossen',
            self::Running => 'Läuft',
            self::Archived => 'Archiviert',
            self::Cancelled => 'Ausgefallen',
        };
    }

    /**
     * An edition that is over or never happened. Those are read-only and never
     * the current edition of a competition.
     */
    public function isClosed(): bool
    {
        return in_array($this, [self::Archived, self::Cancelled], true);
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn (self $status): string => $status->label(), self::cases()),
        );
    }
}
