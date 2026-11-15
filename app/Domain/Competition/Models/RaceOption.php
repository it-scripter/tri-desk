<?php

namespace App\Domain\Competition\Models;

use App\Support\Concerns\RecordsActivity;
use Database\Factories\RaceOptionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An additional option of an edition (specification W-03): shirt, pasta
 * party, timing chip rental. Offered to every race of the edition unless it is
 * restricted to some of them.
 *
 * @property int $id
 * @property int $edition_id
 * @property string $name
 * @property string|null $description
 * @property int $price_cents
 * @property int|null $quota
 * @property int $sort
 * @property bool $is_active
 * @property-read Edition $edition
 * @property-read Collection<int, Race> $races
 * @property-read Collection<int, RaceOptionVariant> $variants
 */
class RaceOption extends Model
{
    /** @use HasFactory<RaceOptionFactory> */
    use HasFactory, RecordsActivity;

    protected $fillable = [
        'edition_id',
        'name',
        'description',
        'price_cents',
        'quota',
        'sort',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_cents' => 'integer',
            'quota' => 'integer',
            'sort' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function auditedAttributes(): array
    {
        return ['edition_id', 'name', 'description', 'price_cents', 'quota', 'is_active'];
    }

    /**
     * @return BelongsTo<Edition, $this>
     */
    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    /**
     * The races the option is restricted to. Empty means every race of the
     * edition.
     *
     * @return BelongsToMany<Race, $this>
     */
    public function races(): BelongsToMany
    {
        return $this->belongsToMany(Race::class, 'race_option_race');
    }

    /**
     * @return HasMany<RaceOptionVariant, $this>
     */
    public function variants(): HasMany
    {
        return $this->hasMany(RaceOptionVariant::class)->orderBy('sort');
    }

    /**
     * Active options a race offers: those of its edition that are either not
     * restricted or restricted to this race.
     *
     * @param  Builder<RaceOption>  $query
     */
    public function scopeOfferedFor(Builder $query, Race $race): void
    {
        $query
            ->where('edition_id', $race->edition_id)
            ->where('is_active', true)
            ->where(fn (Builder $q) => $q
                ->whereDoesntHave('races')
                ->orWhereHas('races', fn (Builder $r) => $r->whereKey($race->getKey())));
    }

    public function isOfferedFor(Race $race): bool
    {
        return static::query()->whereKey($this->getKey())->offeredFor($race)->exists();
    }
}
