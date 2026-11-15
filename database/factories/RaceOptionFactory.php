<?php

namespace Database\Factories;

use App\Domain\Competition\Models\Edition;
use App\Domain\Competition\Models\RaceOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RaceOption>
 */
class RaceOptionFactory extends Factory
{
    protected $model = RaceOption::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'edition_id' => Edition::factory(),
            'name' => fake()->randomElement(['Funktionsshirt', 'Pasta-Party', 'Chip-Miete']),
            'price_cents' => 1500,
            'quota' => null,
            'sort' => 0,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
