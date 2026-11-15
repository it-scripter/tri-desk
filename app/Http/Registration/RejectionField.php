<?php

namespace App\Http\Registration;

use App\Domain\Competition\Exceptions\RegistrationRejected;

/**
 * Places a refusal of the registration service at the form field it is about,
 * so the message appears next to the input that caused it (WCAG: errors are
 * named where they occur).
 */
final class RejectionField
{
    private const PARTICIPANT_FIELDS = [
        'gender_not_allowed' => 'gender',
        'scored_as_required' => 'scored_as',
        'too_young' => 'birth_date',
        'too_old' => 'birth_date',
        'guardian_required' => 'guardian_name',
        'duplicate' => 'last_name',
    ];

    private const FIELDS = [
        'team_size' => 'participants',
        'no_classification' => 'participants',
        'option_not_offered' => 'options',
        'variant_required' => 'options',
        'option_sold_out' => 'options',
        'consent_required' => 'consents',
    ];

    public static function for(RegistrationRejected $rejection): string
    {
        if ($rejection->participant !== null) {
            $field = self::PARTICIPANT_FIELDS[$rejection->reason] ?? 'first_name';

            return "participants.{$rejection->participant}.{$field}";
        }

        return self::FIELDS[$rejection->reason] ?? 'registration';
    }
}
