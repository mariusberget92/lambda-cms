# Post Card Template & Protection Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Seed a polished "Post Card" partial template and protect all system-seeded templates from deletion while still allowing edits.

**Architecture:** Add an `is_system` boolean to `templates`; the seeder marks all pre-built templates as system records; the controller rejects delete of system templates with 403; the Index page shows a Lock icon instead of Trash for system rows. Separately, the `TemplateSeeder` gains a rich Post Card partial (image → title → excerpt → meta → link, fully bound to loop fields) and the existing blog-index/archive/search-results loops are updated to reference it via a `template` block (template_id resolved by title lookup in the seeder).

**Tech Stack:** Laravel 12, SQLite, Vue 3 + Inertia, Tailwind 4, lucide-vue-next

---

## Task 1: Add `is_system` migration

**Files:**
- Create: `database/migrations/2026_04_17_000001_add_is_system_to_templates_table.php`

**Step 1: Create the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Idempotent: skip if column already exists
        if (Schema::hasColumn('templates', 'is_system')) {
            return;
        }

        Schema::table('templates', function (Blueprint $table) {
            $table->boolean('is_system')->default(false)->after('loop_source');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('templates', 'is_system')) {
            Schema::table('templates', function (Blueprint $table) {
                $table->dropColumn('is_system');
            });
        }
    }
};
```

**Step 2: Run the migration**

```bash
php artisan migrate
```

Expected: `Migrating: 2026_04_17_000001_add_is_system_to_templates_table` … `Migrated`

**Step 3: Commit**

```bash
git add database/migrations/2026_04_17_000001_add_is_system_to_templates_table.php
git commit -m "feat: add is_system column to templates"
```

---

## Task 2: Update Template model

**Files:**
- Modify: `app/Models/Template.php`

**Step 1: Add `is_system` to `$fillable` and `$casts`**

In `$fillable`, add `'is_system'` after `'loop_source'`.

In `$casts`, add `'is_system' => 'boolean'`.

Final relevant section:

```php
protected $fillable = [
    'user_id',
    'type',
    'loop_source',
    'is_system',
    'title',
    'status',
    'blocks',
    'meta_title',
    'meta_description',
    'meta_keywords',
];

protected $casts = [
    'blocks'    => 'array',
    'is_system' => 'boolean',
];
```

**Step 2: Commit**

```bash
git add app/Models/Template.php
git commit -m "feat: add is_system to Template fillable and casts"
```

---

## Task 3: Protect system templates in the controller

**Files:**
- Modify: `app/Http/Controllers/TemplateController.php`

**Step 1: Guard `destroy()` against system templates**

At the top of `destroy()`, after the role check, add:

```php
if ($template->is_system) {
    abort(403, 'System templates cannot be deleted.');
}
```

The full `destroy()` method becomes:

```php
public function destroy(Request $request, Template $template)
{
    if ($template->user_id !== $request->user()->id && !$request->user()->hasRole('administrator')) {
        abort(403);
    }

    if ($template->is_system) {
        abort(403, 'System templates cannot be deleted.');
    }

    $template->delete();

    return redirect()->route('templates.index')->with('status', 'Template deleted.');
}
```

**Step 2: Expose `is_system` in `index()` and `edit()` responses**

In `index()`, the `->map()` callback — add `'is_system' => $t->is_system`:

```php
->map(fn (Template $t) => [
    'id'         => $t->id,
    'title'      => $t->title,
    'type'       => $t->type,
    'status'     => $t->status,
    'is_system'  => $t->is_system,
    'updated_at' => $t->updated_at->toDateString(),
    'creator'    => $t->creator->name,
])
```

In `edit()`, add `'is_system' => $template->is_system` to the array returned to Inertia.

**Step 3: Commit**

```bash
git add app/Http/Controllers/TemplateController.php
git commit -m "feat: block deletion of system templates, expose is_system in index/edit"
```

---

## Task 4: Update Templates/Index.vue — Lock icon for system templates

**Files:**
- Modify: `resources/js/Pages/Templates/Index.vue`

**Step 1: Import `Lock` from lucide**

Change the lucide import line to include `Lock`:

```js
import { LayoutTemplate, Plus, Pencil, Trash2, ChevronDown, Lock } from 'lucide-vue-next'
```

**Step 2: Replace Trash2 button with conditional Lock/Trash**

Find the actions cell (currently shows Pencil + Trash2 buttons). Replace the `<button>` for delete with:

```vue
<!-- System templates: show lock, no delete -->
<span
  v-if="template.is_system"
  class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground/40 cursor-default"
  title="System templates cannot be deleted"
>
  <Lock class="w-3.5 h-3.5" />
</span>

<!-- User templates: show trash -->
<button
  v-else
  type="button"
  class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-destructive/10 hover:text-destructive transition-colors"
  title="Delete"
  @click="confirmDelete(template)"
