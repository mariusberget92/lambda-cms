<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PreviewTest extends TestCase
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

    // ── Post preview ──────────────────────────────────────────────────────────

    public function test_valid_post_token_renders_preview(): void
    {
        $post = Post::factory()->create([
            'user_id' => $this->makeUser()->id,
            'title' => 'My Draft Post',
            'status' => 'draft',
            'preview_token' => Str::random(64),
        ]);

        $this->get("/preview/posts/{$post->preview_token}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Blog/TemplatePage')
                ->where('postContext.title', 'My Draft Post')
            );
    }

    public function test_invalid_post_token_returns_404(): void
    {
        $this->get('/preview/posts/'.Str::random(64))
            ->assertNotFound();
    }

    public function test_draft_post_can_be_previewed(): void
    {
        $post = Post::factory()->create([
            'user_id' => $this->makeUser()->id,
            'status' => 'draft',
            'preview_token' => Str::random(64),
        ]);

        $this->get("/preview/posts/{$post->preview_token}")->assertOk();
    }

    public function test_scheduled_post_can_be_previewed(): void
    {
        $post = Post::factory()->create([
            'user_id' => $this->makeUser()->id,
            'status' => 'scheduled',
            'published_at' => now()->addDays(3),
            'preview_token' => Str::random(64),
        ]);

        $this->get("/preview/posts/{$post->preview_token}")->assertOk();
    }

    public function test_published_post_can_be_previewed(): void
    {
        $post = Post::factory()->published()->create([
            'user_id' => $this->makeUser()->id,
            'preview_token' => Str::random(64),
        ]);

        $this->get("/preview/posts/{$post->preview_token}")->assertOk();
    }

    public function test_post_preview_requires_no_authentication(): void
    {
        $post = Post::factory()->create([
            'user_id' => $this->makeUser()->id,
            'status' => 'draft',
            'preview_token' => Str::random(64),
        ]);

        // Explicitly as a guest (no actingAs)
        $this->get("/preview/posts/{$post->preview_token}")->assertOk();
    }

    public function test_post_preview_sets_is_preview_flag(): void
    {
        $post = Post::factory()->create([
            'user_id' => $this->makeUser()->id,
            'status' => 'draft',
            'preview_token' => Str::random(64),
        ]);

        $this->get("/preview/posts/{$post->preview_token}")
            ->assertInertia(fn ($page) => $page->where('isPreview', true)
            );
    }

    public function test_post_preview_has_no_comments(): void
    {
        $post = Post::factory()->create([
            'user_id' => $this->makeUser()->id,
            'status' => 'draft',
            'preview_token' => Str::random(64),
        ]);

        $this->get("/preview/posts/{$post->preview_token}")
            ->assertInertia(fn ($page) => $page->where('commentsData.enabled', false)
                ->where('commentsData.total', 0)
            );
    }

    public function test_post_preview_title_has_preview_prefix(): void
    {
        $post = Post::factory()->create([
            'user_id' => $this->makeUser()->id,
            'title' => 'Test Title',
            'status' => 'draft',
            'preview_token' => Str::random(64),
        ]);

        $this->get("/preview/posts/{$post->preview_token}")
            ->assertInertia(fn ($page) => $page->where('seo.title', fn ($value) => str_contains($value, '[Preview]')
            )
            );
    }

    // ── Page preview ──────────────────────────────────────────────────────────

    public function test_valid_page_token_renders_preview(): void
    {
        $page = Page::factory()->create([
            'title' => 'My Draft Page',
            'status' => 'draft',
            'preview_token' => Str::random(64),
        ]);

        $this->get("/preview/pages/{$page->preview_token}")
            ->assertOk()
            ->assertInertia(fn ($p) => $p->component('Blog/Page')
                ->where('page.title', 'My Draft Page')
            );
    }

    public function test_invalid_page_token_returns_404(): void
    {
        $this->get('/preview/pages/'.Str::random(64))
            ->assertNotFound();
    }

    public function test_draft_page_can_be_previewed(): void
    {
        $page = Page::factory()->create([
            'status' => 'draft',
            'preview_token' => Str::random(64),
        ]);

        $this->get("/preview/pages/{$page->preview_token}")->assertOk();
    }

    public function test_published_page_can_be_previewed(): void
    {
        $page = Page::factory()->published()->create([
            'preview_token' => Str::random(64),
        ]);

        $this->get("/preview/pages/{$page->preview_token}")->assertOk();
    }

    public function test_page_preview_requires_no_authentication(): void
    {
        $page = Page::factory()->create([
            'status' => 'draft',
            'preview_token' => Str::random(64),
        ]);

        $this->get("/preview/pages/{$page->preview_token}")->assertOk();
    }

    public function test_page_preview_sets_is_preview_flag(): void
    {
        $page = Page::factory()->create([
            'status' => 'draft',
            'preview_token' => Str::random(64),
        ]);

        $this->get("/preview/pages/{$page->preview_token}")
            ->assertInertia(fn ($p) => $p->where('isPreview', true)
            );
    }

    public function test_page_preview_title_has_preview_prefix(): void
    {
        $page = Page::factory()->create([
            'title' => 'My Page',
            'status' => 'draft',
            'preview_token' => Str::random(64),
        ]);

        $this->get("/preview/pages/{$page->preview_token}")
            ->assertInertia(fn ($p) => $p->where('seo.title', fn ($value) => str_contains($value, '[Preview]')
            )
            );
    }

    // ── Token auto-generation ──────────────────────────────────────────────────

    public function test_post_is_assigned_preview_token_on_creation(): void
    {
        $post = Post::factory()->create(['user_id' => $this->makeUser()->id]);

        $this->assertNotNull($post->preview_token);
        $this->assertEquals(64, strlen($post->preview_token));
    }

    public function test_page_is_assigned_preview_token_on_creation(): void
    {
        $page = Page::factory()->create();

        $this->assertNotNull($page->preview_token);
        $this->assertEquals(64, strlen($page->preview_token));
    }

    public function test_each_post_gets_a_unique_preview_token(): void
    {
        $user = $this->makeUser();
        $post1 = Post::factory()->create(['user_id' => $user->id]);
        $post2 = Post::factory()->create(['user_id' => $user->id]);

        $this->assertNotEquals($post1->preview_token, $post2->preview_token);
    }
}
