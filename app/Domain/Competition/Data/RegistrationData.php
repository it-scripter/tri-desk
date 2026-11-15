<?php

namespace App\Domain\Competition\Data;

use App\Domain\Competition\Models\Race;
use Carbon\CarbonImmutable;

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
     * @param  array<string, mixed>  $extra  answers to per-registration fields (W-11)
     * @param  bool  $termsAccepted  terms of participation, incl. photo and video (W-10)
     * @param  bool  $privacyAcknowledged  privacy notice (W-10)
     * @param  bool  $hideNameInLists  objection to the name in public lists (4.4)
     * @param  CarbonImmutable|null  $at  moment the rules are judged at; set
     *                                    by the test mode to play the form
     *                                    through before registration opens
     * @param  bool  $isTest  a test registration of the orga team
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
        public array $extra = [],
        public bool $termsAccepted = false,
        public bool $privacyAcknowledged = false,
        public bool $hideNameInLists = false,
        public ?CarbonImmutable $at = null,
        public bool $isTest = false,
    ) {}
}
