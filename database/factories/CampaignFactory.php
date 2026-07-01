<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CampaignFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->sentence(3),
            'subject' => fake()->sentence(5),
            'body' => '<p>'.fake()->paragraph().'</p>',
            'status' => 'draft',
            'sent_at' => null,
        ];
    }

    public function sent(): static
    {
        return $this->state([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    public function sending(): static
    {
        return $this->state([
            'status' => 'sending',
        ]);
    }
}
