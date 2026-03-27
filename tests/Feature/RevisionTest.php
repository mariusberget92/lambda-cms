<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Post;
use App\Models\Revision;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Middleware\RoleMiddleware;
use Tests\TestCase;

class RevisionTest extends TestCase
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

    // ── Page revisions — indexPage ─────────────────────────────────────────────
    // The /pages/{page}/revisions route is behind role:administrator middleware.
    // The controller owner-or-admin check is defense-in-depth; we verify it by
    // bypassing only RoleMiddleware (preserving route model binding).

    public function test_admin_can_view_revisions_for_any_page(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeUser();
        $page  = Page::factory()->create(['user_id' => $owner->id]);
        $page->saveRevision($owner->id);

        $this->actingAs($admin)
            ->getJson(route('pages.revisions', $page))
            ->assertOk()
            ->assertJsonStructure([['id', 'user_id', 'created_at']]);
    }

    public function test_owner_can_view_their_own_page_revisions_bypassing_role_middleware(): void
    {
        $owner = $this->makeUser();
        $page  = Page::factory()->create(['user_id' => $owner->id]);
        $page->saveRevision($owner->id);

        $this->actingAs($owner)
            ->withoutMiddleware([RoleMiddleware::class])
            ->getJson(route('pages.revisions', $page))
            ->assertOk();
    }

    public function test_non_owner_non_admin_gets_403_on_page_revisions_bypassing_role_middleware(): void
    {
        $owner = $this->makeUser();
        $other = $this->makeUser();
        $page  = Page::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($other)
            ->withoutMiddleware([RoleMiddleware::class])
            ->getJson(route('pages.revisions', $page))
            ->assertForbidden();
    }

    // ── Page revisions — restore() ────────────────────────────────────────────
    // The /revisions/{revision}/restore route is in the auth+verified group
    // (not admin-only), so the controller check is the real authorization gate.

    public function test_admin_can_restore_a_revision_for_any_page(): void
    {
        $admin = $this->makeAdmin();
        $owner = $this->makeUser();
        $page  = Page::factory()->create(['user_id' => $owner->id]);
        $page->saveRevision($owner->id);
        $revision = $page->revisions()->first();

        $this->actingAs($admin)
            ->getJson(route('revisions.restore', $revision))
            ->assertOk();
    }

    public function test_owner_can_restore_their_own_page_revision(): void
    {
        $owner = $this->makeUser();
        $page  = Page::factory()->create(['user_id' => $owner->id]);
        $page->saveRevision($owner->id);
        $revision = $page->revisions()->first();

        $this->actingAs($owner)
            ->getJson(route('revisions.restore', $revision))
            ->assertOk();
    }

    public function test_non_owner_non_admin_gets_403_on_restore_page_revision(): void
    {
        $owner  = $this->makeUser();
        $other  = $this->makeUser();
        $page   = Page::factory()->create(['user_id' => $owner->id]);
        $page->saveRevision($owner->id);
        $revision = $page->revisions()->first();

        $this->actingAs($other)
            ->getJson(route('revisions.restore', $revision))
            ->assertForbidden();
    }

    // ── Template revisions — indexTemplate ────────────────────────────────────
    // Same pattern as indexPage: route is admin-only, controller check is
    // defense-in-depth, tested by bypassing only RoleMiddleware.

    public function test_admin_can_view_revisions_for_any_template(): void
    {
        $admin    = $this->makeAdmin();
        $owner    = $this->makeUser();
        $template = Template::factory()->create(['user_id' => $owner->id]);
        $template->saveRevision($owner->id);

        $this->actingAs($admin)
            ->getJson(route('templates.revisions', $template))
            ->assertOk()
            ->assertJsonStructure([['id', 'user', 'created_at']]);
    }

    public function test_owner_can_view_their_own_template_revisions_bypassing_role_middleware(): void
    {
        $owner    = $this->makeUser();
        $template = Template::factory()->create(['user_id' => $owner->id]);
        $template->saveRevision($owner->id);

        $this->actingAs($owner)
            ->withoutMiddleware([RoleMiddleware::class])
            ->getJson(route('templates.revisions', $template))
            ->assertOk();
    }

    public function test_non_owner_non_admin_gets_403_on_template_revisions_bypassing_role_middleware(): void
    {
        $owner    = $this->makeUser();
        $other    = $this->makeUser();
        $template = Template::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($other)
            ->withoutMiddleware([RoleMiddleware::class])
            ->getJson(route('templates.revisions', $template))
            ->assertForbidden();
    }

    // ── Template revisions — restore() ────────────────────────────────────────

    public function test_admin_can_restore_a_revision_for_any_template(): void
    {
        $admin    = $this->makeAdmin();
        $owner    = $this->makeUser();
        $template = Template::factory()->create(['user_id' => $owner->id]);
        $template->saveRevision($owner->id);
        $revision = $template->revisions()->first();

        $this->actingAs($admin)
            ->getJson(route('revisions.restore', $revision))
            ->assertOk();
    }

    public function test_owner_can_restore_their_own_template_revision(): void
    {
        $owner    = $this->makeUser();
        $template = Template::factory()->create(['user_id' => $owner->id]);
        $template->saveRevision($owner->id);
        $revision = $template->revisions()->first();

        $this->actingAs($owner)
            ->getJson(route('revisions.restore', $revision))
            ->assertOk();
    }

    public function test_non_owner_non_admin_gets_403_on_restore_template_revision(): void
    {
        $owner    = $this->makeUser();
        $other    = $this->makeUser();
        $template = Template::factory()->create(['user_id' => $owner->id]);
        $template->saveRevision($owner->id);
        $revision = $template->revisions()->first();

        $this->actingAs($other)
            ->getJson(route('revisions.restore', $revision))
            ->assertForbidden();
    }
}
