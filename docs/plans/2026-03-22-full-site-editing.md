# Full Site Editing + Search Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Let admins build the blog index, single-post, archive, and search-results layouts with the block editor; add a search block and `/search` route; fall back to current hardcoded views when no template is published.

**Architecture:** A new `templates` table (mirrors `pages`) holds per-type block layouts. A `TemplateResolver` service checks for a published template in `BlogController`/`SearchController` and renders `Blog/TemplatePage.vue` instead of the hardcoded views when one exists. Nine new block types expose post/archive/search context via Vue `provide()`/`inject()`, mirroring the existing `LoopItemProvider` pattern.

**Tech Stack:** Laravel 12 (Eloquent, Inertia), Vue 3 Composition API, vue-draggable-plus, lucide-vue-next, Tailwind CSS 4, PHPUnit feature tests.

---

## Task 1: Database migration + Template model

**Files:**
- Create: `database/migrations/TIMESTAMP_create_templates_table.php`
- Create: `app/Models/Template.php`

**Step 1: Write the failing test**

Create `tests/Feature/TemplateTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplateTest extends TestCase
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

    public function test_guest_cannot_access_templates_index(): void
    {
        $this->get('/templates')->assertRedirect('/login');
    }

    public function test_non_admin_cannot_access_templates_index(): void
    {
        $this->actingAs($this->makeUser())->get('/templates')->assertRedirect(route('dashboard'));
    }

    public function test_admin_can_access_templates_index(): void
    {
        $this->actingAs($this->makeAdmin())->get('/templates')->assertOk();
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function test_admin_can_create_template(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->post('/templates', [
            'title'  => 'My Blog Index',
            'type'   => 'blog-index',
            'status' => 'draft',
            'blocks' => [],
        ]);

        $response->assertRedirect('/templates');
        $this->assertDatabaseHas('templates', ['type' => 'blog-index', 'title' => 'My Blog Index']);
    }

    public function test_store_requires_title_and_type(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post('/templates', [])
            ->assertSessionHasErrors(['title', 'type']);
    }

    // ── Publish constraint ────────────────────────────────────────────────────

    public function test_publishing_a_template_unpublishes_previous_same_type(): void
    {
        $admin = $this->makeAdmin();

        $first = Template::create([
            'user_id' => $admin->id,
            'title'   => 'First',
            'type'    => 'blog-index',
            'status'  => 'published',
            'blocks'  => [],
        ]);

        $this->actingAs($admin)->post('/templates', [
            'title'  => 'Second',
            'type'   => 'blog-index',
            'status' => 'published',
            'blocks' => [],
        ]);

        $this->assertEquals('draft', $first->fresh()->status);
        $this->assertDatabaseHas('templates', ['title' => 'Second', 'status' => 'published']);
    }

    // ── Update + Delete ───────────────────────────────────────────────────────

    public function test_admin_can_update_template(): void
    {
        $admin    = $this->makeAdmin();
        $template = Template::create([
            'user_id' => $admin->id,
            'title'   => 'Old',
            'type'    => 'single-post',
            'status'  => 'draft',
            'blocks'  => [],
        ]);

        $this->actingAs($admin)
            ->put("/templates/{$template->id}", ['title' => 'New', 'type' => 'single-post', 'status' => 'draft', 'blocks' => []])
            ->assertRedirect('/templates');

        $this->assertEquals('New', $template->fresh()->title);
    }

    public function test_admin_can_delete_template(): void
    {
        $admin    = $this->makeAdmin();
        $template = Template::create([
            'user_id' => $admin->id,
            'title'   => 'Bye',
            'type'    => 'archive',
            'status'  => 'draft',
            'blocks'  => [],
        ]);

        $this->actingAs($admin)
            ->delete("/templates/{$template->id}")
            ->assertRedirect('/templates');

        $this->assertDatabaseMissing('templates', ['id' => $template->id]);
    }
}
```

**Step 2: Run to confirm it fails**

```bash
php artisan test tests/Feature/TemplateTest.php
```
Expected: errors about missing table / class.

**Step 3: Create the migration**

```bash
php artisan make:migration create_templates_table
```

Edit the generated file:

```php
public function up(): void
{
    Schema::create('templates', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->enum('type', ['blog-index', 'single-post', 'archive', 'search-results']);
        $table->string('title');
        $table->enum('status', ['draft', 'published'])->default('draft');
        $table->json('blocks')->nullable();
        $table->string('meta_title', 100)->nullable();
        $table->string('meta_description', 300)->nullable();
        $table->string('meta_keywords', 255)->nullable();
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('templates');
}
```

**Step 4: Create `app/Models/Template.php`**

```php
<?php

namespace App\Models;

use App\Models\Concerns\HasRevisions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Template extends Model
{
    use HasRevisions;

    protected $fillable = [
        'user_id', 'type', 'title', 'status', 'blocks',
        'meta_title', 'meta_description', 'meta_keywords',
    ];

    protected $casts = ['blocks' => 'array'];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /** Return the one published template for a given type, or null. */
    public static function activeFor(string $type): ?self
    {
        return static::published()->where('type', $type)->first();
    }
}
```

**Step 5: Run migration**

```bash
php artisan migrate
```

**Step 6: Run tests**

```bash
php artisan test tests/Feature/TemplateTest.php
```
Expected: fails on "no route" — that's fine, the model assertions pass. We'll add the controller next.

**Step 7: Commit**

```bash
git add database/migrations/*_create_templates_table.php app/Models/Template.php tests/Feature/TemplateTest.php
git commit -m "feat: add templates table, model, and failing feature tests"
```

---

## Task 2: TemplateResolver service

**Files:**
- Create: `app/Services/TemplateResolver.php`

**Step 1: Write the failing test**

Add to `tests/Feature/TemplateTest.php`:

```php
public function test_template_resolver_returns_null_when_no_published_template(): void
{
    $resolver = new \App\Services\TemplateResolver();
    $this->assertNull($resolver->resolve('blog-index'));
}

public function test_template_resolver_returns_published_template(): void
{
    $admin = $this->makeAdmin();
    Template::create([
        'user_id' => $admin->id, 'title' => 'T', 'type' => 'blog-index',
        'status' => 'published', 'blocks' => [],
    ]);

    $resolver = new \App\Services\TemplateResolver();
    $this->assertNotNull($resolver->resolve('blog-index'));
}
```

**Step 2: Run to confirm failure**

```bash
php artisan test tests/Feature/TemplateTest.php --filter=resolver
```

**Step 3: Create `app/Services/TemplateResolver.php`**

```php
<?php

namespace App\Services;

use App\Models\Template;

class TemplateResolver
{
    /** @var array<string, Template|null> */
    private array $cache = [];

    public function resolve(string $type): ?Template
    {
        if (!array_key_exists($type, $this->cache)) {
            $this->cache[$type] = Template::activeFor($type);
        }

        return $this->cache[$type];
    }
}
```

**Step 4: Run tests**

```bash
php artisan test tests/Feature/TemplateTest.php
```
Expected: resolver tests pass.

**Step 5: Commit**

```bash
git add app/Services/TemplateResolver.php tests/Feature/TemplateTest.php
git commit -m "feat: add TemplateResolver service"
```

---

## Task 3: TemplateController + routes

**Files:**
- Create: `app/Http/Controllers/TemplateController.php`
- Modify: `routes/web.php`

**Step 1: Run tests to confirm they still fail on routes**

```bash
php artisan test tests/Feature/TemplateTest.php
```
Expected: HTTP 404 / redirect failures.

**Step 2: Create `app/Http/Controllers/TemplateController.php`**

