<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class EmailTemplateFactory extends Factory
{
    public function definition(): array
    {
        $subject = 'Hello {{user_name}}';
        $body = '<p>Welcome, {{user_name}}!</p>';

        return [
            'key' => fake()->unique()->slug(2),
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'subject' => $subject,
            'body' => $body,
            'default_subject' => $subject,
            'default_body' => $body,
            'merge_tags' => [
                ['tag' => '{{user_name}}', 'description' => 'The user name'],
            ],
        ];
    }
}
