<?php

namespace App\Domain\Competition\Models;

use App\Support\Concerns\RecordsActivity;
use Carbon\CarbonInterface;
use Database\Factories\PriceTierFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A price tier of a race by date (specification W-03): early bird, normal,
 * late entry. The amount is the price of one entry, for a relay or team the
 * whole team. An open bound means "from the start" or "until the end".
 *
 * @property int $id
 * @property int $race_id
 * @property string $name
 * @property int $amount_cents
 * @property Carbon|null $valid_from
 * @property Carbon|null $valid_until
 * @property bool $orga_only
 * @property-read Race $race
 */
class PriceTier extends Model
{
    /** @use HasFactory<PriceTierFactory> */
    use HasFactory, RecordsActivity;

    protected $fillable = [
        'race_id',
        'name',
        'amount_cents',
        'valid_from',
        'valid_until',
        'orga_only',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
            'orga_only' => 'boolean',
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function auditedAttributes(): array
    {
        return ['race_id', 'name', 'amount_cents', 'valid_from', 'valid_until', 'orga_only'];
    }

    /**
     * @return BelongsTo<Race, $this>
     */
    public function race(): BelongsTo
    {
        return $this->belongsTo(Race::class);
    }

    /**
     * Tiers whose period contains the given moment.
     *
     * @param  Builder<PriceTier>  $query
     */
    public function scopeValidAt(Builder $query, CarbonInterface $at): void
    {
        $query
            ->where(fn (Builder $q) => $q->whereNull('valid_from')->orWhere('valid_from', '<=', $at))
            ->where(fn (Builder $q) => $q->whereNull('valid_until')->orWhere('valid_until', '>=', $at));
    }

    /**
     * Tiers of the same race whose period overlaps the given one. Used to keep
     * the periods of a race apart, so the price at any moment is unambiguous.
     *
     * @param  Builder<PriceTier>  $query
     */
    public function scopeOverlapping(Builder $query, ?CarbonInterface $from, ?CarbonInterface $until): void
    {
        if ($until !== null) {
            $query->where(fn (Builder $q) => $q->whereNull('valid_from')->orWhere('valid_from', '<=', $until));
        }

        if ($from !== null) {
            $query->where(fn (Builder $q) => $q->whereNull('valid_until')->orWhere('valid_until', '>=', $from));
        }
    }
}
