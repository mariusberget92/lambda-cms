<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostApiTest extends TestCase
{
    use RefreshDatabase;

    // ── Index ─────────────────────────────────────────────────────────────────

    public function test_api_posts_index_returns_published_posts(): void
    {
        $this->markAsInstalled();
        $this->seedRolesAndPermissions();

        $user = User::factory()->create()->assignRole('user');
        Post::factory()->create(['user_id' => $user->id, 'status' => 'published', 'published_at' => now(), 'title' => 'Hello World']);
        Post::factory()->create(['user_id' => $user->id, 'status' => 'draft']);

        $response = $this->getJson('/api/v1/posts');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.title', 'Hello World');
    }

    public function test_api_posts_index_does_not_return_drafts(): void
    {
        $this->markAsInstalled();
        $this->seedRolesAndPermissions();

        $user = User::factory()->create()->assignRole('user');
        Post::factory()->count(3)->create(['user_id' => $user->id, 'status' => 'draft']);

        $response = $this->getJson('/api/v1/posts');

        $response->assertOk();
        $response->assertJsonCount(0, 'data');
    }

    public function test_api_posts_index_filters_by_category(): void
    {
        $this->markAsInstalled();
        $this->seedRolesAndPermissions();

        $user = User::factory()->create()->assignRole('user');
        $cat  = Category::factory()->create(['slug' => 'tech']);
        $postWithCat = Post::factory()->create(['user_id' => $user->id, 'status' => 'published', 'published_at' => now()]);
        $postWithCat->categories()->sync([$cat->id]);
        Post::factory()->create(['user_id' => $user->id, 'status' => 'published', 'published_at' => now()]);

        $response = $this->getJson('/api/v1/posts?category=tech');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }

    public function test_api_posts_index_filters_by_tag(): void
    {
        $this->markAsInstalled();
        $this->seedRolesAndPermissions();

        $user = User::factory()->create()->assignRole('user');
        $tag  = Tag::factory()->create(['slug' => 'laravel']);
        $post = Post::factory()->create(['user_id' => $user->id, 'status' => 'published', 'published_at' => now()]);
        $post->tags()->attach($tag);
        Post::factory()->create(['user_id' => $user->id, 'status' => 'published', 'published_at' => now()]);

        $response = $this->getJson('/api/v1/posts?tag=laravel');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }

    public function test_api_posts_index_paginates(): void
    {
        $this->markAsInstalled();
        $this->seedRolesAndPermissions();

        $user = User::factory()->create()->assignRole('user');
        Post::factory()->count(5)->create(['user_id' => $user->id, 'status' => 'published', 'published_at' => now()]);

        $response = $this->getJson('/api/v1/posts?per_page=2');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('total', 5);
    }

    public function test_api_posts_index_response_structure(): void
    {
        $this->markAsInstalled();
        $this->seedRolesAndPermissions();

        $user = User::factory()->create()->assignRole('user');
        Post::factory()->create(['user_id' => $user->id, 'status' => 'published', 'published_at' => now()]);

        $response = $this->getJson('/api/v1/posts');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [['id', 'title', 'slug', 'excerpt', 'status', 'published_at', 'author', 'categories', 'tags', 'featured_image']],
            'current_page', 'last_page', 'per_page', 'total',
        ]);
    }

    // ── Show ──────────────────────────────────────────────────────────────────

    public function test_api_post_show_returns_published_post(): void
    {
        $this->markAsInstalled();
        $this->seedRolesAndPermissions();

        $user = User::factory()->create()->assignRole('user');
        $post = Post::factory()->create(['user_id' => $user->id, 'status' => 'published', 'published_at' => now(), 'slug' => 'my-post']);

        $response = $this->getJson('/api/v1/posts/my-post');

        $response->assertOk();
        $response->assertJsonPath('slug', 'my-post');
    }

    public function test_api_post_show_includes_body(): void
    {
        $this->markAsInstalled();
        $this->seedRolesAndPermissions();

        $user = User::factory()->create()->assignRole('user');
        Post::factory()->create(['user_id' => $user->id, 'status' => 'published', 'published_at' => now(), 'slug' => 'my-post', 'body' => '<p>Hello</p>']);

        $response = $this->getJson('/api/v1/posts/my-post');

        $response->assertOk();
        $response->assertJsonPath('body', '<p>Hello</p>');
    }

    public function test_api_post_show_returns_404_for_draft(): void
    {
        $this->markAsInstalled();
        $this->seedRolesAndPermissions();

        $user = User::factory()->create()->assignRole('user');
        Post::factory()->create(['user_id' => $user->id, 'status' => 'draft', 'slug' => 'secret-draft']);

        $this->getJson('/api/v1/posts/secret-draft')->assertNotFound();
    }

    public function test_api_post_show_returns_404_for_unknown_slug(): void
    {
        $this->markAsInstalled();

        $this->getJson('/api/v1/posts/does-not-exist')->assertNotFound();
    }
}
