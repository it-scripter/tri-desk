<?php

namespace App\Domain\Competition\Models;

use App\Domain\Competition\Enums\RaceFieldScope;
use App\Domain\Competition\Enums\RaceFieldType;
use App\Support\Concerns\RecordsActivity;
use Database\Factories\RaceFieldFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An additional field of a race (specification W-11), e.g. club, licence
 * number or emergency contact.
 *
 * @property int $id
 * @property int $race_id
 * @property string $key
 * @property string $label
 * @property RaceFieldType $type
 * @property RaceFieldScope $scope
 * @property bool $is_required
 * @property array<int, string>|null $choices
 * @property string|null $help
 * @property int $sort
 * @property-read Race $race
 */
class RaceField extends Model
{
    /** @use HasFactory<RaceFieldFactory> */
    use HasFactory, RecordsActivity;

    protected $fillable = [
        'race_id',
        'key',
        'label',
        'type',
        'scope',
        'is_required',
        'choices',
        'help',
        'sort',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => RaceFieldType::class,
            'scope' => RaceFieldScope::class,
            'is_required' => 'boolean',
            'choices' => 'array',
            'sort' => 'integer',
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function auditedAttributes(): array
    {
        return ['race_id', 'key', 'label', 'type', 'scope', 'is_required', 'choices', 'help', 'sort'];
    }

    /**
     * @return BelongsTo<Race, $this>
     */
    public function race(): BelongsTo
    {
        return $this->belongsTo(Race::class);
    }

    /**
     * Validation rules of an answer to this field.
     *
     * @return array<int, mixed>
     */
    public function rules(): array
    {
        return [
            $this->is_required ? 'required' : 'nullable',
            ...$this->type->rules($this->choices ?? []),
        ];
    }
}
