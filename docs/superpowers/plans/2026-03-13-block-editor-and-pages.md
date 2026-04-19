# Block Editor & Custom Pages — Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a visual block editor (10 block types, three-panel admin UI) to Lambda CMS, with opt-in per post and always-on for new admin-managed custom pages served at top-level slugs.

**Architecture:** Blocks stored as JSON arrays in `posts.blocks` and `pages.blocks`. Admin editor uses three-panel layout (BlockList left / BlockPreview centre / BlockSettings right) built in `resources/js/Components/BlockEditor/`. Public rendering via `BlockRenderer.vue` maps block types to `Components/Blocks/*.vue` display components. Custom pages served by a `PublicPageController` via a catch-all route `/{slug}` registered last inside the `installed` middleware group.

**Tech Stack:** Laravel 12, Inertia 2, Vue 3, Tailwind CSS 4, vue-draggable-plus (drag-to-reorder), TiptapEditor (already installed, reused for ParagraphSettings), MediaPicker (already installed, reused for image/gallery blocks)

**Spec:** `docs/superpowers/specs/2026-03-13-block-editor-and-pages-design.md`

---

## Chunk 1: Database & Models

### Task 1: Two Migrations

**Files:**
- Create: `database/migrations/2026_03_13_000001_add_block_editor_to_posts_table.php`
- Create: `database/migrations/2026_03_13_000002_create_pages_table.php`

- [ ] **Step 1: Create the posts migration**

```php
<?php
// database/migrations/2026_03_13_000001_add_block_editor_to_posts_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->boolean('use_block_editor')->default(false)->after('body');
            $table->json('blocks')->nullable()->after('use_block_editor');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn(['use_block_editor', 'blocks']);
        });
    }
};
```

- [ ] **Step 2: Create the pages migration**

```php
<?php
// database/migrations/2026_03_13_000002_create_pages_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->enum('status', ['published', 'draft'])->default('draft');
            $table->json('blocks')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
```

- [ ] **Step 3: Run migrations and verify**

```bash
php artisan migrate
```

Expected output: two new migrations run successfully. Verify with:

```bash
php artisan migrate:status | grep -E "block_editor|pages"
```

Both should show `Ran`.

- [ ] **Step 4: Commit**

```bash
git add database/migrations/2026_03_13_000001_add_block_editor_to_posts_table.php \
        database/migrations/2026_03_13_000002_create_pages_table.php
git commit -m "feat: add block_editor columns to posts and create pages table"
```

---

### Task 2: Page Model + PageFactory

**Files:**
- Create: `app/Models/Page.php`
- Create: `database/factories/PageFactory.php`
- Create: `tests/Feature/PageModelTest.php`

- [ ] **Step 1: Write the failing model test**

```php
<?php
// tests/Feature/PageModelTest.php

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
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test tests/Feature/PageModelTest.php
```

Expected: FAIL — `App\Models\Page` not found.

- [ ] **Step 3: Create the Page model**

```php
<?php
// app/Models/Page.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Page extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'status',
        'blocks',
        'meta_title',
        'meta_description',
        'meta_keywords',
    ];

    protected $casts = [
        'blocks' => 'array',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public static function generateSlug(string $title, ?int $excludeId = null): string
    {
        $slug     = Str::slug($title);
        $original = $slug;
        $count    = 1;

        while (
            static::where('slug', $slug)
                ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
                ->exists()
        ) {
            $slug = $original . '-' . $count++;
        }

        return $slug;
    }
}
```

- [ ] **Step 4: Create the PageFactory**

```php
<?php
// database/factories/PageFactory.php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PageFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->sentence(rand(3, 6), true);
        $title = rtrim($title, '.');

        return [
            'user_id'          => User::factory(),
            'title'            => $title,
            'slug'             => Str::slug($title),
            'status'           => 'draft',
            'blocks'           => null,
            'meta_title'       => null,
            'meta_description' => null,
            'meta_keywords'    => null,
        ];
    }

    public function published(): static
    {
        return $this->state(['status' => 'published']);
    }

    public function draft(): static
    {
        return $this->state(['status' => 'draft']);
    }

    public function withBlocks(array $blocks = []): static
    {
        if (empty($blocks)) {
            $blocks = [
                [
                    'id'   => (string) Str::uuid(),
                    'type' => 'heading',
                    'data' => ['level' => 2, 'text' => 'Hello World'],
                ],
                [
                    'id'   => (string) Str::uuid(),
                    'type' => 'paragraph',
                    'data' => ['content' => '<p>Sample content</p>'],
                ],
            ];
        }

        return $this->state(['blocks' => $blocks]);
    }
}
```

- [ ] **Step 5: Run tests to verify they pass**

```bash
php artisan test tests/Feature/PageModelTest.php
```

Expected: 6 tests pass.

- [ ] **Step 6: Commit**

```bash
git add app/Models/Page.php database/factories/PageFactory.php tests/Feature/PageModelTest.php
git commit -m "feat: add Page model with factory and model tests"
```

---

### Task 3: Post Model Update

**Files:**
- Modify: `app/Models/Post.php`

- [ ] **Step 1: Write the failing test**

Add to `tests/Feature/PostTest.php`:

```php
public function test_post_model_has_block_editor_fillable(): void
{
    $user = $this->makeUser();
    $post = Post::factory()->create([
        'user_id'          => $user->id,
        'use_block_editor' => true,
        'blocks'           => [['id' => 'a1', 'type' => 'heading', 'data' => ['level' => 1, 'text' => 'Hi']]],
    ]);

    $this->assertTrue((bool) $post->fresh()->use_block_editor);
    $this->assertIsArray($post->fresh()->blocks);
}
```

- [ ] **Step 2: Run the new test to verify it fails**

```bash
php artisan test tests/Feature/PostTest.php --filter=test_post_model_has_block_editor_fillable
```

Expected: FAIL — columns not in fillable/casts.

- [ ] **Step 3: Update Post model**

In `app/Models/Post.php`, add to `$fillable` array:

```php
'use_block_editor',
'blocks',
```

Add to `$casts` array:

```php
'use_block_editor' => 'boolean',
'blocks'           => 'array',
```

- [ ] **Step 4: Update PostFactory to include new columns**

In `database/factories/PostFactory.php`, add to the `definition()` return array:

```php
'use_block_editor' => false,
'blocks'           => null,
```

- [ ] **Step 5: Run the test to verify it passes**

```bash
php artisan test tests/Feature/PostTest.php --filter=test_post_model_has_block_editor_fillable
```

Expected: PASS.

- [ ] **Step 6: Run full test suite to check no regressions**

```bash
php artisan test
```

Expected: all existing tests pass.

- [ ] **Step 7: Commit**

```bash
git add app/Models/Post.php database/factories/PostFactory.php tests/Feature/PostTest.php
git commit -m "feat: add use_block_editor and blocks fields to Post model"
```

---

## Chunk 2: Backend Controllers & Routes

### Task 4: PageController + PageTest

**Files:**
- Create: `app/Http/Controllers/PageController.php`
- Create: `tests/Feature/PageTest.php`

- [ ] **Step 1: Write the failing tests**

```php
<?php
// tests/Feature/PageTest.php

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
        $this->actingAs($this->makeUser())->get('/pages')->assertForbidden();
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

        $this->actingAs($this->makeUser())->delete("/pages/{$page->id}")->assertForbidden();
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
php artisan test tests/Feature/PageTest.php
```

Expected: FAIL — route `/pages` does not exist yet.

- [ ] **Step 3: Create PageController**

```php
<?php
// app/Http/Controllers/PageController.php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class PageController extends Controller
{
    public function index()
    {
        $pages = Page::with('creator:id,name')
            ->latest()
            ->paginate(20)
            ->through(fn ($page) => [
                'id'         => $page->id,
                'title'      => $page->title,
                'slug'       => $page->slug,
                'status'     => $page->status,
                'created_at' => $page->created_at->toDateString(),
                'creator'    => $page->creator->name,
            ]);

        return Inertia::render('Pages/Index', ['pages' => $pages]);
    }

    public function create()
    {
        return Inertia::render('Pages/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'            => ['required', 'string', 'max:255'],
            'slug'             => ['required', 'string', 'max:255', 'unique:pages,slug'],
            'status'           => ['required', 'in:published,draft'],
            'blocks'           => ['nullable', 'array'],
            'meta_title'       => ['nullable', 'string', 'max:100'],
            'meta_description' => ['nullable', 'string'],
            'meta_keywords'    => ['nullable', 'string', 'max:255'],
        ]);

        Page::create([
            ...$validated,
            'user_id' => auth()->id(),
        ]);

        return redirect()->route('pages.index')->with('status', 'Page created.');
    }

    public function edit(Page $page)
    {
        return Inertia::render('Pages/Edit', [
            'page' => [
                'id'               => $page->id,
                'title'            => $page->title,
                'slug'             => $page->slug,
                'status'           => $page->status,
                'blocks'           => $page->blocks,
                'meta_title'       => $page->meta_title,
                'meta_description' => $page->meta_description,
                'meta_keywords'    => $page->meta_keywords,
            ],
        ]);
    }

    public function update(Request $request, Page $page)
    {
        $validated = $request->validate([
            'title'            => ['required', 'string', 'max:255'],
            'slug'             => ['required', 'string', 'max:255', Rule::unique('pages', 'slug')->ignore($page->id)],
            'status'           => ['required', 'in:published,draft'],
            'blocks'           => ['nullable', 'array'],
            'meta_title'       => ['nullable', 'string', 'max:100'],
            'meta_description' => ['nullable', 'string'],
            'meta_keywords'    => ['nullable', 'string', 'max:255'],
        ]);

        $page->update($validated);

        return redirect()->route('pages.index')->with('status', 'Page updated.');
    }

    public function destroy(Page $page)
    {
        $page->delete();

        return redirect()->route('pages.index')->with('status', 'Page deleted.');
    }
}
```

- [ ] **Step 4: Add pages routes temporarily to test (will be refined in Task 8)**

