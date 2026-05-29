<?php

namespace Tests\Feature\Api\V1;

use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_tags_index_returns_all_tags(): void
    {
        $this->markAsInstalled();
        Tag::factory()->count(4)->create();

        $response = $this->getJson('/api/v1/tags');

        $response->assertOk();
        $response->assertJsonCount(4, 'data');
    }

    public function test_api_tags_index_response_structure(): void
    {
        $this->markAsInstalled();
        Tag::factory()->create();

        $response = $this->getJson('/api/v1/tags');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [['id', 'name', 'slug', 'posts_count']],
        ]);
    }

    public function test_api_tags_index_counts_published_posts_only(): void
    {
        $this->markAsInstalled();
        $this->seedRolesAndPermissions();

        $user = User::factory()->create()->assignRole('user');
        $tag = Tag::factory()->create();
        $published = Post::factory()->create(['user_id' => $user->id, 'status' => 'published', 'published_at' => now()]);
        $draft = Post::factory()->create(['user_id' => $user->id, 'status' => 'draft']);
        $published->tags()->attach($tag);
        $draft->tags()->attach($tag);

        $response = $this->getJson('/api/v1/tags');

        $response->assertOk();
        $response->assertJsonPath('data.0.posts_count', 1);
    }

    public function test_api_tag_show_returns_tag(): void
    {
        $this->markAsInstalled();
        Tag::factory()->create(['slug' => 'laravel', 'name' => 'Laravel']);

        $response = $this->getJson('/api/v1/tags/laravel');

        $response->assertOk();
        $response->assertJsonPath('name', 'Laravel');
        $response->assertJsonPath('slug', 'laravel');
    }

    public function test_api_tag_show_returns_404_for_unknown_slug(): void
    {
        $this->markAsInstalled();

        $this->getJson('/api/v1/tags/does-not-exist')->assertNotFound();
    }
}
