<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_categories_index_returns_all_categories(): void
    {
        $this->markAsInstalled();
        Category::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/categories');

        $response->assertOk();
        $response->assertJsonCount(3, 'data');
    }

    public function test_api_categories_index_response_structure(): void
    {
        $this->markAsInstalled();
        Category::factory()->create();

        $response = $this->getJson('/api/v1/categories');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [['id', 'name', 'slug', 'description', 'posts_count']],
        ]);
    }

    public function test_api_categories_index_counts_published_posts_only(): void
    {
        $this->markAsInstalled();
        $this->seedRolesAndPermissions();

        $user     = User::factory()->create()->assignRole('user');
        $category = Category::factory()->create();
        $published = Post::factory()->create(['user_id' => $user->id, 'status' => 'published', 'published_at' => now()]);
        $published->categories()->sync([$category->id]);
        $draft = Post::factory()->create(['user_id' => $user->id, 'status' => 'draft']);
        $draft->categories()->sync([$category->id]);

        $response = $this->getJson('/api/v1/categories');

        $response->assertOk();
        $response->assertJsonPath('data.0.posts_count', 1);
    }

    public function test_api_category_show_returns_category(): void
    {
        $this->markAsInstalled();
        Category::factory()->create(['slug' => 'technology', 'name' => 'Technology']);

        $response = $this->getJson('/api/v1/categories/technology');

        $response->assertOk();
        $response->assertJsonPath('name', 'Technology');
        $response->assertJsonPath('slug', 'technology');
    }

    public function test_api_category_show_returns_404_for_unknown_slug(): void
    {
        $this->markAsInstalled();

        $this->getJson('/api/v1/categories/does-not-exist')->assertNotFound();
    }
}
