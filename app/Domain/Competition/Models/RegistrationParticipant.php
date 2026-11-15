<?php

namespace App\Domain\Competition\Models;

use App\Domain\Competition\Enums\Gender;
use App\Support\Concerns\RecordsActivity;
use Database\Factories\RegistrationParticipantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A person within a registration: the single starter, or one member of a
 * relay or team (W-12), in the order of the legs.
 *
 * @property int $id
 * @property int $registration_id
 * @property int $position
 * @property string $first_name
 * @property string $last_name
 * @property Carbon $birth_date
 * @property Gender $gender
 * @property Gender|null $scored_as
 * @property int|null $age_group_id
 * @property string|null $guardian_name
 * @property Carbon|null $guardian_consented_at
 * @property string|null $guardian_consent_version
 * @property-read Registration $registration
 * @property-read AgeGroup|null $ageGroup
 */
class RegistrationParticipant extends Model
{
    /** @use HasFactory<RegistrationParticipantFactory> */
    use HasFactory, RecordsActivity;

    protected $fillable = [
        'registration_id',
        'position',
        'first_name',
        'last_name',
        'birth_date',
        'gender',
        'scored_as',
        'age_group_id',
        'guardian_name',
        'guardian_consented_at',
        'guardian_consent_version',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'birth_date' => 'date',
            'gender' => Gender::class,
            'scored_as' => Gender::class,
            'guardian_consented_at' => 'datetime',
        ];
    }

    /**
     * Changes of name and birth date are what a transfer to another person
     * consists of, so they are recorded - the birth date included, since a
     * transfer has to stay traceable (B-03).
     *
     * @return array<int, string>
     */
    protected function auditedAttributes(): array
    {
        return [
            'registration_id',
            'position',
            'first_name',
            'last_name',
            'birth_date',
            'gender',
            'scored_as',
            'age_group_id',
            'guardian_name',
            'guardian_consented_at',
            'guardian_consent_version',
        ];
    }

    /**
     * @return BelongsTo<Registration, $this>
     */
    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    /**
     * @return BelongsTo<AgeGroup, $this>
     */
    public function ageGroup(): BelongsTo
    {
        return $this->belongsTo(AgeGroup::class);
    }

    public function fullName(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
