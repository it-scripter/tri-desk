<?php

namespace App\Integrations\Results\Models;

use App\Domain\Competition\Models\EditionDay;
use App\Domain\Competition\Models\Race;
use Database\Factories\WkMappingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Maps one wk value of the legacy result database onto a race of this portal
 * (specification W-33, W-37). Several wk values may point at the same race.
 *
 * @property int $id
 * @property string $wk
 * @property int $race_id
 * @property int|null $edition_day_id
 * @property string $status
 * @property string|null $note
 * @property int|null $expected_entries
 * @property-read Race $race
 */
class WkMapping extends Model
{
    /** @use HasFactory<WkMappingFactory> */
    use HasFactory;

    protected $fillable = [
        'wk',
        'race_id',
        'edition_day_id',
        'status',
        'note',
        'expected_entries',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expected_entries' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Race, $this>
     */
    public function race(): BelongsTo
    {
        return $this->belongsTo(Race::class);
    }

    /**
     * @return BelongsTo<EditionDay, $this>
     */
    public function editionDay(): BelongsTo
    {
        return $this->belongsTo(EditionDay::class);
    }
}
