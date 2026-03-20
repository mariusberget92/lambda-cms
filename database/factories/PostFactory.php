<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PostFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->sentence(rand(4, 8), true);
        $title = rtrim($title, '.');

        return [
            'user_id'          => User::factory(),
            'title'            => $title,
            'slug'             => Str::slug($title),
            'excerpt'          => fake()->paragraph(2),
            'body'             => $this->generateBody(),
            'status'           => 'draft',
            'featured'         => false,
            'published_at'     => null,
            'comments_enabled' => true,
            'meta_title'       => null,
            'meta_description' => null,
            'use_block_editor' => false,
            'blocks'           => null,
        ];
    }

    public function published(): static
    {
        return $this->state([
            'status'       => 'published',
            'published_at' => fake()->dateTimeThisYear(),
        ]);
    }

    public function draft(): static
    {
        return $this->state([
            'status'       => 'draft',
            'published_at' => null,
        ]);
    }

    private function generateBody(): string
    {
        $paragraphs = [];
        for ($i = 0; $i < rand(3, 6); $i++) {
            $paragraphs[] = '<p>' . fake()->paragraph(rand(3, 6)) . '</p>';
        }
        return implode("\n", $paragraphs);
    }
}
