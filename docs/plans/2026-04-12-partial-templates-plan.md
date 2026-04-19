# Partial Templates Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add a `partial` template type and a `template` block type so users can build reusable block snippets and embed them anywhere in the block editor — including inside loop blocks where loop bindings flow through automatically.

**Architecture:** Partials are stored as `type='partial'` rows in the existing `templates` table. All published partials are injected into every Inertia page via shared props (`usePage().props.partials`). A new `TemplateBlock.vue` looks up the partial by `template_id` and renders its blocks inline via `BlockRenderer`, inheriting any `LoopItemProvider` context already in scope. The `template` block type is hidden from the editor palette when editing a partial (prevents infinite recursion via an `isPartial` prop chain).

**Tech Stack:** Laravel 12 (PHP), Vue 3 SFCs, Inertia.js v2, `usePage()`, `provide`/`inject`.

---

## Task 1: Backend — add `partial` to TemplateController validation

**Files:**
- Modify: `app/Http/Controllers/TemplateController.php`

### Step 1: Read the file

Read `app/Http/Controllers/TemplateController.php`. Note:
- `create()`, `store()`, `update()` all validate `type` with `'in:blog-index,single-post,archive,search-results'`
- `store()` and `update()` have a "demote other published" block that ensures only one published template per type — **this must be skipped for `partial`**, since many partials can be published simultaneously

### Step 2: Update `create()` validation

```php
$request->validate(['type' => ['required', 'in:blog-index,single-post,archive,search-results,partial']]);
```

### Step 3: Update `store()` validation + demote logic

In the `store()` validated rules, change:
```php
'type' => ['required', 'in:blog-index,single-post,archive,search-results,partial'],
```

Wrap the "demote" block to skip partials:
```php
if ($validated['status'] === 'published' && $validated['type'] !== 'partial') {
    Template::where('type', $validated['type'])
        ->where('status', 'published')
        ->update(['status' => 'draft']);
}
```

### Step 4: Update `update()` validation + demote logic

Same two changes as Step 3 (the `update()` method has identical validation and demote logic).

### Step 5: Verify with tinker

```bash
php artisan tinker --execute="
\$t = App\Models\Template::create([
    'user_id' => App\Models\User::first()->id,
    'type'    => 'partial',
    'title'   => 'Test Card',
    'status'  => 'published',
    'blocks'  => [],
]);
echo 'Created: ' . \$t->id . PHP_EOL;
\$t->delete();
echo 'OK' . PHP_EOL;
"
```
Expect: "Created: X\nOK" — no exception.

### Step 6: Commit

```bash
git add app/Http/Controllers/TemplateController.php
git commit -m "feat: add partial type to TemplateController — allow multiple published partials"
```

---

## Task 2: Backend — inject partials into Inertia shared props

**Files:**
- Modify: `app/Http/Middleware/HandleInertiaRequests.php`

### Step 1: Read the file

Read `app/Http/Middleware/HandleInertiaRequests.php`. Find the `share()` method. It returns an array of shared props including `auth`, `flash`, `currentRoute`, `pendingCommentsCount`, `accentColor`.

### Step 2: Add the `partials` key

Add after `'accentColor'`:
```php
'partials' => fn () => \App\Models\Template::published()
    ->where('type', 'partial')
    ->get(['id', 'title', 'blocks'])
    ->toArray(),
```

> The lazy `fn()` closure means this only queries when a response is actually sent — not on every middleware tick.

### Step 3: Verify

```bash
php artisan tinker --execute="
// Simulate a fresh partial
\$t = App\Models\Template::create([
    'user_id' => App\Models\User::first()->id,
    'type'    => 'partial',
    'title'   => 'My Card',
    'status'  => 'published',
    'blocks'  => [['id' => 'x1', 'type' => 'heading', 'data' => ['text' => 'Hello']]],
]);
\$partials = App\Models\Template::published()->where('type', 'partial')->get(['id', 'title', 'blocks'])->toArray();
echo 'Partials count: ' . count(\$partials) . PHP_EOL;
echo 'Has blocks: ' . (!empty(\$partials[0]['blocks']) ? 'yes' : 'no') . PHP_EOL;
\$t->delete();
"
```
Expect: `Partials count: 1\nHas blocks: yes`

### Step 4: Commit

```bash
git add app/Http/Middleware/HandleInertiaRequests.php
git commit -m "feat: inject published partials into Inertia shared props"
```

---

## Task 3: Create `TemplateBlock.vue`

**Files:**
- Create: `resources/js/Components/Blocks/TemplateBlock.vue`

This component:
- Reads `block.data.template_id`
- Finds the matching partial in `usePage().props.partials`
- Renders the partial's blocks via `<BlockRenderer>`
- Loop context flows through automatically (no extra work needed — `LoopItemProvider` is already a parent in the tree when inside a loop)
- Shows a neutral placeholder if no partial is selected or the partial isn't found

