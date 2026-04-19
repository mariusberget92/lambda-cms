<?php

namespace Tests\Unit;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostSlugTest extends TestCase
{
    use RefreshDatabase;

    public function test_generates_slug_from_title(): void
    {
        $slug = Post::generateSlug('Hello World');
        $this->assertSame('hello-world', $slug);
    }

    public function test_appends_counter_on_collision(): void
    {
        Post::factory()->create(['title' => 'Hello World', 'slug' => 'hello-world']);

        $slug = Post::generateSlug('Hello World');
        $this->assertSame('hello-world-1', $slug);
    }

    public function test_increments_counter_further_on_multiple_collisions(): void
    {
        Post::factory()->create(['slug' => 'hello-world']);
        Post::factory()->create(['slug' => 'hello-world-1']);

        $slug = Post::generateSlug('Hello World');
        $this->assertSame('hello-world-2', $slug);
    }

    public function test_excludes_own_id_when_updating(): void
    {
        $post = Post::factory()->create(['title' => 'Hello World', 'slug' => 'hello-world']);

        // Should return the same slug because we exclude its own ID
        $slug = Post::generateSlug('Hello World', $post->id);
        $this->assertSame('hello-world', $slug);
    }

    public function test_is_published_returns_true_for_published_post(): void
    {
        $post = Post::factory()->published()->make();
        $this->assertTrue($post->isPublished());
    }

    public function test_is_published_returns_false_for_draft(): void
    {
        $post = Post::factory()->draft()->make();
        $this->assertFalse($post->isPublished());
    }
}
