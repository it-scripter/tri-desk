<?php

namespace Database\Factories;

use App\Domain\Competition\Enums\Gender;
use App\Domain\Competition\Models\Registration;
use App\Domain\Competition\Models\RegistrationParticipant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RegistrationParticipant>
 */
class RegistrationParticipantFactory extends Factory
{
    protected $model = RegistrationParticipant::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'registration_id' => Registration::factory(),
            'position' => 0,
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'birth_date' => fake()->dateTimeBetween('-60 years', '-20 years')->format('Y-m-d'),
            'gender' => fake()->randomElement([Gender::Male, Gender::Female]),
        ];
    }
}
