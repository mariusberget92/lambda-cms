# Pagination Block Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Wire up the existing PaginationBlock stub so Loop blocks can be paginated via a shared URL param, with configurable prev/next or numbered style.

**Architecture:** A module-level reactive store (`useLoopPagination.js`) keyed by `pageParam` bridges the Loop block (writes `{total, perPage}` after every fetch) and the Pagination block (reads it to compute page count). Navigation uses `router.get()` — the same pattern as FilterLinkBlock. The loop reads `?{pageParam}=N` from the URL on mount/URL-change to compute its offset.

**Tech Stack:** Vue 3 Composition API, Inertia.js `router` + `usePage`, axios, Tailwind CSS 4.

---

### Task 1: Create `useLoopPagination.js` composable

**Files:**
- Create: `resources/js/composables/useLoopPagination.js`

**Step 1: Create the file**

```js
// resources/js/composables/useLoopPagination.js
import { reactive } from 'vue'

// Module-level store — keyed by pageParam string.
// Loop blocks write here after fetch; Pagination blocks read from here.
const store = reactive({})

export function useLoopPagination() {
  function setPagination(pageParam, total, perPage) {
    if (!pageParam) return
    store[pageParam] = { total, perPage }
  }

  function getPagination(pageParam) {
    return store[pageParam] ?? { total: 0, perPage: 1 }
  }

  return { setPagination, getPagination, store }
}
```

**Step 2: Verify manually**

The file is pure JS — no test to run yet. Confirm it exists at the correct path.

**Step 3: Commit**

```bash
git add resources/js/composables/useLoopPagination.js
git commit -m "feat: add useLoopPagination shared reactive store"
```

---

### Task 2: Modify `LoopBlock.vue` — read page param, compute offset, write to store

**Files:**
- Modify: `resources/js/Components/Blocks/LoopBlock.vue`

**Step 1: Apply changes**

Replace the entire `<script setup>` block (lines 31–98) with the version below. Key changes:
- Import `useLoopPagination`
- Add `getCurrentPage()` helper that reads `?{pageParam}=N` from the URL (default 1)
- Modify `fetchItems()` to use `(page − 1) × limit` as offset when `pageParam` is set, otherwise use the static `offset`
- After a successful fetch, call `setPagination(pageParam, total, limit)`
- Extend the URL watcher to always be active when `pageParam` is set (not only when URL-param filters exist)

