<?php

namespace App\Domain\Competition\Models;

use App\Domain\Competition\Enums\Gender;
use App\Support\Concerns\RecordsActivity;
use Database\Factories\AgeGroupFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One age group of a scheme, e.g. "M40" for men aged 40 to 44 in the year of
 * the race (specification W-05). An open bound means "and younger" or "and
 * older"; no gender means the group is open to everyone.
 *
 * @property int $id
 * @property int $age_group_scheme_id
 * @property string $code
 * @property string|null $name
 * @property Gender|null $gender
 * @property int|null $min_age
 * @property int|null $max_age
 * @property int $sort
 * @property-read AgeGroupScheme $scheme
 */
class AgeGroup extends Model
{
    /** @use HasFactory<AgeGroupFactory> */
    use HasFactory, RecordsActivity;

    protected $fillable = [
        'age_group_scheme_id',
        'code',
        'name',
        'gender',
        'min_age',
        'max_age',
        'sort',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gender' => Gender::class,
            'min_age' => 'integer',
            'max_age' => 'integer',
            'sort' => 'integer',
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function auditedAttributes(): array
    {
        return ['age_group_scheme_id', 'code', 'name', 'gender', 'min_age', 'max_age', 'sort'];
    }

    /**
     * @return BelongsTo<AgeGroupScheme, $this>
     */
    public function scheme(): BelongsTo
    {
        return $this->belongsTo(AgeGroupScheme::class, 'age_group_scheme_id');
    }

    public function matches(int $age, ?Gender $gender): bool
    {
        if ($this->gender !== null && $this->gender !== $gender) {
            return false;
        }

        return ($this->min_age === null || $age >= $this->min_age)
            && ($this->max_age === null || $age <= $this->max_age);
    }
}
