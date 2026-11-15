<?php

namespace Database\Factories;

use App\Domain\Competition\Models\RaceOption;
use App\Domain\Competition\Models\RaceOptionVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RaceOptionVariant>
 */
class RaceOptionVariantFactory extends Factory
{
    protected $model = RaceOptionVariant::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'race_option_id' => RaceOption::factory(),
            'name' => fake()->randomElement(['S', 'M', 'L', 'XL']),
            'quota' => null,
            'sort' => 0,
        ];
    }
}
