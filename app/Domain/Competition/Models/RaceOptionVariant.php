<?php

namespace App\Domain\Competition\Models;

use App\Support\Concerns\RecordsActivity;
use Database\Factories\RaceOptionVariantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A choice within an option, e.g. a shirt size, with an optional quota of its
 * own (specification W-03).
 *
 * @property int $id
 * @property int $race_option_id
 * @property string $name
 * @property int|null $quota
 * @property int $sort
 * @property-read RaceOption $option
 */
class RaceOptionVariant extends Model
{
    /** @use HasFactory<RaceOptionVariantFactory> */
    use HasFactory, RecordsActivity;

    protected $fillable = [
        'race_option_id',
        'name',
        'quota',
        'sort',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quota' => 'integer',
            'sort' => 'integer',
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function auditedAttributes(): array
    {
        return ['race_option_id', 'name', 'quota'];
    }

    /**
     * @return BelongsTo<RaceOption, $this>
     */
    public function option(): BelongsTo
    {
        return $this->belongsTo(RaceOption::class, 'race_option_id');
    }
}
