<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'created_at' => $this->faker->dateTimeBetween('-2 years', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-2 years', 'now'),
            'email_verified_at' => $this->faker->optional(0.7)->dateTimeBetween('-1 year', 'now'),
            'verification_code' => random_int(0, 999999),
            'verification_code_expires_at' => $this->faker->dateTimeBetween('-10 minutes', '+10 minutes'),
        ];
    }
}
