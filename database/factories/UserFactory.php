<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'google_id' => (string) fake()->unique()->numerify('####################'),
            'email' => fake()->unique()->safeEmail(),
            'name' => fake()->name(),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (): array => ['role' => 'admin']);
    }
}
