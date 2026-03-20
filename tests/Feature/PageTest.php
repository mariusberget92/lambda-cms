<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
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

    // ── Public component block resolution ─────────────────────────────────────

    public function test_component_post_list_block_is_resolved_on_page_load(): void
    {
        $post = Post::factory()->create([
            'title'        => 'Test Post',
            'status'       => 'published',
            'published_at' => now()->subDay(),
        ]);

        $page = Page::create([
            'user_id' => User::factory()->create()->id,
            'title'   => 'My Page',
            'slug'    => 'my-page',
            'status'  => 'published',
            'blocks'  => [
                [
                    'id'   => 'block-1',
                    'type' => 'component',
                    'data' => [
                        'component'     => 'post-list',
                        'limit'         => 6,
                        'offset'        => 0,
                        'order'         => 'latest',
                        'featured_only' => false,
                        'category_ids'  => [],
                        'tag_ids'       => [],
                    ],
                ],
            ],
        ]);

        $this->get("/{$page->slug}")
            ->assertOk()
            ->assertInertia(
                fn ($p) => $p
                    ->component('Blog/Page')
                    ->where('page.blocks.0.data.resolved.posts.0.title', 'Test Post')
            );
    }

    public function test_component_post_list_respects_category_filter(): void
    {
        $category = Category::create(['name' => 'Test Category', 'slug' => 'test-category']);
        $included = Post::factory()->published()->create([
            'title'        => 'In Category',
            'published_at' => now()->subDay(),
        ]);
        $excluded = Post::factory()->published()->create([
            'title'        => 'Not In Category',
            'published_at' => now()->subDay(),
        ]);
        $included->categories()->attach($category);

        $page = Page::create([
            'user_id' => User::factory()->create()->id,
            'title'   => 'Filtered Page',
            'slug'    => 'filtered-page',
            'status'  => 'published',
            'blocks'  => [[
                'id'   => 'block-1',
                'type' => 'component',
                'data' => [
                    'component'     => 'post-list',
                    'limit'         => 6,
                    'offset'        => 0,
                    'order'         => 'latest',
                    'featured_only' => false,
                    'category_ids'  => [$category->id],
                    'tag_ids'       => [],
                ],
            ]],
        ]);

        $this->get("/{$page->slug}")
            ->assertOk()
            ->assertInertia(fn ($p) => $p
                ->where('page.blocks.0.data.resolved.posts.0.title', 'In Category')
                ->where('page.blocks.0.data.resolved', fn ($resolved) =>
                    count($resolved['posts']) === 1
                )
            );
    }

    public function test_draft_posts_are_excluded_from_component_post_list(): void
    {
        Post::factory()->create(['title' => 'Draft Post', 'status' => 'draft']);

        $page = Page::create([
            'user_id' => User::factory()->create()->id,
            'title'   => 'Test Page',
            'slug'    => 'test-page-draft',
            'status'  => 'published',
            'blocks'  => [[
                'id'   => 'block-1',
                'type' => 'component',
                'data' => ['component' => 'post-list', 'limit' => 6, 'offset' => 0,
                           'order' => 'latest', 'featured_only' => false,
                           'category_ids' => [], 'tag_ids' => []],
            ]],
        ]);

        $this->get("/{$page->slug}")
            ->assertInertia(fn ($p) => $p
                ->where('page.blocks.0.data.resolved.posts', [])
            );
    }

    // ── Container block ───────────────────────────────────────────────────────

    public function test_container_block_children_are_preserved_on_page_load(): void
    {
        $page = Page::create([
            'user_id' => User::factory()->create()->id,
            'title'   => 'Container Test',
            'slug'    => 'container-test',
            'status'  => 'published',
            'blocks'  => [
                [
                    'id'            => 'container-1',
                    'type'          => 'container',
                    'data'          => ['direction' => 'row', 'gap' => 4, 'wrap' => true, 'justify' => 'start', 'align' => 'start', 'maxWidth' => 'full', 'padding' => 4],
                    'children'      => [
                        ['id' => 'child-1', 'type' => 'paragraph', 'data' => ['content' => 'Hello']],
                    ],
                    'customId'      => '',
                    'customClasses' => '',
                    'customCss'     => '',
                    'fontFamily'    => '',
                ],
            ],
        ]);

        $this->get("/{$page->slug}")
            ->assertOk()
            ->assertInertia(fn ($p) => $p
                ->component('Blog/Page')
                ->where('page.blocks.0.type', 'container')
                ->where('page.blocks.0.children.0.type', 'paragraph')
                ->where('page.blocks.0.children.0.data.content', 'Hello')
            );
    }

    public function test_nested_component_block_inside_container_is_resolved(): void
    {
        $post = Post::factory()->create([
            'status'       => 'published',
            'published_at' => now()->subDay(),
        ]);

        $page = Page::create([
            'user_id' => User::factory()->create()->id,
            'title'   => 'Nested Component Test',
            'slug'    => 'nested-component-test',
            'status'  => 'published',
            'blocks'  => [
                [
                    'id'       => 'container-1',
                    'type'     => 'container',
                    'data'     => ['direction' => 'row', 'gap' => 4, 'wrap' => true, 'justify' => 'start', 'align' => 'start', 'maxWidth' => 'full', 'padding' => 4],
                    'children' => [
                        [
                            'id'   => 'comp-1',
                            'type' => 'component',
                            'data' => [
                                'component'     => 'post-list',
                                'limit'         => 6,
                                'offset'        => 0,
                                'order'         => 'latest',
                                'featured_only' => false,
                                'category_ids'  => [],
                                'tag_ids'       => [],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $this->get("/{$page->slug}")
            ->assertOk()
            ->assertInertia(fn ($p) => $p
                ->component('Blog/Page')
                ->where('page.blocks.0.type', 'container')
                ->where('page.blocks.0.children.0.type', 'component')
                ->has('page.blocks.0.children.0.data.resolved.posts')
            );
    }
}
