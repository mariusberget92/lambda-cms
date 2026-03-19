<?php

namespace Tests\Feature;

use App\Models\NavItem;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavItemModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->markAsInstalled();
        $this->seedRolesAndPermissions();
    }

    public function test_factory_creates_custom_nav_item(): void
    {
        $item = NavItem::factory()->create();

        $this->assertDatabaseHas('nav_items', ['id' => $item->id, 'type' => 'custom']);
    }

    public function test_resolved_url_for_custom_item(): void
    {
        $item = NavItem::factory()->create(['url' => 'https://example.com']);

        $this->assertSame('https://example.com', $item->resolvedUrl);
    }

    public function test_resolved_url_for_page_item(): void
    {
        $page = Page::factory()->published()->create(['slug' => 'about']);
        $item = NavItem::factory()->forPage($page)->create();

        $this->assertSame('/about', $item->resolvedUrl);
    }

    public function test_deleting_page_cascades_to_nav_item(): void
    {
        $page = Page::factory()->published()->create();
        $item = NavItem::factory()->forPage($page)->create();

        $page->delete();

        $this->assertDatabaseMissing('nav_items', ['id' => $item->id]);
    }
}
