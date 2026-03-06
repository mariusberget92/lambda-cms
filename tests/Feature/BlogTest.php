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

    // ── SEO helper ───────────────────────────────────────────────────────────

    private function seedSeoSettings(
        string $separator      = ' | ',
        string $defaultDesc    = '',
        string $defaultOgImage = '',
        string $siteName       = 'Test Site'
    ): void {
        \App\Models\Setting::insert([
            ['group' => 'site', 'key' => 'site.name',                'value' => $siteName,       'type' => 'string',  'created_at' => now(), 'updated_at' => now()],
            ['group' => 'seo',  'key' => 'seo.title_separator',      'value' => $separator,      'type' => 'string',  'created_at' => now(), 'updated_at' => now()],
            ['group' => 'seo',  'key' => 'seo.default_description',  'value' => $defaultDesc,    'type' => 'string',  'created_at' => now(), 'updated_at' => now()],
            ['group' => 'seo',  'key' => 'seo.default_og_image_url', 'value' => $defaultOgImage, 'type' => 'string',  'created_at' => now(), 'updated_at' => now()],
        ]);
        app(\App\Services\SettingService::class)->bust();
    }

    // ── SEO prop: show ────────────────────────────────────────────────────────

    public function test_blog_show_seo_title_uses_meta_title_when_set(): void
    {
        $this->seedSeoSettings();
        $post = Post::factory()->published()->create([
            'title'      => 'Post Title',
            'meta_title' => 'Custom SEO Title',
        ]);

        $this->get("/blog/{$post->slug}")
            ->assertInertia(fn ($page) => $page
                ->where('seo.title', 'Custom SEO Title | Test Site')
            );
    }

    public function test_blog_show_seo_title_falls_back_to_post_title_when_meta_title_absent(): void
    {
        $this->seedSeoSettings();
        $post = Post::factory()->published()->create([
            'title'      => 'Post Title',
            'meta_title' => null,
        ]);

        $this->get("/blog/{$post->slug}")
            ->assertInertia(fn ($page) => $page
                ->where('seo.title', 'Post Title | Test Site')
            );
    }

    public function test_blog_show_seo_description_uses_meta_description_when_set(): void
    {
        $this->seedSeoSettings();
        $post = Post::factory()->published()->create([
            'excerpt'          => 'Post excerpt',
            'meta_description' => 'Custom meta desc',
        ]);

        $this->get("/blog/{$post->slug}")
            ->assertInertia(fn ($page) => $page
                ->where('seo.description', 'Custom meta desc')
            );
    }

    public function test_blog_show_seo_description_falls_back_to_excerpt(): void
    {
        $this->seedSeoSettings();
        $post = Post::factory()->published()->create([
            'excerpt'          => 'Post excerpt',
            'meta_description' => null,
        ]);

        $this->get("/blog/{$post->slug}")
            ->assertInertia(fn ($page) => $page
                ->where('seo.description', 'Post excerpt')
            );
    }

    public function test_blog_show_seo_description_falls_back_to_global_default(): void
    {
        $this->seedSeoSettings(defaultDesc: 'Site-wide default desc');
        $post = Post::factory()->published()->create([
            'excerpt'          => null,
            'meta_description' => null,
        ]);

        $this->get("/blog/{$post->slug}")
            ->assertInertia(fn ($page) => $page
                ->where('seo.description', 'Site-wide default desc')
            );
    }

    public function test_blog_show_seo_image_falls_back_to_global_default_when_no_featured_image(): void
    {
        $this->seedSeoSettings(defaultOgImage: 'https://example.com/default.jpg');
        $post = Post::factory()->published()->create(['featured_image_id' => null]);

        $this->get("/blog/{$post->slug}")
            ->assertInertia(fn ($page) => $page
                ->where('seo.image', 'https://example.com/default.jpg')
            );
    }

    public function test_blog_show_seo_type_is_article(): void
    {
        $this->seedSeoSettings();
        $post = Post::factory()->published()->create();

        $this->get("/blog/{$post->slug}")
            ->assertInertia(fn ($page) => $page
                ->where('seo.type', 'article')
            );
    }

    public function test_blog_show_seo_canonical_is_post_url(): void
    {
        $this->seedSeoSettings();
        $post = Post::factory()->published()->create(['slug' => 'my-post']);

        $this->get('/blog/my-post')
            ->assertInertia(fn ($page) => $page
                ->where('seo.canonical', url('/blog/my-post'))
            );
    }

    // ── SEO prop: index ───────────────────────────────────────────────────────

    public function test_blog_index_seo_title_uses_site_name(): void
    {
        $this->seedSeoSettings(siteName: 'My Blog');

        $this->get('/')
            ->assertInertia(fn ($page) => $page
                ->where('seo.title', 'My Blog')
            );
    }

    public function test_blog_index_seo_type_is_website(): void
    {
        $this->seedSeoSettings();

        $this->get('/')
            ->assertInertia(fn ($page) => $page
                ->where('seo.type', 'website')
            );
    }

    public function test_blog_index_seo_uses_global_default_description(): void
    {
        $this->seedSeoSettings(defaultDesc: 'Welcome to the blog');

        $this->get('/')
            ->assertInertia(fn ($page) => $page
                ->where('seo.description', 'Welcome to the blog')
            );
    }

    public function test_blog_index_seo_canonical_is_blog_url(): void
    {
        $this->seedSeoSettings();

        $this->get('/')
            ->assertInertia(fn ($page) => $page
                ->where('seo.canonical', url('/blog'))
            );
    }

    public function test_blog_index_seo_image_uses_global_og_image(): void
    {
        $this->seedSeoSettings(defaultOgImage: 'https://example.com/og-default.jpg');

        $this->get('/')
            ->assertInertia(fn ($page) => $page
                ->where('seo.image', 'https://example.com/og-default.jpg')
            );
    }
}
