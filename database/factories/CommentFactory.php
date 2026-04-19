<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommentFactory extends Factory
{
    protected $model = Comment::class;

    public function definition(): array
    {
        return [
            'post_id'      => Post::factory()->published(),
            'user_id'      => null,
            'author_name'  => $this->faker->name(),
            'author_email' => $this->faker->safeEmail(),
            'body'         => $this->faker->paragraph(),
            'status'       => 'pending',
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => 'pending']);
    }

    public function approved(): static
    {
        return $this->state(['status' => 'approved']);
    }

    public function rejected(): static
    {
        return $this->state(['status' => 'rejected']);
    }

    public function forUser(User $user): static
    {
        return $this->state([
            'user_id'      => $user->id,
            'author_name'  => $user->name,
            'author_email' => null,
            'status'       => 'approved',
        ]);
    }
}
