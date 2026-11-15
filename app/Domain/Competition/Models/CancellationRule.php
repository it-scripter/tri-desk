<?php

namespace App\Domain\Competition\Models;

use App\Support\Concerns\RecordsActivity;
use Database\Factories\CancellationRuleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One step of the cancellation rules of an edition (specification W-04): a
 * cancellation up to valid_until refunds refund_percent of the amount paid,
 * less the fee.
 *
 * @property int $id
 * @property int $edition_id
 * @property Carbon $valid_until
 * @property int $refund_percent
 * @property int $fee_cents
 * @property string|null $note
 * @property-read Edition $edition
 */
class CancellationRule extends Model
{
    /** @use HasFactory<CancellationRuleFactory> */
    use HasFactory, RecordsActivity;

    protected $fillable = [
        'edition_id',
        'valid_until',
        'refund_percent',
        'fee_cents',
        'note',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'valid_until' => 'datetime',
            'refund_percent' => 'integer',
            'fee_cents' => 'integer',
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function auditedAttributes(): array
    {
        return ['edition_id', 'valid_until', 'refund_percent', 'fee_cents', 'note'];
    }

    /**
     * @return BelongsTo<Edition, $this>
     */
    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    /**
     * The refund for an amount paid: the share of this step, less the fee,
     * never below zero. Rounded down to the cent, so the organiser never pays
     * out more than the rule says.
     */
    public function refundFor(int $paidCents): int
    {
        return max(0, intdiv($paidCents * $this->refund_percent, 100) - $this->fee_cents);
    }
}