```php
<?php

namespace App\Http\Controllers;

use App\Models\Template;
use App\Services\TemplateResolver;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TemplateController extends Controller
{
    public function index()
    {
        $templates = Template::with('creator:id,name')
            ->latest()
            ->get()
            ->map(fn (Template $t) => [
                'id'         => $t->id,
                'title'      => $t->title,
                'type'       => $t->type,
                'status'     => $t->status,
                'updated_at' => $t->updated_at->toDateString(),
                'creator'    => $t->creator->name,
            ])
            ->groupBy('type');

        return Inertia::render('Templates/Index', ['templates' => $templates]);
    }

    public function create(Request $request)
    {
        $request->validate(['type' => ['required', 'in:blog-index,single-post,archive,search-results']]);

        return Inertia::render('Templates/Create', ['type' => $request->type]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'            => ['required', 'string', 'max:255'],
            'type'             => ['required', 'in:blog-index,single-post,archive,search-results'],
            'status'           => ['required', 'in:draft,published'],
            'blocks'           => ['nullable', 'array'],
            'meta_title'       => ['nullable', 'string', 'max:100'],
            'meta_description' => ['nullable', 'string', 'max:300'],
            'meta_keywords'    => ['nullable', 'string', 'max:255'],
        ]);

        if ($validated['status'] === 'published') {
            Template::where('type', $validated['type'])
                ->where('status', 'published')
                ->update(['status' => 'draft']);
        }

        $template = Template::create([...$validated, 'user_id' => auth()->id()]);
        $template->saveRevision(auth()->id());

        return redirect()->route('templates.index')->with('status', 'Template created.');
    }

    public function edit(Template $template)
    {
        $autosave = $template->autosave(auth()->id());

        return Inertia::render('Templates/Edit', [
            'template' => [
                'id'               => $template->id,
                'title'            => $template->title,
                'type'             => $template->type,
                'status'           => $template->status,
                'blocks'           => $template->blocks ?? [],
                'meta_title'       => $template->meta_title,
                'meta_description' => $template->meta_description,
                'meta_keywords'    => $template->meta_keywords,
            ],
            'autosave' => $autosave ? [
                'payload'    => $autosave->payload,
                'updated_at' => $autosave->updated_at->diffForHumans(),
            ] : null,
        ]);
    }

    public function update(Request $request, Template $template)
    {
        $validated = $request->validate([
            'title'            => ['required', 'string', 'max:255'],
            'type'             => ['required', 'in:blog-index,single-post,archive,search-results'],
            'status'           => ['required', 'in:draft,published'],
            'blocks'           => ['nullable', 'array'],
            'meta_title'       => ['nullable', 'string', 'max:100'],
            'meta_description' => ['nullable', 'string', 'max:300'],
            'meta_keywords'    => ['nullable', 'string', 'max:255'],
        ]);

        if ($validated['status'] === 'published') {
            Template::where('type', $validated['type'])
                ->where('id', '!=', $template->id)
                ->where('status', 'published')
                ->update(['status' => 'draft']);
        }

        $template->saveRevision(auth()->id());
        $template->update($validated);

        // Clear autosave on successful save
        $template->autosaves()->where('user_id', auth()->id())->delete();

        return redirect()->route('templates.index')->with('status', 'Template saved.');
    }

    public function destroy(Template $template)
    {
        $template->delete();

        return redirect()->route('templates.index')->with('status', 'Template deleted.');
    }
}
```

**Step 3: Add routes to `routes/web.php`**

Inside the `role:administrator` middleware group, after the pages routes:

```php
Route::resource('templates', TemplateController::class)->except(['show']);
Route::post('/templates/{template}/autosave',   [AutosaveController::class, 'storeTemplate'])->name('templates.autosave');
Route::delete('/templates/{template}/autosave', [AutosaveController::class, 'destroyTemplate'])->name('templates.autosave.destroy');
Route::get('/templates/{template}/revisions',   [RevisionController::class, 'indexTemplate'])->name('templates.revisions');
```

Also add the search route inside the `installed` middleware group, **before** the `/{slug}` catch-all:

```php
Route::get('/search', [SearchController::class, 'index'])->name('search');
```

Also add `templates` to the catch-all exclusion regex so `/{slug}` doesn't intercept `/templates/...`:
Find the line with `->where('slug', '^(?!login|...` and add `templates|` to the list.

**Step 4: Add autosave/revision methods for templates**

In `app/Http/Controllers/AutosaveController.php`, add:

```php
public function storeTemplate(Request $request, Template $template)
{
    $this->authorize('update', $template); // or gate check

    $template->autosaves()->updateOrCreate(
        ['user_id' => auth()->id()],
        ['payload' => $request->validate(['payload' => 'required|array'])['payload']]
    );

    return response()->json(['saved_at' => now()->toTimeString()]);
}

public function destroyTemplate(Template $template)
{
    $template->autosaves()->where('user_id', auth()->id())->delete();
    return response()->noContent();
}
```

In `app/Http/Controllers/RevisionController.php`, add:

```php
public function indexTemplate(Template $template)
{
    $revisions = $template->revisions()
        ->with('user:id,name')
        ->orderByDesc('id')
        ->get()
        ->map(fn ($r) => [
            'id'         => $r->id,
            'user'       => $r->user->name,
            'created_at' => $r->created_at->diffForHumans(),
        ]);

    return response()->json($revisions);
}
```

Also ensure `Template` has the `autosave()` helper. Check `Page` model — if `autosave()` is defined on the model directly (not a trait), add the same to `Template`:

```php
// In app/Models/Template.php
public function autosaves()
{
    return $this->morphMany(\App\Models\Autosave::class, 'autosaveable');
}

public function autosave(int $userId): ?\App\Models\Autosave
{
    return $this->autosaves()->where('user_id', $userId)->first();
}
```

**Step 5: Run tests**

```bash
php artisan test tests/Feature/TemplateTest.php
```
Expected: all tests pass.

**Step 6: Run full test suite**

```bash
php artisan test
```
Expected: all existing tests still pass.

**Step 7: Commit**

```bash
git add app/Http/Controllers/TemplateController.php app/Http/Controllers/AutosaveController.php app/Http/Controllers/RevisionController.php routes/web.php app/Models/Template.php
git commit -m "feat: add TemplateController, routes, and autosave/revision support for templates"
```

---

## Task 4: Templates admin Vue pages

**Files:**
- Create: `resources/js/Pages/Templates/Index.vue`
- Create: `resources/js/Pages/Templates/Create.vue`
- Create: `resources/js/Pages/Templates/Edit.vue`
- Modify: `resources/js/Layouts/AppLayout.vue`

**Step 1: Create `resources/js/Pages/Templates/Index.vue`**

