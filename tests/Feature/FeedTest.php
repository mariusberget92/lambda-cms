<?php

namespace Tests\Feature;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->markAsInstalled();
    }

    public function test_feed_is_publicly_accessible(): void
    {
        $this->get('/feed')->assertOk();
    }

    public function test_feed_returns_rss_content_type(): void
    {
        $this->get('/feed')->assertHeader('Content-Type', 'application/rss+xml; charset=utf-8');
    }

    public function test_feed_contains_rss_structure(): void
    {
        $content = $this->get('/feed')->getContent();

        $this->assertStringContainsString('<rss', $content);
        $this->assertStringContainsString('<channel>', $content);
        $this->assertStringContainsString('</channel>', $content);
    }

    public function test_feed_includes_published_post_title_and_link(): void
    {
        Post::factory()->published()->create([
            'title' => 'Hello World',
            'slug' => 'hello-world',
        ]);

        $content = $this->get('/feed')->getContent();

        $this->assertStringContainsString('Hello World', $content);
        $this->assertStringContainsString('/blog/hello-world', $content);
    }

    public function test_feed_excludes_draft_posts(): void
    {
        Post::factory()->draft()->create(['title' => 'Secret Draft']);

        $content = $this->get('/feed')->getContent();

        $this->assertStringNotContainsString('Secret Draft', $content);
    }

    public function test_feed_is_limited_to_20_posts(): void
    {
        Post::factory()->published()->count(25)->create();

        $content = $this->get('/feed')->getContent();

        $this->assertSame(20, substr_count($content, '<item>'));
    }
}
