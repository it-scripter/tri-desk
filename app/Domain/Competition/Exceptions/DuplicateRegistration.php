<?php

namespace App\Domain\Competition\Exceptions;

/**
 * The person is already registered for the race (W-16). The public form
 * refuses; the orga team may confirm the warning and save anyway.
 */
class DuplicateRegistration extends RegistrationRejected
{
    public function __construct(int $participant, string $name)
    {
        parent::__construct('duplicate', $participant, ['name' => $name]);
    }
}
