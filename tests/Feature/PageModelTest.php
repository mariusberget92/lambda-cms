<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->markAsInstalled();
        $this->seedRolesAndPermissions();
    }

    public function test_page_can_be_created_with_factory(): void
    {
        $page = Page::factory()->create();

        $this->assertDatabaseHas('pages', ['id' => $page->id]);
        $this->assertInstanceOf(User::class, $page->creator);
    }

    public function test_published_scope_returns_only_published(): void
    {
        Page::factory()->published()->create();
        Page::factory()->create(['status' => 'draft']);

        $this->assertCount(1, Page::published()->get());
    }

    public function test_draft_scope_returns_only_drafts(): void
    {
        Page::factory()->create(['status' => 'draft']);
        Page::factory()->published()->create();

        $this->assertCount(1, Page::draft()->get());
    }

    public function test_generate_slug_creates_unique_slug(): void
    {
        Page::factory()->create(['slug' => 'about']);

        $slug = Page::generateSlug('About');

        $this->assertSame('about-1', $slug);
    }

    public function test_blocks_cast_to_array(): void
    {
        $blocks = [['id' => 'abc', 'type' => 'heading', 'data' => ['level' => 1, 'text' => 'Hi']]];
        $page = Page::factory()->create(['blocks' => $blocks]);

        $this->assertIsArray($page->fresh()->blocks);
        $this->assertSame('heading', $page->fresh()->blocks[0]['type']);
    }

    public function test_factory_with_blocks_state(): void
    {
        $page = Page::factory()->withBlocks()->create();

        $this->assertIsArray($page->fresh()->blocks);
        $this->assertNotEmpty($page->fresh()->blocks);
    }
}