```vue
<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link } from '@inertiajs/vue3'
import { LayoutTemplate, Plus, Pencil, Trash2 } from 'lucide-vue-next'
import { router } from '@inertiajs/vue3'

const props = defineProps({ templates: Object })

const TYPE_LABELS = {
  'blog-index':     'Blog Index',
  'single-post':    'Single Post',
  'archive':        'Archive',
  'search-results': 'Search Results',
}

const ALL_TYPES = ['blog-index', 'single-post', 'archive', 'search-results']

function deleteTemplate(id) {
  if (!confirm('Delete this template?')) return
  router.delete(`/templates/${id}`)
}
</script>

<template>
  <AppLayout title="Templates">
    <div class="max-w-5xl mx-auto px-6 py-8 space-y-8">
      <div class="flex items-center justify-between">
        <h1 class="text-xl font-semibold">Templates</h1>
        <!-- Type picker — open a modal or link with ?type= -->
        <div class="flex gap-2 flex-wrap">
          <Link v-for="type in ALL_TYPES" :key="type"
            :href="`/templates/create?type=${type}`"
            class="inline-flex items-center gap-1.5 text-xs px-3 py-1.5 rounded-md border border-dashed border-border hover:border-foreground hover:text-foreground text-muted-foreground transition-colors"
          >
            <Plus class="w-3 h-3" />
            New {{ TYPE_LABELS[type] }}
          </Link>
        </div>
      </div>

      <div v-for="type in ALL_TYPES" :key="type" class="space-y-2">
        <h2 class="text-sm font-medium text-muted-foreground uppercase tracking-wider">
          {{ TYPE_LABELS[type] }}
        </h2>
        <div v-if="templates[type]?.length" class="border border-border rounded-lg divide-y divide-border">
          <div v-for="t in templates[type]" :key="t.id"
            class="flex items-center gap-4 px-4 py-3">
            <LayoutTemplate class="w-4 h-4 text-muted-foreground shrink-0" />
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium truncate">{{ t.title }}</p>
              <p class="text-xs text-muted-foreground">{{ t.creator }} · {{ t.updated_at }}</p>
            </div>
            <span class="text-xs px-2 py-0.5 rounded-full border"
              :class="t.status === 'published'
                ? 'bg-green-500/10 text-green-600 border-green-500/20'
                : 'bg-muted text-muted-foreground border-border'">
              {{ t.status }}
            </span>
            <div class="flex gap-1">
              <Link :href="`/templates/${t.id}/edit`"
                class="p-1.5 rounded hover:bg-muted transition-colors">
                <Pencil class="w-3.5 h-3.5" />
              </Link>
              <button @click="deleteTemplate(t.id)"
                class="p-1.5 rounded hover:bg-destructive/10 hover:text-destructive transition-colors">
                <Trash2 class="w-3.5 h-3.5" />
              </button>
            </div>
          </div>
        </div>
        <p v-else class="text-sm text-muted-foreground italic py-2">No templates yet.</p>
      </div>
    </div>
  </AppLayout>
</template>
```

**Step 2: Create `resources/js/Pages/Templates/Create.vue`**

Copy `resources/js/Pages/Pages/Create.vue` as the base. Key differences:
- Replace `'Pages/Create'` → `'Templates/Create'` in the Inertia page component name (via `defineOptions`)
- Add a `type` prop (passed from controller)
- Add a hidden `type` field to the form: `form.type = props.type`
- Change form POST target to `/templates`
- Change redirect label from "Pages" to "Templates"
- The block editor and meta card (status, SEO) remain identical

```vue
<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import BlockEditor from '@/Components/BlockEditor/BlockEditor.vue'
import { useForm, Link } from '@inertiajs/vue3'
import { ref } from 'vue'
import { ArrowLeft, ChevronDown } from 'lucide-vue-next'

const props = defineProps({
  type: { type: String, required: true },
})

const TYPE_LABELS = {
  'blog-index':     'Blog Index',
  'single-post':    'Single Post',
  'archive':        'Archive',
  'search-results': 'Search Results',
}

const form = useForm({
  title:            '',
  type:             props.type,
  status:           'draft',
  blocks:           [],
  meta_title:       '',
  meta_description: '',
  meta_keywords:    '',
})

const seoOpen = ref(false)

function submit() {
  form.post('/templates')
}
</script>

<template>
  <AppLayout :title="`New ${TYPE_LABELS[type]} Template`">
    <form @submit.prevent="submit" class="space-y-4">
      <!-- Header bar -->
      <div class="sticky top-0 z-10 bg-background border-b border-border px-6 py-3 flex items-center gap-4">
        <Link href="/templates" class="text-muted-foreground hover:text-foreground transition-colors">
          <ArrowLeft class="w-4 h-4" />
        </Link>
        <h1 class="text-sm font-medium flex-1">New {{ TYPE_LABELS[type] }} Template</h1>
        <button type="submit" :disabled="form.processing"
          class="px-4 py-1.5 bg-primary text-primary-foreground text-sm rounded-md hover:bg-primary/90 transition-colors disabled:opacity-50">
          {{ form.processing ? 'Saving…' : 'Save' }}
        </button>
      </div>

      <div class="px-6 space-y-4">
        <!-- Meta card -->
        <div class="border border-border rounded-lg p-4 space-y-4">
          <input v-model="form.title" type="text" placeholder="Template title…"
            class="w-full text-xl font-semibold bg-transparent border-none outline-none placeholder:text-muted-foreground" />
          <p v-if="form.errors.title" class="text-xs text-destructive">{{ form.errors.title }}</p>

          <div class="flex flex-wrap items-center gap-4 pt-2 border-t border-border">
            <!-- Status -->
            <div class="flex items-center gap-3 text-sm">
              <span class="text-muted-foreground text-xs font-medium">Status</span>
              <label class="flex items-center gap-1.5 cursor-pointer">
                <input type="radio" v-model="form.status" value="draft" class="accent-primary" /> Draft
              </label>
              <label class="flex items-center gap-1.5 cursor-pointer">
                <input type="radio" v-model="form.status" value="published" class="accent-primary" /> Published
              </label>
            </div>

            <!-- SEO toggle -->
            <button type="button" @click="seoOpen = !seoOpen"
              class="flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground transition-colors ml-auto">
              SEO
              <ChevronDown class="w-3 h-3 transition-transform" :class="{ 'rotate-180': seoOpen }" />
            </button>
          </div>

          <!-- SEO fields -->
          <div v-if="seoOpen" class="grid grid-cols-1 lg:grid-cols-3 gap-3 pt-2 border-t border-border">
            <div>
              <label class="text-xs text-muted-foreground block mb-1">Meta title</label>
              <input v-model="form.meta_title" type="text" maxlength="100"
                class="w-full rounded border border-border bg-background px-2 py-1.5 text-sm" />
            </div>
            <div>
              <label class="text-xs text-muted-foreground block mb-1">Meta description</label>
              <input v-model="form.meta_description" type="text" maxlength="300"
                class="w-full rounded border border-border bg-background px-2 py-1.5 text-sm" />
            </div>
            <div>
              <label class="text-xs text-muted-foreground block mb-1">Meta keywords</label>
              <input v-model="form.meta_keywords" type="text" maxlength="255"
                class="w-full rounded border border-border bg-background px-2 py-1.5 text-sm" />
            </div>
          </div>
        </div>

        <!-- Block editor -->
        <BlockEditor :template-type="type" v-model="form.blocks" />
      </div>
    </form>
  </AppLayout>
</template>
```

**Step 3: Create `resources/js/Pages/Templates/Edit.vue`**

Copy `Create.vue`, change to `PUT /templates/{id}`, pre-populate from `template` prop (same as `Pages/Edit.vue` pattern). Include autosave watcher and revisions panel identical to `Pages/Edit.vue`.

Key diff from Create:
```js
const props = defineProps({
  template: Object,
  autosave: Object,
})

const form = useForm({
  title:            props.template.title,
  type:             props.template.type,
  status:           props.template.status,
  blocks:           props.template.blocks ?? [],
  meta_title:       props.template.meta_title ?? '',
  meta_description: props.template.meta_description ?? '',
  meta_keywords:    props.template.meta_keywords ?? '',
})

function submit() {
  form.put(`/templates/${props.template.id}`)
}
```

**Step 4: Add Templates to sidebar in `resources/js/Layouts/AppLayout.vue`**

Find the navigation links array / section that contains Pages and Navigation. Add after Pages:

```vue
<SidebarMenuItem>
  <SidebarMenuButton as-child :is-active="currentRoute?.startsWith('templates')">
    <Link href="/templates">
      <LayoutTemplate class="w-4 h-4" />
      <span>Templates</span>
    </Link>
  </SidebarMenuButton>
</SidebarMenuItem>
```

