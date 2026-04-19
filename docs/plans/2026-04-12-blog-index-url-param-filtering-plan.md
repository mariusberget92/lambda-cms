# Blog Index URL-Param Filtering — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Redesign the blog-index default template to a two-column layout with a sidebar, and introduce live in-place filtering by category/tag via URL params, backed by a new `FilterLinkBlock` component and QueryBuilder relationship filters.

**Architecture:** A new `filter-link` block type renders Inertia-aware anchor tags inside loop blocks (categories/tags sidebar). LoopBlock's existing `hasUrlParamFilters` / `getUrlParams()` watcher already fires re-fetches when `page.url` changes — no new JS infrastructure. Backend adds `whereHas` relationship filters for `category_slug` / `tag_slug` as special cases in `QueryBuilder::applyFilter()`.

**Tech Stack:** Laravel 12 (PHP), Vue 3 SFCs, Inertia.js v2 (`router.get`), `useFieldBinding` / `inject('loopItem')`, Tailwind CSS 4.

---

## Task 1: QueryBuilder — relationship filters + `filter_url` field

**Files:**
- Modify: `app/Services/QueryBuilder.php`

### Step 1: Open the file and study `applyFilter()`

Read `app/Services/QueryBuilder.php`. Note:
- `FILTERABLE['posts']` is `['featured', 'title', 'slug']`.
- `applyFilter()` starts with a whitelist check and returns early if the field isn't allowed.
- Relationship filters (`category_slug`, `tag_slug`) are not column-level — they need `whereHas()` and must be handled **before** the whitelist check.

### Step 2: Add relationship filter cases in `applyFilter()`

In `applyFilter()`, add special-case handling for `category_slug` and `tag_slug` **before** the whitelist check and return:

```php
private function applyFilter($query, array $filter, string $source): void
{
    $field = $filter['field'] ?? null;
    $op    = $filter['op']    ?? '=';
    $value = $filter['value'] ?? null;

    if (!$field) return;

    // Relationship filters (posts only) — handled before whitelist
    if ($source === 'posts') {
        if ($field === 'category_slug' && $op === '=' && $value !== null && $value !== '') {
            $query->whereHas('categories', fn ($q) => $q->where('slug', $value));
            return;
        }
        if ($field === 'tag_slug' && $op === '=' && $value !== null && $value !== '') {
            $query->whereHas('tags', fn ($q) => $q->where('slug', $value));
            return;
        }
    }

    // Security: only allow whitelisted filter fields
    if (!in_array($field, self::FILTERABLE[$source] ?? [], true)) return;

    switch ($op) {
        // ... existing cases unchanged
    }
}
```

### Step 3: Add `filter_url` to category output in `resolveCategories()`

In the `->map()` closure inside `resolveCategories()`, add:
```php
'filter_url' => '/?category=' . $cat->slug,
```

### Step 4: Add `filter_url` to tag output in `resolveTags()`

In the `->map()` closure inside `resolveTags()`, add:
```php
'filter_url' => '/?tag=' . $tag->slug,
```

### Step 5: Verify manually (no automated test exists for QueryBuilder)

Run `php artisan tinker` and execute:
```php
app(\App\Services\QueryBuilder::class)->resolve(
    ['source' => 'posts', 'filters' => [['field' => 'category_slug', 'op' => '=', 'value' => 'general']], 'limit' => 3],
    []
)
```
Expect: only posts in the `general` category returned (or empty array if none). No exception.

### Step 6: Commit

```bash
git add app/Services/QueryBuilder.php
git commit -m "feat: add category_slug/tag_slug relationship filters and filter_url field to QueryBuilder"
```

---

## Task 2: Create `FilterLinkBlock.vue`

**Files:**
- Create: `resources/js/Components/Blocks/FilterLinkBlock.vue`

This component renders an Inertia-aware anchor. It:
- Injects `loopItem` (provided by `LoopBlock`) to get the current item's `slug` and other fields
- Reads `block.data.paramName` (e.g. `"category"`) to know which URL param to set
- Constructs the filter URL: `/?{paramName}={slug}`
- Uses `router.get(url, {}, { preserveScroll: true })` via `@click.prevent` so Inertia intercepts the navigation (updates `page.url`, which LoopBlock watches)
- Detects active state: checks `new URLSearchParams(window.location.search).get(paramName) === slug`
- Binds `label` via `useFieldBinding` so seeder can set `bindings: { label: 'loop:name' }`

