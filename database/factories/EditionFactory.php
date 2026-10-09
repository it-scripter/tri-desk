<?php

namespace Database\Factories;

use App\Domain\Competition\Enums\EditionStatus;
use App\Domain\Competition\Models\Competition;
use App\Domain\Competition\Models\Edition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Edition>
 */
class EditionFactory extends Factory
{
    protected $model = Edition::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $year = fake()->numberBetween(1989, 2030);

        return [
            'competition_id' => Competition::factory(),
            'year' => $year,
            'slug' => (string) $year,
            'status' => EditionStatus::Planned,
            'is_current' => false,
        ];
    }

    public function current(): static
    {
        return $this->state(['is_current' => true]);
    }

    public function archived(): static
    {
        return $this->state(['status' => EditionStatus::Archived]);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => EditionStatus::Cancelled]);
    }
}
