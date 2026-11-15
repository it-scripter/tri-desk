<?php

namespace Database\Factories;

use App\Domain\Competition\Models\AgeGroup;
use App\Domain\Competition\Models\AgeGroupScheme;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AgeGroup>
 */
class AgeGroupFactory extends Factory
{
    protected $model = AgeGroup::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'age_group_scheme_id' => AgeGroupScheme::factory(),
            'code' => fake()->unique()->bothify('AK##'),
            'gender' => null,
            'min_age' => null,
            'max_age' => null,
            'sort' => 0,
        ];
    }
}