```vue
<!-- resources/js/Components/Blocks/FilterLinkBlock.vue -->
<script setup>
import { computed, inject } from 'vue'
import { router } from '@inertiajs/vue3'
import { useFieldBinding } from '@/composables/useLoopBinding.js'

const props = defineProps({ block: { type: Object, required: true } })

const loopItem = inject('loopItem', null)

const paramName = computed(() => props.block.data?.paramName || 'category')
const slug      = computed(() => loopItem?.value?.slug ?? '')
const label     = useFieldBinding(() => props.block, 'label')

const filterUrl = computed(() => slug.value ? `/?${paramName.value}=${slug.value}` : '/')

const isActive = computed(() => {
  if (!slug.value) return false
  try {
    const params = new URLSearchParams(window.location.search)
    return params.get(paramName.value) === slug.value
  } catch {
    return false
  }
})

function navigate(e) {
  e.preventDefault()
  router.get(filterUrl.value, {}, { preserveScroll: true })
}
</script>

<template>
  <a
    :href="filterUrl"
    :class="[
      'block text-sm transition-colors hover:text-foreground',
      isActive
        ? 'font-semibold text-foreground ring-1 ring-primary/40 rounded px-2 py-0.5'
        : 'text-muted-foreground px-2 py-0.5',
    ]"
    @click="navigate"
  >
    {{ label || slug }}
  </a>
</template>
```

### Step: Commit

```bash
git add resources/js/Components/Blocks/FilterLinkBlock.vue
git commit -m "feat: add FilterLinkBlock component for sidebar category/tag filter links"
```

---

## Task 3: Create `FilterLinkSettings.vue`

**Files:**
- Create: `resources/js/Components/BlockEditor/blocks/FilterLinkSettings.vue`

A minimal settings panel — just one field: `paramName`.

```vue
<!-- resources/js/Components/BlockEditor/blocks/FilterLinkSettings.vue -->
<script setup>
const props = defineProps({
  block: Object,
  tab:   { type: String, default: null },
})
const emit = defineEmits(['update'])
function update(key, value) {
  emit('update', { data: { ...props.block.data, [key]: value } })
}
</script>

<template>
  <div class="space-y-3 p-3">
    <div v-show="!tab || tab === 'content'" class="space-y-3">
      <div>
        <label class="text-xs font-medium text-muted-foreground block mb-1">URL param name</label>
        <input
          type="text"
          :value="block.data?.paramName ?? 'category'"
          placeholder="category"
          @input="update('paramName', $event.target.value)"
          class="w-full rounded-md border border-border bg-background px-2 py-1.5 text-xs"
        />
        <p class="text-[10px] text-muted-foreground mt-1">
          Sets <code>?{paramName}=slug</code> in the URL when clicked.
        </p>
      </div>
    </div>
  </div>
</template>
```

### Step: Commit

```bash
git add resources/js/Components/BlockEditor/blocks/FilterLinkSettings.vue
git commit -m "feat: add FilterLinkSettings panel for filter-link block"
```

---

## Task 4: Register `filter-link` in `BlockRenderer.vue`

**Files:**
- Modify: `resources/js/Components/BlockRenderer.vue`

### Step 1: Add the import

After the `LinkBlock` import line, add:
```js
import FilterLinkBlock from '@/Components/Blocks/FilterLinkBlock.vue'
```

### Step 2: Add to BLOCK_MAP

Find the `BLOCK_MAP` object. Add:
```js
'filter-link': FilterLinkBlock,
```

The BLOCK_MAP is defined as an object literal. Add the entry alongside the other block registrations.

### Step 3: Verify

Open the block editor in the browser, drag a Filter Link block onto the canvas, confirm no console errors.

### Step 4: Commit

```bash
git add resources/js/Components/BlockRenderer.vue
git commit -m "feat: register filter-link block type in BlockRenderer"
```

---

## Task 5: Register `filter-link` in `BlockTypePanel.vue` and `BlockLayers.vue`

**Files:**
- Modify: `resources/js/Components/BlockEditor/BlockTypePanel.vue`
- Modify: `resources/js/Components/BlockEditor/BlockLayers.vue`

### Step 1: BlockTypePanel — add to ALL_TYPES

In `ALL_TYPES`, inside the `// ── Interactive ──` section, add after the `link` entry:
```js
{ type: 'filter-link', label: 'Filter Link', icon: Filter, group: 'Interactive' },
```

Import `Filter` from `lucide-vue-next` (add to the existing import block).

### Step 2: BlockTypePanel — add to DEFAULT_DATA

```js
'filter-link': { paramName: 'category', label: '' },
```

### Step 3: BlockLayers — add import

After the `import LinkSettings` line, add:
```js
import FilterLinkSettings from './blocks/FilterLinkSettings.vue'
```

### Step 4: BlockLayers — add to LABELS

In the `LABELS` object, add:
```js
'filter-link': 'Filter Link',
```

