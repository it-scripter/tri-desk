<?php

namespace App\Domain\Competition\Models;

use App\Domain\Competition\Enums\Discipline;
use App\Domain\Competition\Enums\Gender;
use App\Domain\Competition\Enums\RaceType;
use App\Domain\Competition\Enums\TeamClassification;
use App\Integrations\Results\Models\WkMapping;
use App\Models\User;
use App\Support\Concerns\RecordsActivity;
use Database\Factories\RaceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsEnumCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
 * @property int|null $age_group_scheme_id
 * @property bool $allows_diverse
 * @property Collection<int, TeamClassification>|null $team_classifications
 * @property bool $is_public
 * @property-read Edition $edition
 * @property-read AgeGroupScheme|null $ageGroupScheme
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
        'age_group_scheme_id',
        'allows_diverse',
        'team_classifications',
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
            'age_group_scheme_id' => 'integer',
            'allows_diverse' => 'boolean',
            'team_classifications' => AsEnumCollection::class.':'.TeamClassification::class,
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
            'age_group_scheme_id',
            'allows_diverse',
            'team_classifications',
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
     * @return BelongsTo<AgeGroupScheme, $this>
     */
    public function ageGroupScheme(): BelongsTo
    {
        return $this->belongsTo(AgeGroupScheme::class);
    }

    /**
     * @return HasMany<PriceTier, $this>
     */
    public function priceTiers(): HasMany
    {
        return $this->hasMany(PriceTier::class)->orderBy('valid_from');
    }

    /**
     * Options restricted to this race. Options open to every race of the
     * edition are not listed here; see RaceOption::scopeOfferedFor.
     *
     * @return BelongsToMany<RaceOption, $this>
     */
    public function restrictedOptions(): BelongsToMany
    {
        return $this->belongsToMany(RaceOption::class, 'race_option_race');
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

    /**
     * The genders the registration may offer: "d" only where the race allows
     * it, championships run without it.
     *
     * @return array<int, Gender>
     */
    public function allowedGenders(): array
    {
        return $this->allows_diverse
            ? [Gender::Male, Gender::Female, Gender::Diverse]
            : [Gender::Male, Gender::Female];
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