Add to `routes/web.php` inside the `['auth', 'verified', 'role:administrator']` middleware group. Also add the `PageController` import at the top of the file (do NOT add `PublicPageController` yet — it doesn't exist until Task 5):

```php
// At the top of routes/web.php, add:
use App\Http\Controllers\PageController;

// Inside the administrator middleware group:
Route::resource('pages', PageController::class)->except(['show']);
```

- [ ] **Step 5: Run tests to verify they pass**

```bash
php artisan test tests/Feature/PageTest.php
```

Expected: all 11 tests pass.

- [ ] **Step 6: Run full suite for regressions**

```bash
php artisan test
```

Expected: all pass.

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/PageController.php tests/Feature/PageTest.php routes/web.php
git commit -m "feat: add PageController with CRUD and admin-only access tests"
```

---

### Task 5: PublicPageController + PublicPageTest

**Files:**
- Create: `app/Http/Controllers/PublicPageController.php`
- Create: `tests/Feature/PublicPageTest.php`

- [ ] **Step 1: Write the failing tests**

```php
<?php
// tests/Feature/PublicPageTest.php

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
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
php artisan test tests/Feature/PublicPageTest.php
```

Expected: FAIL — route `/{slug}` does not exist.

- [ ] **Step 3: Create PublicPageController**

```php
<?php
// app/Http/Controllers/PublicPageController.php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Setting;
use Inertia\Inertia;

class PublicPageController extends Controller
{
    public function show(string $slug)
    {
        $page = Page::published()->where('slug', $slug)->firstOrFail();

        $separator = Setting::get('seo.title_separator', ' | ');
        $siteName  = Setting::get('site.name', config('app.name'));

        $seo = [
            'title'       => ($page->meta_title ?: $page->title) . $separator . $siteName,
            'description' => $page->meta_description ?: Setting::get('seo.default_description', ''),
            'image'       => Setting::get('seo.default_og_image_url', ''),
            'canonical'   => url("/{$page->slug}"),
            'type'        => 'website',
            'keywords'    => $page->meta_keywords ?: Setting::get('seo.default_keywords', ''),
        ];

        return Inertia::render('Blog/Page', [
            'page' => [
                'title'  => $page->title,
                'slug'   => $page->slug,
                'blocks' => $page->blocks,
            ],
            'seo' => $seo,
        ]);
    }
}
```

- [ ] **Step 4: Add `PublicPageController` import and catch-all route to `routes/web.php`**

At the top of `routes/web.php`, add (alongside the `PageController` import added in Task 4):

```php
use App\Http\Controllers\PublicPageController;
```

Then at the END of the `Route::middleware('installed')->group(...)` block, immediately before its closing brace, add:

```php
// ── Public custom pages (catch-all — must be last inside this group) ─────
Route::get('/{slug}', [PublicPageController::class, 'show'])
    ->where('slug', '^(?!login|logout|dashboard|blog|feed|sitemap\.xml|posts|categories|tags|users|profile|settings|media|comments|pages|calendar|password|register|verify|install|email|forgot-password|reset-password).*$')
    ->name('pages.show');
```

- [ ] **Step 5: Run tests to verify they pass**

```bash
php artisan test tests/Feature/PublicPageTest.php
```

Expected: all 4 tests pass.

- [ ] **Step 6: Run full suite for regressions**

```bash
php artisan test
```

Expected: all pass.

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/PublicPageController.php tests/Feature/PublicPageTest.php routes/web.php
git commit -m "feat: add PublicPageController and catch-all /{slug} route for custom pages"
```

---

### Task 6: PostController Update + PostBlockTest

> **Pre-requisite:** Task 3 (Post model update) must be completed before this task. `use_block_editor` and `blocks` must already be in the `Post` model's `$fillable` and `$casts` arrays for `Post::create()` / `$post->update()` to mass-assign these fields correctly.

**Files:**
- Modify: `app/Http/Controllers/PostController.php`
- Create: `tests/Feature/PostBlockTest.php`

- [ ] **Step 1: Write the failing tests**

```php
<?php
// tests/Feature/PostBlockTest.php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostBlockTest extends TestCase
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

    public function test_edit_response_includes_block_editor_fields(): void
    {
        $user = $this->makeUser();
        $post = Post::factory()->create([
            'user_id'          => $user->id,
            'use_block_editor' => false,
            'blocks'           => null,
        ]);

        $this->actingAs($user)
            ->get("/posts/{$post->id}/edit")
            ->assertOk()
            ->assertInertia(fn ($page) =>
                $page->component('Posts/Edit')
                     ->where('post.use_block_editor', false)
                     ->where('post.blocks', null)
            );
    }

    public function test_edit_response_includes_blocks_when_block_editor_enabled(): void
    {
        $user   = $this->makeUser();
        $blocks = [['id' => 'abc', 'type' => 'heading', 'data' => ['level' => 1, 'text' => 'Hi']]];
        $post   = Post::factory()->create([
            'user_id'          => $user->id,
            'use_block_editor' => true,
            'blocks'           => $blocks,
        ]);

        $this->actingAs($user)
            ->get("/posts/{$post->id}/edit")
            ->assertOk()
            ->assertInertia(fn ($page) =>
                $page->where('post.use_block_editor', true)
                     ->has('post.blocks')
            );
    }

    public function test_store_accepts_use_block_editor_and_blocks(): void
    {
        $user   = $this->makeUser();
        $blocks = [['id' => 'x1', 'type' => 'paragraph', 'data' => ['content' => '<p>Hi</p>']]];

        $this->actingAs($user)->post('/posts', [
            'title'            => 'Block Post',
            'status'           => 'draft',
            'use_block_editor' => true,
            'blocks'           => $blocks,
        ])->assertRedirect('/posts');

        $this->assertDatabaseHas('posts', ['title' => 'Block Post', 'use_block_editor' => true]);
        $post = Post::where('title', 'Block Post')->first();
        $this->assertIsArray($post->blocks);
    }

    public function test_update_saves_block_editor_fields(): void
    {
        $user   = $this->makeUser();
        $post   = Post::factory()->create(['user_id' => $user->id, 'use_block_editor' => false]);
        $blocks = [['id' => 'y1', 'type' => 'heading', 'data' => ['level' => 2, 'text' => 'Updated']]];

        $this->actingAs($user)->put("/posts/{$post->id}", [
            'title'            => $post->title,
            'status'           => 'draft',
            'use_block_editor' => true,
            'blocks'           => $blocks,
        ])->assertRedirect('/posts');

        $this->assertTrue((bool) $post->fresh()->use_block_editor);
        $this->assertIsArray($post->fresh()->blocks);
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
php artisan test tests/Feature/PostBlockTest.php
```

Expected: first 2 tests fail (fields not in Inertia response), last 2 fail (validation rejects unknown fields).

- [ ] **Step 3: Update `PostController::store()` validation**

In `app/Http/Controllers/PostController.php`, inside the `store()` method's `$request->validate([...])` call, add after the existing `'meta_keywords'` line:

```php
'use_block_editor' => ['nullable', 'boolean'],
'blocks'           => ['nullable', 'array'],
```

- [ ] **Step 4: Update `PostController::update()` validation**

Same addition inside `update()` method's validate call:

```php
'use_block_editor' => ['nullable', 'boolean'],
'blocks'           => ['nullable', 'array'],
```

- [ ] **Step 5: Update `PostController::edit()` Inertia response**

In the `'post' => [...]` array inside `edit()`, add after `'meta_keywords'`:

```php
'use_block_editor' => (bool) $post->use_block_editor,
'blocks'           => $post->blocks,
```

- [ ] **Step 6: Run tests to verify they pass**

```bash
php artisan test tests/Feature/PostBlockTest.php
```

Expected: all 4 tests pass.

- [ ] **Step 7: Run full suite**

```bash
php artisan test
```

Expected: all pass.

- [ ] **Step 8: Commit**

```bash
git add app/Http/Controllers/PostController.php tests/Feature/PostBlockTest.php
git commit -m "feat: add use_block_editor and blocks fields to PostController store/update/edit"
```

---

### Task 7: BlogController Update

**Files:**
- Modify: `app/Http/Controllers/BlogController.php`

The `show()` method in `BlogController` builds an explicit post array for `Blog/Show.vue`. Add `use_block_editor` and `blocks` to it so the frontend can switch rendering modes.

- [ ] **Step 1: Update `BlogController::show()`**

In `app/Http/Controllers/BlogController.php`, inside the `show()` method's `'post' => [...]` array, add after `'body' => $post->body,`:

```php
'use_block_editor' => (bool) $post->use_block_editor,
'blocks'           => $post->blocks,
```

- [ ] **Step 2: Verify with existing blog tests**

```bash
php artisan test
```

Expected: all pass (this is a non-breaking addition — existing tests don't assert on exact post array keys being absent).

- [ ] **Step 3: Commit**

```bash
git add app/Http/Controllers/BlogController.php
git commit -m "feat: pass use_block_editor and blocks to Blog/Show via BlogController"
```

---

### Task 8: AppLayout Sidebar — Pages Entry

**Files:**
- Modify: `resources/js/Layouts/AppLayout.vue`

- [ ] **Step 1: Add Pages sidebar link**

In `resources/js/Layouts/AppLayout.vue`, after the `<SidebarLink>` block for `route('posts.index')` and its Calendar and Categories siblings, add a Pages entry. Insert it between the Posts/Calendar block and the Categories block (i.e., after the Calendar SidebarLink, before the Categories SidebarLink):

```html
<SidebarLink
  v-if="user.role === 'administrator'"
  :href="route('pages.index')"
  :active="currentRoute?.startsWith('pages.')"
>
  <template #icon>
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10l6 6v8a2 2 0 01-2 2z"/>
    </svg>
  </template>
  Pages
</SidebarLink>
```

- [ ] **Step 2: Verify build**

```bash
npm run build
```

Expected: no errors.

- [ ] **Step 3: Commit**

```bash
git add resources/js/Layouts/AppLayout.vue
git commit -m "feat: add Pages sidebar entry for administrators"
```

---

## Chunk 3: Block Editor Admin Components

### Task 9: Install vue-draggable-plus

- [ ] **Step 1: Install the package**

```bash
npm install vue-draggable-plus
```

- [ ] **Step 2: Verify installation**

```bash
node -e "require('vue-draggable-plus'); console.log('ok')"
```

Expected: `ok`

- [ ] **Step 3: Commit**

```bash
git add package.json package-lock.json
git commit -m "chore: install vue-draggable-plus for block list drag-to-reorder"
```

---

### Task 10: BlockEditor.vue — Main Shell

**Files:**
- Create: `resources/js/Components/BlockEditor/BlockEditor.vue`

This is the top-level component. It owns the three-panel layout and all block mutation logic.

- [ ] **Step 1: Create `BlockEditor.vue`**

```vue
<!-- resources/js/Components/BlockEditor/BlockEditor.vue -->
<template>
  <div class="flex border rounded-lg overflow-hidden bg-background" style="min-height: 500px">
    <!-- Left panel: block list -->
    <BlockList
      :blocks="localBlocks"
      :selected-id="selectedBlockId"
      :is-admin="isAdmin"
      @select="selectBlock"
      @add="addBlock"
      @remove="removeBlock"
      @reorder="onReorder"
    />

    <!-- Centre panel: live preview -->
    <BlockPreview :blocks="localBlocks" />

    <!-- Right panel: settings -->
    <BlockSettings
      :block="selectedBlock"
      :is-admin="isAdmin"
      @update="updateBlock"
    />
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import BlockList     from './BlockList.vue'
import BlockPreview  from './BlockPreview.vue'
import BlockSettings from './BlockSettings.vue'

const props = defineProps({
  modelValue: { type: Array, default: () => [] },
  isAdmin:    { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue'])

// ── Internal state ────────────────────────────────────────────────────────────

const localBlocks     = ref([...(props.modelValue ?? [])])
const selectedBlockId = ref(null)

const selectedBlock = computed(() =>
  localBlocks.value.find(b => b.id === selectedBlockId.value) ?? null
)

// ── Sync: parent → local (e.g. tab switch clears blocks) ─────────────────────

watch(
  () => props.modelValue,
  (newVal) => {
    localBlocks.value = [...(newVal ?? [])]
    if (!localBlocks.value.find(b => b.id === selectedBlockId.value)) {
      selectedBlockId.value = null
    }
  },
  { deep: true }
)

// ── Default data per block type ───────────────────────────────────────────────

function defaultData(type) {
  const defaults = {
    paragraph: { content: '' },
    heading:   { level: 2, text: '' },
    image:     { media_id: null, url: '', caption: '', alt: '' },
    quote:     { text: '', attribution: '' },
    code:      { code: '', language: 'javascript' },
    gallery:   { items: [] },
    video:     { url: '', caption: '' },
    divider:   { style: 'line' },
    cta:       { headline: '', text: '', button_label: '', button_url: '' },
    html:      { content: '' },
  }
  return defaults[type] ?? {}
}

// ── Mutations (each immediately emits up) ─────────────────────────────────────

function addBlock(type) {
  const block = { id: generateId(), type, data: defaultData(type) }
  localBlocks.value = [...localBlocks.value, block]
  selectedBlockId.value = block.id
  emit('update:modelValue', localBlocks.value)
}

function removeBlock(id) {
  const block = localBlocks.value.find(b => b.id === id)
  if (block) {
    const hasContent = Object.values(block.data ?? {}).some(v =>
      v !== null && v !== '' && !(Array.isArray(v) && v.length === 0)
    )
    if (hasContent && !confirm('Remove this block? Its content will be lost.')) return
  }
  localBlocks.value = localBlocks.value.filter(b => b.id !== id)
  if (selectedBlockId.value === id) selectedBlockId.value = null
  emit('update:modelValue', localBlocks.value)
}

function selectBlock(id) {
  selectedBlockId.value = id
}

function onReorder(newList) {
  localBlocks.value = newList
  emit('update:modelValue', localBlocks.value)
}

function updateBlock({ id, data }) {
  localBlocks.value = localBlocks.value.map(b =>
    b.id === id ? { ...b, data: { ...b.data, ...data } } : b
  )
  emit('update:modelValue', localBlocks.value)
}

function generateId() {
  // Use crypto.randomUUID() if available, otherwise fallback
  if (typeof crypto !== 'undefined' && crypto.randomUUID) {
    return crypto.randomUUID()
  }
  return Math.random().toString(36).slice(2) + Date.now().toString(36)
}
</script>
```

- [ ] **Step 2: Verify build compiles**

```bash
npm run build 2>&1 | tail -5
```

Expected: no errors (will warn about missing child components — that's OK for now).

- [ ] **Step 3: Commit**

```bash
git add resources/js/Components/BlockEditor/BlockEditor.vue
git commit -m "feat: add BlockEditor.vue main shell with state management"
```

---

### Task 11: BlockList.vue

**Files:**
- Create: `resources/js/Components/BlockEditor/BlockList.vue`

The left panel. Shows draggable list of blocks plus an "Add block" button that opens a type picker.

- [ ] **Step 1: Create `BlockList.vue`**

```vue
<!-- resources/js/Components/BlockEditor/BlockList.vue -->
<template>
  <div class="w-44 shrink-0 border-r flex flex-col bg-sidebar">
    <!-- Block list header -->
    <div class="px-3 py-2 border-b">
      <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Blocks</p>
    </div>

    <!-- Draggable list -->
    <VueDraggable
      v-model="draggableBlocks"
      class="flex-1 overflow-y-auto p-2 space-y-1"
      handle=".drag-handle"
      :animation="150"
    >
      <div
        v-for="block in draggableBlocks"
        :key="block.id"
        class="flex items-center gap-1 rounded-md px-2 py-1.5 cursor-pointer text-sm transition-colors"
        :class="block.id === selectedId
          ? 'bg-primary text-primary-foreground'
          : 'hover:bg-accent text-foreground'"
        @click="$emit('select', block.id)"
      >
        <span class="drag-handle text-muted-foreground cursor-grab active:cursor-grabbing mr-1 shrink-0" @click.stop>
          <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
            <path d="M7 2a1 1 0 011 1v1a1 1 0 01-2 0V3a1 1 0 011-1zm6 0a1 1 0 011 1v1a1 1 0 01-2 0V3a1 1 0 011-1zM7 8a1 1 0 011 1v1a1 1 0 01-2 0V9a1 1 0 011-1zm6 0a1 1 0 011 1v1a1 1 0 01-2 0V9a1 1 0 011-1zM7 14a1 1 0 011 1v1a1 1 0 01-2 0v-1a1 1 0 011-1zm6 0a1 1 0 011 1v1a1 1 0 01-2 0v-1a1 1 0 011-1z"/>
          </svg>
        </span>
        <span class="flex-1 truncate text-xs">{{ blockLabel(block.type) }}</span>
        <button
          type="button"
          class="shrink-0 opacity-50 hover:opacity-100 transition-opacity"
          :class="block.id === selectedId ? 'text-primary-foreground' : 'text-muted-foreground'"
          @click.stop="$emit('remove', block.id)"
          title="Remove block"
        >
          <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>
    </VueDraggable>

    <!-- Add block button -->
    <div class="p-2 border-t relative">
      <button
        type="button"
        class="w-full rounded-md border border-dashed border-border px-2 py-1.5 text-xs text-muted-foreground hover:border-primary hover:text-primary transition-colors flex items-center justify-center gap-1"
        @click="showPicker = !showPicker"
      >
        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        Add block
      </button>

      <!-- Block type picker popover -->
      <div
        v-if="showPicker"
        class="absolute bottom-full left-0 mb-1 w-56 rounded-lg border bg-popover shadow-lg p-2 z-50"
      >
        <p class="text-xs font-semibold text-muted-foreground mb-2 px-1">Choose block type</p>
        <div class="grid grid-cols-2 gap-1">
          <button
            v-for="btype in availableTypes"
            :key="btype.type"
            type="button"
            class="flex items-center gap-1.5 rounded-md px-2 py-1.5 text-xs hover:bg-accent transition-colors text-left"
            @click="pickType(btype.type)"
          >
            <span>{{ btype.icon }}</span>
            <span>{{ btype.label }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { VueDraggable } from 'vue-draggable-plus'

const props = defineProps({
  blocks:     { type: Array,   default: () => [] },
  selectedId: { type: String,  default: null },
  isAdmin:    { type: Boolean, default: false },
})

const emit = defineEmits(['select', 'add', 'remove', 'reorder'])

const showPicker = ref(false)

// Wrap as writable computed so VueDraggable can mutate it via its v-model setter
const draggableBlocks = computed({
  get: () => props.blocks,
  set: (val) => emit('reorder', val),
})

const ALL_TYPES = [
  { type: 'paragraph', label: 'Paragraph',  icon: '¶' },
  { type: 'heading',   label: 'Heading',    icon: 'H' },
  { type: 'image',     label: 'Image',      icon: '🖼' },
  { type: 'quote',     label: 'Quote',      icon: '"' },
  { type: 'code',      label: 'Code',       icon: '</>' },
  { type: 'gallery',   label: 'Gallery',    icon: '⬛' },
  { type: 'video',     label: 'Video',      icon: '▶' },
  { type: 'divider',   label: 'Divider',    icon: '—' },
  { type: 'cta',       label: 'CTA',        icon: '📢' },
  { type: 'html',      label: 'HTML',       icon: '{}', adminOnly: true },
]

const availableTypes = computed(() =>
  ALL_TYPES.filter(t => !t.adminOnly || props.isAdmin)
)

const LABELS = Object.fromEntries(ALL_TYPES.map(t => [t.type, t.label]))

function blockLabel(type) {
  return LABELS[type] ?? type
}

function pickType(type) {
  showPicker.value = false
  emit('add', type)
}
</script>
```

- [ ] **Step 2: Verify build**

```bash
npm run build 2>&1 | tail -5
```

Expected: no errors.

- [ ] **Step 3: Commit**

```bash
git add resources/js/Components/BlockEditor/BlockList.vue
git commit -m "feat: add BlockList.vue with drag-to-reorder and block type picker"
```

---

### Task 12: Block Settings Subcomponents (10 types)

**Files:**
- Create: `resources/js/Components/BlockEditor/blocks/HeadingSettings.vue`
- Create: `resources/js/Components/BlockEditor/blocks/ParagraphSettings.vue`
- Create: `resources/js/Components/BlockEditor/blocks/ImageSettings.vue`
- Create: `resources/js/Components/BlockEditor/blocks/QuoteSettings.vue`
- Create: `resources/js/Components/BlockEditor/blocks/CodeSettings.vue`
- Create: `resources/js/Components/BlockEditor/blocks/GallerySettings.vue`
- Create: `resources/js/Components/BlockEditor/blocks/VideoSettings.vue`
- Create: `resources/js/Components/BlockEditor/blocks/DividerSettings.vue`
- Create: `resources/js/Components/BlockEditor/blocks/CtaSettings.vue`
- Create: `resources/js/Components/BlockEditor/blocks/HtmlSettings.vue`

Each receives `block` (the full block object) and emits `update` with `{ id, data }`.

- [ ] **Step 1: Create `HeadingSettings.vue`**

```vue
<!-- resources/js/Components/BlockEditor/blocks/HeadingSettings.vue -->
<template>
  <div class="space-y-3">
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Level</label>
      <select
        :value="block.data.level"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @change="emit('update', { id: block.id, data: { level: Number($event.target.value) } })"
      >
        <option v-for="n in 6" :key="n" :value="n">H{{ n }}</option>
      </select>
    </div>
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Text</label>
      <input
        :value="block.data.text"
        type="text"
        placeholder="Heading text..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { text: $event.target.value } })"
      />
    </div>
  </div>
</template>

<script setup>
defineProps({ block: { type: Object, required: true } })
const emit = defineEmits(['update'])
</script>
```

- [ ] **Step 2: Create `ParagraphSettings.vue`**

Reuses the existing `TiptapEditor` component.

```vue
<!-- resources/js/Components/BlockEditor/blocks/ParagraphSettings.vue -->
<template>
  <div>
    <label class="text-xs font-medium text-muted-foreground block mb-1">Content</label>
    <TiptapEditor
      :model-value="block.data.content"
      @update:model-value="emit('update', { id: block.id, data: { content: $event } })"
    />
  </div>
</template>

<script setup>
import TiptapEditor from '@/Components/TiptapEditor.vue'
defineProps({ block: { type: Object, required: true } })
const emit = defineEmits(['update'])
</script>
```

- [ ] **Step 3: Create `ImageSettings.vue`**

```vue
<!-- resources/js/Components/BlockEditor/blocks/ImageSettings.vue -->
<template>
  <div class="space-y-3">
    <!-- Current image preview -->
    <div v-if="block.data.url" class="rounded-md overflow-hidden border">
      <img :src="block.data.url" :alt="block.data.alt" class="w-full object-cover max-h-32" />
    </div>

    <button
      type="button"
      class="w-full rounded-md border border-dashed px-3 py-2 text-xs text-muted-foreground hover:border-primary hover:text-primary transition-colors"
      @click="showPicker = true"
    >
      {{ block.data.url ? 'Change image' : 'Select image' }}
    </button>

    <MediaPicker v-model="showPicker" @select="onMediaSelect" />

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Alt text</label>
      <input
        :value="block.data.alt"
        type="text"
        placeholder="Describe the image..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { alt: $event.target.value } })"
      />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Caption (optional)</label>
      <input
        :value="block.data.caption"
        type="text"
        placeholder="Caption..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { caption: $event.target.value } })"
      />
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import MediaPicker from '@/Components/MediaPicker.vue'

const props = defineProps({ block: { type: Object, required: true } })
const emit  = defineEmits(['update'])

const showPicker = ref(false)

function onMediaSelect(media) {
  showPicker.value = false
  emit('update', {
    id:   props.block.id,
    data: { media_id: media.id, url: media.url, alt: media.alt ?? '' },
  })
}
</script>
```

- [ ] **Step 4: Create `QuoteSettings.vue`**

```vue
<!-- resources/js/Components/BlockEditor/blocks/QuoteSettings.vue -->
<template>
  <div class="space-y-3">
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Quote text</label>
      <textarea
        :value="block.data.text"
        rows="4"
        placeholder="The quote..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring resize-none"
        @input="emit('update', { id: block.id, data: { text: $event.target.value } })"
      />
    </div>
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Attribution (optional)</label>
      <input
        :value="block.data.attribution"
        type="text"
        placeholder="— Author name"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { attribution: $event.target.value } })"
      />
    </div>
  </div>
</template>

<script setup>
defineProps({ block: { type: Object, required: true } })
const emit = defineEmits(['update'])
</script>
```

- [ ] **Step 5: Create `CodeSettings.vue`**

```vue
<!-- resources/js/Components/BlockEditor/blocks/CodeSettings.vue -->
<template>
  <div class="space-y-3">
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Language</label>
      <select
        :value="block.data.language"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @change="emit('update', { id: block.id, data: { language: $event.target.value } })"
      >
        <option value="javascript">JavaScript</option>
        <option value="typescript">TypeScript</option>
        <option value="php">PHP</option>
        <option value="python">Python</option>
        <option value="html">HTML</option>
        <option value="css">CSS</option>
        <option value="bash">Bash</option>
        <option value="json">JSON</option>
        <option value="sql">SQL</option>
        <option value="plaintext">Plain text</option>
      </select>
    </div>
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Code</label>
      <textarea
        :value="block.data.code"
        rows="8"
        placeholder="Paste code here..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-xs font-mono focus:outline-none focus:ring-2 focus:ring-ring resize-y"
        @input="emit('update', { id: block.id, data: { code: $event.target.value } })"
      />
    </div>
  </div>
</template>

<script setup>
defineProps({ block: { type: Object, required: true } })
const emit = defineEmits(['update'])
</script>
```

- [ ] **Step 6: Create `GallerySettings.vue`**

```vue
<!-- resources/js/Components/BlockEditor/blocks/GallerySettings.vue -->
<template>
  <div class="space-y-3">
    <!-- Existing items -->
    <div v-if="block.data.items?.length" class="grid grid-cols-3 gap-1">
      <div
        v-for="(item, i) in block.data.items"
        :key="item.media_id"
        class="relative group rounded overflow-hidden border aspect-square"
      >
        <img :src="item.url" :alt="item.alt" class="w-full h-full object-cover" />
        <button
          type="button"
          class="absolute top-0.5 right-0.5 w-5 h-5 rounded-full bg-destructive text-destructive-foreground text-xs flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity"
          @click="removeItem(i)"
        >✕</button>
      </div>
    </div>
    <p v-else class="text-xs text-muted-foreground text-center py-2">No images yet</p>

    <button
      type="button"
      class="w-full rounded-md border border-dashed px-3 py-2 text-xs text-muted-foreground hover:border-primary hover:text-primary transition-colors"
      @click="showPicker = true"
    >
      + Add image
    </button>

    <MediaPicker v-model="showPicker" @select="onMediaSelect" />
  </div>
</template>

<script setup>
import { ref } from 'vue'
import MediaPicker from '@/Components/MediaPicker.vue'

const props = defineProps({ block: { type: Object, required: true } })
const emit  = defineEmits(['update'])

const showPicker = ref(false)

function onMediaSelect(media) {
  showPicker.value = false
  const items = [...(props.block.data.items ?? []), { media_id: media.id, url: media.url, alt: media.alt ?? '' }]
  emit('update', { id: props.block.id, data: { items } })
}

function removeItem(index) {
  const items = props.block.data.items.filter((_, i) => i !== index)
  emit('update', { id: props.block.id, data: { items } })
}
</script>
```

- [ ] **Step 7: Create `VideoSettings.vue`**

```vue
<!-- resources/js/Components/BlockEditor/blocks/VideoSettings.vue -->
<template>
  <div class="space-y-3">
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">YouTube or Vimeo URL</label>
      <input
        :value="block.data.url"
        type="url"
        placeholder="https://www.youtube.com/watch?v=..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        :class="{ 'border-destructive': urlError }"
        @input="onUrlInput"
      />
      <p v-if="urlError" class="mt-1 text-xs text-destructive">{{ urlError }}</p>
    </div>

    <!-- Embedded preview (read-only) -->
    <div v-if="embedUrl" class="rounded-md overflow-hidden border aspect-video">
      <iframe
        :src="embedUrl"
        class="w-full h-full"
        frameborder="0"
        allow="autoplay; encrypted-media"
        allowfullscreen
      />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Caption (optional)</label>
      <input
        :value="block.data.caption"
        type="text"
        placeholder="Caption..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { caption: $event.target.value } })"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({ block: { type: Object, required: true } })
const emit  = defineEmits(['update'])

const urlError = ref('')

const embedUrl = computed(() => {
  const url = props.block.data.url ?? ''
  if (!url) return null
  const ytMatch = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\s]+)/)
  if (ytMatch) return `https://www.youtube.com/embed/${ytMatch[1]}`
  const vmMatch = url.match(/vimeo\.com\/(\d+)/)
  if (vmMatch) return `https://player.vimeo.com/video/${vmMatch[1]}`
  return null
})

function onUrlInput(e) {
  const url = e.target.value
  const isYt = /(?:youtube\.com|youtu\.be)/.test(url)
  const isVm = /vimeo\.com/.test(url)
  urlError.value = url && !isYt && !isVm ? 'Must be a YouTube or Vimeo URL' : ''
  emit('update', { id: props.block.id, data: { url } })
}
</script>
```

- [ ] **Step 8: Create `DividerSettings.vue`**

```vue
<!-- resources/js/Components/BlockEditor/blocks/DividerSettings.vue -->
<template>
  <div>
    <label class="text-xs font-medium text-muted-foreground block mb-2">Style</label>
    <div class="grid grid-cols-3 gap-2">
      <button
        v-for="opt in options"
        :key="opt.value"
        type="button"
        class="rounded-md border px-2 py-3 text-center text-xs transition-colors"
        :class="block.data.style === opt.value
          ? 'border-primary bg-primary/10 text-primary font-medium'
          : 'hover:border-foreground/30 text-muted-foreground'"
        @click="emit('update', { id: block.id, data: { style: opt.value } })"
      >
        <div class="mb-1">{{ opt.preview }}</div>
        <div>{{ opt.label }}</div>
      </button>
    </div>
  </div>
</template>

<script setup>
defineProps({ block: { type: Object, required: true } })
const emit = defineEmits(['update'])

const options = [
  { value: 'line',  label: 'Line',  preview: '——' },
  { value: 'dots',  label: 'Dots',  preview: '· · ·' },
  { value: 'space', label: 'Space', preview: '∅' },
]
</script>
```

- [ ] **Step 9: Create `CtaSettings.vue`**

```vue
<!-- resources/js/Components/BlockEditor/blocks/CtaSettings.vue -->
<template>
  <div class="space-y-3">
    <div v-for="field in fields" :key="field.key">
      <label class="text-xs font-medium text-muted-foreground block mb-1">{{ field.label }}</label>
      <input
        :value="block.data[field.key]"
        :type="field.type ?? 'text'"
        :placeholder="field.placeholder"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { [field.key]: $event.target.value } })"
      />
    </div>
  </div>
