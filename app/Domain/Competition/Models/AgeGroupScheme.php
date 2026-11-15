<?php

namespace App\Domain\Competition\Models;

use App\Domain\Competition\Enums\DiverseScoring;
use App\Domain\Competition\Enums\Gender;
use App\Support\Concerns\RecordsActivity;
use Database\Factories\AgeGroupSchemeFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An age group scheme (specification W-05), e.g. after DTU or DLV. Defined
 * once and assigned to races of any edition.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property DiverseScoring $diverse_scoring
 * @property string|null $description
 * @property-read Collection<int, AgeGroup> $ageGroups
 */
class AgeGroupScheme extends Model
{
    /** @use HasFactory<AgeGroupSchemeFactory> */
    use HasFactory, RecordsActivity;

    protected $fillable = [
        'name',
        'slug',
        'diverse_scoring',
        'description',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'diverse_scoring' => DiverseScoring::class,
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function auditedAttributes(): array
    {
        return ['name', 'slug', 'diverse_scoring', 'description'];
    }

    /**
     * @return HasMany<AgeGroup, $this>
     */
    public function ageGroups(): HasMany
    {
        return $this->hasMany(AgeGroup::class)->orderBy('sort');
    }

    /**
     * @return HasMany<Race, $this>
     */
    public function races(): HasMany
    {
        return $this->hasMany(Race::class);
    }

    /**
     * The gender an entry is scored in. With "as chosen" a diverse entry is
     * scored in the male or female groups it picked; without a choice it
     * cannot be placed and gets no group.
     */
    public function scoringGender(Gender $gender, ?Gender $scoredAs = null): ?Gender
    {
        if ($gender !== Gender::Diverse || $this->diverse_scoring === DiverseScoring::Separate) {
            return $gender;
        }

        return $scoredAs === Gender::Diverse ? null : $scoredAs;
    }
}
