<?php

namespace App\Domain\Competition\Models;

use App\Domain\Competition\Enums\Discipline;
use App\Domain\Competition\Enums\RaceType;
use App\Integrations\Results\Models\WkMapping;
use App\Models\User;
use App\Support\Concerns\RecordsActivity;
use Database\Factories\RaceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsEnumCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * A single starting class within an edition (specification W-02): sprint
 * distance, relay, kids race. The discipline sequence is free per race and
 * drives transition zones, split times and result lists.
 *
 * @property int $id
 * @property int $edition_id
 * @property int|null $edition_day_id
 * @property string $name
 * @property string $slug
 * @property RaceType $type
 * @property Collection<int, Discipline> $discipline_sequence
 * @property array<string, mixed>|null $distances
 * @property int|null $participant_limit
 * @property bool $is_public
 * @property-read Edition $edition
 */
class Race extends Model
{
    /** @use HasFactory<RaceFactory> */
    use HasFactory, RecordsActivity;

    protected $fillable = [
        'edition_id',
        'edition_day_id',
        'name',
        'slug',
        'type',
        'discipline_sequence',
        'distances',
        'starts_at',
        'participant_limit',
        'min_age',
        'max_age',
        'sort',
        'is_public',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => RaceType::class,
            'discipline_sequence' => AsEnumCollection::class.':'.Discipline::class,
            'distances' => 'array',
            'participant_limit' => 'integer',
            'min_age' => 'integer',
            'max_age' => 'integer',
            'sort' => 'integer',
            'is_public' => 'boolean',
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function auditedAttributes(): array
    {
        return [
            'edition_id',
            'edition_day_id',
            'name',
            'slug',
            'type',
            'discipline_sequence',
            'distances',
            'starts_at',
            'participant_limit',
            'min_age',
            'max_age',
            'is_public',
        ];
    }

    /**
     * @return BelongsTo<Edition, $this>
     */
    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    /**
     * @return BelongsTo<EditionDay, $this>
     */
    public function editionDay(): BelongsTo
    {
        return $this->belongsTo(EditionDay::class);
    }

    /**
     * @return HasMany<WkMapping, $this>
     */
    public function wkMappings(): HasMany
    {
        return $this->hasMany(WkMapping::class);
    }

    /**
     * Narrows a query to the races of the editions the user may see.
     *
     * @param  Builder<Race>  $query
     */
    public function scopeVisibleTo(Builder $query, User $user, string $permission = 'race.view'): void
    {
        if ($user->hasPermissionFor($permission)) {
            return;
        }

        $query->whereIn('edition_id', $user->scopeIdsWithPermission(Edition::class, $permission));
    }

    public function hasDiscipline(Discipline $discipline): bool
    {
        return $this->discipline_sequence->contains($discipline);
    }

    /**
     * The sequence in German, e.g. "Schwimmen – Rad – Lauf".
     */
    public function disciplineSequenceLabel(): string
    {
        return $this->discipline_sequence
            ->map(fn (Discipline $discipline): string => $discipline->label())
            ->implode(' – ');
    }
}