Import `LayoutTemplate` from `lucide-vue-next` at the top of the file.

**Step 5: Build and smoke-test in browser**

```bash
npm run build
```
Visit `/templates` in the browser, create a template of each type, verify it appears in the index grouped by type. Verify published → auto-draft of previous works.

**Step 6: Commit**

```bash
git add resources/js/Pages/Templates/ resources/js/Layouts/AppLayout.vue
git commit -m "feat: add Templates admin pages and sidebar link"
```

---

## Task 5: New block type — `search`

**Files:**
- Create: `resources/js/Components/BlockEditor/blocks/SearchSettings.vue`
- Create: `resources/js/Components/Blocks/SearchBlock.vue`
- Modify: `resources/js/Components/BlockEditor/BlockTypePanel.vue`
- Modify: `resources/js/Components/BlockRenderer.vue`

**Step 1: Create `resources/js/Components/BlockEditor/blocks/SearchSettings.vue`**

```vue
<script setup>
const props = defineProps({ block: Object })
const emit = defineEmits(['update'])
function update(key, value) {
  emit('update', { data: { ...props.block.data, [key]: value } })
}
</script>

<template>
  <div class="space-y-3 p-3">
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Placeholder</label>
      <input type="text" :value="block.data?.placeholder ?? 'Search…'"
        @input="update('placeholder', $event.target.value)"
        class="w-full rounded-md border border-border bg-background px-2 py-1.5 text-xs" />
    </div>
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Button label</label>
      <input type="text" :value="block.data?.buttonLabel ?? 'Search'"
        @input="update('buttonLabel', $event.target.value)"
        class="w-full rounded-md border border-border bg-background px-2 py-1.5 text-xs" />
    </div>
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Scope</label>
      <select :value="block.data?.scope ?? 'posts'"
        @change="update('scope', $event.target.value)"
        class="w-full rounded-md border border-border bg-background px-2 py-1.5 text-xs">
        <option value="posts">Posts only</option>
        <option value="all">Posts + Pages</option>
      </select>
    </div>
  </div>
</template>
```

**Step 2: Create `resources/js/Components/Blocks/SearchBlock.vue`**

```vue
<script setup>
import { usePage } from '@inertiajs/vue3'

const props = defineProps({ block: Object })

// Pre-fill input when on the search results page
const currentQ = typeof window !== 'undefined'
  ? new URLSearchParams(window.location.search).get('q') ?? ''
  : ''
</script>

<template>
  <form method="GET" action="/search" class="flex gap-2">
    <input
      type="text"
      name="q"
      :placeholder="block.data?.placeholder ?? 'Search…'"
      :value="currentQ"
      class="flex-1 rounded-md border border-border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary/40"
    />
    <button type="submit"
      class="px-4 py-2 bg-primary text-primary-foreground text-sm rounded-md hover:bg-primary/90 transition-colors">
      {{ block.data?.buttonLabel ?? 'Search' }}
    </button>
  </form>
</template>
```

**Step 3: Register in `BlockTypePanel.vue`**

Open `BlockTypePanel.vue`. In the `ALL_TYPES` array, add a new entry in the appropriate group (or create a new "Search" group):

```js
{ type: 'search', label: 'Search', icon: Search, group: 'Interactive',
  defaultData: { placeholder: 'Search…', buttonLabel: 'Search', scope: 'posts' } },
```

Import `Search` from `lucide-vue-next`.

**Step 4: Register in `BlockRenderer.vue`**

```js
import SearchBlock from '@/Components/Blocks/SearchBlock.vue'

// In BLOCK_MAP:
search: SearchBlock,
```

**Step 5: Register settings in `BlockLayers.vue`**

In the `SETTINGS_MAP` (or equivalent) where block type → settings component is mapped:

```js
import SearchSettings from '@/Components/BlockEditor/blocks/SearchSettings.vue'
// ...
search: SearchSettings,
```

**Step 6: Build and smoke-test**

```bash
npm run build
```
Open a page/template in the block editor, drag a "Search" block onto the canvas, verify settings panel shows placeholder/button/scope fields.

**Step 7: Commit**

```bash
git add resources/js/Components/BlockEditor/blocks/SearchSettings.vue resources/js/Components/Blocks/SearchBlock.vue resources/js/Components/BlockEditor/BlockTypePanel.vue resources/js/Components/BlockRenderer.vue resources/js/Components/BlockEditor/BlockLayers.vue
git commit -m "feat: add search block type (editor settings + public renderer)"
```

---

## Task 6: Post-context block types (editor settings)

**Files:**
- Create: `resources/js/Components/BlockEditor/blocks/PostTitleSettings.vue`
- Create: `resources/js/Components/BlockEditor/blocks/PostBodySettings.vue`
- Create: `resources/js/Components/BlockEditor/blocks/PostFeaturedImageSettings.vue`
- Create: `resources/js/Components/BlockEditor/blocks/PostMetaSettings.vue`
- Create: `resources/js/Components/BlockEditor/blocks/PostAuthorSettings.vue`
- Create: `resources/js/Components/BlockEditor/blocks/PostTaxonomySettings.vue`
- Create: `resources/js/Components/BlockEditor/blocks/ArchiveTitleSettings.vue`
- Modify: `resources/js/Components/BlockEditor/BlockTypePanel.vue`
- Modify: `resources/js/Components/BlockEditor/BlockLayers.vue`

**Step 1: `PostTitleSettings.vue`**

```vue
<script setup>
const props = defineProps({ block: Object })
const emit = defineEmits(['update'])
function update(key, val) { emit('update', { data: { ...props.block.data, [key]: val } }) }
</script>

<template>
  <div class="space-y-3 p-3">
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Tag</label>
      <select :value="block.data?.tag ?? 'h1'" @change="update('tag', $event.target.value)"
        class="w-full rounded-md border border-border bg-background px-2 py-1.5 text-xs">
        <option>h1</option><option>h2</option><option>h3</option>
      </select>
    </div>
  </div>
</template>
```

**Step 2: `PostBodySettings.vue`** — No user-configurable settings:

```vue
<template>
  <div class="p-3 text-xs text-muted-foreground">Renders the full post content. No settings.</div>
</template>
```

**Step 3: `PostFeaturedImageSettings.vue`**

```vue
<script setup>
const props = defineProps({ block: Object })
const emit = defineEmits(['update'])
function update(key, val) { emit('update', { data: { ...props.block.data, [key]: val } }) }
</script>

<template>
  <div class="space-y-3 p-3">
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Max width</label>
      <input type="text" :value="block.data?.maxWidth ?? '100%'"
        @input="update('maxWidth', $event.target.value)"
        class="w-full rounded-md border border-border bg-background px-2 py-1.5 text-xs" />
    </div>
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Aspect ratio</label>
      <select :value="block.data?.aspectRatio ?? 'auto'" @change="update('aspectRatio', $event.target.value)"
        class="w-full rounded-md border border-border bg-background px-2 py-1.5 text-xs">
        <option value="auto">Auto</option>
        <option value="16/9">16:9</option>
        <option value="4/3">4:3</option>
        <option value="1/1">1:1</option>
      </select>
    </div>
  </div>
</template>
```

**Step 4: `PostMetaSettings.vue`**

