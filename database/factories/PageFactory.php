<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PageFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->sentence(rand(3, 6), true);
        $title = rtrim($title, '.');

        return [
            'user_id' => User::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'status' => 'draft',
            'blocks' => null,
            'meta_title' => null,
            'meta_description' => null,
            'meta_keywords' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(['status' => 'published']);
    }

    public function draft(): static
    {
        return $this->state(['status' => 'draft']);
    }

    public function withBlocks(array $blocks = []): static
    {
        if (empty($blocks)) {
            $blocks = [
                [
                    'id' => (string) Str::uuid(),
                    'type' => 'heading',
                    'data' => ['level' => 2, 'text' => 'Hello World'],
                ],
                [
                    'id' => (string) Str::uuid(),
                    'type' => 'paragraph',
                    'data' => ['content' => '<p>Sample content</p>'],
                ],
            ];
        }

        return $this->state(['blocks' => $blocks]);
    }
}
