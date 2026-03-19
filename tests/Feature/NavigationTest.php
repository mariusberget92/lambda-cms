<?php
// tests/Feature/NavigationTest.php

namespace Tests\Feature;

use App\Models\NavItem;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class NavigationTest extends TestCase
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

    // ─── Authorization ────────────────────────────────────────────────────────

    public function test_admin_can_view_navigation_manager(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get(route('navigation.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Navigation/Index'));
    }

    public function test_non_admin_cannot_view_navigation_manager(): void
    {
        $this->actingAs($this->makeUser())
            ->get(route('navigation.index'))
            ->assertRedirect(route('dashboard'));
    }

    // ─── Store ────────────────────────────────────────────────────────────────

    public function test_admin_can_add_custom_nav_item(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post(route('navigation.store'), [
                'type'  => 'custom',
                'label' => 'Blog',
                'url'   => '/',
            ])
            ->assertRedirect(route('navigation.index'));

        $this->assertDatabaseHas('nav_items', [
            'type'  => 'custom',
            'label' => 'Blog',
            'url'   => '/',
        ]);
    }

    public function test_admin_can_add_page_nav_item(): void
    {
        $page = Page::factory()->published()->create();

        $this->actingAs($this->makeAdmin())
            ->post(route('navigation.store'), [
                'type'    => 'page',
                'label'   => $page->title,
                'page_id' => $page->id,
            ])
            ->assertRedirect(route('navigation.index'));

        $this->assertDatabaseHas('nav_items', [
            'type'    => 'page',
            'page_id' => $page->id,
        ]);
    }

    public function test_store_assigns_incrementing_sort_order(): void
    {
        NavItem::factory()->create(['sort_order' => 0]);
        NavItem::factory()->create(['sort_order' => 1]);

        $this->actingAs($this->makeAdmin())
            ->post(route('navigation.store'), [
                'type'  => 'custom',
                'label' => 'New',
                'url'   => '/new',
            ]);

        $this->assertDatabaseHas('nav_items', ['label' => 'New', 'sort_order' => 2]);
    }

    public function test_store_validates_required_label(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post(route('navigation.store'), ['type' => 'custom', 'url' => '/'])
            ->assertSessionHasErrors('label');
    }

    public function test_store_validates_url_required_for_custom(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post(route('navigation.store'), ['type' => 'custom', 'label' => 'Test'])
            ->assertSessionHasErrors('url');
    }

    public function test_store_validates_page_id_required_for_page_type(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post(route('navigation.store'), ['type' => 'page', 'label' => 'Test'])
            ->assertSessionHasErrors('page_id');
    }

    public function test_non_admin_cannot_store_nav_item(): void
    {
        $this->actingAs($this->makeUser())
            ->post(route('navigation.store'), [
                'type'  => 'custom',
                'label' => 'Test',
                'url'   => '/',
            ])
            ->assertRedirect(route('dashboard'));
    }

    // ─── Reorder ──────────────────────────────────────────────────────────────

    public function test_admin_can_reorder_nav_items(): void
    {
        $first  = NavItem::factory()->create(['sort_order' => 0]);
        $second = NavItem::factory()->create(['sort_order' => 1]);

        $this->actingAs($this->makeAdmin())
            ->post(route('navigation.reorder'), [
                'items' => [
                    ['id' => $first->id,  'sort_order' => 1],
                    ['id' => $second->id, 'sort_order' => 0],
                ],
            ])
            ->assertNoContent();

        $this->assertSame(1, $first->fresh()->sort_order);
        $this->assertSame(0, $second->fresh()->sort_order);
    }

    public function test_non_admin_cannot_reorder(): void
    {
        $item = NavItem::factory()->create();

        $this->actingAs($this->makeUser())
            ->post(route('navigation.reorder'), [
                'items' => [['id' => $item->id, 'sort_order' => 0]],
            ])
            ->assertRedirect(route('dashboard'));
    }

    // ─── Destroy ──────────────────────────────────────────────────────────────

    public function test_admin_can_delete_nav_item(): void
    {
        $item = NavItem::factory()->create();

        $this->actingAs($this->makeAdmin())
            ->delete(route('navigation.destroy', $item))
            ->assertRedirect(route('navigation.index'));

        $this->assertDatabaseMissing('nav_items', ['id' => $item->id]);
    }

    public function test_non_admin_cannot_delete_nav_item(): void
    {
        $item = NavItem::factory()->create();

        $this->actingAs($this->makeUser())
            ->delete(route('navigation.destroy', $item))
            ->assertRedirect(route('dashboard'));
    }

    // ─── Shared prop ──────────────────────────────────────────────────────────

    public function test_nav_items_shared_prop_includes_custom_items(): void
    {
        NavItem::factory()->create([
            'type'       => 'custom',
            'label'      => 'Blog',
            'url'        => '/',
            'sort_order' => 0,
        ]);

        $this->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('navItems', 1)
                ->where('navItems.0.label', 'Blog')
                ->where('navItems.0.url', '/')
            );
    }

    public function test_nav_items_shared_prop_includes_published_page_items(): void
    {
        $page = Page::factory()->published()->create(['slug' => 'about']);
        NavItem::factory()->forPage($page)->create(['sort_order' => 0]);

        $this->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('navItems', 1)
                ->where('navItems.0.url', '/about')
            );
    }

    public function test_nav_items_shared_prop_excludes_draft_page_items(): void
    {
        $page = Page::factory()->create(['status' => 'draft']);
        NavItem::factory()->forPage($page)->create();

        $this->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page->has('navItems', 0));
    }

    public function test_nav_items_shared_prop_respects_sort_order(): void
    {
        NavItem::factory()->create(['label' => 'Second', 'url' => '/b', 'sort_order' => 1]);
        NavItem::factory()->create(['label' => 'First',  'url' => '/a', 'sort_order' => 0]);

        $this->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('navItems.0.label', 'First')
                ->where('navItems.1.label', 'Second')
            );
    }
}
