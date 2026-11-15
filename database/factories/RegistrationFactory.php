<?php

namespace Database\Factories;

use App\Domain\Competition\Enums\RegistrationStatus;
use App\Domain\Competition\Models\Race;
use App\Domain\Competition\Models\Registration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Registration>
 */
class RegistrationFactory extends Factory
{
    protected $model = Registration::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'race_id' => Race::factory(),
            'amount_cents' => 4500,
            'status' => RegistrationStatus::Confirmed,
            'confirmed_at' => now(),
            'contact_first_name' => fake()->firstName(),
            'contact_last_name' => fake()->lastName(),
            'contact_email' => fake()->unique()->safeEmail(),
            'token_hash' => hash('sha256', fake()->unique()->uuid()),
            'entered_by_orga' => false,
        ];
    }

    public function reserved(?string $until = null): static
    {
        return $this->state([
            'status' => RegistrationStatus::Reserved,
            'confirmed_at' => null,
            'reserved_until' => $until ?? now()->addDay(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state([
            'status' => RegistrationStatus::Cancelled,
            'cancelled_at' => now(),
        ]);
    }
}
