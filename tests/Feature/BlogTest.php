<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->markAsInstalled();
    }

    // ── Blog index ────────────────────────────────────────────────────────────

    public function test_blog_index_is_publicly_accessible(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_blog_index_shows_published_posts(): void
    {
        $post = Post::factory()->published()->create(['title' => 'A Published Post']);

        $response = $this->get('/');
        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Blog/Index')
                ->has('posts.data', 1)
                ->where('posts.data.0.title', 'A Published Post')
        );
    }

    public function test_blog_index_does_not_show_draft_posts(): void
    {
        Post::factory()->draft()->create(['title' => 'A Draft Post']);

        $this->get('/')->assertInertia(
            fn ($page) => $page->has('posts.data', 0)
        );
    }

    public function test_blog_index_includes_sidebar_data(): void
    {
        $this->get('/')->assertInertia(
            fn ($page) => $page
                ->has('sidebar')
                ->has('sidebar.categories')
                ->has('sidebar.tags')
                ->has('sidebar.recentPosts')
        );
    }

    public function test_blog_index_orders_posts_by_published_at_desc(): void
    {
        $older = Post::factory()->published()->create([
            'title'        => 'Older Post',
            'published_at' => now()->subDays(5),
        ]);
        $newer = Post::factory()->published()->create([
            'title'        => 'Newer Post',
            'published_at' => now()->subDay(),
        ]);

        $this->get('/')->assertInertia(
            fn ($page) => $page
                ->where('posts.data.0.title', 'Newer Post')
                ->where('posts.data.1.title', 'Older Post')
        );
    }

    // ── Blog show ─────────────────────────────────────────────────────────────

    public function test_blog_show_returns_published_post(): void
    {
        $post = Post::factory()->published()->create([
            'title' => 'My Great Post',
            'slug'  => 'my-great-post',
        ]);

        $this->get('/blog/my-great-post')->assertInertia(
            fn ($page) => $page
                ->component('Blog/Show')
                ->where('post.title', 'My Great Post')
                ->where('post.slug', 'my-great-post')
        );
    }

    public function test_blog_show_returns_404_for_draft_post(): void
    {
        Post::factory()->draft()->create(['slug' => 'secret-draft']);

        $this->get('/blog/secret-draft')->assertNotFound();
    }

    public function test_blog_show_returns_404_for_unknown_slug(): void
    {
        $this->get('/blog/does-not-exist')->assertNotFound();
    }

    public function test_blog_show_includes_post_body(): void
    {
        $post = Post::factory()->published()->create([
            'slug' => 'body-post',
            'body' => '<p>Hello World</p>',
        ]);

        $this->get('/blog/body-post')->assertInertia(
            fn ($page) => $page->where('post.body', '<p>Hello World</p>')
        );
    }

    public function test_blog_show_includes_sidebar(): void
    {
        $post = Post::factory()->published()->create(['slug' => 'sidebar-post']);

        $this->get('/blog/sidebar-post')->assertInertia(
            fn ($page) => $page->has('sidebar')
        );
    }
}