```vue
<!-- resources/js/Components/Blocks/TemplateBlock.vue -->
<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import BlockRenderer from '@/Components/BlockRenderer.vue'

const props = defineProps({ block: { type: Object, required: true } })

const partials = computed(() => usePage().props.partials ?? [])

const partial = computed(() =>
  partials.value.find(p => p.id === props.block.data?.template_id) ?? null
)
</script>

<template>
  <!-- Partial found: render its blocks inline, inheriting any loop context -->
  <BlockRenderer
    v-if="partial"
    :blocks="partial.blocks ?? []"
    wrapper-class="contents"
  />

  <!-- No partial selected or partial was deleted/unpublished -->
  <div
    v-else
    class="rounded-lg border border-dashed border-border/60 px-4 py-6 text-center text-sm text-muted-foreground"
  >
    {{ block.data?.template_id ? 'Partial not found (deleted or unpublished)' : 'No partial selected — choose one in settings' }}
  </div>
</template>
```

### Step: Commit

```bash
git add resources/js/Components/Blocks/TemplateBlock.vue
git commit -m "feat: add TemplateBlock component for rendering embedded partials"
```

---

## Task 4: Create `TemplateSettings.vue`

**Files:**
- Create: `resources/js/Components/BlockEditor/blocks/TemplateSettings.vue`

Settings panel with:
- A `<select>` listing published partials from `usePage().props.partials`
- An "Edit partial →" link opening the selected partial in a new tab
- An empty state message when no partials exist yet

```vue
<!-- resources/js/Components/BlockEditor/blocks/TemplateSettings.vue -->
<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { ExternalLink } from 'lucide-vue-next'

const props = defineProps({
  block: Object,
  tab:   { type: String, default: null },
})
const emit = defineEmits(['update'])

const partials = computed(() => usePage().props.partials ?? [])

const selectedId = computed(() => props.block.data?.template_id ?? null)

const editUrl = computed(() =>
  selectedId.value ? `/templates/${selectedId.value}/edit` : null
)

function update(value) {
  emit('update', { data: { ...props.block.data, template_id: value ? Number(value) : null } })
}
</script>

<template>
  <div class="space-y-3 p-3">
    <div v-show="!tab || tab === 'content'" class="space-y-3">

      <!-- No partials exist yet -->
      <div v-if="partials.length === 0" class="rounded-md border border-dashed p-4 text-center">
        <p class="text-xs text-muted-foreground">No partials yet.</p>
        <a
          href="/templates"
          target="_blank"
          class="mt-1 inline-flex items-center gap-1 text-xs text-primary hover:underline"
        >
          Create one in Templates <ExternalLink class="w-3 h-3" />
        </a>
      </div>

      <!-- Partial selector -->
      <template v-else>
        <div>
          <label class="text-xs font-medium text-muted-foreground block mb-1">Partial</label>
          <select
            :value="selectedId"
            @change="update($event.target.value || null)"
            class="w-full rounded-md border border-border bg-background px-2 py-1.5 text-xs"
          >
            <option value="">— Select a partial —</option>
            <option
              v-for="p in partials"
              :key="p.id"
              :value="p.id"
            >
              {{ p.title }}
            </option>
          </select>
        </div>

        <a
          v-if="editUrl"
          :href="editUrl"
          target="_blank"
          class="inline-flex items-center gap-1 text-xs text-primary hover:underline"
        >
          Edit partial <ExternalLink class="w-3 h-3" />
        </a>
      </template>

    </div>
  </div>
</template>
```

### Step: Commit

```bash
git add resources/js/Components/BlockEditor/blocks/TemplateSettings.vue
git commit -m "feat: add TemplateSettings panel — partial selector with edit link"
```

---

## Task 5: Register `template` block in BlockRenderer, BlockTypePanel, BlockLayers

**Files:**
- Modify: `resources/js/Components/BlockRenderer.vue`
- Modify: `resources/js/Components/BlockEditor/BlockTypePanel.vue`
- Modify: `resources/js/Components/BlockEditor/BlockLayers.vue`

### Step 1: BlockRenderer — add import

After the `import FilterLinkBlock` line, add:
```js
import TemplateBlock from '@/Components/Blocks/TemplateBlock.vue'
```

### Step 2: BlockRenderer — add to BLOCK_MAP

```js
'template': TemplateBlock,
```

### Step 3: BlockTypePanel — add `isPartial` prop

The component currently has:
```js
const props = defineProps({
  isAdmin: { type: Boolean, default: false },
})
```

Add:
```js
const props = defineProps({
  isAdmin:    { type: Boolean, default: false },
  isPartial:  { type: Boolean, default: false },
})
```

### Step 4: BlockTypePanel — add import + type entry

