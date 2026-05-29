<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaTest extends TestCase
{
    use RefreshDatabase;

    // ── Index ─────────────────────────────────────────────────────────────────

    public function test_guest_is_redirected_from_media_index(): void
    {
        $this->markAsInstalled();
        $response = $this->get(route('media.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_media_index(): void
    {
        $this->markAsInstalled();
        $this->seedRolesAndPermissions();
        $user = User::factory()->create()->assignRole('user');

        $response = $this->actingAs($user)->get(route('media.index'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Media/Index'));
    }

    // ── Upload ────────────────────────────────────────────────────────────────

    public function test_authenticated_user_can_upload_an_image(): void
    {
        $this->markAsInstalled();
        $this->seedRolesAndPermissions();
        Storage::fake('public');

        $user = User::factory()->create()->assignRole('user');
        $file = UploadedFile::fake()->create('photo.jpg', 100, 'image/jpeg');

        $response = $this->actingAs($user)->post(route('media.store'), [
            'file' => $file,
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['id', 'url', 'filename', 'type', 'size', 'formatted_size']);

        $this->assertDatabaseHas('media', [
            'user_id' => $user->id,
            'mime_type' => 'image/jpeg',
            'type' => 'image',
        ]);
    }

    public function test_upload_rejects_files_over_max_size(): void
    {
        $this->markAsInstalled();
        $this->seedRolesAndPermissions();
        Storage::fake('public');

        config(['media.max_upload_mb' => 0.001]); // ~1KB

        $user = User::factory()->create()->assignRole('user');
        $file = UploadedFile::fake()->create('big.jpg', 500, 'image/jpeg'); // 500KB

        $response = $this->actingAs($user)->post(route('media.store'), [
            'file' => $file,
        ]);

        $response->assertStatus(422);
    }

    public function test_upload_rejects_disallowed_mime_types(): void
    {
        $this->markAsInstalled();
        $this->seedRolesAndPermissions();
        Storage::fake('public');

        $user = User::factory()->create()->assignRole('user');
        $file = UploadedFile::fake()->create('script.php', 10, 'application/x-php');

        $response = $this->actingAs($user)->post(route('media.store'), [
            'file' => $file,
        ]);

        $response->assertStatus(422);
    }

    // ── Update (alt text + description) ──────────────────────────────────────

    public function test_owner_can_update_alt_text(): void
    {
        $this->markAsInstalled();
        $this->seedRolesAndPermissions();
        Storage::fake('public');

        $user = User::factory()->create()->assignRole('user');
        $media = Media::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->patch(route('media.update', $media), [
            'alt' => 'A beautiful sunset',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('media', ['id' => $media->id, 'alt' => 'A beautiful sunset']);
    }

    public function test_owner_can_update_description(): void
    {
        $this->markAsInstalled();
        $this->seedRolesAndPermissions();
        Storage::fake('public');

        $user = User::factory()->create()->assignRole('user');
        $media = Media::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->patch(route('media.update', $media), [
            'description' => 'A photo taken during the evening in Oslo.',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('media', ['id' => $media->id, 'description' => 'A photo taken during the evening in Oslo.']);
    }

    public function test_non_owner_cannot_update_alt_text(): void
    {
        $this->markAsInstalled();
        $this->seedRolesAndPermissions();
        Storage::fake('public');

        $owner = User::factory()->create()->assignRole('user');
        $other = User::factory()->create()->assignRole('user');
        $media = Media::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($other)->patch(route('media.update', $media), [
            'alt' => 'Hacked',
        ]);

        $response->assertForbidden();
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function test_owner_can_delete_their_media(): void
    {
        $this->markAsInstalled();
        $this->seedRolesAndPermissions();
        Storage::fake('public');

        $user = User::factory()->create()->assignRole('user');
        $media = Media::factory()->create(['user_id' => $user->id, 'disk' => 'public', 'path' => 'media/2026/02/test.jpg']);
        Storage::disk('public')->put($media->path, 'fake-content');

        $response = $this->actingAs($user)->delete(route('media.destroy', $media));

        $response->assertOk();
        $this->assertDatabaseMissing('media', ['id' => $media->id]);
        Storage::disk('public')->assertMissing($media->path);
    }

    public function test_admin_can_delete_any_media(): void
    {
        $this->markAsInstalled();
        $this->seedRolesAndPermissions();
        Storage::fake('public');

        $owner = User::factory()->create()->assignRole('user');
        $admin = User::factory()->create()->assignRole('administrator');
        $media = Media::factory()->create(['user_id' => $owner->id, 'disk' => 'public', 'path' => 'media/2026/02/test.jpg']);
        Storage::disk('public')->put($media->path, 'fake-content');

        $response = $this->actingAs($admin)->delete(route('media.destroy', $media));

        $response->assertOk();
        $this->assertDatabaseMissing('media', ['id' => $media->id]);
    }

    public function test_non_owner_regular_user_cannot_delete_others_media(): void
    {
        $this->markAsInstalled();
        $this->seedRolesAndPermissions();
        Storage::fake('public');

        $owner = User::factory()->create()->assignRole('user');
        $other = User::factory()->create()->assignRole('user');
        $media = Media::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($other)->delete(route('media.destroy', $media));

        $response->assertForbidden();
    }

    // ── Usage ─────────────────────────────────────────────────────────────────

    public function test_usage_returns_posts_using_media(): void
    {
        $this->markAsInstalled();
        $this->seedRolesAndPermissions();

        $admin = User::factory()->create()->assignRole('administrator');

        $media = Media::factory()->create(['user_id' => $admin->id]);
        $post = Post::factory()->create(['featured_image_id' => $media->id, 'user_id' => $admin->id]);

        $this->actingAs($admin)
            ->getJson(route('media.usage', $media))
            ->assertOk()
            ->assertJsonFragment(['id' => $post->id, 'title' => $post->title]);
    }

    public function test_usage_returns_empty_when_not_used(): void
    {
        $this->markAsInstalled();
        $this->seedRolesAndPermissions();

        $admin = User::factory()->create()->assignRole('administrator');
        $media = Media::factory()->create(['user_id' => $admin->id]);

        $this->actingAs($admin)
            ->getJson(route('media.usage', $media))
            ->assertOk()
            ->assertJson(['posts' => []]);
    }

    public function test_usage_is_forbidden_for_non_owner(): void
    {
        $this->markAsInstalled();
        $this->seedRolesAndPermissions();

        $owner = User::factory()->create()->assignRole('administrator');
        $other = User::factory()->create()->assignRole('user');

        $media = Media::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($other)
            ->getJson(route('media.usage', $media))
            ->assertForbidden();
    }

    // ── Bulk Destroy ──────────────────────────────────────────────────────────

    public function test_user_can_bulk_delete_own_media(): void
    {
        $this->markAsInstalled();
        $this->seedRolesAndPermissions();
        Storage::fake('public');

        $user = User::factory()->create()->assignRole('user');
        $media1 = Media::factory()->create(['user_id' => $user->id, 'disk' => 'public', 'path' => 'media/2026/02/a.jpg']);
        $media2 = Media::factory()->create(['user_id' => $user->id, 'disk' => 'public', 'path' => 'media/2026/02/b.jpg']);
        Storage::disk('public')->put($media1->path, 'x');
        Storage::disk('public')->put($media2->path, 'x');

        $response = $this->actingAs($user)->delete(route('media.bulk-destroy'), [
            'ids' => [$media1->id, $media2->id],
        ]);

        $response->assertOk();
        $this->assertDatabaseMissing('media', ['id' => $media1->id]);
        $this->assertDatabaseMissing('media', ['id' => $media2->id]);
    }

    public function test_bulk_delete_silently_skips_others_media_for_regular_user(): void
    {
        $this->markAsInstalled();
        $this->seedRolesAndPermissions();
        Storage::fake('public');

        $user = User::factory()->create()->assignRole('user');
        $owner = User::factory()->create()->assignRole('user');
        $media = Media::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($user)->delete(route('media.bulk-destroy'), [
            'ids' => [$media->id],
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('media', ['id' => $media->id]);
    }
}