</template>

<script setup>
defineProps({ block: { type: Object, required: true } })
const emit = defineEmits(['update'])

const fields = [
  { key: 'headline',     label: 'Headline',      placeholder: 'Bold headline...' },
  { key: 'text',         label: 'Body text',      placeholder: 'Supporting text...' },
  { key: 'button_label', label: 'Button label',   placeholder: 'Click here' },
  { key: 'button_url',   label: 'Button URL',     placeholder: 'https://...', type: 'url' },
]
</script>
```

- [ ] **Step 10: Create `HtmlSettings.vue`**

```vue
<!-- resources/js/Components/BlockEditor/blocks/HtmlSettings.vue -->
<template>
  <!-- Defensive self-guard: disabled for non-admins even if rendered directly -->
  <div v-if="!isAdmin" class="rounded-md border border-dashed p-4 text-center">
    <p class="text-xs text-muted-foreground">HTML blocks are admin-only.</p>
  </div>
  <div v-else>
    <label class="text-xs font-medium text-muted-foreground block mb-1">Raw HTML</label>
    <textarea
      :value="block.data.content"
      rows="12"
      placeholder="<div>...</div>"
      class="w-full rounded-md border bg-background px-2 py-1.5 text-xs font-mono focus:outline-none focus:ring-2 focus:ring-ring resize-y"
      @input="emit('update', { id: block.id, data: { content: $event.target.value } })"
    />
    <p class="mt-1 text-xs text-muted-foreground">⚠ Admin only — rendered as-is in the page.</p>
  </div>
