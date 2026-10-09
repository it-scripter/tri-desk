<?php

namespace Database\Factories;

use App\Domain\Competition\Models\Edition;
use App\Domain\Competition\Models\EditionDay;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EditionDay>
 */
class EditionDayFactory extends Factory
{
    protected $model = EditionDay::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'edition_id' => Edition::factory(),
            'date' => fake()->dateTimeBetween('-2 years', '+2 years')->format('Y-m-d'),
            'label' => null,
            'sort' => 0,
        ];
    }
}
