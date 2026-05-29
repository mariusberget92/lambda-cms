<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class QueryApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->markAsInstalled();
        $this->seedRolesAndPermissions();
    }

    private function makeUser(): User
    {
        return User::factory()->create()->assignRole('user');
    }

    private function query(array $payload): TestResponse
    {
        return $this->postJson('/api/v1/query', $payload);
    }

    // ── Validation ────────────────────────────────────────────────────────────

    public function test_source_is_required(): void
    {
        $this->query([])->assertUnprocessable()->assertJsonValidationErrors('source');
    }

    public function test_invalid_source_is_rejected(): void
    {
        $this->query(['source' => 'invalid'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('source');
    }

    public function test_valid_sources_are_accepted(): void
    {
        foreach (['posts', 'categories', 'tags', 'pages'] as $source) {
            $this->query(['source' => $source])->assertOk();
        }
    }

    public function test_limit_must_be_between_1_and_100(): void
    {
        $this->query(['source' => 'posts', 'limit' => 0])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('limit');

        $this->query(['source' => 'posts', 'limit' => 101])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('limit');
    }

    public function test_offset_must_be_non_negative(): void
    {
        $this->query(['source' => 'posts', 'offset' => -1])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('offset');
    }

    public function test_sort_direction_must_be_asc_or_desc(): void
    {
        $this->query(['source' => 'posts', 'sort' => ['field' => 'title', 'direction' => 'random']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('sort.direction');
    }

    // ── Response shape ────────────────────────────────────────────────────────

    public function test_response_always_contains_items_and_total_keys(): void
    {
        $response = $this->query(['source' => 'posts'])->assertOk();

        $this->assertArrayHasKey('items', $response->json());
        $this->assertArrayHasKey('total', $response->json());
    }

    // ── Posts source ──────────────────────────────────────────────────────────

    public function test_posts_source_returns_published_posts(): void
    {
        $user = $this->makeUser();
        Post::factory()->published()->create(['user_id' => $user->id, 'title' => 'Published Post']);
        Post::factory()->draft()->create(['user_id' => $user->id, 'title' => 'Draft Post']);

        $response = $this->query(['source' => 'posts']);

        $response->assertOk();
        $this->assertCount(1, $response->json('items'));
        $this->assertEquals('Published Post', $response->json('items.0.title'));
        $this->assertEquals(1, $response->json('total'));
    }

    public function test_posts_source_excludes_drafts(): void
    {
        $user = $this->makeUser();
        Post::factory()->draft()->count(3)->create(['user_id' => $user->id]);

        $response = $this->query(['source' => 'posts']);

        $response->assertOk();
        $this->assertCount(0, $response->json('items'));
        $this->assertEquals(0, $response->json('total'));
    }

    public function test_posts_source_respects_limit(): void
    {
        $user = $this->makeUser();
        Post::factory()->published()->count(5)->create(['user_id' => $user->id]);

        $response = $this->query(['source' => 'posts', 'limit' => 2]);

        $response->assertOk();
        $this->assertCount(2, $response->json('items'));
        $this->assertEquals(5, $response->json('total')); // total is always the full count
    }

    public function test_posts_source_respects_offset(): void
    {
        $user = $this->makeUser();
        Post::factory()->published()->count(4)->create(['user_id' => $user->id]);

        $all = $this->query(['source' => 'posts'])->json('items');
        $offset = $this->query(['source' => 'posts', 'offset' => 2])->json('items');

        $this->assertCount(4, $all);
        $this->assertCount(2, $offset);
        $this->assertEquals($all[2]['id'], $offset[0]['id']);
    }

    public function test_posts_source_sort_ascending_by_title(): void
    {
        $user = $this->makeUser();
        Post::factory()->published()->create(['user_id' => $user->id, 'title' => 'Zebra']);
        Post::factory()->published()->create(['user_id' => $user->id, 'title' => 'Apple']);

        $response = $this->query([
            'source' => 'posts',
            'sort' => ['field' => 'title', 'direction' => 'asc'],
        ]);

        $items = $response->json('items');
        $this->assertEquals('Apple', $items[0]['title']);
        $this->assertEquals('Zebra', $items[1]['title']);
    }

    public function test_posts_source_sort_descending_by_title(): void
    {
        $user = $this->makeUser();
        Post::factory()->published()->create(['user_id' => $user->id, 'title' => 'Apple']);
        Post::factory()->published()->create(['user_id' => $user->id, 'title' => 'Zebra']);

        $response = $this->query([
            'source' => 'posts',
            'sort' => ['field' => 'title', 'direction' => 'desc'],
        ]);

        $items = $response->json('items');
        $this->assertEquals('Zebra', $items[0]['title']);
        $this->assertEquals('Apple', $items[1]['title']);
    }

    public function test_posts_response_contains_expected_fields(): void
    {
        $user = $this->makeUser();
        Post::factory()->published()->create(['user_id' => $user->id]);

        $item = $this->query(['source' => 'posts', 'limit' => 1])->json('items.0');

        $this->assertArrayHasKey('id', $item);
        $this->assertArrayHasKey('title', $item);
        $this->assertArrayHasKey('slug', $item);
        $this->assertArrayHasKey('url', $item);
    }

    public function test_posts_filter_by_featured(): void
    {
        $user = $this->makeUser();
        Post::factory()->published()->create(['user_id' => $user->id, 'featured' => true,  'title' => 'Featured']);
        Post::factory()->published()->create(['user_id' => $user->id, 'featured' => false, 'title' => 'Normal']);

        $response = $this->query([
            'source' => 'posts',
            'filters' => [['field' => 'featured', 'op' => '=', 'value' => true]],
        ]);

        $this->assertCount(1, $response->json('items'));
        $this->assertEquals('Featured', $response->json('items.0.title'));
    }

    // ── Categories source ─────────────────────────────────────────────────────

    public function test_categories_source_returns_all_categories(): void
    {
        Category::factory()->count(3)->create();

        $response = $this->query(['source' => 'categories']);

        $response->assertOk();
        $this->assertCount(3, $response->json('items'));
        $this->assertEquals(3, $response->json('total'));
    }

    public function test_categories_source_respects_limit(): void
    {
        Category::factory()->count(5)->create();

        $response = $this->query(['source' => 'categories', 'limit' => 2]);

        $response->assertOk();
        $this->assertCount(2, $response->json('items'));
        $this->assertEquals(5, $response->json('total'));
    }

    public function test_categories_response_contains_expected_fields(): void
    {
        Category::factory()->create(['name' => 'Tech', 'slug' => 'tech']);

        $item = $this->query(['source' => 'categories', 'limit' => 1])->json('items.0');

        $this->assertArrayHasKey('id', $item);
        $this->assertArrayHasKey('name', $item);
        $this->assertArrayHasKey('slug', $item);
        $this->assertArrayHasKey('posts_count', $item);
        $this->assertArrayHasKey('url', $item);
    }

    // ── Tags source ───────────────────────────────────────────────────────────

    public function test_tags_source_returns_all_tags(): void
    {
        Tag::factory()->count(4)->create();

        $response = $this->query(['source' => 'tags']);

        $response->assertOk();
        $this->assertCount(4, $response->json('items'));
        $this->assertEquals(4, $response->json('total'));
    }

    public function test_tags_source_respects_limit(): void
    {
        Tag::factory()->count(6)->create();

        $response = $this->query(['source' => 'tags', 'limit' => 3]);

        $response->assertOk();
        $this->assertCount(3, $response->json('items'));
    }

    public function test_tags_response_contains_expected_fields(): void
    {
        Tag::factory()->create(['name' => 'Laravel', 'slug' => 'laravel']);

        $item = $this->query(['source' => 'tags', 'limit' => 1])->json('items.0');

        $this->assertArrayHasKey('id', $item);
        $this->assertArrayHasKey('name', $item);
        $this->assertArrayHasKey('slug', $item);
        $this->assertArrayHasKey('posts_count', $item);
        $this->assertArrayHasKey('url', $item);
    }

    // ── Pages source ──────────────────────────────────────────────────────────

    public function test_pages_source_returns_published_pages(): void
    {
        Page::factory()->published()->create(['title' => 'About Us']);
        Page::factory()->draft()->create(['title' => 'Hidden Draft']);

        $response = $this->query(['source' => 'pages']);

        $response->assertOk();
        $items = $response->json('items');
        $this->assertCount(1, $items);
        $this->assertEquals('About Us', $items[0]['title']);
    }

    public function test_pages_source_excludes_drafts(): void
    {
        Page::factory()->draft()->count(3)->create();

        $response = $this->query(['source' => 'pages']);

        $response->assertOk();
        $this->assertCount(0, $response->json('items'));
        $this->assertEquals(0, $response->json('total'));
    }

    public function test_pages_source_respects_limit(): void
    {
        Page::factory()->published()->count(5)->create();

        $response = $this->query(['source' => 'pages', 'limit' => 2]);

        $response->assertOk();
        $this->assertCount(2, $response->json('items'));
        $this->assertEquals(5, $response->json('total'));
    }

    public function test_pages_response_contains_expected_fields(): void
    {
        Page::factory()->published()->create();

        $item = $this->query(['source' => 'pages', 'limit' => 1])->json('items.0');

        $this->assertArrayHasKey('id', $item);
        $this->assertArrayHasKey('title', $item);
        $this->assertArrayHasKey('slug', $item);
        $this->assertArrayHasKey('url', $item);
    }

    // ── Empty results ─────────────────────────────────────────────────────────

    public function test_empty_database_returns_empty_items_for_posts(): void
    {
        $this->query(['source' => 'posts'])
            ->assertOk()
            ->assertJson(['items' => [], 'total' => 0]);
    }

    public function test_empty_database_returns_empty_items_for_categories(): void
    {
        $this->query(['source' => 'categories'])
            ->assertOk()
            ->assertJson(['items' => [], 'total' => 0]);
    }
}
