# Navigation Manager — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development to implement this plan task-by-task.

**Goal:** Add a managed public navigation system — admins build a drag-to-reorder list of page links and custom URLs that render in the public blog header.

**Architecture:** New `nav_items` table stores ordered items (type page|custom, page FK, label, url, sort_order). `NavigationController` handles CRUD + reorder. `HandleInertiaRequests` shares `navItems` (filtered to only published pages) to all public pages. `Navigation/Index.vue` is the admin manager (drag list + add form). `BlogLayout.vue` renders the shared prop.

**Tech Stack:** Laravel 12 · Inertia 2 · Vue 3 · Tailwind CSS 4 · vue-draggable-plus (already installed) · Spatie Permission (already installed)

**Spec:** `docs/plans/2026-03-19-navigation-manager-design.md`

---

## Task 1: Migration + NavItem Model + Factory + Model Tests

**Files:**
- Create: `database/migrations/2026_03_19_000001_create_nav_items_table.php`
- Create: `app/Models/NavItem.php`
- Create: `database/factories/NavItemFactory.php`
- Create: `tests/Feature/NavItemModelTest.php`

### Step 1: Write the failing model test

```php
<?php
// tests/Feature/NavItemModelTest.php

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
```

### Step 2: Run test to verify it fails

```bash
php artisan test tests/Feature/NavItemModelTest.php
```

Expected: FAIL — `NavItem` class not found.

### Step 3: Create the migration

```php
<?php
// database/migrations/2026_03_19_000001_create_nav_items_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nav_items', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['page', 'custom']);
            $table->string('label');
            $table->string('url')->nullable();
            $table->foreignId('page_id')->nullable()->constrained()->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nav_items');
    }
};
```

### Step 4: Create the NavItem model

```php
<?php
// app/Models/NavItem.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NavItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'label',
        'url',
        'page_id',
        'sort_order',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    // ─── Accessors ────────────────────────────────────────────────────────────

    public function getResolvedUrlAttribute(): string
    {
        if ($this->type === 'custom') {
            return $this->url ?? '';
        }

        return $this->page ? "/{$this->page->slug}" : '';
    }
}
```

### Step 5: Create the factory

```php
<?php
// database/factories/NavItemFactory.php

namespace Database\Factories;

use App\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;

class NavItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'type'       => 'custom',
            'label'      => $this->faker->words(2, true),
            'url'        => '/' . $this->faker->slug(),
            'page_id'    => null,
            'sort_order' => $this->faker->numberBetween(0, 100),
        ];
    }

    public function forPage(?Page $page = null): static
    {
        return $this->state(function () use ($page) {
            $page ??= Page::factory()->published()->create();

            return [
                'type'    => 'page',
                'label'   => $page->title,
                'url'     => null,
                'page_id' => $page->id,
            ];
        });
    }
}
```

### Step 6: Run migration and tests

```bash
php artisan migrate
php artisan test tests/Feature/NavItemModelTest.php
```

Expected: 4 tests pass.

### Step 7: Commit

```bash
git add database/migrations/2026_03_19_000001_create_nav_items_table.php \
        app/Models/NavItem.php \
        database/factories/NavItemFactory.php \
        tests/Feature/NavItemModelTest.php
git commit -m "feat: add NavItem model, migration, factory and model tests"
```

---

## Task 2: NavigationController + Routes + Feature Tests

**Files:**
- Create: `app/Http/Controllers/NavigationController.php`
- Create: `tests/Feature/NavigationTest.php`
- Modify: `routes/web.php`

### Step 1: Write the failing feature tests

```php
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
            ->assertForbidden();
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
            ->assertForbidden();
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
            ->assertForbidden();
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
            ->assertForbidden();
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
```

### Step 2: Run test to verify it fails

```bash
php artisan test tests/Feature/NavigationTest.php
```

Expected: FAIL — route `navigation.index` not defined.

### Step 3: Create the controller

