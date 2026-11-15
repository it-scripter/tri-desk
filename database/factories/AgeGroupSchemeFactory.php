<?php

namespace Database\Factories;

use App\Domain\Competition\Enums\DiverseScoring;
use App\Domain\Competition\Models\AgeGroupScheme;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AgeGroupScheme>
 */
class AgeGroupSchemeFactory extends Factory
{
    protected $model = AgeGroupScheme::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        /** @var string $words */
        $words = fake()->unique()->words(2, true);

        return [
            'name' => Str::title($words),
            'slug' => Str::slug($words),
            'diverse_scoring' => DiverseScoring::Separate,
        ];
    }

    public function scoredAsChosen(): static
    {
        return $this->state(['diverse_scoring' => DiverseScoring::AsChosen]);
    }
}