>
  <Trash2 class="w-3.5 h-3.5" />
</button>
```

**Step 3: Commit**

```bash
git add resources/js/Pages/Templates/Index.vue
git commit -m "feat: show lock icon for system templates in index UI"
```

---

## Task 5: Update TemplateSeeder — mark existing templates as system

**Files:**
- Modify: `database/seeders/TemplateSeeder.php`

**Step 1: Add `is_system => true` to all `Template::create()` calls**

In the `foreach` loop inside `run()`, update the `Template::create()` call:

```php
Template::create([
    'user_id'   => $admin->id,
    'type'      => $def['type'],
    'title'     => $def['title'],
    'status'    => 'published',
    'is_system' => true,
    'blocks'    => $def['blocks'],
]);
```

Also mark existing rows as system (for environments that have already been seeded) by adding this before the foreach:

```php
// Mark any existing seeded templates as system templates
Template::whereIn('title', [
    'Default Blog Index',
    'Default Single Post',
    'Default Archive',
    'Default Search Results',
    'Post Card',
])->update(['is_system' => true]);
```

**Step 2: Commit**

```bash
git add database/seeders/TemplateSeeder.php
git commit -m "feat: mark seeded templates as is_system"
```

---

## Task 6: Seed the Post Card partial template

**Files:**
- Modify: `database/seeders/TemplateSeeder.php`

**Step 1: Add Post Card to the seeder definitions list**

In `run()`, add the Post Card entry to `$definitions`:

```php
['type' => 'partial', 'title' => 'Post Card', 'loop_source' => 'posts', 'blocks' => $this->postCardBlocks()],
```

The full `$definitions` array becomes:

```php
$definitions = [
    ['type' => 'partial',        'title' => 'Post Card',             'loop_source' => 'posts', 'blocks' => $this->postCardBlocks()],
    ['type' => 'blog-index',     'title' => 'Default Blog Index',     'blocks' => $this->blogIndexBlocks()],
    ['type' => 'single-post',    'title' => 'Default Single Post',    'blocks' => $this->singlePostBlocks()],
    ['type' => 'archive',        'title' => 'Default Archive',        'blocks' => $this->archiveBlocks()],
    ['type' => 'search-results', 'title' => 'Default Search Results', 'blocks' => $this->searchBlocks()],
];
```

> **Important:** Post Card must be listed first so it gets a DB ID before blog-index/archive/search reference it.

**Step 2: Update the foreach to handle `loop_source`**

```php
foreach ($definitions as $def) {
    $existingQuery = Template::where('type', $def['type'])
        ->where('title', $def['title']);

    if ($existingQuery->exists()) {
        // Ensure existing records are marked system
        $existingQuery->update(['is_system' => true]);
        continue;
    }

    Template::create([
        'user_id'     => $admin->id,
        'type'        => $def['type'],
        'title'       => $def['title'],
        'loop_source' => $def['loop_source'] ?? null,
        'status'      => 'published',
        'is_system'   => true,
        'blocks'      => $def['blocks'],
    ]);
}
```

Note: the existing check was `where('type')` + `where('status', 'published')`. Change it to match on `type` + `title` so the Post Card partial (same type `partial` as any user-created partial) isn't skipped.

**Step 3: Add the `postCardBlocks()` method**

Add this private method to the class (it replaces the old inline `postCard()` helper — keep the helper for now, it will be removed in Task 7):

```php
private function postCardBlocks(): array
{
    // Outer wrapper: rounded card, shadow, overflow-hidden so image fills top edge
    return [
        $this->block(
            500, 'container',
            [
                'mode'      => 'flex',
                'direction' => 'column',
                'gap'       => 0,
                'padding'   => 0,
                'maxWidth'  => 'full',
            ],
            [
                // Featured image — binds to loop:featured_image_url
                $this->block(501, 'image',
                    ['url' => '', 'alt' => '', 'maxHeight' => '200px'],
                    [], ['url' => 'loop:featured_image_url', 'alt' => 'loop:title']
                ),

                // Inner content area with padding
                $this->block(502, 'container',
                    [
                        'mode'      => 'flex',
                        'direction' => 'column',
                        'gap'       => '0.5rem',
                        'padding'   => 16,
                        'maxWidth'  => 'full',
                    ],
                    [
                        // Title
                        $this->block(503, 'heading',
                            ['level' => 3, 'text' => ''],
                            [], ['text' => 'loop:title']
                        ),

                        // Excerpt — two-line clamp via customClasses
                        $this->block(504, 'paragraph',
                            ['content' => ''],
                            [], ['content' => 'loop:excerpt'],
                            'line-clamp-2 text-sm text-muted-foreground'
                        ),

                        // Date + author — small muted paragraph
                        $this->block(505, 'paragraph',
                            ['content' => ''],
                            [], ['content' => 'loop:published_at'],
                            'text-xs text-muted-foreground/70'
                        ),

                        // Read more link
                        $this->block(506, 'link',
                            ['label' => 'Read more →', 'url' => '#', 'target' => '_self'],
                            [], ['url' => 'loop:url'],
                            'text-sm font-medium text-primary hover:underline mt-1'
                        ),
                    ]
                ),
            ],
            [], 'rounded-xl shadow-md overflow-hidden bg-card',
            'font-family: Inter, sans-serif;'
        ),
    ];
}
```

**Step 4: Commit**

```bash
git add database/seeders/TemplateSeeder.php
git commit -m "feat: seed Post Card partial template with dynamic bindings"
```

---

## Task 7: Update blog-index, archive and search loops to use the Post Card template

**Files:**
- Modify: `database/seeders/TemplateSeeder.php`

The blog-index, archive, and search loops currently embed an inline `postCard()` helper. Replace those with a `template` block that references the seeded Post Card partial by ID (looked up by title inside each method).

**Step 1: Add a helper method to look up the Post Card template ID**

```php
private function postCardTemplateId(): ?int
{
    return \App\Models\Template::where('title', 'Post Card')
        ->where('type', 'partial')
        ->value('id');
}
```

**Step 2: Add a `templateBlock()` helper**

```php
private function templateBlock(int $id, int $templateId): array
{
    return $this->block($id, 'template', ['template_id' => $templateId]);
}
```

**Step 3: Update `blogIndexBlocks()`**

Replace `$this->postCard(10)` with:

```php
$this->templateBlock(10, $this->postCardTemplateId() ?? 0)
```

**Step 4: Update `archiveBlocks()`**

Replace `$this->postCard(210)` with:

```php
$this->templateBlock(210, $this->postCardTemplateId() ?? 0)
```

**Step 5: Update `searchBlocks()`**

Replace `$this->postCard(310)` with:

```php
$this->templateBlock(310, $this->postCardTemplateId() ?? 0)
```

**Step 6: Remove the old inline `postCard()` helper method**

Delete the `private function postCard(int $baseId): array { ... }` method entirely — it is no longer used.

**Step 7: Commit**

```bash
git add database/seeders/TemplateSeeder.php
git commit -m "refactor: replace inline postCard helper with Post Card template reference in loops"
```

---

## Task 8: Ensure ImageBlock respects `maxHeight`

**Files:**
- Modify: `resources/js/Components/Blocks/ImageBlock.vue`

The Post Card's image block uses `block.data.maxHeight`. Check that `ImageBlock.vue` applies it. Currently the `<img>` only applies `w-full rounded-lg object-cover`.

**Step 1: Apply maxHeight and remove rounded corners (card handles that)**

Update the `<img>` tag:

```vue
<img
  v-if="resolvedUrl"
  :src="resolvedUrl"
  :alt="resolvedAlt || ''"
  class="w-full object-cover"
  :style="[
    block.data?.maxHeight ? { maxHeight: block.data.maxHeight, height: block.data.maxHeight } : {},
    block.data?.aspectRatio ? { aspectRatio: block.data.aspectRatio } : {}
  ]"
  @error="onError"