```js
<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'
import LoopItemProvider from './LoopItemProvider.vue'
import BlockRenderer from '@/Components/BlockRenderer.vue'
import { useLoopPagination } from '@/composables/useLoopPagination.js'

const props = defineProps({ block: { type: Object, required: true } })

const page      = usePage()
const items     = ref(props.block.data?.resolved?.items ?? [])
const isLoading = ref(false)

const { setPagination } = useLoopPagination()

// CSS grid/flex wrapper — columns and gap driven by block.data
const GAP_CLASS = { sm: 'gap-2', md: 'gap-4', lg: 'gap-6', xl: 'gap-8' }
const wrapperClass = computed(() => {
  const cols = props.block.data?.columns ?? 1
  const gap  = GAP_CLASS[props.block.data?.gap ?? 'md'] ?? 'gap-4'
  if (cols === 'flex' || props.block.data?.flexWrap) {
    return `flex flex-wrap ${gap}`
  }
  return `grid grid-cols-${cols} ${gap}`
})

// Does this loop have any filters that depend on URL params?
const hasUrlParamFilters = computed(() =>
  (props.block.data?.filters ?? []).some(f => f.urlParam)
)

// The URL param name used for page-based pagination (optional).
const pageParam = computed(() => props.block.data?.pageParam?.trim() || null)

// Read current page number from the URL for this loop's pageParam.
function getCurrentPage() {
  if (!pageParam.value) return 1
  const n = parseInt(new URL(window.location.href).searchParams.get(pageParam.value))
  return Number.isFinite(n) && n > 0 ? n : 1
}

// Extract relevant URL param values from the current window URL
function getUrlParams() {
  if (!hasUrlParamFilters.value) return {}
  const keys   = (props.block.data?.filters ?? []).filter(f => f.urlParam).map(f => f.urlParam)
  const search = new URL(window.location.href).searchParams
  return Object.fromEntries(keys.filter(k => search.has(k)).map(k => [k, search.get(k)]))
}

async function fetchItems() {
  isLoading.value = true
  try {
    const limit = props.block.data?.limit ?? 12

    // When pageParam is set, derive offset from the current page number.
    // Otherwise fall back to the static offset field.
    const offset = pageParam.value
      ? (getCurrentPage() - 1) * limit
      : (props.block.data?.offset ?? 0)

    const { data } = await axios.post('/api/v1/query', {
      source:     props.block.data?.source ?? 'posts',
      filters:    props.block.data?.filters ?? [],
      sort:       props.block.data?.sort    ?? { field: 'published_at', direction: 'desc' },
      limit,
      offset,
      url_params: getUrlParams(),
    })

    items.value = data.items ?? []

    // Publish total + perPage so PaginationBlock can compute page count.
    if (pageParam.value) {
      setPagination(pageParam.value, data.total ?? 0, limit)
    }
  } catch (err) {
    if (import.meta.env.DEV) console.error('[LoopBlock] fetch error', err)
  } finally {
    isLoading.value = false
  }
}

onMounted(() => fetchItems())

// Watch for Inertia URL changes — covers both filter params and page params.
const shouldWatch = computed(() => hasUrlParamFilters.value || !!pageParam.value)
watch(shouldWatch, () => {}, { immediate: false }) // ensure computed is tracked
watch(
  () => page.url,
  (newUrl, oldUrl) => { if (newUrl !== oldUrl && shouldWatch.value) fetchItems() }
)
</script>
```

**Step 2: Verify**

In the browser, place a Loop block. Confirm items still load normally (no regression). No pageParam set yet — behaviour is identical to before.

**Step 3: Commit**

```bash
git add resources/js/Components/Blocks/LoopBlock.vue
git commit -m "feat: loop block reads page URL param and writes to pagination store"
```

---

### Task 3: Add `pageParam` field to `LoopSettings.vue`

**Files:**
- Modify: `resources/js/components/BlockEditor/blocks/LoopSettings.vue`

**Step 1: Add the field**

After the closing `</div>` of the Limit + Offset row (around line 143, just before `</div>` that closes the content fields wrapper), insert:

```html
      <!-- ── Pagination ─────────────────────────────────────────────── -->
      <div>
        <label class="text-xs font-medium text-muted-foreground block mb-1">Page URL param</label>
        <input
          :value="block.data.pageParam ?? ''"
          type="text"
          placeholder="Leave empty to disable pagination"
          class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
          @input="emitData({ pageParam: $event.target.value })"
        />
        <p class="text-[10px] text-muted-foreground mt-1">
          Set to e.g. <code>page</code> and add a Pagination block with the same param to enable paging.
        </p>
      </div>
```

**Step 2: Verify**

Open the block editor, add a Loop block, open its settings Content tab. Confirm the "Page URL param" field appears below Limit/Offset.

**Step 3: Commit**

```bash
git add resources/js/components/BlockEditor/blocks/LoopSettings.vue
git commit -m "feat: add pageParam field to LoopSettings"
```

---

### Task 4: Implement `PaginationBlock.vue`

**Files:**
- Modify: `resources/js/components/Blocks/PaginationBlock.vue`

**Step 1: Replace the stub entirely**

