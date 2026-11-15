<?php

namespace App\Domain\Competition\Data;

use App\Domain\Competition\Enums\Gender;
use Carbon\CarbonImmutable;

/**
 * One person of a registration as entered: the single starter or a member
 * of a relay or team (W-12).
 */
final readonly class ParticipantData
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public CarbonImmutable $birthDate,
        public Gender $gender,
        public ?Gender $scoredAs = null,
        public ?string $guardianName = null,
        public bool $guardianConsent = false,
        /** @var array<string, mixed> answers to per-person fields (W-11) */
        public array $extra = [],
    ) {}
}