```vue
<script setup>
const props = defineProps({ block: Object })
const emit = defineEmits(['update'])
function update(key, val) { emit('update', { data: { ...props.block.data, [key]: val } }) }
</script>

<template>
  <div class="space-y-2 p-3">
    <p class="text-xs font-medium text-muted-foreground mb-2">Show fields</p>
    <label v-for="field in ['date', 'author', 'readTime']" :key="field"
      class="flex items-center gap-2 text-xs cursor-pointer">
      <input type="checkbox"
        :checked="block.data?.[field] !== false"
        @change="update(field, $event.target.checked)"
        class="accent-primary" />
      {{ { date: 'Published date', author: 'Author name', readTime: 'Read time' }[field] }}
    </label>
  </div>
</template>
```

**Step 5: `PostAuthorSettings.vue`**

```vue
<script setup>
const props = defineProps({ block: Object })
const emit = defineEmits(['update'])
function update(key, val) { emit('update', { data: { ...props.block.data, [key]: val } }) }
</script>

<template>
  <div class="space-y-2 p-3">
    <label class="flex items-center gap-2 text-xs cursor-pointer">
      <input type="checkbox"
        :checked="block.data?.showAvatar !== false"
        @change="update('showAvatar', $event.target.checked)"
        class="accent-primary" />
      Show avatar
    </label>
  </div>
</template>
```

**Step 6: `PostTaxonomySettings.vue`**

```vue
<script setup>
const props = defineProps({ block: Object })
const emit = defineEmits(['update'])
function update(key, val) { emit('update', { data: { ...props.block.data, [key]: val } }) }
</script>

<template>
  <div class="space-y-2 p-3">
    <label class="flex items-center gap-2 text-xs cursor-pointer">
      <input type="checkbox"
        :checked="block.data?.showCategories !== false"
        @change="update('showCategories', $event.target.checked)"
        class="accent-primary" />
      Show categories
    </label>
    <label class="flex items-center gap-2 text-xs cursor-pointer">
      <input type="checkbox"
        :checked="block.data?.showTags !== false"
        @change="update('showTags', $event.target.checked)"
        class="accent-primary" />
      Show tags
    </label>
  </div>
</template>
```

**Step 7: `ArchiveTitleSettings.vue`**

```vue
<script setup>
const props = defineProps({ block: Object })
const emit = defineEmits(['update'])
function update(key, val) { emit('update', { data: { ...props.block.data, [key]: val } }) }
</script>

<template>
  <div class="p-3">
    <label class="text-xs font-medium text-muted-foreground block mb-1">Tag</label>
    <select :value="block.data?.tag ?? 'h1'" @change="update('tag', $event.target.value)"
      class="w-full rounded-md border border-border bg-background px-2 py-1.5 text-xs">
      <option>h1</option><option>h2</option><option>h3</option>
    </select>
  </div>
</template>
```

**Step 8: Register all in `BlockTypePanel.vue`**

Add a **"Post"** group and an **"Archive"** group to `ALL_TYPES`:

```js
// Post context group
{ type: 'post-title',          label: 'Post Title',          icon: Heading1,    group: 'Post', defaultData: { tag: 'h1' } },
{ type: 'post-body',           label: 'Post Body',           icon: AlignLeft,   group: 'Post', defaultData: {} },
{ type: 'post-featured-image', label: 'Featured Image',      icon: ImageIcon,   group: 'Post', defaultData: { maxWidth: '100%', aspectRatio: 'auto' } },
{ type: 'post-meta',           label: 'Post Meta',           icon: Info,        group: 'Post', defaultData: { date: true, author: true, readTime: true } },
{ type: 'post-author',         label: 'Author',              icon: User,        group: 'Post', defaultData: { showAvatar: true } },
{ type: 'post-taxonomy',       label: 'Categories & Tags',   icon: Tag,         group: 'Post', defaultData: { showCategories: true, showTags: true } },
{ type: 'post-comments',       label: 'Comments',            icon: MessageCircle, group: 'Post', defaultData: {} },
// Archive context group
{ type: 'archive-title',       label: 'Archive Title',       icon: FolderOpen,  group: 'Archive', defaultData: { tag: 'h1' } },
{ type: 'archive-loop',        label: 'Archive Loop',        icon: List,        group: 'Archive', defaultData: { columns: 3, gap: 6, limit: 12 } },
```

Import the needed icons from `lucide-vue-next` (add any missing: `Heading1, AlignLeft, Info, MessageCircle, FolderOpen, List`).

**Step 9: Register settings in `BlockLayers.vue`**

```js
import PostTitleSettings        from '@/Components/BlockEditor/blocks/PostTitleSettings.vue'
import PostBodySettings         from '@/Components/BlockEditor/blocks/PostBodySettings.vue'
import PostFeaturedImageSettings from '@/Components/BlockEditor/blocks/PostFeaturedImageSettings.vue'
import PostMetaSettings         from '@/Components/BlockEditor/blocks/PostMetaSettings.vue'
import PostAuthorSettings       from '@/Components/BlockEditor/blocks/PostAuthorSettings.vue'
import PostTaxonomySettings     from '@/Components/BlockEditor/blocks/PostTaxonomySettings.vue'
import ArchiveTitleSettings     from '@/Components/BlockEditor/blocks/ArchiveTitleSettings.vue'

// Add to SETTINGS_MAP:
'post-title':          PostTitleSettings,
'post-body':           PostBodySettings,
'post-featured-image': PostFeaturedImageSettings,
'post-meta':           PostMetaSettings,
'post-author':         PostAuthorSettings,
'post-taxonomy':       PostTaxonomySettings,
'post-comments':       null, // no settings panel
'archive-title':       ArchiveTitleSettings,
'archive-loop':        LoopSettings, // reuse existing LoopSettings component
```

**Step 10: Build and smoke-test**

```bash
npm run build
```
Open a template editor, verify "Post" and "Archive" groups appear in the palette with correct icons and [post] context badge.

**Step 11: Commit**

```bash
git add resources/js/Components/BlockEditor/blocks/Post*.vue resources/js/Components/BlockEditor/blocks/ArchiveTitleSettings.vue resources/js/Components/BlockEditor/BlockTypePanel.vue resources/js/Components/BlockEditor/BlockLayers.vue
git commit -m "feat: add post-context and archive-context block settings components"
```

---

## Task 7: Post-context public block renderers

**Files:**
- Create: `resources/js/Components/Blocks/PostTitleBlock.vue`
- Create: `resources/js/Components/Blocks/PostBodyBlock.vue`
- Create: `resources/js/Components/Blocks/PostFeaturedImageBlock.vue`
- Create: `resources/js/Components/Blocks/PostMetaBlock.vue`
- Create: `resources/js/Components/Blocks/PostAuthorBlock.vue`
- Create: `resources/js/Components/Blocks/PostTaxonomyBlock.vue`
- Create: `resources/js/Components/Blocks/PostCommentsBlock.vue`
- Create: `resources/js/Components/Blocks/ArchiveTitleBlock.vue`
- Modify: `resources/js/Components/BlockRenderer.vue`

All post-context blocks use `inject('postContext', null)` to get the current post. If `postContext` is null (block used outside a single-post template), they render a muted placeholder.

**Step 1: Create `PostTitleBlock.vue`**

```vue
<script setup>
import { inject } from 'vue'
const props = defineProps({ block: Object })
const post = inject('postContext', null)
const tag = props.block.data?.tag ?? 'h1'
</script>

<template>
  <component :is="tag" v-if="post" class="font-bold leading-tight">{{ post.title }}</component>
  <div v-else class="h-8 rounded bg-muted/40 animate-pulse w-2/3" />
</template>
```

**Step 2: Create `PostBodyBlock.vue`**

