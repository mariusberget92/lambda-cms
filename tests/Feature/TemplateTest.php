<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->markAsInstalled();
        $this->seedRolesAndPermissions();
    }

    private function makeAdmin(): User
    {
        return User::factory()->create()->assignRole('administrator');
    }

    private function makeUser(): User
    {
        return User::factory()->create()->assignRole('user');
    }

    // ── Access control ────────────────────────────────────────────────────────

    public function test_guest_cannot_access_templates_index(): void
    {
        $this->get('/templates')->assertRedirect('/login');
    }

    public function test_non_admin_cannot_access_templates_index(): void
    {
        $this->actingAs($this->makeUser())->get('/templates')->assertRedirect(route('dashboard'));
    }

    public function test_admin_can_access_templates_index(): void
    {
        $this->actingAs($this->makeAdmin())->get('/templates')->assertOk();
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function test_admin_can_create_template(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->post('/templates', [
            'title'  => 'My Blog Index',
            'type'   => 'blog-index',
            'status' => 'draft',
            'blocks' => [],
        ]);

        $response->assertRedirect(route('templates.index'));
        $this->assertDatabaseHas('templates', ['type' => 'blog-index', 'title' => 'My Blog Index']);
    }

    public function test_store_requires_title_and_type(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post('/templates', [])
            ->assertSessionHasErrors(['title', 'type']);
    }

    // ── Publish constraint ────────────────────────────────────────────────────

    public function test_publishing_a_template_unpublishes_previous_same_type(): void
    {
        $admin = $this->makeAdmin();

        $first = Template::create([
            'user_id' => $admin->id,
            'title'   => 'First',
            'type'    => 'blog-index',
            'status'  => 'published',
            'blocks'  => [],
        ]);

        $this->actingAs($admin)->post('/templates', [
            'title'  => 'Second',
            'type'   => 'blog-index',
            'status' => 'published',
            'blocks' => [],
        ]);

        $this->assertEquals('draft', $first->fresh()->status);
        $this->assertDatabaseHas('templates', ['title' => 'Second', 'status' => 'published']);
    }

    // ── Update + Delete ───────────────────────────────────────────────────────

    public function test_admin_can_update_template(): void
    {
        $admin    = $this->makeAdmin();
        $template = Template::create([
            'user_id' => $admin->id,
            'title'   => 'Old',
            'type'    => 'single-post',
            'status'  => 'draft',
            'blocks'  => [],
        ]);

        $this->actingAs($admin)
            ->put("/templates/{$template->id}", ['title' => 'New', 'type' => 'single-post', 'status' => 'draft', 'blocks' => []])
            ->assertRedirect(route('templates.index'));

        $this->assertEquals('New', $template->fresh()->title);
    }

    public function test_admin_can_delete_template(): void
    {
        $admin    = $this->makeAdmin();
        $template = Template::create([
            'user_id' => $admin->id,
            'title'   => 'Bye',
            'type'    => 'archive',
            'status'  => 'draft',
            'blocks'  => [],
        ]);

        $this->actingAs($admin)
            ->delete("/templates/{$template->id}")
            ->assertRedirect(route('templates.index'));

        $this->assertDatabaseMissing('templates', ['id' => $template->id]);
    }

    // ── TemplateResolver ──────────────────────────────────────────────────────

    public function test_template_resolver_returns_null_when_no_published_template(): void
    {
        $resolver = app(\App\Services\TemplateResolver::class);
        $this->assertNull($resolver->resolve('blog-index'));
    }

    public function test_template_resolver_returns_published_template(): void
    {
        $admin = $this->makeAdmin();
        Template::create([
            'user_id' => $admin->id, 'title' => 'T', 'type' => 'blog-index',
            'status' => 'published', 'blocks' => [],
        ]);

        $resolver = app(\App\Services\TemplateResolver::class);
        $this->assertNotNull($resolver->resolve('blog-index'));
    }

    public function test_blog_index_uses_template_when_published(): void
    {
        $admin = $this->makeAdmin();
        Template::create([
            'user_id' => $admin->id, 'title' => 'Blog Index Template',
            'type' => 'blog-index', 'status' => 'published', 'blocks' => [],
        ]);

        $this->get('/')->assertInertia(fn ($page) => $page->component('Blog/TemplatePage'));
    }

    public function test_blog_index_uses_default_view_without_template(): void
    {
        $this->get('/')->assertInertia(fn ($page) => $page->component('Blog/Index'));
    }

    public function test_single_post_uses_template_when_published(): void
    {
        $admin = $this->makeAdmin();
        $post  = Post::factory()->create(['status' => 'published', 'published_at' => now()]);

        Template::create([
            'user_id' => $admin->id, 'title' => 'Single Post Template',
            'type' => 'single-post', 'status' => 'published', 'blocks' => [],
        ]);

        $this->get("/blog/{$post->slug}")
            ->assertInertia(fn ($page) => $page->component('Blog/TemplatePage'));
    }
}
