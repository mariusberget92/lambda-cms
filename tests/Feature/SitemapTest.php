<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->markAsInstalled();
    }

    public function test_sitemap_is_publicly_accessible(): void
    {
        $this->get('/sitemap.xml')->assertOk();
    }

    public function test_sitemap_returns_xml_content_type(): void
    {
        $this->get('/sitemap.xml')->assertHeader('Content-Type', 'application/xml; charset=utf-8');
    }

    public function test_sitemap_contains_urlset(): void
    {
        $content = $this->get('/sitemap.xml')->getContent();

        $this->assertStringContainsString('<urlset', $content);
        $this->assertStringContainsString('</urlset>', $content);
    }

    public function test_sitemap_includes_published_post_urls(): void
    {
        Post::factory()->published()->create(['slug' => 'hello-world']);

        $content = $this->get('/sitemap.xml')->getContent();

        $this->assertStringContainsString('/blog/hello-world', $content);
    }

    public function test_sitemap_excludes_draft_post_urls(): void
    {
        Post::factory()->draft()->create(['slug' => 'draft-post']);

        $content = $this->get('/sitemap.xml')->getContent();

        $this->assertStringNotContainsString('/blog/draft-post', $content);
    }

    public function test_sitemap_includes_category_with_published_posts(): void
    {
        $category = Category::factory()->create(['slug' => 'my-category']);
        $post     = Post::factory()->published()->create();
        $post->categories()->attach($category);

        $content = $this->get('/sitemap.xml')->getContent();

        $this->assertStringContainsString('/blog/category/my-category', $content);
    }

    public function test_sitemap_excludes_empty_categories(): void
    {
        Category::factory()->create(['slug' => 'empty-category']);

        $content = $this->get('/sitemap.xml')->getContent();

        $this->assertStringNotContainsString('/blog/category/empty-category', $content);
    }

    public function test_sitemap_includes_tag_with_published_posts(): void
    {
        $tag  = Tag::factory()->create(['slug' => 'my-tag']);
        $post = Post::factory()->published()->create();
        $post->tags()->attach($tag);

        $content = $this->get('/sitemap.xml')->getContent();

        $this->assertStringContainsString('/blog/tag/my-tag', $content);
    }

    public function test_sitemap_excludes_empty_tags(): void
    {
        Tag::factory()->create(['slug' => 'empty-tag']);

        $content = $this->get('/sitemap.xml')->getContent();

        $this->assertStringNotContainsString('/blog/tag/empty-tag', $content);
    }

    public function test_sitemap_includes_published_pages(): void
    {
        Page::factory()->published()->create(['slug' => 'about']);

        $content = $this->get('/sitemap.xml')->getContent();

        $this->assertStringContainsString('<loc>' . url('/about') . '</loc>', $content);
    }

    public function test_sitemap_excludes_draft_pages(): void
    {
        Page::factory()->create(['status' => 'draft', 'slug' => 'hidden-page']);

        $content = $this->get('/sitemap.xml')->getContent();

        $this->assertStringNotContainsString('/hidden-page', $content);
    }
}
