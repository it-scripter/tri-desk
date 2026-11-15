<?php

namespace App\Domain\Competition\Data;

use App\Domain\Competition\Models\Race;

/**
 * Everything a registration is made of, as entered in the public form or by
 * the orga team.
 */
final readonly class RegistrationData
{
    /**
     * @param  array<int, ParticipantData>  $participants  in the order of the legs
     * @param  array<int, OptionChoice>  $options
     * @param  bool  $byOrga  entered by the orga team: late entry tiers apply,
     *                        the registration window does not
     * @param  bool  $allowDuplicate  the orga team confirmed a duplicate warning
     */
    public function __construct(
        public Race $race,
        public string $contactFirstName,
        public string $contactLastName,
        public string $contactEmail,
        public array $participants,
        public array $options = [],
        public ?string $teamName = null,
        public bool $byOrga = false,
        public bool $allowDuplicate = false,
    ) {}
}
