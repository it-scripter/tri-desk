<?php

namespace Database\Factories;

use App\Domain\Competition\Models\PriceTier;
use App\Domain\Competition\Models\Race;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PriceTier>
 */
class PriceTierFactory extends Factory
{
    protected $model = PriceTier::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'race_id' => Race::factory(),
            'name' => 'Normal',
            'amount_cents' => 4500,
            'valid_from' => null,
            'valid_until' => null,
            'orga_only' => false,
        ];
    }

    public function orgaOnly(): static
    {
        return $this->state(['orga_only' => true]);
    }
}
