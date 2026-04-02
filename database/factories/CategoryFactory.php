<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CategoryFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(rand(1, 3), true);
        $name = ucwords($name);

        return [
            'name'        => $name,
            'slug'        => Str::slug($name),
            'description' => fake()->optional(0.6)->sentence(),
            'color'       => null,
        ];
    }
}