```php
<?php
// app/Http/Controllers/NavigationController.php

namespace App\Http\Controllers;

use App\Models\NavItem;
use App\Models\Page;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NavigationController extends Controller
{
    public function index()
    {
        $items = NavItem::with('page')
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($item) => [
                'id'           => $item->id,
                'type'         => $item->type,
                'label'        => $item->label,
                'url'          => $item->url,
                'page_id'      => $item->page_id,
                'sort_order'   => $item->sort_order,
                'resolved_url' => $item->resolvedUrl,
                'page_status'  => $item->page?->status,
            ]);

        $pages = Page::published()
            ->orderBy('title')
            ->get(['id', 'title', 'slug']);

        return Inertia::render('Navigation/Index', compact('items', 'pages'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type'    => ['required', 'in:page,custom'],
            'label'   => ['required', 'string', 'max:255'],
            'url'     => ['required_if:type,custom', 'nullable', 'string', 'max:255'],
            'page_id' => ['required_if:type,page', 'nullable', 'exists:pages,id'],
        ]);

        $maxOrder = NavItem::max('sort_order') ?? -1;

        NavItem::create([
            ...$validated,
            'sort_order' => $maxOrder + 1,
        ]);

        return redirect()->route('navigation.index')->with('status', 'Navigation item added.');
    }

    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'items'              => ['required', 'array'],
            'items.*.id'         => ['required', 'integer', 'exists:nav_items,id'],
            'items.*.sort_order' => ['required', 'integer'],
        ]);

        foreach ($validated['items'] as $data) {
            NavItem::where('id', $data['id'])->update(['sort_order' => $data['sort_order']]);
        }

        return response()->noContent();
    }

    public function destroy(NavItem $navItem)
    {
        $navItem->delete();

        return redirect()->route('navigation.index')->with('status', 'Navigation item removed.');
    }
}
```

### Step 4: Add routes

In `routes/web.php`, inside the `Route::middleware(['auth', 'verified', 'role:administrator'])` group, add these four routes (before the closing brace of that group):

```php
use App\Http\Controllers\NavigationController;

// add to the use block at the top of the file, then inside the admin group:
Route::get('/navigation',          [NavigationController::class, 'index'])->name('navigation.index');
Route::post('/navigation',         [NavigationController::class, 'store'])->name('navigation.store');
Route::post('/navigation/reorder', [NavigationController::class, 'reorder'])->name('navigation.reorder');
Route::delete('/navigation/{navItem}', [NavigationController::class, 'destroy'])->name('navigation.destroy');
```

### Step 5: Run the tests

```bash
php artisan test tests/Feature/NavigationTest.php
```

Expected: All tests pass. The shared-prop tests will fail until Task 3 — that's fine, note them and come back.

Actually: run only the non-shared-prop tests first by checking which pass. The shared prop tests (`test_nav_items_shared_prop_*`) will fail until `HandleInertiaRequests` is updated in Task 3. All other tests in this file should pass.

### Step 6: Commit

```bash
git add app/Http/Controllers/NavigationController.php \
        tests/Feature/NavigationTest.php \
        routes/web.php
git commit -m "feat: add NavigationController with CRUD, reorder, and feature tests"
```

---

## Task 3: Share navItems via HandleInertiaRequests

**Files:**
- Modify: `app/Http/Middleware/HandleInertiaRequests.php`

### Step 1: Add the `navItems` shared prop

Open `app/Http/Middleware/HandleInertiaRequests.php`. In the `share()` method, add `navItems` to the returned array:

```php
use App\Models\NavItem;

// inside the array_merge(parent::share($request), [...]) array:
'navItems' => fn () => NavItem::with('page')
    ->orderBy('sort_order')
    ->get()
    ->filter(fn ($item) =>
        $item->type === 'custom' ||
        ($item->page && $item->page->status === 'published')
    )
    ->map(fn ($item) => [
        'label' => $item->label,
        'url'   => $item->resolvedUrl,
    ])
    ->values(),
```

The full updated `share()` method should look like:

```php
public function share(Request $request): array
{
    return array_merge(parent::share($request), [
        'appName' => config('app.name', 'Lambda CMS'),
        'auth' => [
            'user' => $request->user() ? array_merge(
                $request->user()->only('id', 'name', 'email', 'avatar_url'),
                [
                    'role'           => $request->user()->getRoleNames()->first(),
                    'email_verified' => $request->user()->hasVerifiedEmail(),
                ]
            ) : null,
        ],
        'flash' => [
            'status' => fn () => $request->session()->get('status'),
            'error'  => fn () => $request->session()->get('error'),
        ],
        'currentRoute'         => $request->route()?->getName(),
        'pendingCommentsCount' => fn () => $request->user()?->hasRole('administrator')
            ? Comment::pending()->count()
            : null,
        'navItems' => fn () => NavItem::with('page')
            ->orderBy('sort_order')
            ->get()
            ->filter(fn ($item) =>
                $item->type === 'custom' ||
                ($item->page && $item->page->status === 'published')
            )
            ->map(fn ($item) => [
                'label' => $item->label,
                'url'   => $item->resolvedUrl,
            ])
            ->values(),
    ]);
}
```

Note: `Comment` is already imported at the top of the file. Add `use App\Models\NavItem;` to the imports.

### Step 2: Run the shared-prop tests

```bash
php artisan test tests/Feature/NavigationTest.php --filter=shared_prop
```

Expected: 4 shared-prop tests pass.

### Step 3: Run full navigation test suite

```bash
php artisan test tests/Feature/NavigationTest.php
```

Expected: All tests pass.

### Step 4: Commit

```bash
git add app/Http/Middleware/HandleInertiaRequests.php
git commit -m "feat: share navItems prop via HandleInertiaRequests"
```

