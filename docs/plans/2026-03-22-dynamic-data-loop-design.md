# Dynamic Data — Loop Block Design

**Date:** 2026-03-22
**Status:** Approved

---

## Overview

Add a **Loop block** to the page builder that fetches a collection of records (posts, categories, tags, or pages), iterates over them, and renders a set of child blocks once per item. Child blocks can bind their content fields to item properties (e.g. heading text → `item.title`) and can declare visibility conditions (e.g. show only if `item.featured === true`). Filtering supports both URL-param-driven server rendering and live client-side re-fetching.

---

## Architecture: Hybrid Resolution

The system uses **server-side initial resolution** (SEO + fast first paint) combined with **client-side re-fetching** (live URL param filtering without page reload).

A shared `QueryBuilder` PHP service class encapsulates all query logic — used by both the page resolver and the API endpoint, with no duplication.

---

## 1. Block Schema

### Loop block (stored in DB)

```json
{
  "id": "uuid",
  "type": "loop",
  "data": {
    "source": "posts",
    "filters": [
      { "field": "status",      "op": "=",   "value": "published" },
      { "field": "featured",    "op": "=",   "value": true },
      { "field": "category_id", "op": "in",  "urlParam": "category" }
    ],
    "sort":   { "field": "published_at", "direction": "desc" },
    "limit":  12,
    "offset": 0,
    "columns": 3,
    "gap": "md"
  },
  "children": []
}
```

`resolved` is **never persisted**. It is injected at render time by the PHP resolver and stripped before save.

### Child block with binding + condition

```json
{
  "id": "uuid",
  "type": "heading",
  "data":     { "text": "Fallback",  "level": 2 },
  "bindings": { "text": "title" },
  "condition": { "field": "featured", "op": "===", "value": true }
}
```

`bindings` maps a block's data field name → the loop item property to use instead.
`condition` is `null` / absent when the block is always visible.

---

## 2. Data Sources & Exposed Fields

| Source       | Exposed fields                                                                 |
|--------------|--------------------------------------------------------------------------------|
| `posts`      | `title`, `slug`, `excerpt`, `body`, `featured`, `published_at`, `author_name`, `featured_image_url`, `url` |
| `categories` | `name`, `slug`, `description`, `posts_count`, `url`                           |
| `tags`       | `name`, `slug`, `posts_count`, `url`                                          |
| `pages`      | `title`, `slug`, `meta_description`, `url`                                    |

Field selection is **hardcoded per source** — no arbitrary column access, no raw SQL.
All sources return only published / public content; drafts and private data are never exposed.

---

## 3. Query API Endpoint

**`POST /api/v1/query`** — no authentication required (read-only public data).

### Request body

```json
{
  "source": "posts",
  "filters": [
    { "field": "status",      "op": "=",   "value": "published" },
    { "field": "category_id", "op": "in",  "urlParam": "category" }
  ],
  "sort":      { "field": "published_at", "direction": "desc" },
  "limit":     12,
  "offset":    0,
  "url_params": { "category": "news" }
}
```

`url_params` carries the page's current URL query string so the server can apply `urlParam` filters.

### Response

```json
{
  "items": [ ... ],
  "total": 45
}
```

---

## 4. Server-Side Resolution

`PublicPageController::resolveBlocks()` is extended to handle `type === 'loop'`:

```php
if ($block['type'] === 'loop') {
    $block['data']['resolved']['items'] = $this->queryBuilder->resolve(
        $block['data'],
        $request->query()
    );
}
```

`QueryBuilder` is a dedicated service (`app/Services/QueryBuilder.php`) injected into both `PublicPageController` and the new `QueryController` (API endpoint). Same logic, zero duplication.

---

## 5. Frontend Rendering

### provide/inject item context

`LoopBlock.vue` iterates resolved items. For each item it provides the current item via Vue's `provide/inject` — no prop threading through the component tree.

```vue
<div v-for="item in items" :key="item.slug || item.id">
  <LoopItemProvider :item="item">
    <BlockRenderer :blocks="block.children" />
  </LoopItemProvider>
</div>
```

`LoopItemProvider` is a tiny renderless wrapper that calls `provide('loopItem', ref(item))`.

### Dynamic binding in block components

Each block that supports binding reads the injected item:

```js
const loopItem = inject('loopItem', null)

const resolvedText = computed(() => {
  const binding = props.block.bindings?.text
  if (binding && loopItem?.value) return loopItem.value[binding] ?? props.block.data.text
  return props.block.data.text
})
```

Blocks that support binding: `heading`, `paragraph`, `image`, `cta`, `quote`.

### Conditional rendering

`BlockRenderer` checks `block.condition` before mounting each block:

