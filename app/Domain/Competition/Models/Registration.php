<?php

namespace App\Domain\Competition\Models;

use App\Domain\Competition\Enums\RegistrationStatus;
use App\Domain\Competition\Enums\TeamClassification;
use App\Models\User;
use App\Support\Concerns\RecordsActivity;
use Carbon\CarbonInterface;
use Database\Factories\RegistrationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * One entry in a race (specification 4.2): a single person, or a whole relay
 * or team with all its members (W-12). Price tier and amount are frozen at
 * the moment of registration.
 *
 * @property int $id
 * @property int $race_id
 * @property int|null $price_tier_id
 * @property int $amount_cents
 * @property RegistrationStatus $status
 * @property Carbon|null $reserved_until
 * @property Carbon|null $confirmed_at
 * @property Carbon|null $cancelled_at
 * @property string $contact_first_name
 * @property string $contact_last_name
 * @property string $contact_email
 * @property string|null $team_name
 * @property TeamClassification|null $team_classification
 * @property array<string, mixed>|null $extra
 * @property bool $hide_name_in_lists
 * @property Carbon|null $terms_accepted_at
 * @property string|null $terms_version
 * @property Carbon|null $privacy_acknowledged_at
 * @property string|null $privacy_version
 * @property string|null $token_hash
 * @property int|null $user_id
 * @property bool $entered_by_orga
 * @property bool $is_test
 * @property Carbon $created_at
 * @property-read Race $race
 * @property-read PriceTier|null $priceTier
 * @property-read Collection<int, RegistrationParticipant> $participants
 * @property-read Collection<int, RegistrationOption> $options
 */
class Registration extends Model
{
    /** @use HasFactory<RegistrationFactory> */
    use HasFactory, RecordsActivity;

    protected $fillable = [
        'race_id',
        'price_tier_id',
        'amount_cents',
        'status',
        'reserved_until',
        'confirmed_at',
        'cancelled_at',
        'contact_first_name',
        'contact_last_name',
        'contact_email',
        'team_name',
        'team_classification',
        'extra',
        'hide_name_in_lists',
        'terms_accepted_at',
        'terms_version',
        'privacy_acknowledged_at',
        'privacy_version',
        'token_hash',
        'user_id',
        'entered_by_orga',
        'is_test',
    ];

    /**
     * The magic link hash is never shown or serialised.
     *
     * @var list<string>
     */
    protected $hidden = ['token_hash'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'status' => RegistrationStatus::class,
            'reserved_until' => 'datetime',
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'team_classification' => TeamClassification::class,
            'extra' => 'array',
            'hide_name_in_lists' => 'boolean',
            'terms_accepted_at' => 'datetime',
            'privacy_acknowledged_at' => 'datetime',
            'entered_by_orga' => 'boolean',
            'is_test' => 'boolean',
        ];
    }

    /**
     * Personal data and the magic link stay out of the audit log; status,
     * amount and classification are what transfers and payments are traced
     * by (B-03).
     *
     * @return array<int, string>
     */
    protected function auditedAttributes(): array
    {
        return [
            'race_id',
            'price_tier_id',
            'amount_cents',
            'status',
            'reserved_until',
            'confirmed_at',
            'cancelled_at',
            'team_name',
            'team_classification',
            'hide_name_in_lists',
            'terms_accepted_at',
            'terms_version',
            'privacy_acknowledged_at',
            'privacy_version',
            'entered_by_orga',
            'is_test',
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
     * @return BelongsTo<PriceTier, $this>
     */
    public function priceTier(): BelongsTo
    {
        return $this->belongsTo(PriceTier::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<RegistrationParticipant, $this>
     */
    public function participants(): HasMany
    {
        return $this->hasMany(RegistrationParticipant::class)->orderBy('position');
    }

    /**
     * @return HasMany<RegistrationOption, $this>
     */
    public function options(): HasMany
    {
        return $this->hasMany(RegistrationOption::class);
    }

    /**
     * Registrations that hold a place: confirmed ones, and reserved ones
     * whose payment window has not run out yet. A reservation past its window
     * no longer counts, even before the expiry job has marked it. Test
     * registrations never hold a place.
     *
     * @param  Builder<Registration>  $query
     */
    public function scopeHoldingPlace(Builder $query, ?CarbonInterface $at = null): void
    {
        $at ??= now();

        $query->where('is_test', false)->where(fn (Builder $q) => $q
            ->where('status', RegistrationStatus::Confirmed)
            ->orWhere(fn (Builder $r) => $r
                ->where('status', RegistrationStatus::Reserved)
                ->where('reserved_until', '>', $at)));
    }

    /**
     * Real registrations only, without the test registrations of the orga
     * team. Every list and export of participants starts from here.
     *
     * @param  Builder<Registration>  $query
     */
    public function scopeReal(Builder $query): void
    {
        $query->where('is_test', false);
    }

    /**
     * Registrations of the editions the user may see.
     *
     * @param  Builder<Registration>  $query
     */
    public function scopeVisibleTo(Builder $query, User $user, string $permission = 'registration.view'): void
    {
        if ($user->hasPermissionFor($permission)) {
            return;
        }

        $query->whereHas('race', fn (Builder $race) => $race
            ->whereIn('edition_id', $user->scopeIdsWithPermission(Edition::class, $permission)));
    }

    public function isTeam(): bool
    {
        return $this->race->isTeamRace();
    }
}