---

## Task 4: Navigation/Index.vue (admin manager UI)

**Files:**
- Create: `resources/js/Pages/Navigation/Index.vue`

### Step 1: Create the Vue page

```vue
<!-- resources/js/Pages/Navigation/Index.vue -->
<script setup>
import { ref, watch } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { VueDraggable } from 'vue-draggable-plus'
import AppLayout from '@/Layouts/AppLayout.vue'
import FlashMessage from '@/Components/FlashMessage.vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
  items: { type: Array, default: () => [] },
  pages: { type: Array, default: () => [] },
})

// ─── Drag-to-reorder ──────────────────────────────────────────────────────────

const draggableItems = ref(props.items.map(i => ({ ...i })))

watch(() => props.items, (val) => {
  draggableItems.value = val.map(i => ({ ...i }))
})

function onReorder() {
  router.post(route('navigation.reorder'), {
    items: draggableItems.value.map((item, index) => ({
      id:         item.id,
      sort_order: index,
    })),
  }, { preserveScroll: true })
}

// ─── Add item form ────────────────────────────────────────────────────────────

const addType = ref('custom')

const form = useForm({
  type:    'custom',
  label:   '',
  url:     '',
  page_id: null,
})

function onTypeChange(type) {
  addType.value = type
  form.type    = type
  form.label   = ''
  form.url     = ''
  form.page_id = null
}

function onPageSelect(e) {
  const pageId = parseInt(e.target.value)
  const page   = props.pages.find(p => p.id === pageId)
  form.page_id = pageId || null
  if (page && !form.label) form.label = page.title
}

function submit() {
  form.post(route('navigation.store'), {
    onSuccess: () => {
      form.reset()
      addType.value = 'custom'
    },
    preserveScroll: true,
  })
}

function deleteItem(id) {
  if (!confirm('Remove this nav item?')) return
  router.delete(route('navigation.destroy', id), { preserveScroll: true })
}
</script>

<template>
  <Head title="Navigation" />
  <FlashMessage />

  <div class="max-w-4xl space-y-6">
    <h2 class="text-lg font-semibold">Navigation</h2>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">

      <!-- Left: current items -->
      <div class="rounded-lg border bg-card p-4 space-y-3">
        <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Current items</p>

        <div v-if="!draggableItems.length" class="py-8 text-center text-sm text-muted-foreground">
          No nav items yet. Add your first item →
        </div>

        <VueDraggable
          v-model="draggableItems"
          handle=".drag-handle"
          class="space-y-2"
          @end="onReorder"
        >
          <div
            v-for="item in draggableItems"
            :key="item.id"
            class="flex items-center gap-2 rounded-md border bg-background px-3 py-2 text-sm"
          >
            <span class="drag-handle cursor-grab active:cursor-grabbing text-muted-foreground shrink-0">⋮⋮</span>

            <div class="flex-1 min-w-0">
              <span class="font-medium">{{ item.label }}</span>
              <span class="ml-2 text-xs text-muted-foreground truncate">{{ item.resolved_url }}</span>
            </div>

            <span
              class="shrink-0 text-xs rounded-full px-2 py-0.5"
              :class="item.type === 'page'
                ? 'bg-primary/10 text-primary'
                : 'bg-muted text-muted-foreground'"
            >{{ item.type }}</span>

            <span
              v-if="item.type === 'page' && item.page_status !== 'published'"
              class="shrink-0 text-xs rounded-full px-2 py-0.5 bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400"
            >draft</span>

            <button
              type="button"
              class="shrink-0 text-muted-foreground hover:text-destructive transition-colors text-base leading-none"
              @click="deleteItem(item.id)"
            >&times;</button>
          </div>
        </VueDraggable>
      </div>

      <!-- Right: add item form -->
      <div class="rounded-lg border bg-card p-4 space-y-4">
        <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Add item</p>

        <!-- Type toggle -->
        <div class="flex rounded-md border overflow-hidden text-sm">
          <button
            type="button"
            class="flex-1 px-3 py-1.5 transition-colors"
            :class="addType === 'custom' ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'"
            @click="onTypeChange('custom')"
          >Custom link</button>
          <button
            type="button"
            class="flex-1 px-3 py-1.5 transition-colors"
            :class="addType === 'page' ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'"
            @click="onTypeChange('page')"
          >Page</button>
        </div>

        <form @submit.prevent="submit" class="space-y-3">

          <!-- Page selector -->
          <div v-if="addType === 'page'">
            <label class="text-xs font-medium text-muted-foreground block mb-1">Page</label>
            <select
              :value="form.page_id"
              @change="onPageSelect"
              class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
            >
              <option value="">Select a page…</option>
              <option v-for="page in pages" :key="page.id" :value="page.id">{{ page.title }}</option>
            </select>
            <p v-if="form.errors.page_id" class="mt-1 text-xs text-destructive">{{ form.errors.page_id }}</p>
          </div>

          <!-- Label -->
          <div>
            <label class="text-xs font-medium text-muted-foreground block mb-1">Label</label>
            <input
              v-model="form.label"
              type="text"
              placeholder="e.g. About"
              class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
            />
            <p v-if="form.errors.label" class="mt-1 text-xs text-destructive">{{ form.errors.label }}</p>
          </div>

          <!-- URL (custom only) -->
          <div v-if="addType === 'custom'">
            <label class="text-xs font-medium text-muted-foreground block mb-1">URL</label>
            <input
              v-model="form.url"
              type="text"
              placeholder="e.g. /about or https://example.com"
              class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
            />
            <p v-if="form.errors.url" class="mt-1 text-xs text-destructive">{{ form.errors.url }}</p>
          </div>

          <button
            type="submit"
            :disabled="form.processing"
            class="w-full rounded-md bg-primary px-3 py-1.5 text-sm font-medium text-primary-foreground hover:bg-primary/90 transition-colors disabled:opacity-50"
          >Add item</button>

        </form>
      </div>

    </div>
  </div>
</template>
```