</template>

<script setup>
defineProps({
  block:   { type: Object,  required: true },
  isAdmin: { type: Boolean, default: false },
})
const emit = defineEmits(['update'])
</script>
```

- [ ] **Step 11: Verify build**

```bash
npm run build 2>&1 | tail -5
```

Expected: no errors.

- [ ] **Step 12: Commit**

```bash
git add resources/js/Components/BlockEditor/blocks/
git commit -m "feat: add 10 block settings subcomponents for BlockEditor"
```

---

### Task 13: BlockSettings.vue (Dynamic Switcher)

**Files:**
- Create: `resources/js/Components/BlockEditor/BlockSettings.vue`

The right panel. Switches between the 10 settings components based on `block.type`. Guards HTML blocks from non-admins.

- [ ] **Step 1: Create `BlockSettings.vue`**

```vue
<!-- resources/js/Components/BlockEditor/BlockSettings.vue -->
<template>
  <div class="w-60 shrink-0 border-l flex flex-col bg-sidebar">
    <div class="px-3 py-2 border-b">
      <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
        {{ block ? blockLabel(block.type) + ' Settings' : 'Settings' }}
      </p>
    </div>

    <div class="flex-1 overflow-y-auto p-3">
      <!-- No block selected -->
      <div v-if="!block" class="h-full flex items-center justify-center">
        <p class="text-xs text-muted-foreground text-center">Select a block<br>to edit its settings</p>
      </div>

      <!-- HTML block — non-admin disabled state -->
      <div v-else-if="block.type === 'html' && !isAdmin" class="rounded-md border border-dashed p-4 text-center">
        <p class="text-xs text-muted-foreground">HTML blocks are admin-only and cannot be edited here.</p>
      </div>

      <!-- Dynamic settings component -->
      <component
        v-else
        :is="settingsComponent"
        :block="block"
        :is-admin="isAdmin"
        @update="$emit('update', $event)"
      />
    </div>
  </div>
