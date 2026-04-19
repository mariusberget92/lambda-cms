# Table Block Design

**Date:** 2026-04-10
**Status:** Approved

## Overview

Add a `table` block type to the block editor. Supports both a manually authored static table and a data-driven dynamic table bound to the existing CMS query sources (posts, categories, tags, pages). Both modes share a unified column-first data model and the same styling controls.

---

## Approach: Option C — Unified column-first model

One `table` block with a Static / Dynamic mode toggle. Columns are always the primary concept; switching modes changes only how rows are populated, not the column definitions. This keeps prefix/suffix, alignment, and header labels intact when switching modes.

---

## Data Model (`block.data`)

```js
{
  mode: 'static' | 'dynamic',

  // Columns — shared by both modes
  columns: [
    { id: 'uuid', label: 'Name',  field: 'title',      prefix: '',  suffix: '', align: 'left' },
    { id: 'uuid', label: 'Price', field: 'meta_price', prefix: '£', suffix: '', align: 'right' },
  ],

  // Static mode — rows keyed by column ID (survives add/remove column)
  rows: [
    { 'col-id-1': 'Product A', 'col-id-2': '9.99' },
    { 'col-id-1': 'Product B', 'col-id-2': '14.99' },
  ],

  // Dynamic mode — identical shape to Loop block
  source:       'posts',
  filters:      [],
  filter_logic: 'and',
  sort:         { field: 'published_at', direction: 'desc' },
  limit:        10,
  offset:       0,

  // Styling — both modes
  striped:     true,
  borderStyle: 'full',    // 'full' | 'outer' | 'none'
  headerStyle: true,
  responsive:  'scroll',  // 'scroll' | 'stack'
}
```

Rows are keyed by column `id` so adding/removing columns never corrupts existing cell values.

---

## Settings Panel (`TableSettings.vue`)

### Content tab

- **Mode toggle** — Static / Dynamic pill buttons at the top
- **Columns section** (always visible):
  - Draggable list of column cards (VueDraggable)
  - Each card: Label input, Field dropdown (dynamic) or hidden (static), Prefix + Suffix inputs, Align toggle (L/C/R), Remove button
  - "+ Add Column" button
- **Static only — Rows section:**
  - Compact editable grid: each cell is an `<input>`
  - "+ Add Row" button; row delete button on each row
- **Dynamic only — Query section:**
  - Source dropdown, Filters (reuse LoopSettings pattern + `loopSources.js`), Sort field + direction, Limit + Offset
  - Identical UI to LoopSettings content tab

### Style tab

- Striped rows — checkbox toggle
- Border style — 3-button toggle: Full grid / Outer only / None
- Header styling — checkbox toggle (bold + distinct background color)
- Responsive — 2-button toggle: Scroll horizontally / Stack columns

---

## Canvas Editor (`EditorTableBlock.vue`)

- Renders a real `<table>` inside the dark editor canvas
- **Static mode:** `<td>` cells use `contenteditable` — click to edit inline; blur/enter syncs via `update` emit
- **Dynamic mode:** fetches `POST /api/v1/query` on mount and on source/filter change; renders live rows with a skeleton shimmer while loading
- Column headers always come from `column.label`
- Prefix/suffix rendered flanking each cell value
- Styling (striped, borders, header) applied live in editor preview

---

## Public Renderer (`TableBlock.vue`)

- **Static:** pure render from `data.rows` + `data.columns` — no server calls
- **Dynamic:** data resolved server-side via `QueryBuilder` and injected into `block.resolvedData` (same pattern as Loop block) — no client fetch on public pages
- **Responsive scroll:** wraps `<table>` in `overflow-x-auto` div
- **Responsive stack:** CSS collapses each row into label+value pairs on `sm` breakpoint using `data-label` attributes and `display: block` cells
- Prefix/suffix rendered as muted-foreground flanking spans

---

## Files

| File | Action |
|---|---|
| `resources/js/components/BlockEditor/blocks/TableSettings.vue` | Create |
| `resources/js/components/BlockEditor/EditorTableBlock.vue` | Create |
| `resources/js/components/blocks/TableBlock.vue` | Create (public renderer) |
| `resources/js/components/BlockEditor/BlockTypePanel.vue` | Add `table` to Interactive group, add default data |
| `resources/js/components/BlockEditor/BlockCanvas.vue` | Add `v-else-if="block.type === 'table'"` case |
| `resources/js/components/BlockEditor/BlockEditor.vue` | Add `table` to `CHILD_CAPABLE` check is N/A (leaf block) |
| `resources/js/components/BlockEditor/BlockLayers.vue` | Register `TableSettings` in `COMPONENT_MAP`, add `table` to `STYLE_BLOCKS` |
| `resources/js/components/BlockRenderer.vue` | Add `TableBlock` to `BLOCK_MAP` |

`QueryBuilder.php` — no changes needed; `table` dynamic mode reuses the existing `/api/v1/query` endpoint identically to Loop.

---

## Default Block Data

```js
table: {
  mode: 'static',
  columns: [
    { id: crypto.randomUUID(), label: 'Column 1', field: '', prefix: '', suffix: '', align: 'left' },
    { id: crypto.randomUUID(), label: 'Column 2', field: '', prefix: '', suffix: '', align: 'left' },
  ],
  rows: [
    {},
    {},
  ],
  source: 'posts',
  filters: [],
  filter_logic: 'and',
  sort: { field: 'published_at', direction: 'desc' },
  limit: 10,
  offset: 0,
  striped: true,
  borderStyle: 'full',
  headerStyle: true,
  responsive: 'scroll',
}
```
