<?php

namespace App\Domain\Competition\Models;

use App\Domain\Competition\Enums\EditionStatus;
use App\Models\User;
use App\Support\Concerns\RecordsActivity;
use Database\Factories\EditionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One year of a competition (specification W-01) and the central reference
 * point of the data model: races, org units and mail templates hang off it.
 *
 * @property int $id
 * @property int $competition_id
 * @property int $year
 * @property int|null $number
 * @property string|null $display_name
 * @property string $slug
 * @property EditionStatus $status
 * @property bool $is_current
 * @property string|null $note
 * @property-read Competition $competition
 */
class Edition extends Model
{
    /** @use HasFactory<EditionFactory> */
    use HasFactory, RecordsActivity;

    protected $fillable = [
        'competition_id',
        'year',
        'number',
        'display_name',
        'slug',
        'status',
        'starts_on',
        'ends_on',
        'registration_opens_at',
        'registration_closes_at',
        'transfer_deadline_at',
        'is_current',
        'note',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => EditionStatus::class,
            'year' => 'integer',
            'number' => 'integer',
            'starts_on' => 'date',
            'ends_on' => 'date',
            'registration_opens_at' => 'datetime',
            'registration_closes_at' => 'datetime',
            'transfer_deadline_at' => 'datetime',
            'is_current' => 'boolean',
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function auditedAttributes(): array
    {
        return [
            'competition_id',
            'year',
            'number',
            'display_name',
            'slug',
            'status',
            'starts_on',
            'ends_on',
            'registration_opens_at',
            'registration_closes_at',
            'transfer_deadline_at',
            'is_current',
        ];
    }

    protected static function booted(): void
    {
        // Only one edition per competition may be the current one. MariaDB has
        // no partial unique index, so the previous holder is cleared here.
        static::saved(function (Edition $edition): void {
            if (! $edition->is_current) {
                return;
            }

            static::where('competition_id', $edition->competition_id)
                ->whereKeyNot($edition->getKey())
                ->where('is_current', true)
                ->update(['is_current' => false]);
        });
    }

    /**
     * @return BelongsTo<Competition, $this>
     */
    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    /**
     * @return HasMany<EditionDay, $this>
     */
    public function days(): HasMany
    {
        return $this->hasMany(EditionDay::class)->orderBy('sort');
    }

    /**
     * @return HasMany<Race, $this>
     */
    public function races(): HasMany
    {
        return $this->hasMany(Race::class)->orderBy('sort');
    }

    /**
     * @return HasMany<RaceOption, $this>
     */
    public function raceOptions(): HasMany
    {
        return $this->hasMany(RaceOption::class)->orderBy('sort');
    }

    /**
     * @return HasMany<CancellationRule, $this>
     */
    public function cancellationRules(): HasMany
    {
        return $this->hasMany(CancellationRule::class)->orderBy('valid_until');
    }

    /**
     * Narrows a query to the editions the user may see with this permission.
     * Needed next to the policy: a policy answers "may I open this row", a
     * query scope keeps the other rows out of the list in the first place.
     *
     * @param  Builder<Edition>  $query
     */
    public function scopeVisibleTo(Builder $query, User $user, string $permission = 'edition.view'): void
    {
        if ($user->hasPermissionFor($permission)) {
            return;
        }

        $query->whereKey($user->scopeIdsWithPermission(self::class, $permission));
    }

    /**
     * The name this edition ran under - "BerlinMan 2024" for a BerlinWoMen
     * edition up to 2024, otherwise the competition's own name.
     */
    public function title(): string
    {
        return trim(($this->display_name ?? $this->competition->name).' '.$this->year);
    }

    /**
     * The name including the edition number where the organiser counts them,
     * e.g. "38. Volkstriathlon 2027".
     */
    public function titleWithNumber(): string
    {
        if ($this->number === null) {
            return $this->title();
        }

        return "{$this->number}. {$this->title()}";
    }

    public function isClosed(): bool
    {
        return $this->status->isClosed();
    }
}
