<?php

namespace Database\Factories;

use App\Models\Media;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class MediaFactory extends Factory
{
    protected $model = Media::class;

    public function definition(): array
    {
        $uuid = Str::uuid();
        $ext  = 'jpg';

        return [
            'user_id'           => User::factory(),
            'filename'          => "{$uuid}.{$ext}",
            'original_filename' => $this->faker->word() . ".{$ext}",
            'disk'              => 'public',
            'path'              => "media/2026/02/{$uuid}.{$ext}",
            'mime_type'         => 'image/jpeg',
            'type'              => 'image',
            'size'              => $this->faker->numberBetween(10_000, 5_000_000),
            'width'             => $this->faker->numberBetween(400, 4000),
            'height'            => $this->faker->numberBetween(300, 3000),
            'alt'               => null,
        ];
    }
}
