<?php

namespace App\Domain\Competition\Exceptions;

use RuntimeException;

/**
 * A registration that cannot be accepted. The reason is a key below
 * registration.rejected, so forms can show the German text and tests can
 * check the cause; participant names the person it is about, if any.
 */
class RegistrationRejected extends RuntimeException
{
    /**
     * @param  array<string, string|int>  $replace
     */
    public function __construct(
        public readonly string $reason,
        public readonly ?int $participant = null,
        array $replace = [],
    ) {
        parent::__construct(__('registration.rejected.'.$reason, $replace));
    }
}