### Step 5: BlockLayers — add to COMPONENT_MAP

```js
'filter-link': FilterLinkSettings,
```

### Step 6: Commit

```bash
git add resources/js/Components/BlockEditor/BlockTypePanel.vue \
        resources/js/Components/BlockEditor/BlockLayers.vue
git commit -m "feat: register filter-link in block editor palette and settings panel"
```

---

## Task 6: Update `TemplateSeeder.php` — new `blogIndexBlocks()`

**Files:**
- Modify: `database/seeders/TemplateSeeder.php`

### Step 1: Understand the target layout

```
Section (id:1, fullWidth:true, paddingY:16, paddingX:8)
  Container (id:2, flex-row, gap:'2rem', padding:0)        ← outer flex-row wrapper
    Container (id:3, flex-col, gap:'1rem', padding:0,       ← main column
               customCss:'flex:3;min-width:0')
      Heading (id:4, h2, 'Latest Posts')
      Loop    (id:5, posts, 3cols, limit:9,
               filters:[category_slug urlParam:category, tag_slug urlParam:tag],
               filter_logic:and, sort:published_at desc)
        postCard(10)                                        ← uses IDs 10–13
    Container (id:20, flex-col, gap:'1rem', padding:0,      ← sidebar
               customCss:'flex:1;min-width:0')
      SearchBlock (id:21)
      Heading (id:22, h3, 'Categories')
      Loop    (id:23, categories, 1col, limit:20,
               sort:posts_count desc)
        FilterLinkBlock (id:30, paramName:'category',
                         bindings:{label:'loop:name'})
      Heading (id:24, h3, 'Tags')
      Loop    (id:25, tags, 1col, limit:30,
               sort:posts_count desc)
        FilterLinkBlock (id:31, paramName:'tag',
                         bindings:{label:'loop:name'})
```

### Step 2: Key notes on ContainerBlock data

