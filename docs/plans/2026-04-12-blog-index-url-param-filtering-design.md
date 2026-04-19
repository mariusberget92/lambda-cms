# Blog Index URL-Param Filtering — Design

**Date:** 2026-04-12
**Status:** Approved

## Goal

Redesign the default `blog-index` template to have a two-column layout (post grid + sidebar) and introduce live in-place filtering: clicking a category or tag in the sidebar updates the URL (`/?category=slug` or `/?tag=slug`) and the post grid silently re-fetches the filtered results without a page reload.

Also fix the `QueryController` validation gap that was blocking the existing `contains` op.

---

## Layout

Two-column flex-row layout inside a section block:

- **Main column** (`flex: 3`): post loop (3-col grid) with URL-param filters
- **Sidebar column** (`flex: 1`): search block, categories list, tags list

Width ratio is enforced via `customCss` (`flex: 3` / `flex: 1`) on each column's block, which overrides ContainerBlock's default `flex-1` through ID-selector CSS specificity.

---

## Filtering Mechanism

The main post loop carries two `urlParam` filters:

```json
[
  { "field": "category_slug", "op": "=", "urlParam": "category" },
  { "field": "tag_slug",      "op": "=", "urlParam": "tag"      }
]
```

`filter_logic: "and"`.

When the URL becomes `/?category=technology`, LoopBlock detects `hasUrlParamFilters`, sends `url_params: { category: "technology" }` to `/api/v1/query`, and the post grid updates in place. LoopBlock already watches Inertia's `page.url` and re-fetches on change — no new JS infrastructure needed.

---

## Sidebar Filter Links

### Problem
Sidebar category/tag items need to navigate to `/?category={slug}` via Inertia (not a native anchor click), so the URL watcher fires and no full reload occurs. They also need an **active state** (visual highlight) when the current URL already has the matching param+value.

### Solution — new `FilterLinkBlock` component

A new block type `filter-link` that:
- Renders an Inertia-aware anchor (uses `router.get(url)` via `@click.prevent`)
- Reads `block.data.paramName` (e.g. `"category"`) and `loop:slug` via `useFieldBinding` to construct the filter URL: `/?{paramName}={slug}`
- Reads the current URL search params to detect the active state and applies an active CSS class (ring / bold)
- Binds `label` via `useFieldBinding` to `loop:name` (optionally appending `loop:posts_count`)

### `filter_url` field in QueryBuilder
QueryBuilder adds a `filter_url` field to each category and tag item:
- Categories: `/?category={slug}`
- Tags: `/?tag={slug}`

The `FilterLinkBlock` does not rely on this field directly (it constructs the URL itself from `paramName` + `loop:slug`), but the field is available as a binding fallback.

---

## Backend Changes

### `QueryBuilder.php`
1. Add `category_slug` filter field for posts — JOIN `category_post` + `categories`, filter on `categories.slug`
2. Add `tag_slug` filter field for posts — JOIN `post_tag` + `tags`, filter on `tags.slug`
3. Add `filter_url` to category items: `'/?category=' . $cat->slug`
4. Add `filter_url` to tag items: `'/?tag=' . $tag->slug`

### `QueryController.php`
Add `category_slug` and `tag_slug` to the `filters.*.field` validation whitelist (currently only `featured`, `title`, `slug`).

---

## Frontend Changes

### New: `resources/js/Components/Blocks/FilterLinkBlock.vue`
- Props: `block` (type, data, bindings)
- Injects `loopItem` via `inject('loopItem')`
- Constructs filter URL: `/?{block.data.paramName}={loopItem.value.slug}`
- Uses `router.get()` via `@click.prevent` for Inertia navigation
- Active state: checks `new URLSearchParams(window.location.search).get(paramName) === slug`

### `BlockRenderer.vue`
Register `filter-link` → `FilterLinkBlock`.

### `BlockTypePanel.vue`
Add `filter-link` to `ALL_TYPES` (group: Interactive) with default data `{ paramName: 'category' }`.

### `BlockLayers.vue`
Register `FilterLinkSettings` in `COMPONENT_MAP`.

### New: `resources/js/Components/BlockEditor/blocks/FilterLinkSettings.vue`
Settings panel for `filter-link` — one field: `paramName` (text input, label "URL param name", placeholder `category`).

---

## Updated `TemplateSeeder.php`

`blogIndexBlocks()` is replaced with the new two-column layout:

```
Section (py-16, px-8, fullWidth)
  Container (flex-row, gap: 2rem)
    Container (flex-col) [main, customCss: flex:3]
      Heading "Latest Posts" (h2)
      Loop posts (3 cols, md gap, limit 9)
        filters: [category_slug urlParam:category, tag_slug urlParam:tag]
        filter_logic: and
        [post card: heading h3 → title, paragraph → excerpt, link → url]
    Container (flex-col) [sidebar, customCss: flex:1]
      SearchBlock
      Heading "Categories" (h3)
      Loop categories (1 col, sm gap, limit 20, sort: posts_count desc)
        FilterLinkBlock (paramName: category, label: loop:name)
      Heading "Tags" (h3)
      Loop tags (1 col, sm gap, limit 30, sort: posts_count desc)
        FilterLinkBlock (paramName: tag, label: loop:name)
```

---

## File Change Summary

| File | Action |
|------|--------|
| `app/Services/QueryBuilder.php` | Add `category_slug` / `tag_slug` JOIN filtering + `filter_url` field |
| `app/Http/Controllers/Api/V1/QueryController.php` | Whitelist `category_slug`, `tag_slug` in `filters.*.field` |
| `resources/js/Components/Blocks/FilterLinkBlock.vue` | New component |
| `resources/js/Components/BlockEditor/blocks/FilterLinkSettings.vue` | New settings panel |
| `resources/js/Components/BlockRenderer.vue` | Register `filter-link` |
| `resources/js/Components/BlockEditor/BlockTypePanel.vue` | Register `filter-link` type + default data |
| `resources/js/Components/BlockEditor/BlockLayers.vue` | Register `FilterLinkSettings` |
| `database/seeders/TemplateSeeder.php` | Update `blogIndexBlocks()` |