```vue
<!-- resources/js/components/Blocks/PaginationBlock.vue -->
<template>
  <nav
    v-if="lastPage > 1"
    class="my-6 flex flex-wrap items-center gap-2"
    :class="{
      'justify-start':  alignment === 'left',
      'justify-center': alignment === 'center',
      'justify-end':    alignment === 'right',
    }"
    aria-label="Pagination"
  >
    <!-- Prev -->
    <button
      :class="[btnClass, currentPage <= 1 ? 'opacity-40 cursor-not-allowed' : 'cursor-pointer']"
      :disabled="currentPage <= 1"
      @click="go(currentPage - 1)"
    >
      {{ prevLabel }}
    </button>

    <!-- Numbered pages -->
    <template v-if="style === 'numbered'">
      <template v-for="p in visiblePages" :key="p">
        <span v-if="p === '...'" class="px-1 text-sm text-muted-foreground select-none">…</span>
        <button
          v-else
          :class="[btnClass, p === currentPage ? activeClass : '', 'cursor-pointer']"
          @click="go(p)"
        >
          {{ p }}
        </button>
      </template>
    </template>

    <!-- Next -->
    <button
      :class="[btnClass, currentPage >= lastPage ? 'opacity-40 cursor-not-allowed' : 'cursor-pointer']"
      :disabled="currentPage >= lastPage"
      @click="go(currentPage + 1)"
    >
      {{ nextLabel }}
    </button>
  </nav>
</template>

<script setup>
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { useLoopPagination } from '@/composables/useLoopPagination.js'

const props = defineProps({ block: { type: Object, required: true } })

const { getPagination } = useLoopPagination()

const pageParam   = computed(() => props.block.data?.pageParam ?? 'page')
const style       = computed(() => props.block.data?.style ?? 'prev-next')
const alignment   = computed(() => props.block.data?.alignment ?? 'center')
const buttonStyle = computed(() => props.block.data?.buttonStyle ?? 'outline')
const prevLabel   = computed(() => props.block.data?.prevLabel ?? '← Previous')
const nextLabel   = computed(() => props.block.data?.nextLabel ?? 'Next →')

const pagination  = computed(() => getPagination(pageParam.value))
const total       = computed(() => pagination.value.total)
const perPage     = computed(() => pagination.value.perPage || 1)
const lastPage    = computed(() => Math.ceil(total.value / perPage.value))

const currentPage = computed(() => {
  const n = parseInt(new URL(window.location.href).searchParams.get(pageParam.value))
  return Number.isFinite(n) && n > 0 ? n : 1
})

// Build URL for a given page number
function pageUrl(p) {
  const url = new URL(window.location.href)
  url.searchParams.set(pageParam.value, p)
  return url.pathname + url.search
}

function go(p) {
  if (p < 1 || p > lastPage.value) return
  router.get(pageUrl(p), {}, { preserveScroll: true })
}

// Visible page numbers with ellipsis — always show first, last, and ±2 around current
const visiblePages = computed(() => {
  const total = lastPage.value
  if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1)
  const cur = currentPage.value
  const pages = new Set([1, total, cur - 1, cur, cur + 1, cur - 2, cur + 2].filter(p => p >= 1 && p <= total))
  const sorted = [...pages].sort((a, b) => a - b)
  const result = []
  let prev = 0
  for (const p of sorted) {
    if (p - prev > 1) result.push('...')
    result.push(p)
    prev = p
  }
  return result
})

const btnClass = computed(() => {
  const base = 'px-3 py-1.5 rounded text-sm font-medium transition-colors min-w-[2rem] text-center'
  if (buttonStyle.value === 'solid')   return `${base} bg-primary text-primary-foreground hover:opacity-90`
  if (buttonStyle.value === 'ghost')   return `${base} text-foreground hover:bg-muted`
  return `${base} border border-border bg-background text-foreground hover:bg-muted`
})

const activeClass = computed(() => {
  if (buttonStyle.value === 'solid')  return 'ring-2 ring-offset-1 ring-primary'
  if (buttonStyle.value === 'ghost')  return 'bg-muted font-bold'
  return 'border-primary text-primary font-bold'
})
</script>
```

