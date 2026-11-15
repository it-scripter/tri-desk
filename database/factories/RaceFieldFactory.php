<?php

namespace Database\Factories;

use App\Domain\Competition\Enums\RaceFieldScope;
use App\Domain\Competition\Enums\RaceFieldType;
use App\Domain\Competition\Models\Race;
use App\Domain\Competition\Models\RaceField;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RaceField>
 */
class RaceFieldFactory extends Factory
{
    protected $model = RaceField::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'race_id' => Race::factory(),
            'key' => fake()->unique()->slug(2),
            'label' => 'Verein',
            'type' => RaceFieldType::Text,
            'scope' => RaceFieldScope::Participant,
            'is_required' => false,
            'sort' => 0,
        ];
    }

    public function required(): static
    {
        return $this->state(['is_required' => true]);
    }

    public function perRegistration(): static
    {
        return $this->state(['scope' => RaceFieldScope::Registration]);
    }
}