</template>

<script setup>
import { computed }         from 'vue'
import HeadingSettings     from './blocks/HeadingSettings.vue'
import ParagraphSettings   from './blocks/ParagraphSettings.vue'
import ImageSettings       from './blocks/ImageSettings.vue'
import QuoteSettings       from './blocks/QuoteSettings.vue'
import CodeSettings        from './blocks/CodeSettings.vue'
import GallerySettings     from './blocks/GallerySettings.vue'
import VideoSettings       from './blocks/VideoSettings.vue'
import DividerSettings     from './blocks/DividerSettings.vue'
import CtaSettings         from './blocks/CtaSettings.vue'
import HtmlSettings        from './blocks/HtmlSettings.vue'

const props = defineProps({
  block:   { type: Object,  default: null },
  isAdmin: { type: Boolean, default: false },
})

defineEmits(['update'])

const COMPONENT_MAP = {
  paragraph: ParagraphSettings,
  heading:   HeadingSettings,
  image:     ImageSettings,
  quote:     QuoteSettings,
  code:      CodeSettings,
  gallery:   GallerySettings,
  video:     VideoSettings,
  divider:   DividerSettings,
  cta:       CtaSettings,
  html:      HtmlSettings,
}

const LABELS = {
  paragraph: 'Paragraph',
  heading:   'Heading',
  image:     'Image',
  quote:     'Quote',
  code:      'Code',
  gallery:   'Gallery',
  video:     'Video',
  divider:   'Divider',
  cta:       'CTA',
  html:      'HTML',
}

const settingsComponent = computed(() =>
  props.block ? COMPONENT_MAP[props.block.type] ?? null : null
)

function blockLabel(type) {
  return LABELS[type] ?? type
}
</script>
```

- [ ] **Step 2: Verify build**

```bash
npm run build 2>&1 | tail -5
```

Expected: no errors.

- [ ] **Step 3: Commit**

```bash
git add resources/js/Components/BlockEditor/BlockSettings.vue
git commit -m "feat: add BlockSettings.vue dynamic settings panel"
```

---

### Task 14: BlockPreview.vue

**Files:**
- Create: `resources/js/Components/BlockEditor/BlockPreview.vue`

The centre panel. Renders all blocks in read-only preview using `BlockRenderer`. Note: `BlockRenderer` is created in Chunk 4 (Task 15). For now, use a simple placeholder that lists block types — it will be wired up in Task 15.

- [ ] **Step 1: Create `BlockPreview.vue`**

```vue
<!-- resources/js/Components/BlockEditor/BlockPreview.vue -->
<template>
  <div class="flex-1 overflow-y-auto border-r bg-background">
    <div class="p-4 prose prose-sm max-w-none dark:prose-invert">
      <p v-if="!blocks.length" class="text-muted-foreground text-sm text-center py-8">
        No blocks yet — add one from the left panel.
      </p>
      <BlockRenderer v-else :blocks="blocks" />
    </div>
  </div>
</template>

<script setup>
import BlockRenderer from '@/Components/BlockRenderer.vue'

defineProps({
  blocks: { type: Array, default: () => [] },
})
</script>
```

- [ ] **Step 2: Create a placeholder `BlockRenderer.vue` so the build doesn't fail**

```vue
<!-- resources/js/Components/BlockRenderer.vue -->
<!-- Placeholder — full implementation in Task 15 -->
<template>
  <div class="space-y-4">
    <div
      v-for="block in blocks"
      :key="block.id"
      class="text-sm text-muted-foreground border-l-2 border-border pl-3 py-1"
    >
      [{{ block.type }}]
    </div>
  </div>
</template>

