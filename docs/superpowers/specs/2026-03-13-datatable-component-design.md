# DataTable Component & Dark Mode Background Fix — Design Spec

**Date:** 2026-03-13
**Status:** Draft

---

## Overview

Two related changes:

1. **`DataTable.vue`** — a new reusable shell component that provides consistent table framing (border, rounded corners, loading skeleton, empty state) across the app. Parents retain full control over column headers and row markup via slots.
2. **Dark mode background fix** — replace the Nord-tinted `--background` token with a more desaturated dark slate.

---

## Part 1: DataTable Component

### Philosophy

The component is a *visual shell*, not a data-management layer. It owns the outer frame and presentation states (loading, empty). It does **not** own column definitions, sorting, filtering, pagination, or bulk actions — those stay in the parent page.

### File

- **Create:** `resources/js/Components/DataTable.vue`

### Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `loading` | `Boolean` | `false` | When true, hides `#rows`/`#empty` and shows skeleton rows |
| `empty` | `Boolean` | `false` | When true (and not loading), shows empty state instead of `#rows` |

### Slots

| Name | Required | Description |
|------|----------|-------------|
| `#headers` | Yes | `<th>` elements for the header row |
| `#rows` | Yes | `<tr>` elements for data rows |
| `#empty` | No | Custom empty state content; defaults to "No results found." |

### Rendered Structure

```
<div class="rounded-lg border overflow-hidden">
  <table class="w-full text-sm">
    <thead class="bg-muted/50">
      <tr>
        [#headers slot — parent provides <th> elements]
      </tr>
    </thead>
    <tbody class="divide-y divide-border">

      [when loading]
      → 5 × <tr><td colspan="100"><skeleton bar /></td></tr>

      [when empty and not loading]
      → <tr><td colspan="100">[#empty slot or default text]</td></tr>

      [otherwise]
      → [#rows slot — parent provides <tr> elements]

    </tbody>
  </table>
</div>
```

### Styles Applied by Component

**`<th>` elements** (applied via CSS targeting `thead th`):
- Padding: `px-4 py-3`
- Text: `text-left text-xs font-medium text-muted-foreground uppercase tracking-wide`

**`<td>` elements** (applied via CSS targeting `tbody td`):
- Padding: `px-4 py-3`

Parents remove any existing inline `<th>`/`<td>` padding classes when migrating — the component enforces them consistently.

**Loading skeleton rows:**
- 5 rows
- Each: `<tr><td colspan="100" class="px-4 py-3"><div class="h-4 bg-muted animate-pulse rounded" /></td></tr>`

**Empty state:**
- `<tr><td colspan="100" class="px-4 py-12 text-center text-sm text-muted-foreground">`
- Default text: "No results found."

### Row hover

`hover:bg-muted/30 transition-colors` is **not** applied by the component. Parents place it on their `<tr>` elements directly. This gives pages flexibility (e.g. a future clickable-row variant).

### Example Usage

```vue
<DataTable :loading="loading" :empty="posts.length === 0">
  <template #headers>
    <th>Title</th>
    <th>Author</th>
    <th>Status</th>
    <th class="w-px" />
  </template>
  <template #rows>
    <tr
      v-for="post in posts"
      :key="post.id"
      class="hover:bg-muted/30 transition-colors"
    >
      <td>{{ post.title }}</td>
      <td>{{ post.author.name }}</td>
      <td><Badge>{{ post.status }}</Badge></td>
      <td class="text-right">
        <Link :href="route('posts.edit', post.id)">Edit</Link>
      </td>
    </tr>
  </template>
</DataTable>
```

---

## Part 2: Dark Mode Background Fix

### Problem

The current dark mode `--background` value is `#2e3440` (Nord Polar Night 1), which has a noticeable blue-tinted cast. It reads as "Nord-themed" rather than neutral dark.

### Change

**File:** `resources/scss/app.scss`

In the `.dark` block, update `--background`:

```scss
// Before
--background: #2e3440;

// After — desaturated dark slate, near-neutral with faint cool undertone
--background: oklch(14% 0.01 260);
```

`oklch(14% 0.01 260)` is approximately `#191c21` in hex — similar lightness to `#2e3440` but with the saturation stripped back from 0.07 to 0.01, removing the Nord blue cast.

No other dark mode tokens change. `--card`, `--muted`, and `--secondary` retain their existing values; they provide enough contrast against the updated background.

### Scope

This affects the app background everywhere in dark mode: dashboard, content pages, and the right panel of `AuthLayout.vue` (the auth split-panel). The left panel of `AuthLayout` uses a hard-coded `bg-[#2e3440]` on the canvas container — that value is **not** changed (it's intentional, tied to the particle simulation aesthetic).

---

## Part 3: Migration of Existing Pages

Four index pages are refactored to use `DataTable`. No logic changes — only the table markup is updated.

### Pages

| File | Migration notes |
|------|-----------------|
| `resources/js/Pages/Categories/Index.vue` | Simple — name, description, post count, actions |
| `resources/js/Pages/Tags/Index.vue` | Simple — name, post count, actions |
| `resources/js/Pages/Users/Index.vue` | Medium — avatar, role badge, last-seen timestamp |
| `resources/js/Pages/Posts/Index.vue` | Complex — checkbox column, status badge; bulk action bar stays in parent |

### Migration Pattern (per page)

**Before:**
```vue
<div class="rounded-lg border overflow-hidden">
  <table class="w-full text-sm">
    <thead class="bg-muted/50">
      <tr>
        <th class="px-4 py-3 ...">Title</th>
        ...
      </tr>
    </thead>
    <tbody class="divide-y divide-border">
      <tr v-for="item in items" :key="item.id" class="hover:bg-muted/30">
        <td class="px-4 py-3">...</td>
      </tr>
    </tbody>
  </table>
</div>
```

**After:**
```vue
<DataTable :loading="false" :empty="items.length === 0">
  <template #headers>
    <th>Title</th>
    ...
  </template>
  <template #rows>
    <tr v-for="item in items" :key="item.id" class="hover:bg-muted/30 transition-colors">
      <td>...</td>
    </tr>
  </template>
</DataTable>
```

Key changes per page:
- Remove `rounded-lg border overflow-hidden` wrapper div
- Remove `bg-muted/50` from `<thead>` (component applies it)
- Remove `divide-y divide-border` from `<tbody>` (component applies it)
- Remove `px-4 py-3` from individual `<th>` and `<td>` elements (component applies them)
- Remove empty-state rows that pages currently handle inline (if any)
- Add `import DataTable from '@/Components/DataTable.vue'`

---

## Out of Scope

- Sorting, filtering, pagination — remain in parent pages
- Bulk action UI — remains in parent pages (Posts/Index)
- Column-level configuration (widths, alignment beyond left) — set via classes on `<th>`/`<td>` in the parent
- Server-side data fetching
- Virtualization or infinite scroll

---

## Acceptance Criteria

- [ ] `DataTable.vue` renders correctly with `#headers` and `#rows` slots
- [ ] Loading state shows 5 animated skeleton rows
- [ ] Empty state shows "No results found." by default; `#empty` slot overrides it
- [ ] `colspan="100"` correctly spans all columns for empty/loading states
- [ ] All four migrated pages render identically to their pre-migration appearance
- [ ] `Posts/Index.vue` bulk action bar continues to function after migration
- [ ] Dark mode background is visually desaturated (no Nord blue cast)
- [ ] Auth split-panel left canvas panel retains `bg-[#2e3440]`
- [ ] `npm run build` passes with no errors
- [ ] `php artisan test` passes (all existing tests)