- `mode: 'flex'`, `direction: 'row'` → flex-row (children get `flex-1 min-w-0` from parent's `rendererItemClass`)
- `direction: 'column'` → flex-col
- `gap` as a **string** (e.g. `'2rem'`, `'1rem'`) → applied as CSS `gap` inline style
- `padding: 0` → maps to `p-0` via legacy integer map (no internal padding)
- `customCss: 'flex:3;min-width:0'` → written as `<style>#block-{id} { flex:3;min-width:0 }</style>` — overrides `flex-1` via ID-selector specificity

### Step 3: Replace `blogIndexBlocks()` with the new layout

```php
private function blogIndexBlocks(): array
{
    return [
        $this->section(1, [
            'paddingY'  => ['default' => 16],
            'paddingX'  => ['default' => 8],
            'fullWidth' => true,
            'minHeight' => 'auto',
        ], [
            // Outer flex-row container
            $this->block(2, 'container', [
                'mode'      => 'flex',
                'direction' => 'row',
                'wrap'      => false,
                'gap'       => '2rem',
                'padding'   => 0,
                'align'     => 'start',
                'maxWidth'  => 'full',
            ], [
                // ── Main column (flex: 3) ────────────────────────────────
                $this->block(3, 'container', [
                    'mode'      => 'flex',
                    'direction' => 'column',
                    'gap'       => '1.5rem',
                    'padding'   => 0,
                    'maxWidth'  => 'full',
                ], [
                    $this->block(4, 'heading', ['level' => 2, 'text' => 'Latest Posts']),
                    $this->block(5, 'loop', [
                        'source'       => 'posts',
                        'filters'      => [
                            ['field' => 'category_slug', 'op' => '=', 'urlParam' => 'category'],
                            ['field' => 'tag_slug',      'op' => '=', 'urlParam' => 'tag'],
                        ],
                        'filter_logic' => 'and',
                        'sort'         => ['field' => 'published_at', 'direction' => 'desc'],
                        'limit'        => 9,
                        'columns'      => 3,
                        'gap'          => 'md',
                    ], [$this->postCard(10)]),
                ], [], '', 'flex:3;min-width:0'),

                // ── Sidebar column (flex: 1) ─────────────────────────────
                $this->block(20, 'container', [
                    'mode'      => 'flex',
                    'direction' => 'column',
                    'gap'       => '1rem',
                    'padding'   => 0,
                    'maxWidth'  => 'full',
                ], [
                    $this->block(21, 'search', [
                        'placeholder' => 'Search posts…',
                        'buttonLabel' => 'Search',
                        'scope'       => 'posts',
                    ]),
                    $this->block(22, 'heading', ['level' => 3, 'text' => 'Categories']),
                    $this->block(23, 'loop', [
                        'source'  => 'categories',
                        'filters' => [],
                        'sort'    => ['field' => 'posts_count', 'direction' => 'desc'],
                        'limit'   => 20,
                        'columns' => 1,
                        'gap'     => 'sm',
                    ], [
                        $this->block(30, 'filter-link',
                            ['paramName' => 'category', 'label' => ''],
                            [], ['label' => 'loop:name']
                        ),
                    ]),
                    $this->block(24, 'heading', ['level' => 3, 'text' => 'Tags']),
                    $this->block(25, 'loop', [
                        'source'  => 'tags',
                        'filters' => [],
                        'sort'    => ['field' => 'posts_count', 'direction' => 'desc'],
                        'limit'   => 30,
                        'columns' => 1,
                        'gap'     => 'sm',
                    ], [
                        $this->block(31, 'filter-link',
                            ['paramName' => 'tag', 'label' => ''],
                            [], ['label' => 'loop:name']
                        ),
                    ]),
                ], [], '', 'flex:1;min-width:0'),
            ]),
        ]),
    ];
}
```

> **Note:** The `block()` helper signature is:
> `block(int $id, string $type, array $data, array $children = [], array $bindings = [], string $customClasses = '', string $customCss = '')`
>
> The current helper only has 6 params (no `$customCss`). You must **extend the helper** to accept and store `customCss`:
> ```php
> private function block(int $id, string $type, array $data, array $children = [], array $bindings = [], string $customClasses = '', string $customCss = ''): array
> {
>     $b = ['id' => $id, 'type' => $type, 'data' => $data];
>     if (!empty($children))     $b['children']     = $children;
>     if (!empty($bindings))     $b['bindings']      = $bindings;
>     if ($customClasses !== '') $b['customClasses'] = $customClasses;
>     if ($customCss     !== '') $b['customCss']     = $customCss;
>     return $b;
> }
> ```

### Step 4: Re-seed and verify

```bash
php artisan migrate:fresh --seed
```

Then open `http://lambda-cms.test/` (or your local URL). Expect:
- Two-column layout: left is the post grid (wider), right is sidebar with search + categories + tags
- Clicking a category name filters the post grid in place (URL becomes `/?category=general`)
- Clicking another category replaces the filter
- Clicking the same category again still works (it just re-sets the same URL)
- When a category is active, the link shows a ring highlight

### Step 5: If the published template already exists, delete it first

The seeder skips existing published templates. For dev re-seeding use `migrate:fresh --seed`. On an existing install:
```bash
php artisan tinker --execute="App\Models\Template::where('type', 'blog-index')->where('status', 'published')->delete();"
php artisan db:seed --class=TemplateSeeder
```

### Step 6: Commit

```bash
git add database/seeders/TemplateSeeder.php
git commit -m "feat: update blog-index template to two-column layout with URL-param filtering sidebar"
```

---

## Task 7: End-to-end verification checklist

Run through these manually in the browser:

- [ ] `/` shows two-column layout (post grid + sidebar)
- [ ] Sidebar has search box, "Categories" heading + list, "Tags" heading + list
- [ ] Categories and tags load via loop blocks (live data, not hardcoded)
- [ ] Clicking a category link changes URL to `/?category={slug}` — **no full page reload**
- [ ] Post grid updates to show only posts in that category
- [ ] Active category link has ring highlight; inactive links do not
- [ ] Clicking a tag link filters posts by tag (`/?tag={slug}`)
- [ ] `/blog` search (topbar) still works (contains fix from prior commit)
- [ ] Block editor: drag a "Filter Link" block from Interactive group → appears in layers panel → settings panel shows "URL param name" field
- [ ] `php artisan test` passes (no regressions)

---

## File Change Summary

| File | Action |
|------|--------|
| `app/Services/QueryBuilder.php` | Add `category_slug`/`tag_slug` relationship filters; add `filter_url` to category/tag output |
| `resources/js/Components/Blocks/FilterLinkBlock.vue` | **New** — Inertia filter link with active state |
| `resources/js/Components/BlockEditor/blocks/FilterLinkSettings.vue` | **New** — settings panel (paramName input) |
| `resources/js/Components/BlockRenderer.vue` | Register `filter-link` → `FilterLinkBlock` |
| `resources/js/Components/BlockEditor/BlockTypePanel.vue` | Add `filter-link` to ALL_TYPES + DEFAULT_DATA |
| `resources/js/Components/BlockEditor/BlockLayers.vue` | Add to LABELS, COMPONENT_MAP; import `FilterLinkSettings` |
| `database/seeders/TemplateSeeder.php` | Replace `blogIndexBlocks()`, extend `block()` helper with `$customCss` param |
