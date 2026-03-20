# Component Block — Post List Design

**Date:** 2026-03-20
**Status:** Approved

## Overview

Add a `component` block type to the block editor. Users drag it onto the canvas like any other block, then configure a dynamic content source (post list) via the settings panel. Data is resolved server-side when the page loads — no client-side API calls.

Only one sub-type exists for now: `post-list`. The architecture supports adding more sub-types later.

---

## 1. Data Model

### Stored in `pages.blocks` (never includes `resolved`)

```json
{
  "id": "uuid",
  "type": "component",
  "data": {
    "component": "post-list",
    "limit": 6,
    "offset": 0,
    "order": "latest",
    "featured_only": false,
    "category_ids": [1, 3],
    "tag_ids": []
  }
}
```

### Enriched at render time (injected by controller, not persisted)

```json
"resolved": {
  "posts": [
    {
      "id": 1,
      "title": "Hello World",
      "slug": "hello-world",
      "excerpt": "...",
      "published_at": "2026-03-01T09:00:00Z",
      "author_name": "Admin",
      "featured_image_url": "https://..."
    }
  ]
}
```

### Migration

Add `featured` boolean column to `posts` table (default `false`) to support `featured_only` filter.

---

## 2. Backend

### `PublicPageController::show()`

Pipe blocks through `resolveBlocks()` before passing to Inertia:

```php
private function resolveBlocks(array $blocks): array
{
    return array_map(fn($block) => match(true) {
        $block['type'] === 'component' && ($block['data']['component'] ?? null) === 'post-list'
            => $this->resolvePostList($block),
        default => $block,
    }, $blocks);
}
```

### `resolvePostList()` query logic

Always applied:
- `status = 'published'`
- `published_at <= now()`

Conditional:
- `featured_only = true` → `where('featured', true)`
- `category_ids` non-empty → `whereHas('categories', fn($q) => $q->whereIn('id', $ids))`
- `tag_ids` non-empty → `whereHas('tags', fn($q) => $q->whereIn('id', $ids))`

Ordering (`order` field):
- `latest` → `orderByDesc('published_at')`
- `oldest` → `orderBy('published_at')`
- `alpha` → `orderBy('title')`

Pagination: `skip($offset)->take($limit)`

Selects: `id`, `title`, `slug`, `excerpt`, `published_at`, with eager-loaded `author` (name only) and `featuredImage` (url only).

### `PageController::create()` and `edit()`

Pass `categories` and `tags` as Inertia props so the settings panel can populate its filter checkboxes:

```php
return Inertia::render('Pages/Create', [
    'categories' => Category::orderBy('name')->get(['id', 'name']),
    'tags'       => Tag::orderBy('name')->get(['id', 'name']),
]);
```

---

## 3. Block Editor — Settings Panel

### New files

- `resources/js/components/BlockEditor/blocks/ComponentSettings.vue`
  - Sub-form for `post-list`: limit, offset, order select, featured_only checkbox, category multi-select, tag multi-select
  - `component` select is present but only "Post List" is available (extensible)

### Prop chain for categories/tags

`Pages/Create.vue` / `Pages/Edit.vue`
→ `BlockEditor` (new `meta` prop: `{ categories, tags }`)
→ `BlockLayers` (forwards `meta`)
→ `ComponentSettings` (receives `meta.categories`, `meta.tags`)

### `BlockTypePanel` addition

Add `component` to `ALL_TYPES`:
```js
{ type: 'component', label: 'Component', icon: '⚙️' }
```

Default data:
```js
{ component: 'post-list', limit: 6, offset: 0, order: 'latest', featured_only: false, category_ids: [], tag_ids: [] }
```

### `BlockEditor.vue` `defaultData()`

Add `component` entry matching the default data above.

---

## 4. Public Rendering

### `PostListBlock.vue`

- Reads `block.data.resolved.posts`
- Renders a responsive grid of post cards (featured image, title link, excerpt, author, date)
- Shows "No posts found" if resolved list is empty or missing

### `BlockRenderer.vue`

Add `component: PostListBlock` to `BLOCK_MAP`.

---

## Files to Create / Modify

| Action | File |
|--------|------|
| Create | `database/migrations/XXXX_add_featured_to_posts_table.php` |
| Create | `resources/js/components/BlockEditor/blocks/ComponentSettings.vue` |
| Create | `resources/js/components/Blocks/PostListBlock.vue` |
| Modify | `app/Http/Controllers/PublicPageController.php` |
| Modify | `app/Http/Controllers/PageController.php` |
| Modify | `resources/js/components/BlockEditor/BlockTypePanel.vue` |
| Modify | `resources/js/components/BlockEditor/BlockEditor.vue` |
| Modify | `resources/js/components/BlockEditor/BlockLayers.vue` |
| Modify | `resources/js/components/BlockRenderer.vue` |
| Modify | `resources/js/Pages/Pages/Create.vue` |
| Modify | `resources/js/Pages/Pages/Edit.vue` |
