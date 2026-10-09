<?php

namespace App\Domain\Competition\Models;

use App\Support\Concerns\RecordsActivity;
use Database\Factories\EditionDayFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A single race day of an edition (specification 1.5). Races, volunteer shifts
 * and setup work are tied to one of these.
 *
 * @property int $id
 * @property int $edition_id
 * @property Carbon|null $date
 * @property string|null $label
 * @property int $sort
 * @property-read Edition $edition
 */
class EditionDay extends Model
{
    /** @use HasFactory<EditionDayFactory> */
    use HasFactory, RecordsActivity;

    protected $fillable = [
        'edition_id',
        'date',
        'label',
        'sort',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'sort' => 'integer',
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function auditedAttributes(): array
    {
        return ['edition_id', 'date', 'label', 'sort'];
    }

    /**
     * @return BelongsTo<Edition, $this>
     */
    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    /**
     * @return HasMany<Race, $this>
     */
    public function races(): HasMany
    {
        return $this->hasMany(Race::class);
    }

    /**
     * The label, the date, or both - whatever is known. Historic editions may
     * have only a weekday label and no date.
     */
    public function title(): string
    {
        $date = $this->date?->format('d.m.Y');

        return match (true) {
            $this->label !== null && $date !== null => "{$this->label}, {$date}",
            $this->label !== null => $this->label,
            $date !== null => $date,
            default => 'Wettkampftag '.($this->sort + 1),
        };
    }
}