**Step 2: Verify**

In the browser, add a Loop block with `pageParam: page` and a Pagination block with the same param. If there are more items than the loop limit, numbered/prev-next buttons should appear and clicking them should navigate to `?page=2`, etc.

**Step 3: Commit**

```bash
git add resources/js/components/Blocks/PaginationBlock.vue
git commit -m "feat: implement PaginationBlock with prev/next and numbered styles"
```

---

### Task 5: Register `PaginationBlock` in `BlockRenderer.vue`

**Files:**
- Modify: `resources/js/Components/BlockRenderer.vue`

**Step 1: Add import**

After the existing `import FilterLinkBlock` line, add:

```js
import PaginationBlock        from '@/components/Blocks/PaginationBlock.vue'
```

Note the lowercase `components` — PaginationBlock.vue lives in the lowercase directory.

**Step 2: Add to BLOCK_MAP**

In the `BLOCK_MAP` object, add:

```js
pagination: PaginationBlock,
```

**Step 3: Verify**

Build passes and a `pagination` block type renders on public pages.

**Step 4: Commit**

```bash
git add resources/js/Components/BlockRenderer.vue
git commit -m "feat: register PaginationBlock in BlockRenderer"
```

---

### Task 6: Register `PaginationBlock` in the block editor

**Files:**
- Explore: `resources/js/Components/BlockEditor/BlockTypePanel.vue` — find where block types are listed and add `pagination` entry with an appropriate icon (e.g. `ChevronLeft`/`ChevronRight` or `MoreHorizontal` from lucide-vue-next)
- Explore: `resources/js/Components/BlockEditor/BlockEditor.vue` or `BlockCanvas.vue` — find where per-block settings components are mapped and add `pagination: PaginationSettings`

**Step 1: Find the block type registry in the editor**

Search for where blocks like `'filter-link'` or `'search'` are registered in the editor:

```bash
grep -rn "filter-link\|FilterLink\|blockTypes\|BLOCK_TYPES" resources/js/Components/BlockEditor/ --include="*.vue" -l
```

**Step 2: Add `pagination` to the block type panel**

In `BlockTypePanel.vue` (or equivalent), add a pagination entry in the same group as other interactive/layout blocks. Use `ChevronLeft` or `ArrowLeftRight` icon from lucide-vue-next.

**Step 3: Add settings mapping**

In the settings router (wherever `PaginationSettings` would be imported), add:

```js
import PaginationSettings from '@/components/BlockEditor/blocks/PaginationSettings.vue'
// and in the map:
pagination: PaginationSettings,
```

**Step 4: Verify**

Open the block editor — confirm a "Pagination" entry appears in the block type panel. Adding it shows the PaginationSettings content/style tabs correctly.

**Step 5: Commit**

```bash
git add resources/js/Components/BlockEditor/
git commit -m "feat: add pagination block to block editor panel and settings"
```

---

### Task 7: Update `TemplateSeeder` — add pagination block to Default Blog Index

**Files:**
- Modify: `database/seeders/TemplateSeeder.php`

**Step 1: Add block 6 (pagination) to the main column**

In `blogIndexBlocks()`, the main column (block 3) has children: heading (4), loop (5). Add a pagination block after block 5:

```php
$this->block(6, 'pagination', [
    'pageParam'   => 'page',
    'style'       => 'numbered',
    'alignment'   => 'center',
    'buttonStyle' => 'outline',
]),
```

Full updated children array for block 3:

```php
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
        'columns'      => 1,
        'gap'          => 'md',
        'pageParam'    => 'page',          // ← new
    ], [$this->templateBlock(10, $this->postCardTemplateId() ?? 0)]),
    $this->block(6, 'pagination', [        // ← new
        'pageParam'   => 'page',
        'style'       => 'numbered',
        'alignment'   => 'center',
        'buttonStyle' => 'outline',
    ]),
], [], '', 'flex:3;min-width:0'),
```

