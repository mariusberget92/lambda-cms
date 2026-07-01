<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DealFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->sentence(3),
            'value' => fake()->randomFloat(2, 100, 50000),
            'stage' => 'lead',
            'expected_close_date' => fake()->dateTimeBetween('+1 week', '+3 months'),
        ];
    }

    public function won(): static
    {
        return $this->state([
            'stage' => 'won',
            'closed_at' => now(),
        ]);
    }

    public function lost(): static
    {
        return $this->state([
            'stage' => 'lost',
            'closed_at' => now(),
        ]);
    }
}
