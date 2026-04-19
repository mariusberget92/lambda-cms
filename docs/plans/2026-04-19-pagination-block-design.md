# Pagination Block Design

**Date:** 2026-04-19
**Status:** Approved

---

## Problem

The Loop block currently renders a fixed slice of data (static `limit` + `offset`). There is no way for a visitor to navigate to the next page of results inside a block-editor-driven page. A `PaginationBlock` stub and `PaginationSettings` stub exist but are non-functional.

---

## Goals

- Let any Loop block become paginated by configuring a shared URL param name.
- Support multiple independent loop+pagination pairs on the same page (e.g. posts loop + articles loop, each with its own page param).
- Style configurable per block: **prev/next only** or **numbered** (with current page highlighted).
- Page navigation uses Inertia `router.get()` — consistent with FilterLinkBlock, back-button works, URL is shareable.

---

## Architecture

### Shared reactive store — `useLoopPagination.js`

A module-level composable holding a `reactive` map keyed by `pageParam`:

```js
// { "page": { total: 42, perPage: 9 }, "articles_page": { total: 17, perPage: 6 } }
const store = reactive({})

export function useLoopPagination() {
  return {
    setPagination(pageParam, total, perPage) { store[pageParam] = { total, perPage } },
    getPagination(pageParam) { return store[pageParam] ?? { total: 0, perPage: 1 } },
  }
}
```

Loop block writes to it after every fetch. Pagination block reads from it. No component coupling required — they are linked purely by convention (same `pageParam` value).

### Loop block changes

1. **New setting** — `pageParam` (string, optional). Exposed in LoopSettings below the existing Limit field.
2. **On mount** — if `pageParam` is set, read `?{pageParam}=N` from the URL, compute `offset = (N − 1) × limit`, pass to QueryBuilder.
3. **After fetch** — call `setPagination(pageParam, total, limit)` so the Pagination block can compute page count.
4. **No pageParam** — existing static `limit`/`offset` behaviour is completely unchanged.

### Pagination block

Reads from the shared store via `getPagination(pageParam)`:

```
currentPage  = parseInt(URLSearchParams.get(pageParam)) || 1
{ total, perPage } = getPagination(pageParam)
lastPage     = Math.ceil(total / perPage)
```

Generates page URLs by cloning the current `window.location.search` and replacing `pageParam=N`. Navigation uses `router.get(url, {}, { preserveScroll: true })`.

**Styles (configurable in block editor):**
- `prev-next` — renders `← Previous` / `Next →` buttons; buttons are disabled on first/last page.
- `numbered` — renders `← 1 2 … N →`; current page highlighted; first/last page buttons disabled.

**Alignment** — configurable: left / center / right (already in existing settings stub).

### PaginationSettings (block editor)

Already stubbed. Confirm/clean up:
- `pageParam` — text input (must match the Loop block's pageParam)
- `style` — select: `prev-next` | `numbered`
- `alignment` — select: `left` | `center` | `right`

---

## Files Changed

| File | Action |
|---|---|
| `resources/js/composables/useLoopPagination.js` | Create |
| `resources/js/Components/Blocks/LoopBlock.vue` | Add pageParam URL reading + store write |
| `resources/js/components/BlockEditor/blocks/LoopSettings.vue` | Add pageParam field |
| `resources/js/components/Blocks/PaginationBlock.vue` | Replace stub with real implementation |
| `resources/js/components/BlockEditor/blocks/PaginationSettings.vue` | Review and clean up |
| `resources/js/Components/BlockRenderer.vue` | Register PaginationBlock |
| `database/seeders/TemplateSeeder.php` | Add pagination block after loop in blog index |
| `database/migrations/2026_04_19_000002_add_pagination_to_blog_index_template.php` | Patch existing DB template |

---

## Out of Scope

- Server-side (Inertia) pagination — blog index page already handles this separately.
- Infinite scroll / load-more — a future feature.
- Pagination on category/tag archive pages — those use Inertia pagination, not the Loop block.