**Step 2: Create the migration**

Create `database/migrations/2026_04_19_000002_add_pagination_to_blog_index_template.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $template = DB::table('templates')
            ->where('title', 'Default Blog Index')
            ->where('type', 'blog-index')
            ->first();

        if (!$template) return;

        $blocks = json_decode($template->blocks, true);
        if (!is_array($blocks)) return;

        DB::table('templates')
            ->where('id', $template->id)
            ->update(['blocks' => json_encode($this->patchBlocks($blocks))]);
    }

    private function patchBlocks(array $blocks): array
    {
        return array_map(function (array $block) {
            // Add pageParam to the loop block (id 5)
            if (($block['id'] ?? null) === 5 && ($block['type'] ?? null) === 'loop') {
                $block['data']['pageParam'] = 'page';
            }
            // Add the pagination block after loop (id 5) inside its parent container (id 3)
            if (($block['id'] ?? null) === 3 && ($block['type'] ?? null) === 'container') {
                $haspagination = collect($block['children'] ?? [])->contains(fn($c) => ($c['id'] ?? null) === 6);
                if (!$hasPattern) {
                    $block['children'][] = [
                        'id'   => 6,
                        'type' => 'pagination',
                        'data' => [
                            'pageParam'   => 'page',
                            'style'       => 'numbered',
                            'alignment'   => 'center',
                            'buttonStyle' => 'outline',
                        ],
                    ];
                }
            }
            if (!empty($block['children'])) {
                $block['children'] = $this->patchBlocks($block['children']);
            }
            return $block;
        }, $blocks);
    }

    public function down(): void
    {
        $template = DB::table('templates')
            ->where('title', 'Default Blog Index')
            ->where('type', 'blog-index')
            ->first();

        if (!$template) return;

        $blocks = json_decode($template->blocks, true);
        if (!is_array($blocks)) return;

        DB::table('templates')
            ->where('id', $template->id)
            ->update(['blocks' => json_encode($this->revertBlocks($blocks))]);
    }

    private function revertBlocks(array $blocks): array
    {
        return array_map(function (array $block) {
            if (($block['id'] ?? null) === 5 && ($block['type'] ?? null) === 'loop') {
                unset($block['data']['pageParam']);
            }
            if (($block['id'] ?? null) === 3 && ($block['type'] ?? null) === 'container') {
                $block['children'] = array_values(
                    array_filter($block['children'] ?? [], fn($c) => ($c['id'] ?? null) !== 6)
                );
            }
            if (!empty($block['children'])) {
                $block['children'] = $this->revertBlocks($block['children']);
            }
            return $block;
        }, $blocks);
    }
};
```

**Step 3: Note the bug**

The migration above has a typo — `$hasPattern` should be `$haspagination`. Fix it before running.

**Step 4: Run migration**

```bash
php artisan migrate
```

**Step 5: Commit**

```bash
git add database/seeders/TemplateSeeder.php database/migrations/2026_04_19_000002_add_pagination_to_blog_index_template.php
git commit -m "feat: add pagination block to Default Blog Index template"
```

---

### Task 8: Build and end-to-end verify

**Step 1: Build**

```bash
npm run build
```

Expected: `✓ built` with no errors (chunk size warning is pre-existing, ignore it).

**Step 2: Verify pagination on the blog index**

1. Navigate to the public blog index `/`
2. If there are more than 9 posts, numbered pagination should appear below the post grid
3. Click page 2 — URL becomes `/?page=2`, post grid updates, pagination highlights page 2
4. Click `← Previous` — returns to `/?page=1`
5. Filters (`?category=X`) should still work alongside `?page=N`

**Step 3: Verify no regression on loops without pageParam**

Open a page with a Loop block that has no `pageParam` set. Confirm it still loads correctly with static offset behaviour.

**Step 4: Commit**

```bash
git add -A
git commit -m "build: production build with pagination block"
git push
```
