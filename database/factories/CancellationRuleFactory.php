<?php

namespace Database\Factories;

use App\Domain\Competition\Models\CancellationRule;
use App\Domain\Competition\Models\Edition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CancellationRule>
 */
class CancellationRuleFactory extends Factory
{
    protected $model = CancellationRule::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'edition_id' => Edition::factory(),
            'valid_until' => now()->addMonth(),
            'refund_percent' => 100,
            'fee_cents' => 0,
        ];
    }
}
