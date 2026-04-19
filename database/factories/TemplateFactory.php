<?php

namespace Database\Factories;

use App\Models\Template;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TemplateFactory extends Factory
{
    protected $model = Template::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title'   => $this->faker->sentence(3),
            'type'    => $this->faker->randomElement(['blog-index', 'single-post', 'archive', 'search-results']),
            'status'  => 'draft',
            'blocks'  => [],
        ];
    }

    public function published(): static
    {
        return $this->state(['status' => 'published']);
    }
}
