<?php

namespace Database\Factories;

use App\Domain\Competition\Models\Race;
use App\Integrations\Results\Models\WkMapping;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WkMapping>
 */
class WkMappingFactory extends Factory
{
    protected $model = WkMapping::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'wk' => fake()->unique()->sentence(3),
            'race_id' => Race::factory(),
            'status' => 'sicher',
            'expected_entries' => fake()->numberBetween(10, 600),
        ];
    }
}
