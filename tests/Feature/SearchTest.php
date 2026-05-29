<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->markAsInstalled();
        $this->seedRolesAndPermissions();
    }

    public function test_search_returns_ok(): void
    {
        $this->get('/search?q=hello')->assertOk();
    }

    public function test_search_finds_matching_posts(): void
    {
        Post::factory()->create(['title' => 'Hello World', 'status' => 'published', 'published_at' => now()]);
        Post::factory()->create(['title' => 'Goodbye World', 'status' => 'published', 'published_at' => now()]);

        $response = $this->get('/search?q=Hello');
        $response->assertInertia(fn ($page) => $page
            ->component('Blog/TemplatePage')
            ->has('searchContext.results.data', 1)
        );
    }

    public function test_search_uses_template_when_published(): void
    {
        $admin = User::factory()->create()->assignRole('administrator');
        Template::create([
            'user_id' => $admin->id, 'title' => 'Search Template',
            'type' => 'search-results', 'status' => 'published', 'blocks' => [],
        ]);

        $this->get('/search?q=test')
            ->assertInertia(fn ($page) => $page->component('Blog/TemplatePage'));
    }

    public function test_search_does_not_return_draft_posts(): void
    {
        Post::factory()->create(['title' => 'Draft Hello', 'status' => 'draft']);

        $response = $this->get('/search?q=Hello');
        $response->assertInertia(fn ($page) => $page->has('searchContext.results.data', 0));
    }
}
