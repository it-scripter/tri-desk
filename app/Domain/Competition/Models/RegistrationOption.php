<?php

namespace App\Domain\Competition\Models;

use App\Support\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An option booked with a registration (W-03), with its price frozen at the
 * moment of registration.
 *
 * @property int $id
 * @property int $registration_id
 * @property int $race_option_id
 * @property int|null $race_option_variant_id
 * @property int $price_cents
 * @property-read Registration $registration
 * @property-read RaceOption $option
 * @property-read RaceOptionVariant|null $variant
 */
class RegistrationOption extends Model
{
    use RecordsActivity;

    protected $fillable = [
        'registration_id',
        'race_option_id',
        'race_option_variant_id',
        'price_cents',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_cents' => 'integer',
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function auditedAttributes(): array
    {
        return ['registration_id', 'race_option_id', 'race_option_variant_id', 'price_cents'];
    }

    /**
     * @return BelongsTo<Registration, $this>
     */
    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    /**
     * @return BelongsTo<RaceOption, $this>
     */
    public function option(): BelongsTo
    {
        return $this->belongsTo(RaceOption::class, 'race_option_id');
    }

    /**
     * @return BelongsTo<RaceOptionVariant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(RaceOptionVariant::class, 'race_option_variant_id');
    }
}