Add `LayoutTemplate` to the lucide imports (it's not imported yet — check with `grep LayoutTemplate`):
```js
import {
  // ... existing imports ...
  LayoutTemplate,
} from 'lucide-vue-next'
```

In `ALL_TYPES`, in the `// ── Interactive ──` section, add after `filter-link`:
```js
{ type: 'template', label: 'Template', icon: LayoutTemplate, group: 'Interactive' },
```

### Step 5: BlockTypePanel — add to DEFAULT_DATA

```js
'template': { template_id: null },
```

### Step 6: BlockTypePanel — hide `template` when `isPartial`

The `visibleGroups` computed already filters `adminOnly` and `hiddenFromPalette`. Extend it to also filter `template` when `isPartial`:

Current:
```js
const visible = ALL_TYPES.filter(t => (!t.adminOnly || props.isAdmin) && !t.hiddenFromPalette)
```

Change to:
```js
const visible = ALL_TYPES.filter(t =>
  (!t.adminOnly || props.isAdmin) &&
  !t.hiddenFromPalette &&
  !(t.type === 'template' && props.isPartial)
)
```

### Step 7: BlockLayers — add import

After `import FilterLinkSettings`:
```js
import TemplateSettings from './blocks/TemplateSettings.vue'
```

### Step 8: BlockLayers — add to LABELS

```js
'template': 'Template',
```

### Step 9: BlockLayers — add to COMPONENT_MAP

```js
'template': TemplateSettings,
```

### Step 10: Commit

```bash
git add resources/js/Components/BlockRenderer.vue \
        resources/js/Components/BlockEditor/BlockTypePanel.vue \
        resources/js/Components/BlockEditor/BlockLayers.vue
git commit -m "feat: register template block type in renderer and block editor"
```

---

## Task 6: Pass `isPartial` through BlockEditor

**Files:**
- Modify: `resources/js/Components/BlockEditor/BlockEditor.vue`

The `isPartial` flag needs to travel from Edit/Create pages → BlockEditor → BlockTypePanel.

### Step 1: Read BlockEditor.vue

Read `resources/js/Components/BlockEditor/BlockEditor.vue`. Find:
- The `defineProps` block (has `modelValue`, `isAdmin`, `meta`, `fullscreen`, `contextFields`)
- The `<BlockTypePanel>` usage in the template

### Step 2: Add `isPartial` prop

In `defineProps`:
```js
isPartial: { type: Boolean, default: false },
```

### Step 3: Pass to BlockTypePanel

In the template, find `<BlockTypePanel :is-admin="isAdmin" />` and add the prop:
```vue
<BlockTypePanel :is-admin="isAdmin" :is-partial="isPartial" />
```

### Step 4: Commit

```bash
git add resources/js/Components/BlockEditor/BlockEditor.vue
git commit -m "feat: add isPartial prop to BlockEditor — passed to BlockTypePanel to hide template block"
```

---

## Task 7: Update Templates pages — Index, Create, Edit

**Files:**
- Modify: `resources/js/Pages/Templates/Index.vue`
- Modify: `resources/js/Pages/Templates/Create.vue`
- Modify: `resources/js/Pages/Templates/Edit.vue`

### Step 1: Index.vue — add partial to TYPE_LABELS

In `TYPE_LABELS`:
```js
'partial': 'Partial',
```

### Step 2: Index.vue — add "New Partial" button

In the "New template buttons" section, the current code loops over `ALL_TYPES`. Add a separate standalone button for "New Partial" after the loop:

```vue
<a
  :href="route('templates.create', { type: 'partial' })"
  class="inline-flex items-center gap-1.5 rounded-md border px-3 py-1.5 text-sm font-medium hover:bg-accent transition-colors"
>
  <Plus class="w-3.5 h-3.5" />
  New Partial
</a>
```

### Step 3: Index.vue — add Partials section

After the existing `<div v-for="type in ALL_TYPES">` loop, add a dedicated Partials section:

```vue
<!-- Partials section -->
<div v-if="templates['partial']?.length > 0">
  <div class="flex items-center justify-between mb-2">
    <h3 class="text-sm font-semibold text-muted-foreground uppercase tracking-wider">Partials</h3>
  </div>
  <div class="rounded-lg border bg-card overflow-hidden">
    <table class="w-full text-sm">
      <thead>
        <tr class="border-b bg-muted/40">
          <th class="px-4 py-2 text-left font-medium text-muted-foreground">Name</th>
          <th class="px-4 py-2 text-left font-medium text-muted-foreground">Status</th>
          <th class="px-4 py-2 text-left font-medium text-muted-foreground">Updated</th>
          <th class="px-4 py-2" />
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="template in templates['partial']"
          :key="template.id"
          class="border-b last:border-0 hover:bg-muted/30 transition-colors group"
        >
          <td class="px-4 py-3 font-medium">{{ template.name }}</td>
          <td class="px-4 py-3"><StatusBadge :status="template.status" /></td>
          <td class="px-4 py-3 text-muted-foreground">{{ formatDate(template.updated_at) }}</td>
          <td class="px-4 py-3">
            <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
              <a
                :href="route('templates.edit', template.id)"
                class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-accent hover:text-accent-foreground transition-colors"
                title="Edit"
              >
                <Pencil class="w-3.5 h-3.5" />
              </a>
              <button
                type="button"
                class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-destructive/10 hover:text-destructive transition-colors"
                title="Delete"
                @click="confirmDelete(template)"
              >
                <Trash2 class="w-3.5 h-3.5" />
              </button>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</div>
```

Also update `hasAny()` to include partials:
```js
function hasAny() {
  return [...ALL_TYPES, 'partial'].some(t => props.templates[t]?.length > 0)
}
```

### Step 4: Create.vue — add partial to TYPE_LABELS + pass isPartial

Add `'partial': 'Partial'` to `TYPE_LABELS`.

Pass `isPartial` to `BlockEditor`:
```vue
<BlockEditor
  :model-value="form.blocks"
  :is-admin="authUser?.role === 'administrator'"
  :is-partial="props.type === 'partial'"
  :context-fields="props.type === 'single-post' ? POST_CONTEXT_FIELDS : []"
  @update:model-value="form.blocks = $event"
/>
```

### Step 5: Edit.vue — add partial to TYPE_LABELS + pass isPartial

Add `'partial': 'Partial'` to `TYPE_LABELS`.

Pass `isPartial` to `BlockEditor`:
```vue
<BlockEditor
  :model-value="form.blocks"
  :is-admin="authUser?.role === 'administrator'"
  :is-partial="template.type === 'partial'"
  :context-fields="template.type === 'single-post' ? POST_CONTEXT_FIELDS : []"
  @update:model-value="form.blocks = $event"
/>
```

### Step 6: Commit

```bash
git add resources/js/Pages/Templates/Index.vue \
        resources/js/Pages/Templates/Create.vue \
        resources/js/Pages/Templates/Edit.vue
git commit -m "feat: add partials section to Templates pages — new/edit/list support"
```

---

## Task 8: End-to-end verification checklist

Run through these manually in the browser after `npm run build` or `npm run dev`:

- [ ] `/templates` → "New Partial" button is visible
- [ ] Clicking "New Partial" → creates a partial (type = partial) with block editor
- [ ] While editing a partial, the `template` block type **does not** appear in the Interactive palette (recursion prevention)
- [ ] All other block types (heading, loop, etc.) are available in a partial
- [ ] Publishing a partial does **not** unpublish other partials
- [ ] After creating + publishing a partial, `usePage().props.partials` on any page includes it (check with Vue DevTools or by dragging a Template block and seeing it appear in the settings dropdown)
- [ ] Drop a `template` block in a regular page/post — settings panel shows partial selector
- [ ] Select the partial → block renders the partial's blocks inline
- [ ] Edit the partial (change a heading text) → the page using it reflects the change on next load
- [ ] Drop a `template` block inside a loop block with loop bindings in the partial → bindings resolve to loop item values correctly
- [ ] `php artisan test` — same 20 pre-existing failures, 519 passing, no new failures

---

## File Change Summary

| File | Action |
|------|--------|
| `app/Http/Controllers/TemplateController.php` | Add `partial` to type validation; skip demote logic for partials |
| `app/Http/Middleware/HandleInertiaRequests.php` | Add lazy `partials` shared prop |
| `resources/js/Components/Blocks/TemplateBlock.vue` | **New** — renders embedded partial's blocks |
| `resources/js/Components/BlockEditor/blocks/TemplateSettings.vue` | **New** — partial selector settings panel |
| `resources/js/Components/BlockRenderer.vue` | Register `template` → `TemplateBlock` |
| `resources/js/Components/BlockEditor/BlockTypePanel.vue` | Add `isPartial` prop; add `template` type; hide it when `isPartial` |
| `resources/js/Components/BlockEditor/BlockLayers.vue` | Add `template` to LABELS + COMPONENT_MAP; import `TemplateSettings` |
| `resources/js/Components/BlockEditor/BlockEditor.vue` | Add `isPartial` prop; pass to `BlockTypePanel` |
| `resources/js/Pages/Templates/Index.vue` | Add Partials section + New Partial button; update `hasAny()` |
| `resources/js/Pages/Templates/Create.vue` | Add `partial` to TYPE_LABELS; pass `isPartial` to BlockEditor |
| `resources/js/Pages/Templates/Edit.vue` | Add `partial` to TYPE_LABELS; pass `isPartial` to BlockEditor |