```vue
<script setup>
import { inject } from 'vue'
import BlockRenderer from '@/Components/BlockRenderer.vue'
const post = inject('postContext', null)
</script>

<template>
  <div v-if="post">
    <BlockRenderer v-if="post.use_block_editor && post.blocks?.length" :blocks="post.blocks" />
    <div v-else class="prose dark:prose-invert max-w-none" v-html="post.body" />
  </div>
  <div v-else class="space-y-2">
    <div class="h-4 rounded bg-muted/40 animate-pulse w-full" />
    <div class="h-4 rounded bg-muted/40 animate-pulse w-5/6" />
    <div class="h-4 rounded bg-muted/40 animate-pulse w-4/6" />
  </div>
</template>
```

**Step 3: Create `PostFeaturedImageBlock.vue`**

```vue
<script setup>
import { inject } from 'vue'
const props = defineProps({ block: Object })
const post = inject('postContext', null)
const maxWidth = props.block.data?.maxWidth ?? '100%'
const aspectRatio = props.block.data?.aspectRatio ?? 'auto'
</script>

<template>
  <div v-if="post?.featured_image_url" :style="{ maxWidth }">
    <img :src="post.featured_image_url" :alt="post.featured_image_alt ?? post.title"
      class="w-full rounded-lg object-cover"
      :style="aspectRatio !== 'auto' ? { aspectRatio } : {}" />
  </div>
  <div v-else class="rounded-lg bg-muted/40 animate-pulse w-full aspect-video" />
</template>
```

**Step 4: Create `PostMetaBlock.vue`**

```vue
<script setup>
import { inject, computed } from 'vue'
const props = defineProps({ block: Object })
const post = inject('postContext', null)
const show = (field) => props.block.data?.[field] !== false
const readTime = computed(() => {
  if (!post?.body) return null
  const words = post.body.replace(/<[^>]+>/g, ' ').split(/\s+/).filter(Boolean).length
  return Math.ceil(words / 200) + ' min read'
})
</script>

<template>
  <div v-if="post" class="flex flex-wrap gap-3 text-sm text-muted-foreground">
    <span v-if="show('date')">{{ post.published_at }}</span>
    <span v-if="show('author')">by {{ post.author?.name }}</span>
    <span v-if="show('readTime') && readTime">{{ readTime }}</span>
  </div>
  <div v-else class="h-4 rounded bg-muted/40 animate-pulse w-48" />
</template>
```

**Step 5: Create `PostAuthorBlock.vue`**

```vue
<script setup>
import { inject } from 'vue'
const props = defineProps({ block: Object })
const post = inject('postContext', null)
const showAvatar = props.block.data?.showAvatar !== false
</script>

<template>
  <div v-if="post" class="flex items-center gap-3">
    <img v-if="showAvatar && post.author?.avatar_url"
      :src="post.author.avatar_url" :alt="post.author.name"
      class="w-10 h-10 rounded-full object-cover" />
    <div v-else-if="showAvatar"
      class="w-10 h-10 rounded-full bg-muted flex items-center justify-center text-sm font-semibold">
      {{ post.author?.name?.[0] ?? '?' }}
    </div>
    <div>
      <p class="font-medium text-sm">{{ post.author?.name }}</p>
    </div>
  </div>
  <div v-else class="flex items-center gap-3">
    <div class="w-10 h-10 rounded-full bg-muted/40 animate-pulse" />
    <div class="h-4 w-32 rounded bg-muted/40 animate-pulse" />
  </div>
</template>
```

**Step 6: Create `PostTaxonomyBlock.vue`**

```vue
<script setup>
import { inject } from 'vue'
const props = defineProps({ block: Object })
const post = inject('postContext', null)
const showCategories = props.block.data?.showCategories !== false
const showTags       = props.block.data?.showTags !== false
</script>

<template>
  <div v-if="post" class="flex flex-wrap gap-2">
    <a v-if="showCategories" v-for="cat in post.categories" :key="cat.id"
      :href="`/blog/category/${cat.slug}`"
      class="text-xs px-2.5 py-1 rounded-full bg-primary/10 text-primary hover:bg-primary/20 transition-colors">
      {{ cat.name }}
    </a>
    <a v-if="showTags" v-for="tag in post.tags" :key="tag.slug"
      :href="`/blog/tag/${tag.slug}`"
      class="text-xs px-2.5 py-1 rounded-full border border-border text-muted-foreground hover:text-foreground hover:border-foreground transition-colors">
      #{{ tag.name }}
    </a>
  </div>
  <div v-else class="flex gap-2">
    <div class="h-6 w-16 rounded-full bg-muted/40 animate-pulse" />
    <div class="h-6 w-20 rounded-full bg-muted/40 animate-pulse" />
  </div>
</template>
```

**Step 7: Create `PostCommentsBlock.vue`**

This block renders the existing comments section. The comments data lives in the page props when using a template. Inject `postContext` and read `commentsData` from it:

```vue
<script setup>
import { inject } from 'vue'
// The TemplatePage.vue will provide commentsData alongside postContext
const post        = inject('postContext', null)
const commentsData = inject('commentsData', null)
</script>

<template>
  <div v-if="post">
    <!-- Reuse the existing comments component from Blog/Show.vue -->
    <!-- For now render a minimal version; full wiring done in Task 9 -->
    <p class="text-sm text-muted-foreground">Comments section ({{ commentsData?.total ?? 0 }} comments)</p>
  </div>
  <div v-else class="h-24 rounded bg-muted/40 animate-pulse" />
</template>
```

**Step 8: Create `ArchiveTitleBlock.vue`**

```vue
<script setup>
import { inject } from 'vue'
const props = defineProps({ block: Object })
const archive = inject('archiveContext', null)
const tag = props.block.data?.tag ?? 'h1'
</script>

<template>
  <component :is="tag" v-if="archive" class="font-bold leading-tight">
    {{ archive.type === 'category' ? 'Category: ' : 'Tag: ' }}{{ archive.name }}
  </component>
  <div v-else class="h-8 rounded bg-muted/40 animate-pulse w-1/2" />
</template>
```

**Step 9: Register all in `BlockRenderer.vue`**

```js
import PostTitleBlock         from '@/Components/Blocks/PostTitleBlock.vue'
import PostBodyBlock          from '@/Components/Blocks/PostBodyBlock.vue'
import PostFeaturedImageBlock from '@/Components/Blocks/PostFeaturedImageBlock.vue'
import PostMetaBlock          from '@/Components/Blocks/PostMetaBlock.vue'
import PostAuthorBlock        from '@/Components/Blocks/PostAuthorBlock.vue'
import PostTaxonomyBlock      from '@/Components/Blocks/PostTaxonomyBlock.vue'
import PostCommentsBlock      from '@/Components/Blocks/PostCommentsBlock.vue'
import ArchiveTitleBlock      from '@/Components/Blocks/ArchiveTitleBlock.vue'

// In BLOCK_MAP add:
'post-title':          PostTitleBlock,
'post-body':           PostBodyBlock,
'post-featured-image': PostFeaturedImageBlock,
'post-meta':           PostMetaBlock,
'post-author':         PostAuthorBlock,
'post-taxonomy':       PostTaxonomyBlock,
'post-comments':       PostCommentsBlock,
'archive-title':       ArchiveTitleBlock,
'archive-loop':        LoopBlock, // reuse existing LoopBlock
```

**Step 10: Build and verify no console errors**

```bash
npm run build
```

**Step 11: Commit**

```bash
git add resources/js/Components/Blocks/Post*.vue resources/js/Components/Blocks/ArchiveTitleBlock.vue resources/js/Components/BlockRenderer.vue
git commit -m "feat: add post-context and archive-context public block renderers"
```

---

## Task 8: Blog/TemplatePage.vue + context providers

**Files:**
- Create: `resources/js/Pages/Blog/TemplatePage.vue`

