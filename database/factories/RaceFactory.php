<?php

namespace Database\Factories;

use App\Domain\Competition\Enums\Discipline;
use App\Domain\Competition\Enums\RaceType;
use App\Domain\Competition\Models\Edition;
use App\Domain\Competition\Models\Race;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Race>
 */
class RaceFactory extends Factory
{
    protected $model = Race::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // asText guarantees a string; the stub still declares array|string.
        /** @var string $words */
        $words = fake()->unique()->words(2, true);
        $name = Str::title($words);

        return [
            'edition_id' => Edition::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'type' => RaceType::Individual,
            'discipline_sequence' => [Discipline::Swim, Discipline::Bike, Discipline::Run],
            'sort' => 0,
            'is_public' => true,
        ];
    }

    public function relay(): static
    {
        return $this->state(['type' => RaceType::Relay]);
    }

    public function duathlon(): static
    {
        return $this->state([
            'discipline_sequence' => [Discipline::Run, Discipline::Bike, Discipline::Run],
        ]);
    }

    public function quadrathlon(): static
    {
        return $this->state([
            'discipline_sequence' => [Discipline::Swim, Discipline::Bike, Discipline::Canoe, Discipline::Run],
        ]);
    }
}
