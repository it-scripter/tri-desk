<?php

namespace App\Domain\Competition\Data;

use App\Domain\Competition\Models\Registration;

/**
 * A stored registration with the plain magic link token. The token exists
 * only here - the database holds its hash - so it has to go into the
 * confirmation mail right away (W-15).
 */
final readonly class RegistrationResult
{
    public function __construct(
        public Registration $registration,
        public string $token,
    ) {}
}
