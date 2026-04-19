<?php

namespace Tests\Feature;

use App\Models\Autosave;
use App\Models\Page;
use App\Models\Post;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutosaveTest extends TestCase
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

    private function payload(): array
    {
        return ['payload' => ['title' => 'Draft content']];
    }

    // ── storePost ─────────────────────────────────────────────────────────────

    public function test_owner_can_autosave_own_post(): void
    {
        $owner = $this->makeUser();
        $post  = Post::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($owner)
            ->postJson("/posts/{$post->id}/autosave", $this->payload())
            ->assertOk()
            ->assertJsonStructure(['saved_at']);
    }

    public function test_admin_can_autosave_any_post(): void
    {
        $owner = $this->makeUser();
        $admin = $this->makeAdmin();
        $post  = Post::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($admin)
            ->postJson("/posts/{$post->id}/autosave", $this->payload())
            ->assertOk();
    }

    public function test_non_owner_non_admin_gets_403_on_store_post_bypassing_middleware(): void
    {
        $owner   = $this->makeUser();
        $other   = $this->makeUser();
        $post    = Post::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($other)
            ->withoutMiddleware()
            ->postJson("/posts/{$post->id}/autosave", $this->payload())
            ->assertForbidden();
    }

    // ── destroyPost ───────────────────────────────────────────────────────────

    public function test_owner_can_destroy_own_post_autosave(): void
    {
        $owner = $this->makeUser();
        $post  = Post::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($owner)
            ->deleteJson("/posts/{$post->id}/autosave")
            ->assertOk()
            ->assertJson(['ok' => true]);
    }

    public function test_admin_can_destroy_any_post_autosave(): void
    {
        $owner = $this->makeUser();
        $admin = $this->makeAdmin();
        $post  = Post::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($admin)
            ->deleteJson("/posts/{$post->id}/autosave")
            ->assertOk();
    }

    public function test_non_owner_non_admin_gets_403_on_destroy_post_bypassing_middleware(): void
    {
        $owner = $this->makeUser();
        $other = $this->makeUser();
        $post  = Post::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($other)
            ->withoutMiddleware()
            ->deleteJson("/posts/{$post->id}/autosave")
            ->assertForbidden();
    }

    public function test_admin_destroy_post_autosave_deletes_owners_record(): void
    {
        $owner = $this->makeUser();
        $admin = $this->makeAdmin();
        $post  = Post::factory()->create(['user_id' => $owner->id]);

        Autosave::create([
            'autosaveable_type' => Post::class,
            'autosaveable_id'   => $post->id,
            'user_id'           => $owner->id,
            'payload'           => ['title' => 'Owner draft'],
        ]);

        $this->assertDatabaseHas('autosaves', [
            'autosaveable_type' => Post::class,
            'autosaveable_id'   => $post->id,
            'user_id'           => $owner->id,
        ]);

        $this->actingAs($admin)
            ->deleteJson("/posts/{$post->id}/autosave")
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertDatabaseMissing('autosaves', [
            'autosaveable_type' => Post::class,
            'autosaveable_id'   => $post->id,
            'user_id'           => $owner->id,
        ]);
    }

    // ── storePage ─────────────────────────────────────────────────────────────

    public function test_admin_owner_can_autosave_own_page(): void
    {
        $owner = $this->makeAdmin();
        $page  = Page::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($owner)
            ->postJson("/pages/{$page->id}/autosave", $this->payload())
            ->assertOk()
            ->assertJsonStructure(['saved_at']);
    }

    public function test_admin_can_autosave_any_page(): void
    {
        $owner = $this->makeAdmin();
        $admin = $this->makeAdmin();
        $page  = Page::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($admin)
            ->postJson("/pages/{$page->id}/autosave", $this->payload())
            ->assertOk();
    }

    public function test_non_owner_non_admin_gets_403_on_store_page_bypassing_middleware(): void
    {
        $owner = $this->makeAdmin();
        $other = $this->makeUser();
        $page  = Page::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($other)
            ->withoutMiddleware()
            ->postJson("/pages/{$page->id}/autosave", $this->payload())
            ->assertForbidden();
    }

    // ── destroyPage ───────────────────────────────────────────────────────────

    public function test_admin_owner_can_destroy_own_page_autosave(): void
    {
        $owner = $this->makeAdmin();
        $page  = Page::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($owner)
            ->deleteJson("/pages/{$page->id}/autosave")
            ->assertOk()
            ->assertJson(['ok' => true]);
    }

    public function test_admin_can_destroy_any_page_autosave(): void
    {
        $owner = $this->makeAdmin();
        $admin = $this->makeAdmin();
        $page  = Page::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($admin)
            ->deleteJson("/pages/{$page->id}/autosave")
            ->assertOk();
    }

    public function test_non_owner_non_admin_gets_403_on_destroy_page_bypassing_middleware(): void
    {
        $owner = $this->makeAdmin();
        $other = $this->makeUser();
        $page  = Page::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($other)
            ->withoutMiddleware()
            ->deleteJson("/pages/{$page->id}/autosave")
            ->assertForbidden();
    }

    public function test_admin_destroy_page_autosave_deletes_owners_record(): void
    {
        $owner = $this->makeAdmin();
        $admin = $this->makeAdmin();
        $page  = Page::factory()->create(['user_id' => $owner->id]);

        Autosave::create([
            'autosaveable_type' => Page::class,
            'autosaveable_id'   => $page->id,
            'user_id'           => $owner->id,
            'payload'           => ['title' => 'Owner draft'],
        ]);

        $this->assertDatabaseHas('autosaves', [
            'autosaveable_type' => Page::class,
            'autosaveable_id'   => $page->id,
            'user_id'           => $owner->id,
        ]);

        $this->actingAs($admin)
            ->deleteJson("/pages/{$page->id}/autosave")
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertDatabaseMissing('autosaves', [
            'autosaveable_type' => Page::class,
            'autosaveable_id'   => $page->id,
            'user_id'           => $owner->id,
        ]);
    }

    // ── storeTemplate ─────────────────────────────────────────────────────────

    public function test_admin_owner_can_autosave_own_template(): void
    {
        $owner    = $this->makeAdmin();
        $template = Template::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($owner)
            ->postJson("/templates/{$template->id}/autosave", $this->payload())
            ->assertOk()
            ->assertJsonStructure(['saved_at']);
    }

    public function test_admin_can_autosave_any_template(): void
    {
        $owner    = $this->makeAdmin();
        $admin    = $this->makeAdmin();
        $template = Template::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($admin)
            ->postJson("/templates/{$template->id}/autosave", $this->payload())
            ->assertOk();
    }

    public function test_non_owner_non_admin_gets_403_on_store_template_bypassing_middleware(): void
    {
        $owner    = $this->makeAdmin();
        $other    = $this->makeUser();
        $template = Template::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($other)
            ->withoutMiddleware()
            ->postJson("/templates/{$template->id}/autosave", $this->payload())
            ->assertForbidden();
    }

    // ── destroyTemplate ───────────────────────────────────────────────────────

    public function test_admin_owner_can_destroy_own_template_autosave(): void
    {
        $owner    = $this->makeAdmin();
        $template = Template::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($owner)
            ->deleteJson("/templates/{$template->id}/autosave")
            ->assertOk()
            ->assertJson(['ok' => true]);
    }

    public function test_admin_can_destroy_any_template_autosave(): void
    {
        $owner    = $this->makeAdmin();
        $admin    = $this->makeAdmin();
        $template = Template::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($admin)
            ->deleteJson("/templates/{$template->id}/autosave")
            ->assertOk();
    }

    public function test_non_owner_non_admin_gets_403_on_destroy_template_bypassing_middleware(): void
    {
        $owner    = $this->makeAdmin();
        $other    = $this->makeUser();
        $template = Template::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($other)
            ->withoutMiddleware()
            ->deleteJson("/templates/{$template->id}/autosave")
            ->assertForbidden();
    }

    public function test_admin_destroy_template_autosave_deletes_owners_record(): void
    {
        $owner    = $this->makeAdmin();
        $admin    = $this->makeAdmin();
        $template = Template::factory()->create(['user_id' => $owner->id]);

        Autosave::create([
            'autosaveable_type' => Template::class,
            'autosaveable_id'   => $template->id,
            'user_id'           => $owner->id,
            'payload'           => ['title' => 'Owner draft'],
        ]);

        $this->assertDatabaseHas('autosaves', [
            'autosaveable_type' => Template::class,
            'autosaveable_id'   => $template->id,
            'user_id'           => $owner->id,
        ]);

        $this->actingAs($admin)
            ->deleteJson("/templates/{$template->id}/autosave")
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertDatabaseMissing('autosaves', [
            'autosaveable_type' => Template::class,
            'autosaveable_id'   => $template->id,
            'user_id'           => $owner->id,
        ]);
    }
}
