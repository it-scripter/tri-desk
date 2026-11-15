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
use Illuminate\Support\Carbon;
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
 * @property int|null $team_size_min
 * @property int|null $team_size_max
 * @property Collection<int, Discipline> $discipline_sequence
 * @property array<string, mixed>|null $distances
 * @property int|null $participant_limit
 * @property int|null $age_group_scheme_id
 * @property bool $allows_diverse
 * @property Collection<int, TeamClassification>|null $team_classifications
 * @property int $mixed_min_male
 * @property int $mixed_min_female
 * @property bool $is_public
 * @property-read Edition $edition
 * @property-read AgeGroupScheme|null $ageGroupScheme
 * @property-read EditionDay|null $editionDay
 */
class Race extends Model
{
    /** @use HasFactory<RaceFactory> */
    use HasFactory, RecordsActivity;

    /**
     * Defaults of the table, known to a new model before it is reloaded -
     * otherwise the audit log takes them for a change on the first update.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'mixed_min_male' => 1,
        'mixed_min_female' => 1,
    ];

    protected $fillable = [
        'edition_id',
        'edition_day_id',
        'name',
        'slug',
        'type',
        'team_size_min',
        'team_size_max',
        'discipline_sequence',
        'distances',
        'starts_at',
        'participant_limit',
        'min_age',
        'max_age',
        'age_group_scheme_id',
        'allows_diverse',
        'team_classifications',
        'mixed_min_male',
        'mixed_min_female',
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
            'team_size_min' => 'integer',
            'team_size_max' => 'integer',
            'mixed_min_male' => 'integer',
            'mixed_min_female' => 'integer',
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
            'team_size_min',
            'team_size_max',
            'discipline_sequence',
            'distances',
            'starts_at',
            'participant_limit',
            'min_age',
            'max_age',
            'age_group_scheme_id',
            'allows_diverse',
            'team_classifications',
            'mixed_min_male',
            'mixed_min_female',
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
     * @return HasMany<Registration, $this>
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
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

    public function isTeamRace(): bool
    {
        return in_array($this->type, [RaceType::Relay, RaceType::Team], true);
    }

    /**
     * The classifications a relay or team may be scored in (W-18). A race
     * that lists none offers all of them.
     *
     * @return array<int, TeamClassification>
     */
    public function offeredTeamClassifications(): array
    {
        $offered = $this->team_classifications?->all() ?? [];

        return $offered === [] ? TeamClassification::cases() : array_values($offered);
    }

    /**
     * The date age and minority are judged on: the race day, else the first
     * day of the edition. Null for an edition without a date.
     */
    public function raceDate(): ?Carbon
    {
        return $this->editionDay->date ?? $this->edition->starts_on;
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
