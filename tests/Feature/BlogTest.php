<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\Setting;
use App\Models\Tag;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
                ->component('Blog/TemplatePage')
                ->has('postContext.posts.data', 1)
                ->where('postContext.posts.data.0.title', 'A Published Post')
        );
    }

    public function test_blog_index_does_not_show_draft_posts(): void
    {
        Post::factory()->draft()->create(['title' => 'A Draft Post']);

        $this->get('/')->assertInertia(
            fn ($page) => $page->has('postContext.posts.data', 0)
        );
    }

    public function test_blog_index_includes_sidebar_data(): void
    {
        $this->get('/')->assertInertia(
            fn ($page) => $page
                ->has('postContext')
                ->has('seo')
        );
    }

    public function test_blog_index_orders_posts_by_published_at_desc(): void
    {
        $older = Post::factory()->published()->create([
            'title' => 'Older Post',
            'published_at' => now()->subDays(5),
        ]);
        $newer = Post::factory()->published()->create([
            'title' => 'Newer Post',
            'published_at' => now()->subDay(),
        ]);

        $this->get('/')->assertInertia(
            fn ($page) => $page
                ->where('postContext.posts.data.0.title', 'Newer Post')
                ->where('postContext.posts.data.1.title', 'Older Post')
        );
    }

    // ── Blog show ─────────────────────────────────────────────────────────────

    public function test_blog_show_returns_published_post(): void
    {
        $post = Post::factory()->published()->create([
            'title' => 'My Great Post',
            'slug' => 'my-great-post',
        ]);

        $this->get('/blog/my-great-post')->assertInertia(
            fn ($page) => $page
                ->component('Blog/TemplatePage')
                ->where('postContext.title', 'My Great Post')
                ->where('postContext.slug', 'my-great-post')
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
            fn ($page) => $page->where('postContext.body', '<p>Hello World</p>')
        );
    }

    public function test_blog_show_includes_sidebar(): void
    {
        $post = Post::factory()->published()->create(['slug' => 'sidebar-post']);

        $this->get('/blog/sidebar-post')->assertInertia(
            fn ($page) => $page->has('postContext')
        );
    }

    // ── SEO helper ───────────────────────────────────────────────────────────

    private function seedSeoSettings(
        string $separator = ' | ',
        string $defaultDesc = '',
        string $defaultOgImage = '',
        string $siteName = 'Test Site'
    ): void {
        Setting::insert([
            ['group' => 'site', 'key' => 'site.name',                'value' => $siteName,       'type' => 'string',  'created_at' => now(), 'updated_at' => now()],
            ['group' => 'seo',  'key' => 'seo.title_separator',      'value' => $separator,      'type' => 'string',  'created_at' => now(), 'updated_at' => now()],
            ['group' => 'seo',  'key' => 'seo.default_description',  'value' => $defaultDesc,    'type' => 'string',  'created_at' => now(), 'updated_at' => now()],
            ['group' => 'seo',  'key' => 'seo.default_og_image_url', 'value' => $defaultOgImage, 'type' => 'string',  'created_at' => now(), 'updated_at' => now()],
        ]);
        app(SettingService::class)->bust();
    }

    // ── SEO prop: show ────────────────────────────────────────────────────────

    public function test_blog_show_seo_title_uses_meta_title_when_set(): void
    {
        $this->seedSeoSettings();
        $post = Post::factory()->published()->create([
            'title' => 'Post Title',
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
            'title' => 'Post Title',
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
            'excerpt' => 'Post excerpt',
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
            'excerpt' => 'Post excerpt',
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
            'excerpt' => null,
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
                ->where('seo.canonical', url('/'))
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

    public function test_blog_show_uses_post_meta_keywords_when_set(): void
    {
        Setting::insert([
            ['group' => 'seo',  'key' => 'seo.title_separator',      'value' => ' | ',          'type' => 'string', 'created_at' => now(), 'updated_at' => now()],
            ['group' => 'seo',  'key' => 'seo.default_keywords',     'value' => 'global, kw',   'type' => 'string', 'created_at' => now(), 'updated_at' => now()],
            ['group' => 'site', 'key' => 'site.name',                'value' => 'Lambda CMS',   'type' => 'string', 'created_at' => now(), 'updated_at' => now()],
            ['group' => 'seo',  'key' => 'seo.default_description',  'value' => '',             'type' => 'string', 'created_at' => now(), 'updated_at' => now()],
            ['group' => 'seo',  'key' => 'seo.default_og_image_url', 'value' => '',             'type' => 'string', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $post = Post::factory()->published()->create(['meta_keywords' => 'post, kw']);

        $this->get("/blog/{$post->slug}")
            ->assertInertia(fn ($page) => $page->where('seo.keywords', 'post, kw'));
    }

    public function test_blog_show_falls_back_to_default_keywords(): void
    {
        Setting::insert([
            ['group' => 'seo',  'key' => 'seo.title_separator',      'value' => ' | ',          'type' => 'string', 'created_at' => now(), 'updated_at' => now()],
            ['group' => 'seo',  'key' => 'seo.default_keywords',     'value' => 'global, kw',   'type' => 'string', 'created_at' => now(), 'updated_at' => now()],
            ['group' => 'site', 'key' => 'site.name',                'value' => 'Lambda CMS',   'type' => 'string', 'created_at' => now(), 'updated_at' => now()],
            ['group' => 'seo',  'key' => 'seo.default_description',  'value' => '',             'type' => 'string', 'created_at' => now(), 'updated_at' => now()],
            ['group' => 'seo',  'key' => 'seo.default_og_image_url', 'value' => '',             'type' => 'string', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $post = Post::factory()->published()->create(['meta_keywords' => null]);

        $this->get("/blog/{$post->slug}")
            ->assertInertia(fn ($page) => $page->where('seo.keywords', 'global, kw'));
    }

    // ── Category archive ──────────────────────────────────────────────────────────

    public function test_category_archive_is_publicly_accessible(): void
    {
        $category = Category::factory()->create();

        $this->get("/blog/category/{$category->slug}")->assertOk();
    }

    public function test_category_archive_renders_archive_component(): void
    {
        $category = Category::factory()->create();

        $this->get("/blog/category/{$category->slug}")->assertInertia(
            fn ($page) => $page->component('Blog/TemplatePage')
        );
    }

    public function test_category_archive_shows_only_posts_in_that_category(): void
    {
        $category = Category::factory()->create();
        $other = Category::factory()->create();

        $included = Post::factory()->published()->create(['title' => 'Included Post']);
        $excluded = Post::factory()->published()->create(['title' => 'Excluded Post']);

        $included->categories()->attach($category);
        $excluded->categories()->attach($other);

        $this->get("/blog/category/{$category->slug}")->assertInertia(
            fn ($page) => $page
                ->has('archiveContext.posts.data', 1)
                ->where('archiveContext.posts.data.0.title', 'Included Post')
        );
    }

    public function test_category_archive_excludes_draft_posts(): void
    {
        $category = Category::factory()->create();
        $post = Post::factory()->draft()->create();
        $post->categories()->attach($category);

        $this->get("/blog/category/{$category->slug}")->assertInertia(
            fn ($page) => $page->has('archiveContext.posts.data', 0)
        );
    }

    public function test_category_archive_returns_404_for_nonexistent_slug(): void
    {
        $this->get('/blog/category/does-not-exist')->assertNotFound();
    }

    public function test_category_archive_heading_contains_correct_data(): void
    {
        $category = Category::factory()->create(['name' => 'Laravel', 'slug' => 'laravel']);
        $post = Post::factory()->published()->create();
        $post->categories()->attach($category);

        $this->get("/blog/category/{$category->slug}")->assertInertia(
            fn ($page) => $page
                ->where('archiveContext.type', 'category')
                ->where('archiveContext.name', 'Laravel')
                ->where('archiveContext.slug', 'laravel')
                ->where('archiveContext.postsCount', 1)
        );
    }

    public function test_category_archive_has_correct_seo_canonical(): void
    {
        $category = Category::factory()->create(['slug' => 'laravel']);

        $this->get("/blog/category/{$category->slug}")->assertInertia(
            fn ($page) => $page->where('seo.canonical', url('/blog/category/laravel'))
        );
    }

    public function test_category_archive_includes_sidebar_data(): void
    {
        $category = Category::factory()->create();

        $this->get("/blog/category/{$category->slug}")->assertInertia(
            fn ($page) => $page
                ->has('archiveContext')
                ->has('seo')
        );
    }

    // ── Tag archive ───────────────────────────────────────────────────────────────

    public function test_tag_archive_is_publicly_accessible(): void
    {
        $tag = Tag::factory()->create();

        $this->get("/blog/tag/{$tag->slug}")->assertOk();
    }

    public function test_tag_archive_renders_archive_component(): void
    {
        $tag = Tag::factory()->create();

        $this->get("/blog/tag/{$tag->slug}")->assertInertia(
            fn ($page) => $page->component('Blog/TemplatePage')
        );
    }

    public function test_tag_archive_shows_only_posts_with_that_tag(): void
    {
        $tag = Tag::factory()->create();
        $other = Tag::factory()->create();

        $included = Post::factory()->published()->create(['title' => 'Tagged Post']);
        $excluded = Post::factory()->published()->create(['title' => 'Other Post']);

        $included->tags()->attach($tag);
        $excluded->tags()->attach($other);

        $this->get("/blog/tag/{$tag->slug}")->assertInertia(
            fn ($page) => $page
                ->has('archiveContext.posts.data', 1)
                ->where('archiveContext.posts.data.0.title', 'Tagged Post')
        );
    }

    public function test_tag_archive_excludes_draft_posts(): void
    {
        $tag = Tag::factory()->create();
        $post = Post::factory()->draft()->create();
        $post->tags()->attach($tag);

        $this->get("/blog/tag/{$tag->slug}")->assertInertia(
            fn ($page) => $page->has('archiveContext.posts.data', 0)
        );
    }

    public function test_tag_archive_returns_404_for_nonexistent_slug(): void
    {
        $this->get('/blog/tag/does-not-exist')->assertNotFound();
    }

    public function test_tag_archive_heading_contains_correct_data(): void
    {
        $tag = Tag::factory()->create(['name' => 'PHP', 'slug' => 'php']);
        $post = Post::factory()->published()->create();
        $post->tags()->attach($tag);

        $this->get("/blog/tag/{$tag->slug}")->assertInertia(
            fn ($page) => $page
                ->where('archiveContext.type', 'tag')
                ->where('archiveContext.name', 'PHP')
                ->where('archiveContext.slug', 'php')
                ->where('archiveContext.postsCount', 1)
        );
    }

    public function test_tag_archive_has_correct_seo_canonical(): void
    {
        $tag = Tag::factory()->create(['slug' => 'php']);

        $this->get("/blog/tag/{$tag->slug}")->assertInertia(
            fn ($page) => $page->where('seo.canonical', url('/blog/tag/php'))
        );
    }

    // ── Null-safe author ──────────────────────────────────────────────────────

    public function test_single_post_page_renders_when_author_is_deleted(): void
    {
        $post = Post::factory()->published()->create();
        // Simulate deleted author by nullifying user_id directly
        DB::table('posts')
            ->where('id', $post->id)
            ->update(['user_id' => null]);

        $this->get("/blog/{$post->slug}")->assertOk();
    }
}