<script setup>
defineProps({ blocks: { type: Array, default: () => [] } })
</script>
```

- [ ] **Step 3: Verify build**

```bash
npm run build 2>&1 | tail -5
```

Expected: no errors.

- [ ] **Step 4: Commit**

```bash
git add resources/js/Components/BlockEditor/BlockPreview.vue resources/js/Components/BlockRenderer.vue
git commit -m "feat: add BlockPreview.vue and placeholder BlockRenderer.vue"
```

---

## Chunk 4: Frontend Rendering & Integration

### Task 15: BlockRenderer.vue + 10 Block Display Components

Replace the placeholder `BlockRenderer.vue` with a full implementation, and create all 10 `Blocks/*.vue` display components.

**Files:**
- Modify: `resources/js/Components/BlockRenderer.vue`
- Create: `resources/js/Components/Blocks/ParagraphBlock.vue`
- Create: `resources/js/Components/Blocks/HeadingBlock.vue`
- Create: `resources/js/Components/Blocks/ImageBlock.vue`
- Create: `resources/js/Components/Blocks/QuoteBlock.vue`
- Create: `resources/js/Components/Blocks/CodeBlock.vue`
- Create: `resources/js/Components/Blocks/GalleryBlock.vue`
- Create: `resources/js/Components/Blocks/VideoBlock.vue`
- Create: `resources/js/Components/Blocks/DividerBlock.vue`
- Create: `resources/js/Components/Blocks/CtaBlock.vue`
- Create: `resources/js/Components/Blocks/HtmlBlock.vue`

- [ ] **Step 1: Create `ParagraphBlock.vue`**

```vue
<!-- resources/js/Components/Blocks/ParagraphBlock.vue -->
<template>
  <div class="prose prose-sm max-w-none dark:prose-invert" v-html="block.data.content" />
</template>
<script setup>
defineProps({ block: { type: Object, required: true } })
</script>
```

- [ ] **Step 2: Create `HeadingBlock.vue`**

```vue
<!-- resources/js/Components/Blocks/HeadingBlock.vue -->
<template>
  <component :is="'h' + block.data.level" class="font-bold leading-tight">
    {{ block.data.text }}
  </component>
</template>
<script setup>
defineProps({ block: { type: Object, required: true } })
</script>
```

- [ ] **Step 3: Create `ImageBlock.vue`**

```vue
<!-- resources/js/Components/Blocks/ImageBlock.vue -->
<template>
  <figure class="my-4">
    <img
      v-if="block.data.url"
      :src="block.data.url"
      :alt="block.data.alt || ''"
      class="w-full rounded-lg object-cover"
      @error="onError"
    />
    <div
      v-else
      class="w-full h-32 rounded-lg border-2 border-dashed border-border flex items-center justify-center text-muted-foreground text-sm"
    >
      Image not available
    </div>
    <figcaption v-if="block.data.caption" class="mt-2 text-center text-sm text-muted-foreground">
      {{ block.data.caption }}
    </figcaption>
  </figure>
</template>
<script setup>
defineProps({ block: { type: Object, required: true } })
function onError(e) { e.target.style.display = 'none' }
</script>
```

- [ ] **Step 4: Create `QuoteBlock.vue`**

```vue
<!-- resources/js/Components/Blocks/QuoteBlock.vue -->
<template>
  <blockquote class="border-l-4 border-primary pl-4 my-4 italic">
    <p class="text-lg">{{ block.data.text }}</p>
    <cite v-if="block.data.attribution" class="block mt-2 text-sm text-muted-foreground not-italic">
      — {{ block.data.attribution }}
    </cite>
  </blockquote>
</template>
<script setup>
defineProps({ block: { type: Object, required: true } })
</script>
```

- [ ] **Step 5: Create `CodeBlock.vue`**

```vue
<!-- resources/js/Components/Blocks/CodeBlock.vue -->
<template>
  <div class="my-4 rounded-lg overflow-hidden border border-border">
    <div class="flex items-center justify-between px-3 py-1.5 bg-muted text-xs text-muted-foreground">
      <span>{{ block.data.language || 'code' }}</span>
    </div>
    <pre class="p-4 overflow-x-auto text-sm bg-muted/50"><code>{{ block.data.code }}</code></pre>
  </div>
</template>
<script setup>
defineProps({ block: { type: Object, required: true } })
</script>
```

- [ ] **Step 6: Create `GalleryBlock.vue`**

```vue
<!-- resources/js/Components/Blocks/GalleryBlock.vue -->
<template>
  <div v-if="block.data.items?.length" class="grid gap-2 my-4"
    :class="block.data.items.length === 1 ? 'grid-cols-1' : block.data.items.length === 2 ? 'grid-cols-2' : 'grid-cols-3'"
  >
    <div
      v-for="item in block.data.items"
      :key="item.media_id"
      class="rounded-lg overflow-hidden aspect-square"
    >
      <img
        :src="item.url"
        :alt="item.alt || ''"
        class="w-full h-full object-cover"
        @error="e => e.target.parentElement.classList.add('bg-muted')"
      />
    </div>
  </div>
  <div v-else class="h-24 rounded-lg border-2 border-dashed border-border flex items-center justify-center text-muted-foreground text-sm my-4">
    Empty gallery
  </div>
</template>
<script setup>
defineProps({ block: { type: Object, required: true } })
</script>
```

- [ ] **Step 7: Create `VideoBlock.vue`**

```vue
<!-- resources/js/Components/Blocks/VideoBlock.vue -->
<template>
  <figure class="my-4">
    <div v-if="embedUrl" class="relative aspect-video rounded-lg overflow-hidden border border-border">
      <iframe
        :src="embedUrl"
        class="absolute inset-0 w-full h-full"
        frameborder="0"
        allow="autoplay; encrypted-media"
        allowfullscreen
      />
    </div>
    <div v-else class="aspect-video rounded-lg border-2 border-dashed border-border flex items-center justify-center text-muted-foreground text-sm">
      {{ block.data.url ? 'Invalid video URL' : 'No video URL set' }}
    </div>
    <figcaption v-if="block.data.caption" class="mt-2 text-center text-sm text-muted-foreground">
      {{ block.data.caption }}
    </figcaption>
  </figure>
</template>
<script setup>
import { computed } from 'vue'
const props = defineProps({ block: { type: Object, required: true } })

const embedUrl = computed(() => {
  const url = props.block.data.url ?? ''
  const yt = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\s]+)/)
  if (yt) return `https://www.youtube.com/embed/${yt[1]}`
  const vm = url.match(/vimeo\.com\/(\d+)/)
  if (vm) return `https://player.vimeo.com/video/${vm[1]}`
  return null
})
</script>
```

- [ ] **Step 8: Create `DividerBlock.vue`**

```vue
<!-- resources/js/Components/Blocks/DividerBlock.vue -->
<template>
  <div class="my-6 flex items-center justify-center">
    <hr v-if="block.data.style === 'line'" class="w-full border-border" />
    <div v-else-if="block.data.style === 'dots'" class="flex gap-2 text-muted-foreground">
      <span>·</span><span>·</span><span>·</span>
    </div>
    <div v-else class="h-8" />
  </div>
</template>
<script setup>
defineProps({ block: { type: Object, required: true } })
</script>
```

- [ ] **Step 9: Create `CtaBlock.vue`**

```vue
<!-- resources/js/Components/Blocks/CtaBlock.vue -->
<template>
  <div class="my-4 rounded-lg border bg-card p-6 text-center">
    <h3 v-if="block.data.headline" class="text-xl font-bold mb-2">{{ block.data.headline }}</h3>
    <p v-if="block.data.text" class="text-muted-foreground mb-4">{{ block.data.text }}</p>
    <a
      v-if="block.data.button_url"
      :href="block.data.button_url"
      class="inline-flex items-center rounded-md bg-primary px-5 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 transition-colors"
    >
      {{ block.data.button_label || 'Learn more' }}
    </a>
  </div>
</template>
<script setup>
defineProps({ block: { type: Object, required: true } })
</script>
```

- [ ] **Step 10: Create `HtmlBlock.vue`**

```vue
<!-- resources/js/Components/Blocks/HtmlBlock.vue -->
<template>
  <div v-html="block.data.content" />
</template>
<script setup>
defineProps({ block: { type: Object, required: true } })
</script>
```

- [ ] **Step 11: Replace placeholder `BlockRenderer.vue` with full implementation**

```vue
<!-- resources/js/Components/BlockRenderer.vue -->
<template>
  <div class="space-y-4">
    <component
      v-for="block in blocks"
      :key="block.id"
      :is="BLOCK_MAP[block.type]"
      :block="block"
    />
  </div>
</template>

<script setup>
import ParagraphBlock from '@/Components/Blocks/ParagraphBlock.vue'
import HeadingBlock   from '@/Components/Blocks/HeadingBlock.vue'
import ImageBlock     from '@/Components/Blocks/ImageBlock.vue'
import QuoteBlock     from '@/Components/Blocks/QuoteBlock.vue'
import CodeBlock      from '@/Components/Blocks/CodeBlock.vue'
import GalleryBlock   from '@/Components/Blocks/GalleryBlock.vue'
import VideoBlock     from '@/Components/Blocks/VideoBlock.vue'
import DividerBlock   from '@/Components/Blocks/DividerBlock.vue'
import CtaBlock       from '@/Components/Blocks/CtaBlock.vue'
import HtmlBlock      from '@/Components/Blocks/HtmlBlock.vue'

defineProps({ blocks: { type: Array, default: () => [] } })

const BLOCK_MAP = {
  paragraph: ParagraphBlock,
  heading:   HeadingBlock,
  image:     ImageBlock,
  quote:     QuoteBlock,
  code:      CodeBlock,
  gallery:   GalleryBlock,
  video:     VideoBlock,
  divider:   DividerBlock,
  cta:       CtaBlock,
  html:      HtmlBlock,
}
</script>
```

- [ ] **Step 12: Verify build**

```bash
npm run build 2>&1 | tail -5
```

Expected: no errors.

- [ ] **Step 13: Commit**

```bash
git add resources/js/Components/Blocks/ resources/js/Components/BlockRenderer.vue
git commit -m "feat: add 10 block display components and full BlockRenderer.vue"
```

---

### Task 16: Blog/Show.vue — Block vs Tiptap Rendering

**Files:**
- Modify: `resources/js/Pages/Blog/Show.vue`

When `post.use_block_editor` is true, render blocks via `BlockRenderer`. When false, keep existing `v-html` body.

- [ ] **Step 1: Update `Blog/Show.vue`**

Find the element in `Blog/Show.vue` that renders the post body (it will be something like `<div class="prose" v-html="post.body" />`). Replace it with:

```html
<!-- Block editor content -->
<BlockRenderer v-if="post.use_block_editor && post.blocks" :blocks="post.blocks" />
<!-- Legacy Tiptap content — keep prose-sm to match the existing rendering -->
<div v-else class="prose prose-sm max-w-none dark:prose-invert" v-html="post.body" />
```

Add the import in `<script setup>`:

```js
import BlockRenderer from '@/Components/BlockRenderer.vue'
```

- [ ] **Step 2: Verify build**

```bash
npm run build 2>&1 | tail -5
```

Expected: no errors.

- [ ] **Step 3: Commit**

```bash
git add resources/js/Pages/Blog/Show.vue
git commit -m "feat: render block editor posts via BlockRenderer in Blog/Show.vue"
```

---

### Task 17: Blog/Page.vue — Public Custom Page View

**Files:**
- Create: `resources/js/Pages/Blog/Page.vue`

A new page component for public custom pages. Uses `BlogLayout` (same as Blog/Show.vue) and renders blocks.

- [ ] **Step 1: Create `Blog/Page.vue`**

Uses `BlogLayout` (same as `Blog/Show.vue`). No comments or author section — just title, blocks, and SEO.

```vue
<!-- resources/js/Pages/Blog/Page.vue -->
<script setup>
import { Head }      from '@inertiajs/vue3'
import BlogLayout    from '@/Layouts/BlogLayout.vue'
import SeoHead       from '@/Components/SeoHead.vue'
import BlockRenderer from '@/Components/BlockRenderer.vue'

// Uses the same layout as Blog/Show.vue
defineOptions({ layout: BlogLayout })

const props = defineProps({
  page: { type: Object, required: true }, // { title, slug, blocks }
  seo:  { type: Object, default: () => ({}) },
})
</script>

<template>
  <Head :title="seo.title ?? page.title" />
  <SeoHead v-if="seo.title" :seo="seo" />

  <article class="max-w-3xl mx-auto px-4 py-10">
    <h1 class="text-4xl font-bold mb-8">{{ page.title }}</h1>
    <BlockRenderer v-if="page.blocks?.length" :blocks="page.blocks" />
    <p v-else class="text-muted-foreground">This page has no content yet.</p>
  </article>
</template>
```

- [ ] **Step 2: Verify build**

```bash
npm run build 2>&1 | tail -5
```

Expected: no errors.

- [ ] **Step 3: Commit**

```bash
git add resources/js/Pages/Blog/Page.vue
git commit -m "feat: add Blog/Page.vue for public custom page rendering"
```

---

### Task 18: Posts/Create.vue — Tab Switcher

**Files:**
- Modify: `resources/js/Pages/Posts/Create.vue`

Add the tab switcher (Rich Text / Block Editor) above the content area. The active tab drives `form.use_block_editor`. Switching with content in the other tab shows a confirmation.

- [ ] **Step 1: Add `use_block_editor` and `blocks` to the Inertia form**

In `Posts/Create.vue`, find the `useForm({...})` call. Add:

```js
use_block_editor: false,
blocks: [],
```

- [ ] **Step 2: Replace the editor section with tab switcher**

Find the `<!-- Editor -->` comment section (containing `<TiptapEditor v-model="form.body" />`). Replace the entire section with:

```html
<!-- Editor (tabbed: Rich Text / Block Editor) -->
<div>
  <!-- Tab bar -->
  <div class="flex border-b border-border mb-0">
    <button
      type="button"
      class="px-4 py-2 text-sm font-medium transition-colors border-b-2 -mb-px"
      :class="!form.use_block_editor
        ? 'border-primary text-primary'
        : 'border-transparent text-muted-foreground hover:text-foreground'"
      @click="switchTab(false)"
    >
      Rich Text
    </button>
    <button
      type="button"
      class="px-4 py-2 text-sm font-medium transition-colors border-b-2 -mb-px"
      :class="form.use_block_editor
        ? 'border-primary text-primary'
        : 'border-transparent text-muted-foreground hover:text-foreground'"
      @click="switchTab(true)"
    >
      Block Editor
    </button>
  </div>

  <!-- Rich text panel -->
  <div v-if="!form.use_block_editor" class="border border-t-0 rounded-b-lg overflow-hidden">
    <TiptapEditor v-model="form.body" />
  </div>

  <!-- Block editor panel -->
  <div v-else class="border border-t-0 rounded-b-lg overflow-hidden">
    <BlockEditor
      :model-value="form.blocks"
      :is-admin="$page.props.auth.user?.role === 'administrator'"
      @update:model-value="form.blocks = filterEmptyBlocks($event)"
    />
  </div>

  <p v-if="form.errors.body" class="mt-1 text-xs text-destructive">{{ form.errors.body }}</p>
</div>
```

- [ ] **Step 3: Add `switchTab`, `filterEmptyBlocks`, and import `BlockEditor` in `<script setup>`**

Add the import:

```js
import BlockEditor from '@/Components/BlockEditor/BlockEditor.vue'
```

Add the function inside `<script setup>`:

```js
function switchTab(toBlockEditor) {
  if (toBlockEditor === form.use_block_editor) return

  const hasContent = toBlockEditor ? (form.body ?? '').trim().length > 0
                                   : (form.blocks ?? []).length > 0

  if (hasContent) {
    const other = toBlockEditor ? 'rich text' : 'block editor'
    if (!confirm(`Switching tabs will clear your ${other} content if you save. Continue?`)) return
  }

  form.use_block_editor = toBlockEditor
  // Clear the inactive mode's data
  if (toBlockEditor) { form.body   = '' }
  else               { form.blocks = [] }
}

function filterEmptyBlocks(blocks) {
  return (blocks ?? []).filter(b => {
    const d = b.data ?? {}
    return Object.values(d).some(v =>
      v !== null && v !== '' && !(Array.isArray(v) && v.length === 0)
    )
  })
}
```

- [ ] **Step 4: Ensure submit sends the right fields**

Check the existing `submit()` function. It should use `form.post(route('posts.store'))`. Since `useForm` now includes `use_block_editor` and `blocks`, they will be sent automatically. No change needed.

- [ ] **Step 5: Verify build**

```bash
npm run build 2>&1 | tail -5
```

Expected: no errors.

- [ ] **Step 6: Commit**

```bash
git add resources/js/Pages/Posts/Create.vue
git commit -m "feat: add Rich Text / Block Editor tab switcher to Posts/Create.vue"
```

---

### Task 19: Posts/Edit.vue — Tab Switcher

**Files:**
- Modify: `resources/js/Pages/Posts/Edit.vue`

Same tab switcher as Create, but pre-populated from `post.use_block_editor` and `post.blocks`.

- [ ] **Step 1: Update `useForm` to include block editor fields**

Find the `useForm({...})` call. Add:

```js
use_block_editor: props.post.use_block_editor ?? false,
blocks:           props.post.blocks ?? [],
```

(The existing `body: props.post.body` stays as-is.)

- [ ] **Step 2: Replace the editor section with the same tab switcher**

Apply the exact same HTML and script changes as in Task 18 (steps 2 and 3). Copy the tab switcher HTML and the `switchTab`/`filterEmptyBlocks` functions verbatim.

Add the same import:

```js
import BlockEditor from '@/Components/BlockEditor/BlockEditor.vue'
```

- [ ] **Step 3: Verify build**

```bash
npm run build 2>&1 | tail -5
```

Expected: no errors.

- [ ] **Step 4: Commit**

```bash
git add resources/js/Pages/Posts/Edit.vue
git commit -m "feat: add Rich Text / Block Editor tab switcher to Posts/Edit.vue"
```

---

### Task 20: Pages Admin Views

**Files:**
- Create: `resources/js/Pages/Pages/Index.vue`
- Create: `resources/js/Pages/Pages/Create.vue`
- Create: `resources/js/Pages/Pages/Edit.vue`

- [ ] **Step 1: Create `Pages/Index.vue`**

Model exactly on `Posts/Index.vue` or `Categories/Index.vue`. Use the `DataTable` component. Columns: Title, Slug, Status badge, Created, Actions (Edit / Delete).

```vue
<!-- resources/js/Pages/Pages/Index.vue -->
<script setup>
import AppLayout  from '@/Layouts/AppLayout.vue'
import DataTable  from '@/Components/DataTable.vue'
import { router } from '@inertiajs/vue3'
import { ref }    from 'vue'

const props = defineProps({
  pages: Object, // paginated
})

const deletingId = ref(null)

function confirmDelete(page) {
  if (!confirm(`Delete "${page.title}"? This cannot be undone.`)) return
  deletingId.value = page.id
  router.delete(route('pages.destroy', page.id), {
    onFinish: () => { deletingId.value = null },
  })
}

const columns = [
  { key: 'title',      label: 'Title' },
  { key: 'slug',       label: 'Slug' },
  { key: 'status',     label: 'Status' },
  { key: 'created_at', label: 'Created' },
]
</script>

<template>
  <AppLayout title="Pages">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-lg font-semibold">Pages</h2>
        <p class="text-sm text-muted-foreground mt-0.5">Manage custom site pages</p>
      </div>
      <a
        :href="route('pages.create')"
        class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 transition-colors"
      >
        New page
      </a>
    </div>

    <div class="rounded-lg border bg-card overflow-hidden">
      <table class="w-full text-sm">
        <thead class="border-b bg-muted/50">
          <tr>
            <th v-for="col in columns" :key="col.key" class="px-4 py-3 text-left font-medium text-muted-foreground">
              {{ col.label }}
            </th>
            <th class="px-4 py-3 text-right font-medium text-muted-foreground">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="page in pages.data"
            :key="page.id"
            class="border-b last:border-0 hover:bg-muted/30 transition-colors"
          >
            <td class="px-4 py-3 font-medium">{{ page.title }}</td>
            <td class="px-4 py-3 text-muted-foreground font-mono text-xs">/{{ page.slug }}</td>
            <td class="px-4 py-3">
              <span
                class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                :class="page.status === 'published'
                  ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
                  : 'bg-muted text-muted-foreground'"
              >
                {{ page.status }}
              </span>
            </td>
            <td class="px-4 py-3 text-muted-foreground">{{ page.created_at }}</td>
            <td class="px-4 py-3 text-right">
              <div class="flex items-center justify-end gap-2">
                <a
                  :href="route('pages.edit', page.id)"
                  class="text-xs font-medium text-primary hover:underline"
                >Edit</a>
                <button
                  type="button"
                  class="text-xs font-medium text-destructive hover:underline"
                  :disabled="deletingId === page.id"
                  @click="confirmDelete(page)"
                >
                  {{ deletingId === page.id ? 'Deleting...' : 'Delete' }}
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="!pages.data.length">
            <td colspan="5" class="px-4 py-8 text-center text-muted-foreground text-sm">
              No pages yet. <a :href="route('pages.create')" class="text-primary hover:underline">Create one.</a>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </AppLayout>
</template>
```

- [ ] **Step 2: Create `Pages/Create.vue`**

Pages always use the block editor (no tab switcher).

```vue
<!-- resources/js/Pages/Pages/Create.vue -->
<script setup>
import AppLayout  from '@/Layouts/AppLayout.vue'
import BlockEditor from '@/Components/BlockEditor/BlockEditor.vue'
import { useForm, usePage } from '@inertiajs/vue3'
import { watch }  from 'vue'
import { Head }   from '@inertiajs/vue3'

const authUser = usePage().props.auth.user

const form = useForm({
  title:            '',
  slug:             '',
  status:           'draft',
  blocks:           [],
  meta_title:       '',
  meta_description: '',
  meta_keywords:    '',
})

// Auto-generate slug from title
watch(() => form.title, (val) => {
  if (!form.slug || form.slug === slugify(form.title)) {
    form.slug = slugify(val)
  }
})

function slugify(str) {
  return str.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '')
}

function filterEmptyBlocks(blocks) {
  return (blocks ?? []).filter(b => {
    const d = b.data ?? {}
    return Object.values(d).some(v =>
      v !== null && v !== '' && !(Array.isArray(v) && v.length === 0)
    )
  })
}

function submit() {
  form.blocks = filterEmptyBlocks(form.blocks)
  form.post(route('pages.store'))
}
</script>

<template>
  <AppLayout title="New Page">
    <Head title="New Page" />
    <form @submit.prevent="submit">
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
          <a :href="route('pages.index')" class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-accent transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
          </a>
          <div>
            <h2 class="text-lg font-semibold">New page</h2>
            <p class="text-sm text-muted-foreground mt-0.5">Create a custom site page</p>
          </div>
        </div>
        <button
          type="submit"
          :disabled="form.processing"
          class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50 transition-colors"
        >
          {{ form.processing ? 'Saving...' : 'Save page' }}
        </button>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main: block editor -->
        <div class="lg:col-span-2 space-y-4">
          <!-- Title -->
          <div>
            <input
              v-model="form.title"
              type="text"
              placeholder="Page title..."
              class="w-full rounded-lg border bg-background px-4 py-3 text-xl font-semibold placeholder:text-muted-foreground/50 focus:outline-none focus:ring-2 focus:ring-ring"
              :class="{ 'border-destructive': form.errors.title }"
            />
            <p v-if="form.errors.title" class="mt-1 text-xs text-destructive">{{ form.errors.title }}</p>
          </div>

          <!-- Block editor -->
          <BlockEditor
            :model-value="form.blocks"
            :is-admin="authUser?.role === 'administrator'"
            @update:model-value="form.blocks = $event"
          />
        </div>

        <!-- Sidebar -->
        <div class="space-y-4">
          <!-- Slug -->
          <div class="rounded-lg border bg-card p-4">
            <h3 class="text-sm font-medium mb-3">URL Slug</h3>
            <div class="flex items-center gap-1 text-sm text-muted-foreground mb-1">
              <span>/</span>
              <input
                v-model="form.slug"
                type="text"
                placeholder="page-slug"
                class="flex-1 rounded border bg-background px-2 py-1 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                :class="{ 'border-destructive': form.errors.slug }"
              />
            </div>
            <p v-if="form.errors.slug" class="mt-1 text-xs text-destructive">{{ form.errors.slug }}</p>
          </div>

          <!-- Status -->
          <div class="rounded-lg border bg-card p-4">
            <h3 class="text-sm font-medium mb-3">Status</h3>
            <div class="space-y-2">
              <label class="flex items-center gap-3 cursor-pointer">
                <input type="radio" v-model="form.status" value="draft" class="accent-primary" />
                <span class="text-sm font-medium">Draft</span>
              </label>
              <label class="flex items-center gap-3 cursor-pointer">
                <input type="radio" v-model="form.status" value="published" class="accent-primary" />
                <span class="text-sm font-medium">Published</span>
              </label>
            </div>
          </div>

          <!-- SEO accordion -->
          <details class="rounded-lg border bg-card">
            <summary class="px-4 py-3 text-sm font-medium cursor-pointer">SEO (optional)</summary>
            <div class="px-4 pb-4 space-y-3 border-t pt-3">
              <div>
                <label class="text-xs text-muted-foreground block mb-1">Meta title</label>
                <input v-model="form.meta_title" type="text" class="w-full rounded border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
              </div>
              <div>
                <label class="text-xs text-muted-foreground block mb-1">Meta description</label>
                <textarea v-model="form.meta_description" rows="3" class="w-full rounded border bg-background px-2 py-1.5 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-ring" />
              </div>
              <div>
                <label class="text-xs text-muted-foreground block mb-1">Meta keywords</label>
                <input v-model="form.meta_keywords" type="text" class="w-full rounded border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
              </div>
            </div>
          </details>
        </div>
      </div>
    </form>
  </AppLayout>
</template>
```

- [ ] **Step 3: Create `Pages/Edit.vue`**

Same as Create but pre-populated from `page` prop:

```vue
<!-- resources/js/Pages/Pages/Edit.vue -->
<script setup>
import AppLayout   from '@/Layouts/AppLayout.vue'
import BlockEditor from '@/Components/BlockEditor/BlockEditor.vue'
import { useForm, usePage } from '@inertiajs/vue3'
import { Head }    from '@inertiajs/vue3'

const authUser = usePage().props.auth.user

const props = defineProps({
  page: { type: Object, required: true },
})

const form = useForm({
  title:            props.page.title,
  slug:             props.page.slug,
  status:           props.page.status,
  blocks:           props.page.blocks ?? [],
  meta_title:       props.page.meta_title ?? '',
  meta_description: props.page.meta_description ?? '',
  meta_keywords:    props.page.meta_keywords ?? '',
})

function filterEmptyBlocks(blocks) {
  return (blocks ?? []).filter(b => {
    const d = b.data ?? {}
    return Object.values(d).some(v =>
      v !== null && v !== '' && !(Array.isArray(v) && v.length === 0)
    )
  })
}

function submit() {
  form.blocks = filterEmptyBlocks(form.blocks)
  form.put(route('pages.update', props.page.id))
}
</script>

<template>
  <AppLayout title="Edit Page">
    <Head title="Edit Page" />
    <form @submit.prevent="submit">
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
          <a :href="route('pages.index')" class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-accent transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
          </a>
          <div>
            <h2 class="text-lg font-semibold">Edit page</h2>
            <p class="text-sm text-muted-foreground mt-0.5 line-clamp-1">{{ page.title }}</p>
          </div>
        </div>
        <button
          type="submit"
          :disabled="form.processing"
          class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50 transition-colors"
        >
          {{ form.processing ? 'Saving...' : 'Update page' }}
        </button>
      </div>

      <!-- Same layout as Create: 2-col main + sidebar -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-4">
          <div>
            <input
              v-model="form.title"
              type="text"
              class="w-full rounded-lg border bg-background px-4 py-3 text-xl font-semibold focus:outline-none focus:ring-2 focus:ring-ring"
              :class="{ 'border-destructive': form.errors.title }"
            />
            <p v-if="form.errors.title" class="mt-1 text-xs text-destructive">{{ form.errors.title }}</p>
          </div>
          <BlockEditor
            :model-value="form.blocks"
            :is-admin="authUser?.role === 'administrator'"
            @update:model-value="form.blocks = $event"
          />
        </div>

        <div class="space-y-4">
          <div class="rounded-lg border bg-card p-4">
            <h3 class="text-sm font-medium mb-3">URL Slug</h3>
            <div class="flex items-center gap-1">
              <span class="text-sm text-muted-foreground">/</span>
              <input
                v-model="form.slug"
                type="text"
                class="flex-1 rounded border bg-background px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                :class="{ 'border-destructive': form.errors.slug }"
              />
            </div>
            <p v-if="form.errors.slug" class="mt-1 text-xs text-destructive">{{ form.errors.slug }}</p>
          </div>

          <div class="rounded-lg border bg-card p-4">
            <h3 class="text-sm font-medium mb-3">Status</h3>
            <div class="space-y-2">
              <label class="flex items-center gap-3 cursor-pointer">
                <input type="radio" v-model="form.status" value="draft" class="accent-primary" />
                <span class="text-sm font-medium">Draft</span>
              </label>
              <label class="flex items-center gap-3 cursor-pointer">
                <input type="radio" v-model="form.status" value="published" class="accent-primary" />
                <span class="text-sm font-medium">Published</span>
              </label>
            </div>
          </div>

          <details class="rounded-lg border bg-card">
            <summary class="px-4 py-3 text-sm font-medium cursor-pointer">SEO (optional)</summary>
            <div class="px-4 pb-4 space-y-3 border-t pt-3">
              <div>
                <label class="text-xs text-muted-foreground block mb-1">Meta title</label>
                <input v-model="form.meta_title" type="text" class="w-full rounded border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
              </div>
              <div>
                <label class="text-xs text-muted-foreground block mb-1">Meta description</label>
                <textarea v-model="form.meta_description" rows="3" class="w-full rounded border bg-background px-2 py-1.5 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-ring" />
              </div>
              <div>
                <label class="text-xs text-muted-foreground block mb-1">Meta keywords</label>
                <input v-model="form.meta_keywords" type="text" class="w-full rounded border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
              </div>
            </div>
          </details>
        </div>
      </div>
    </form>
  </AppLayout>
</template>
```

- [ ] **Step 4: Verify build**

```bash
npm run build 2>&1 | tail -5
```

Expected: no errors.

- [ ] **Step 5: Commit**

```bash
git add resources/js/Pages/Pages/
git commit -m "feat: add Pages/Index.vue, Pages/Create.vue, Pages/Edit.vue admin views"
```

---

### Task 21: Full Build + Test Suite Verification

- [ ] **Step 1: Run production build**

```bash
npm run build
```

Expected: successful build, no errors.

- [ ] **Step 2: Run full test suite**

```bash
php artisan test
```

Expected: all tests pass, including:
- `PageModelTest` (6 tests)
- `PageTest` (11 tests)
- `PublicPageTest` (4 tests)
- `PostBlockTest` (4 tests)
- All pre-existing tests (no regressions)

- [ ] **Step 3: If any test fails, investigate and fix before committing**

Check failures with:

```bash
php artisan test --filter=FailingTestName -v
```

- [ ] **Step 4: Final commit**

```bash
git add -p  # review any unstaged changes
git commit -m "feat: block editor + custom pages — final integration complete"
```

---

## Summary

| Chunk | Tasks | What it produces |
|-------|-------|-----------------|
| 1 | 1–3 | Migrations + Page model + PageFactory + Post model update |
| 2 | 4–8 | PageController + PublicPageController + PostController/BlogController updates + routes + sidebar |
| 3 | 9–14 | vue-draggable-plus + full BlockEditor component tree (6 files) + 10 settings subcomponents |
| 4 | 15–21 | 10 block display components + BlockRenderer + Blog/Show update + Blog/Page + Posts tab switchers + Pages admin views + full test run |

**New files:** 37
**Modified files:** 8
**New tests:** `PageModelTest`, `PageTest`, `PublicPageTest`, `PostBlockTest`, + 1 test in `PostTest`