**Step 1: Create `resources/js/Pages/Blog/TemplatePage.vue`**

```vue
<script setup>
import { provide } from 'vue'
import { defineOptions } from 'vue'
import BlockRenderer from '@/Components/BlockRenderer.vue'
import { Head } from '@inertiajs/vue3'

defineOptions({ layout: null })

const props = defineProps({
  blocks:         { type: Array,  default: () => [] },
  postContext:    { type: Object, default: null },
  archiveContext: { type: Object, default: null },
  searchContext:  { type: Object, default: null },
  commentsData:   { type: Object, default: null },
  seo:            { type: Object, default: () => ({}) },
})

// Make all contexts available to any block in the tree
provide('postContext',    props.postContext)
provide('archiveContext', props.archiveContext)
provide('searchContext',  props.searchContext)
provide('commentsData',   props.commentsData)
</script>

<template>
  <Head>
    <title>{{ seo.title ?? '' }}</title>
    <meta v-if="seo.description" name="description" :content="seo.description" />
    <meta v-if="seo.keywords"    name="keywords"    :content="seo.keywords" />
    <meta property="og:title"   :content="seo.title ?? ''" />
    <meta property="og:description" :content="seo.description ?? ''" />
    <meta v-if="seo.image" property="og:image" :content="seo.image" />
  </Head>

  <BlockRenderer :blocks="blocks" />
</template>
```

Note: `layout: null` — the template IS the full layout. The admin controls header/footer by adding blocks. If the user wants the standard AppLayout chrome, they should not use layout:null — but for a public-facing page template this is correct (same as `Blog/Show.vue` which uses its own layout).

Actually check how `Blog/Show.vue` handles layout — if it uses a public `PublicLayout`, do the same. If it doesn't define one and defaults to `AppLayout`, use that. Adjust accordingly.

**Step 2: Build**

```bash
npm run build
```

**Step 3: Commit**

```bash
git add resources/js/Pages/Blog/TemplatePage.vue
git commit -m "feat: add Blog/TemplatePage with context provide() for FSE rendering"
```

---

## Task 9: BlogController — template resolution

**Files:**
- Modify: `app/Http/Controllers/BlogController.php`

**Step 1: Write the failing test**

Add to `tests/Feature/TemplateTest.php`:

```php
public function test_blog_index_uses_template_when_published(): void
{
    $admin = $this->makeAdmin();

    Template::create([
        'user_id' => $admin->id,
        'title'   => 'Blog Index Template',
        'type'    => 'blog-index',
        'status'  => 'published',
        'blocks'  => [],
    ]);

    $response = $this->get('/');
    $response->assertInertia(fn ($page) => $page->component('Blog/TemplatePage'));
}

public function test_blog_index_uses_default_view_without_template(): void
{
    $response = $this->get('/');
    $response->assertInertia(fn ($page) => $page->component('Blog/Index'));
}

public function test_single_post_uses_template_when_published(): void
{
    $admin = $this->makeAdmin();

    $post = Post::factory()->create(['status' => 'published', 'published_at' => now()]);

    Template::create([
        'user_id' => $admin->id,
        'title'   => 'Single Post Template',
        'type'    => 'single-post',
        'status'  => 'published',
        'blocks'  => [],
    ]);

    $response = $this->get("/blog/{$post->slug}");
    $response->assertInertia(fn ($page) => $page->component('Blog/TemplatePage'));
}
```

**Step 2: Run to confirm failure**

```bash
php artisan test tests/Feature/TemplateTest.php --filter=blog
```

**Step 3: Modify `BlogController.php`**

Add at top of class:

```php
use App\Services\TemplateResolver;

private TemplateResolver $templates;

public function __construct(TemplateResolver $templates)
{
    $this->templates = $templates;
}
```

Modify `index()` — wrap the existing return in a template check:

```php
public function index(): Response
{
    $posts = /* existing query unchanged */;
    $seo   = /* existing seo unchanged */;

    if ($template = $this->templates->resolve('blog-index')) {
        return Inertia::render('Blog/TemplatePage', [
            'blocks'        => $template->blocks ?? [],
            'postContext'   => ['posts' => $posts, 'sidebar' => $this->sidebarData()],
            'seo'           => $seo,
        ]);
    }

    return Inertia::render('Blog/Index', [
        'posts'   => $posts,
        'sidebar' => $this->sidebarData(),
        'seo'     => $seo,
    ]);
}
```

Modify `show()`:

```php
public function show(string $slug): Response
{
    $post = /* existing query unchanged */;
    // ... existing comments + seo setup unchanged ...

    if ($template = $this->templates->resolve('single-post')) {
        return Inertia::render('Blog/TemplatePage', [
            'blocks'       => $template->blocks ?? [],
            'postContext'  => [
                // same shape as the 'post' key in existing Blog/Show response
                'id'                 => $post->id,
                'title'              => $post->title,
                'slug'               => $post->slug,
                'excerpt'            => $post->excerpt,
                'body'               => $post->body,
                'use_block_editor'   => (bool) $post->use_block_editor,
                'blocks'             => $post->blocks,
                'published_at'       => $post->published_at?->toDateString(),
                'featured_image_url' => $post->featuredImage?->url,
                'featured_image_alt' => $post->featuredImage?->alt,
                'author'             => ['name' => $post->author->name, 'avatar_url' => $post->author->avatar_url],
                'categories'         => $post->categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'slug' => $c->slug])->values(),
                'tags'               => $post->tags->map(fn ($t) => ['name' => $t->name, 'slug' => $t->slug]),
            ],
            'commentsData' => [
                'total'      => $total,
                'firstPage'  => $firstPage->map(/* existing comment map */),
                'hasMore'    => $firstPage->count() < $total,
                'perPage'    => $perPage,
                'enabled'    => $post->commentsOpen(),
            ],
            'seo' => $seo,
        ]);
    }

    return Inertia::render('Blog/Show', [/* existing unchanged */]);
}
```

Modify `category()` and `tag()` similarly:

```php
if ($template = $this->templates->resolve('archive')) {
    return Inertia::render('Blog/TemplatePage', [
        'blocks'         => $template->blocks ?? [],
        'archiveContext' => [
            'type'       => 'category',
            'name'       => $category->name,
            'slug'       => $category->slug,
            'postsCount' => $posts->total(),
            'posts'      => $posts,
        ],
        'seo' => $seo,
    ]);
}
// else: existing Inertia::render('Blog/Archive', ...) unchanged
```

**Step 4: Run tests**

```bash
php artisan test tests/Feature/TemplateTest.php
```
Expected: all pass.

**Step 5: Run full test suite**

```bash
php artisan test
```

**Step 6: Commit**

```bash
git add app/Http/Controllers/BlogController.php
git commit -m "feat: wire BlogController to use published templates via TemplateResolver"
```

---

## Task 10: SearchController + fallback view + route

**Files:**
- Create: `app/Http/Controllers/SearchController.php`
- Create: `resources/js/Pages/Blog/Search.vue`
- Modify: `routes/web.php` (already has the route stub from Task 3 — just ensure `SearchController` is imported)

**Step 1: Write the failing test**