/>
```

> Note: Removing `rounded-lg` from ImageBlock because the card's `overflow-hidden` handles clipping. If you prefer to keep `rounded-lg` as default, wrap with `v-bind:class="block.data?.maxHeight ? '' : 'rounded-lg'"`.

**Step 2: Commit**

```bash
git add resources/js/Components/Blocks/ImageBlock.vue
git commit -m "feat: apply maxHeight style to ImageBlock"
```

---

## Task 9: Fresh DB seed and visual verification

**Step 1: Re-run migrations and seed**

```bash
php artisan migrate:fresh --seed
```

Expected: all migrations pass, seeder runs without errors, 5 templates created (Post Card, Blog Index, Single Post, Archive, Search Results).

**Step 2: Verify in the browser**

1. Navigate to `/templates` — confirm all 5 templates show a **Lock** icon instead of Trash.
2. Click Edit on "Post Card" — confirm the full-screen editor opens with the card blocks visible in the canvas.
3. Navigate to `/` (blog index) — confirm posts render using the Post Card layout (image → title → excerpt → date → Read more).
4. Try `DELETE /templates/{id}` via the browser on a system template — should get a 403 or redirect with no deletion.

**Step 3: Commit if any last-minute fixes were needed**

```bash
git add -p
git commit -m "fix: post-seed visual corrections"
```

---

## Task 10: Build and final commit

**Step 1: Build assets**

```bash
npm run build
```

Expected: `✓ built` with only the existing chunk-size warnings (no new errors).

**Step 2: Final commit (if anything remains staged)**

```bash
git status
# commit anything not yet committed
```