```js
const loopItem = inject('loopItem', null)
const isVisible = computed(() => {
  const c = block.condition
  if (!c || !loopItem?.value) return true
  const v = loopItem.value[c.field]
  return c.op === '===' ? v === c.value
       : c.op === '!==' ? v !== c.value
       : c.op === 'not_empty' ? !!v
       : c.op === 'empty'     ? !v
       : true
})
```

### Live filtering (client-side)

`LoopBlock.vue` watches `usePage().url` (Inertia's reactive current URL). When any URL param matching a filter's `urlParam` changes, it POSTs to `/api/v1/query` with the updated `url_params` and replaces `items`. A loading skeleton replaces the grid during fetch.

---

## 6. Editor UI

### LoopSettings.vue — three tabs

**Query tab:**
- Source dropdown: Posts / Categories / Tags / Pages
- Filter builder — add/remove rows:
  - Field selector (per-source field list)
  - Operator: `=`, `!=`, `in`, `not_empty`, `empty`
  - Value input — OR "From URL param" toggle → param name input (e.g. `category`)
- Sort: field dropdown + asc/desc toggle
- Limit + Offset number inputs

**Appearance tab:**
- Columns per row (1–4) — controls CSS grid on the loop wrapper
- Gap size

### Dynamic binding in child block settings

`BlockEditor.vue` detects when the selected block is a descendant of a loop block. It passes `insideLoop: true` and `loopFields: ['title', 'slug', ...]` to the settings panel.

In settings components, bindable fields show a small **bind icon** button. Clicking it toggles between:
- Static input (default)
- Dropdown of `loopFields` → selection writes to `block.bindings.<field>`

Unbinding clears `block.bindings.<field>` and restores the static input.

### Condition tab (all blocks inside a loop)

An additional "Condition" tab appears in block settings when `insideLoop` is true:
- "Show only if…" toggle
- Field picker + operator (`===`, `!==`, `not_empty`, `empty`) + value input
- Stored in `block.condition`; null/absent = always visible

---

## 7. Canvas Editor

**`EditorLoopBlock.vue`:**
- Teal dashed border + **"Loop — {Source}"** label (e.g. "Loop — Posts")
- Drop zone for child blocks (same pattern as `EditorSectionBlock`)
- Preview: renders children using the **first resolved item** when available
- Empty state: "No items — check your query settings" when source returns nothing
- Skeleton placeholder cards when no resolved data exists yet

---

## 8. New Files

| File | Purpose |
|------|---------|
| `app/Services/QueryBuilder.php` | Shared query execution service (all sources) |
| `app/Http/Controllers/QueryController.php` | `POST /api/v1/query` endpoint |
| `resources/js/Components/Blocks/LoopBlock.vue` | Frontend loop renderer |
| `resources/js/Components/Blocks/LoopItemProvider.vue` | provide/inject item context per iteration |
| `resources/js/Components/BlockEditor/blocks/LoopSettings.vue` | Loop query/sort/appearance settings |
| `resources/js/Components/BlockEditor/EditorLoopBlock.vue` | Canvas drag-drop loop with drop zone |

## Modified Files

| File | Change |
|------|--------|
| `app/Http/Controllers/PublicPageController.php` | `resolveBlocks()` handles `loop` type via `QueryBuilder` |
| `routes/api.php` | Add `POST /api/v1/query` route |
| `resources/js/Components/BlockRenderer.vue` | `inject('loopItem')` + condition check + `loop` in BLOCK_MAP |
| `resources/js/Components/BlockEditor/BlockEditor.vue` | Detect loop ancestry, pass `insideLoop` + `loopFields` |
| `resources/js/Components/BlockEditor/BlockTypePanel.vue` | Register loop in ALL_TYPES + DEFAULT_DATA |
| `resources/js/Components/BlockEditor/BlockLayers.vue` | Route loop to LoopSettings |
| `resources/js/Components/BlockEditor/BlockCanvas.vue` | Render `EditorLoopBlock` |
| `resources/js/Components/Blocks/HeadingBlock.vue` | Support `bindings.text` |
| `resources/js/Components/Blocks/ParagraphBlock.vue` | Support `bindings.content` |
| `resources/js/Components/Blocks/ImageBlock.vue` | Support `bindings.url`, `bindings.alt` |
| `resources/js/Components/Blocks/CtaBlock.vue` | Support `bindings.headline`, `bindings.button_url` |
| `resources/js/Components/BlockEditor/blocks/HeadingSettings.vue` | Dynamic toggle for `text` |
| `resources/js/Components/BlockEditor/blocks/ParagraphSettings.vue` | Dynamic toggle for `content` |
| `resources/js/Components/BlockEditor/blocks/ImageSettings.vue` | Dynamic toggle for `url`, `alt` |
| `resources/js/Components/BlockEditor/blocks/CtaSettings.vue` | Dynamic toggle for `headline`, `button_url` |
| `resources/js/Components/BlockEditor/blocks/BlockSettingsBase.vue` (new or existing) | Condition tab injected when `insideLoop` |
