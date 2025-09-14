<?php

namespace Database\Factories;

use App\Enums\AccountStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'username'            => $this->faker->unique()->userName(),  // <-- requerido
            'name'                => $this->faker->name(),
            'email'               => $this->faker->unique()->safeEmail(), // si lo quieres opcional, puedes poner null en algunos estados
            'email_verified_at'   => now(),
            'password'            => Hash::make('password123'),
            'remember_token'      => Str::random(10),

            // campos nuevos
            'profile_photo'       => null,
            'student_code'        => null,
            'status'              => AccountStatus::ACTIVE->value, // o 'active'
            'recovery_code'       => null,
            'recovery_expires_at' => null,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