### Step 2: Verify it renders

```bash
npm run build 2>&1 | tail -5
```

Expected: build completes with no errors.

### Step 3: Commit

```bash
git add resources/js/Pages/Navigation/Index.vue
git commit -m "feat: add Navigation/Index.vue admin manager with drag-to-reorder"
```

---

## Task 5: AppLayout.vue — add Navigation sidebar link

**Files:**
- Modify: `resources/js/Layouts/AppLayout.vue`

### Step 1: Add the sidebar link

In `AppLayout.vue`, in the Content section of the sidebar nav, add a Navigation link between Pages and Categories. Look for the Pages `SidebarLink` block and add after it:

```vue
<SidebarLink
  v-if="user.role === 'administrator'"
  :href="route('navigation.index')"
  :active="currentRoute?.startsWith('navigation.')"
>
  <template #icon>
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h7"/>
    </svg>
  </template>
  Navigation
</SidebarLink>
```

### Step 2: Build and verify

```bash
npm run build 2>&1 | tail -5
```

Expected: no errors.

### Step 3: Commit

```bash
git add resources/js/Layouts/AppLayout.vue
git commit -m "feat: add Navigation sidebar link for administrators"
```

---

## Task 6: BlogLayout.vue — render public nav links

**Files:**
- Modify: `resources/js/Layouts/BlogLayout.vue`

### Step 1: Update the layout

In `BlogLayout.vue`, update the `<script setup>` to read `navItems` from page props, and update the header `<nav>` to render those links.

In `<script setup>`, add:

```js
const navItems = computed(() => usePage().props.navItems ?? [])
```

Replace the existing `<nav>` block in the header:

```vue
<!-- OLD -->
<nav>
  <Link
    v-if="authUser"
    :href="route('dashboard')"
    class="text-sm text-muted-foreground hover:text-foreground transition-colors"
  >
    Dashboard
  </Link>
  <Link
    v-else
    :href="route('login')"
    class="text-sm text-muted-foreground hover:text-foreground transition-colors"
  >
    Sign in
  </Link>
</nav>
```

```vue
<!-- NEW -->
<nav class="flex items-center gap-4">
  <template v-for="item in navItems" :key="item.url">
    <a
      v-if="item.url.startsWith('http')"
      :href="item.url"
      target="_blank"
      rel="noopener"
      class="text-sm text-muted-foreground hover:text-foreground transition-colors"
    >{{ item.label }}</a>
    <Link
      v-else
      :href="item.url"
      class="text-sm text-muted-foreground hover:text-foreground transition-colors"
    >{{ item.label }}</Link>
  </template>

  <Link
    v-if="authUser"
    :href="route('dashboard')"
    class="text-sm text-muted-foreground hover:text-foreground transition-colors"
  >Dashboard</Link>
  <Link
    v-else
    :href="route('login')"
    class="text-sm text-muted-foreground hover:text-foreground transition-colors"
  >Sign in</Link>
</nav>
```

### Step 2: Build and verify

```bash
npm run build 2>&1 | tail -5
```

Expected: no errors.

### Step 3: Commit

```bash
git add resources/js/Layouts/BlogLayout.vue
git commit -m "feat: render navItems in public blog header"
```

---

## Task 7: Full build + test suite verification

### Step 1: Run the full test suite

```bash
php artisan test
```

Expected: all previous tests + new NavItemModelTest (4) + NavigationTest (14) pass. Zero failures.

### Step 2: Run the Vite build

```bash
npm run build 2>&1 | tail -5
```

Expected: `✓ built in X.XXs`, no errors.

### Step 3: Push

```bash
git push origin master
```
