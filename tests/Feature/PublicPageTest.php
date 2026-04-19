<?php

namespace Tests\Feature;

use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->markAsInstalled();
        $this->seedRolesAndPermissions();
    }

    public function test_published_page_is_accessible_at_slug(): void
    {
        $page = Page::factory()->published()->withBlocks()->create(['slug' => 'about']);

        $this->get('/about')
            ->assertOk()
            ->assertInertia(fn ($p) =>
                $p->component('Blog/Page')
                  ->where('page.slug', 'about')
                  ->has('page.blocks')
            );
    }

    public function test_draft_page_returns_404(): void
    {
        Page::factory()->create(['slug' => 'contact', 'status' => 'draft']);

        $this->get('/contact')->assertNotFound();
    }

    public function test_unknown_slug_returns_404(): void
    {
        $this->get('/nonexistent-page-xyz')->assertNotFound();
    }

    public function test_published_page_passes_seo_props(): void
    {
        $page = Page::factory()->published()->create([
            'slug'             => 'services',
            'meta_title'       => 'Our Services',
            'meta_description' => 'We provide great services.',
        ]);

        $this->get('/services')
            ->assertOk()
            ->assertInertia(fn ($p) =>
                $p->has('seo')
                  ->where('seo.title', fn ($v) => str_contains($v, 'Our Services'))
            );
    }
}
