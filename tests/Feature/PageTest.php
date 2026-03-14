<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageTest extends TestCase
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

    public function test_guest_cannot_access_pages_index(): void
    {
        $this->get('/pages')->assertRedirect('/login');
    }

    public function test_non_admin_cannot_access_pages_index(): void
    {
        // Spatie role middleware redirects non-admins to dashboard instead of 403
        $this->actingAs($this->makeUser())->get('/pages')->assertRedirect(route('dashboard'));
    }

    public function test_admin_can_access_pages_index(): void
    {
        $this->actingAs($this->makeAdmin())->get('/pages')->assertOk();
    }

    public function test_admin_can_access_pages_create(): void
    {
        $this->actingAs($this->makeAdmin())->get('/pages/create')->assertOk();
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function test_admin_can_create_page(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->post('/pages', [
            'title'  => 'About Us',
            'slug'   => 'about',
            'status' => 'published',
            'blocks' => [
                ['id' => 'b1', 'type' => 'heading', 'data' => ['level' => 1, 'text' => 'About']],
            ],
        ]);

        $response->assertRedirect('/pages');
        $this->assertDatabaseHas('pages', ['slug' => 'about', 'status' => 'published']);
    }

    public function test_store_requires_title_and_slug(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post('/pages', [])
            ->assertSessionHasErrors(['title', 'slug']);
    }

    public function test_store_rejects_duplicate_slug(): void
    {
        Page::factory()->create(['slug' => 'about']);

        $this->actingAs($this->makeAdmin())
            ->post('/pages', ['title' => 'About', 'slug' => 'about', 'status' => 'draft'])
            ->assertSessionHasErrors('slug');
    }

    // ── Edit / Update ─────────────────────────────────────────────────────────

    public function test_admin_can_access_edit_page(): void
    {
        $page = Page::factory()->create();

        $this->actingAs($this->makeAdmin())
            ->get("/pages/{$page->id}/edit")
            ->assertOk()
            ->assertInertia(fn ($p) =>
                $p->component('Pages/Edit')
                  ->where('page.slug', $page->slug)
            );
    }

    public function test_admin_can_update_page(): void
    {
        $admin = $this->makeAdmin();
        $page  = Page::factory()->create(['status' => 'draft']);

        $this->actingAs($admin)->put("/pages/{$page->id}", [
            'title'  => 'Updated Title',
            'slug'   => $page->slug,
            'status' => 'published',
            'blocks' => [],
        ])->assertRedirect('/pages');

        $this->assertDatabaseHas('pages', ['id' => $page->id, 'title' => 'Updated Title', 'status' => 'published']);
    }

    public function test_update_rejects_duplicate_slug_on_other_page(): void
    {
        $admin = $this->makeAdmin();
        Page::factory()->create(['slug' => 'contact']);
        $page = Page::factory()->create(['slug' => 'about']);

        $this->actingAs($admin)->put("/pages/{$page->id}", [
            'title'  => 'About',
            'slug'   => 'contact',
            'status' => 'draft',
        ])->assertSessionHasErrors('slug');
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function test_admin_can_delete_page(): void
    {
        $admin = $this->makeAdmin();
        $page  = Page::factory()->create();

        $this->actingAs($admin)->delete("/pages/{$page->id}")->assertRedirect('/pages');
        $this->assertDatabaseMissing('pages', ['id' => $page->id]);
    }

    public function test_non_admin_cannot_delete_page(): void
    {
        $page = Page::factory()->create();

        // Spatie role middleware redirects non-admins to dashboard instead of 403
        $this->actingAs($this->makeUser())->delete("/pages/{$page->id}")->assertRedirect(route('dashboard'));
    }
}
