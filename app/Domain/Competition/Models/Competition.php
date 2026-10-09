<?php

namespace App\Domain\Competition\Models;

use App\Support\Concerns\RecordsActivity;
use Database\Factories\CompetitionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A recurring brand with its own start page, e.g. Volkstriathlon or
 * BerlinWoMen (specification W-01). Resolved from the path by slug, later
 * possibly from the host (C-01).
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $domain
 * @property array<string, mixed>|null $settings
 * @property bool $is_active
 * @property int $sort
 */
class Competition extends Model
{
    /** @use HasFactory<CompetitionFactory> */
    use HasFactory, RecordsActivity, SoftDeletes;

    /**
     * Slugs that would collide with a panel or a reserved application path.
     * A competition must never claim one of these.
     *
     * @var array<int, string>
     */
    public const RESERVED_SLUGS = [
        'admin',
        'orga',
        'team',
        'helfer',
        'login',
        'logout',
        'register',
        'password',
        'storage',
        'livewire',
        'filament',
        'api',
        'up',
    ];

    protected $fillable = [
        'name',
        'slug',
        'domain',
        'settings',
        'is_active',
        'sort',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function auditedAttributes(): array
    {
        return ['name', 'slug', 'domain', 'is_active', 'sort'];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return HasMany<Edition, $this>
     */
    public function editions(): HasMany
    {
        return $this->hasMany(Edition::class);
    }

    /**
     * @return HasManyThrough<Race, Edition, $this>
     */
    public function races(): HasManyThrough
    {
        return $this->hasManyThrough(Race::class, Edition::class);
    }

    /**
     * The edition the public pages and the orga panel default to: the one
     * marked current, otherwise the most recent one that is still open.
     */
    public function currentEdition(): ?Edition
    {
        return $this->editions()
            ->where('is_current', true)
            ->first()
            ?? $this->editions()
                ->whereNotIn('status', ['archived', 'cancelled'])
                ->orderByDesc('year')
                ->first();
    }

    public static function isReservedSlug(string $slug): bool
    {
        return in_array(mb_strtolower($slug), self::RESERVED_SLUGS, true);
    }

    /**
     * Route constraint for the competition path segment. The negative
     * lookahead keeps a reserved slug from ever matching, so /admin reaches
     * the panel no matter in which order the routes were registered.
     */
    public static function slugRoutePattern(): string
    {
        $reserved = implode('|', array_map(
            fn (string $slug): string => preg_quote($slug, '/'),
            self::RESERVED_SLUGS,
        ));

        return "(?!(?:{$reserved})$)[a-z0-9]+(?:-[a-z0-9]+)*";
    }
}
