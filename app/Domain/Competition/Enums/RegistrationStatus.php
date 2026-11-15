<?php

namespace App\Domain\Competition\Enums;

/**
 * Lifecycle of a registration (W-22): reserved while payment is outstanding,
 * confirmed once paid or with a direct debit mandate, cancelled by the
 * participant or the orga team, expired when the payment window ran out.
 */
enum RegistrationStatus: string
{
    case Reserved = 'reserved';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Reserved => 'Reserviert',
            self::Confirmed => 'Bestätigt',
            self::Cancelled => 'Storniert',
            self::Expired => 'Verfallen',
        };
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
