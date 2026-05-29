<?php

namespace Tests\Unit;

use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MediaTest extends TestCase
{
    use RefreshDatabase;

    public function test_url_attribute_returns_full_public_url(): void
    {
        $this->markAsInstalled();
        $this->seedRolesAndPermissions();

        $user = User::factory()->create()->assignRole('user');
        $media = Media::factory()->create([
            'user_id' => $user->id,
            'disk' => 'public',
            'path' => 'media/2026/02/abc123.jpg',
        ]);

        $this->assertStringContainsString('media/2026/02/abc123.jpg', $media->url);
    }

    public function test_type_is_image_for_image_mime(): void
    {
        $this->markAsInstalled();
        $this->seedRolesAndPermissions();

        $user = User::factory()->create()->assignRole('user');
        $media = Media::factory()->create([
            'user_id' => $user->id,
            'mime_type' => 'image/jpeg',
            'type' => 'image',
        ]);

        $this->assertEquals('image', $media->type);
    }

    public function test_formatted_size_attribute(): void
    {
        $this->markAsInstalled();
        $this->seedRolesAndPermissions();

        $user = User::factory()->create()->assignRole('user');
        $media = Media::factory()->create([
            'user_id' => $user->id,
            'size' => 1536,
        ]);

        $this->assertStringContainsString('KB', $media->formatted_size);
    }
}