Create `tests/Feature/SearchTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->markAsInstalled();
        $this->seedRolesAndPermissions();
    }

    public function test_search_returns_ok(): void
    {
        $this->get('/search?q=hello')->assertOk();
    }

    public function test_search_finds_matching_posts(): void
    {
        Post::factory()->create(['title' => 'Hello World', 'status' => 'published', 'published_at' => now()]);
        Post::factory()->create(['title' => 'Goodbye World', 'status' => 'published', 'published_at' => now()]);

        $response = $this->get('/search?q=Hello');
        $response->assertInertia(fn ($page) => $page
            ->component('Blog/Search')
            ->has('results.data', 1)
        );
    }

    public function test_search_uses_template_when_published(): void
    {
        $admin = User::factory()->create()->assignRole('administrator');
        Template::create([
            'user_id' => $admin->id, 'title' => 'Search Template',
            'type' => 'search-results', 'status' => 'published', 'blocks' => [],
        ]);

        $this->get('/search?q=test')
            ->assertInertia(fn ($page) => $page->component('Blog/TemplatePage'));
    }

    public function test_search_does_not_return_draft_posts(): void
    {
        Post::factory()->create(['title' => 'Draft Hello', 'status' => 'draft']);

        $response = $this->get('/search?q=Hello');
        $response->assertInertia(fn ($page) => $page->has('results.data', 0));
    }
}
```

**Step 2: Run to confirm failure**

```bash
php artisan test tests/Feature/SearchTest.php
```

**Step 3: Create `app/Http/Controllers/SearchController.php`**

```php
<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Services\TemplateResolver;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SearchController extends Controller
{
    public function __construct(private TemplateResolver $templates) {}

    public function index(Request $request)
    {
        $q     = trim($request->get('q', ''));
        $scope = $request->get('scope', 'posts');

        $results = Post::published()
            ->when($q, fn ($query) => $query->where(fn ($q2) =>
                $q2->where('title',   'LIKE', "%{$q}%")
                   ->orWhere('excerpt', 'LIKE', "%{$q}%")
                   ->orWhere('body',    'LIKE', "%{$q}%")
            ))
            ->with(['author:id,name,avatar', 'featuredImage:id,path,disk'])
            ->orderByDesc('published_at')
            ->paginate(15)
            ->through(fn (Post $post) => [
                'id'                 => $post->id,
                'title'              => $post->title,
                'slug'               => $post->slug,
                'excerpt'            => $post->excerpt,
                'published_at'       => $post->published_at?->toDateString(),
                'featured_image_url' => $post->featuredImage?->url,
                'author'             => ['name' => $post->author->name, 'avatar_url' => $post->author->avatar_url],
            ]);

        $seo = [
            'title'       => $q ? "Search: {$q}" : 'Search',
            'description' => '',
            'canonical'   => url("/search?q={$q}"),
            'type'        => 'website',
        ];

        if ($template = $this->templates->resolve('search-results')) {
            return Inertia::render('Blog/TemplatePage', [
                'blocks'        => $template->blocks ?? [],
                'searchContext' => ['query' => $q, 'results' => $results],
                'seo'           => $seo,
            ]);
        }

        return Inertia::render('Blog/Search', [
            'query'   => $q,
            'results' => $results,
            'seo'     => $seo,
        ]);
    }
}
```

**Step 4: Ensure route is wired in `routes/web.php`**

```php
use App\Http\Controllers\SearchController;
// ...
Route::get('/search', [SearchController::class, 'index'])->name('search');
```
(This was added in Task 3 — verify the import is present.)

**Step 5: Create `resources/js/Pages/Blog/Search.vue`**

Model it on `Blog/Archive.vue`. Key differences: heading shows "Search results for '…'" instead of category/tag, results come from `results` prop, show "No results found." when empty.

```vue
<script setup>
import { Head, Link } from '@inertiajs/vue3'

defineOptions({ layout: null }) // or use PublicLayout if one exists — match Blog/Archive.vue

const props = defineProps({
  query:   String,
  results: Object,
  seo:     Object,
})
</script>

<template>
  <!-- Match the exact same outer chrome/layout as Blog/Archive.vue -->
  <!-- Replace the archive heading with: -->
  <h1 class="text-2xl font-bold">
    {{ query ? `Search results for "${query}"` : 'Search' }}
  </h1>
  <p class="text-sm text-muted-foreground mt-1">{{ results.total }} result{{ results.total === 1 ? '' : 's' }}</p>

  <!-- Reuse post card list identical to Archive.vue -->
  <!-- ... -->
</template>
```

Copy the full post card grid + pagination from `Blog/Archive.vue` and use `results` instead of `posts`.

**Step 6: Run tests**

```bash
php artisan test tests/Feature/SearchTest.php
php artisan test
```
Expected: all pass.

**Step 7: Build and smoke-test**

```bash
npm run build
```
Visit `/search?q=test` in browser. Verify fallback view renders. Create a `search-results` template in `/templates`, publish it, visit `/search?q=test` again — verify it uses `TemplatePage`.

**Step 8: Commit**

```bash
git add app/Http/Controllers/SearchController.php resources/js/Pages/Blog/Search.vue tests/Feature/SearchTest.php
git commit -m "feat: add SearchController, Blog/Search fallback view, and search-results template support"
```

---

## Task 11: Final wiring + full test run

**Step 1: Register `TemplateResolver` in `AppServiceProvider`**

In `app/Providers/AppServiceProvider.php`, `register()`:

```php
$this->app->singleton(\App\Services\TemplateResolver::class);
```

**Step 2: Add `templates` to the catch-all slug exclusion regex in `routes/web.php`**

Find the `->where('slug', '^(?!...')` line and add `templates|` to the exclusion list so `/templates/...` routes aren't intercepted by the `/{slug}` page catch-all.

**Step 3: Run full test suite**

```bash
php artisan test
```
Expected: all tests pass (existing 364 + new template + search tests).

**Step 4: Build**

```bash
npm run build
```

**Step 5: End-to-end smoke test checklist**
- [ ] `/templates` — loads, grouped list visible
- [ ] Create a `blog-index` template with a Heading + Loop block, publish it → `/blog` uses template
- [ ] Create a `single-post` template with post-context blocks, publish it → `/blog/{slug}` uses template
- [ ] Create an `archive` template with archive-title + loop, publish it → category/tag archives use template
- [ ] Search block on any page/template → submits to `/search?q=…`
- [ ] `/search?q=test` → shows results, respects published-only posts
- [ ] Deleting the published template → falls back to hardcoded view immediately

**Step 6: Commit**

```bash
git add app/Providers/AppServiceProvider.php routes/web.php
git commit -m "feat: register TemplateResolver singleton and guard slug catch-all against /templates"
```

---

## Summary of All New Files

| path | purpose |
|---|---|
| `database/migrations/…_create_templates_table.php` | Schema |
| `app/Models/Template.php` | Eloquent model |
| `app/Services/TemplateResolver.php` | Active template lookup |
| `app/Http/Controllers/TemplateController.php` | Admin CRUD |
| `app/Http/Controllers/SearchController.php` | Public search |
| `resources/js/Pages/Templates/Index.vue` | Admin templates list |
| `resources/js/Pages/Templates/Create.vue` | Admin create form |
| `resources/js/Pages/Templates/Edit.vue` | Admin edit form |
| `resources/js/Pages/Blog/TemplatePage.vue` | Public FSE renderer |
| `resources/js/Pages/Blog/Search.vue` | Public search fallback |
| `resources/js/Components/Blocks/Post*.vue` (×7) | Post-context renderers |
| `resources/js/Components/Blocks/ArchiveTitleBlock.vue` | Archive heading renderer |
| `resources/js/Components/Blocks/SearchBlock.vue` | Search form renderer |
| `resources/js/Components/BlockEditor/blocks/Post*Settings.vue` (×6) | Post-context editor settings |
| `resources/js/Components/BlockEditor/blocks/ArchiveTitleSettings.vue` | Archive editor settings |
| `resources/js/Components/BlockEditor/blocks/SearchSettings.vue` | Search editor settings |
| `tests/Feature/TemplateTest.php` | Backend tests |
| `tests/Feature/SearchTest.php` | Search tests |
