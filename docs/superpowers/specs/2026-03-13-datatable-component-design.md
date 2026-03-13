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

The `loading` prop is an affordance for future use. In all current pages, Inertia handles navigation loading natively — set `:loading="false"` on every current page. Do not wire it to Inertia's progress state.

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
    <thead class="bg-muted/50 border-b border-border">
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

Note: `border-b border-border` on `<thead>` ensures a visible separator line between the header row and the first data row. Some existing pages (Users) applied this via `border-b` on the `<thead> <tr>` — the component moves it to `<thead>` itself for consistency.

### Styles Applied by Component

The component applies styles to slotted `<th>` and `<td>` elements using `:deep()` selectors inside a `<style scoped>` block with `@apply`. This is the standard Vue 3 mechanism for styling slot content from the component owner.

```vue
<style scoped>
:deep(thead th) {
  @apply px-4 py-3 text-xs font-medium text-muted-foreground;
}
:deep(tbody td) {
  @apply px-4 py-3;
}
</style>
```

`@apply` works correctly in Tailwind CSS 4 SFC `<style>` blocks processed by Vite.

**`<th>` elements** — enforced by component:
- Padding: `px-4 py-3`
- Text: `text-xs font-medium text-muted-foreground`

Note: `text-left` is **not** enforced — column alignment is the parent's responsibility. Note: `text-xs` is a deliberate visual normalisation; existing pages inherit `text-sm` for `<th>` from the table element. Header text will be slightly smaller after migration — this is intentional and consistent with common table UI conventions.

**`<td>` elements** — enforced by component:
- Padding: `px-4 py-3`

**What parents remove when migrating:**
- `px-4 py-3` from individual `<th>` and `<td>` elements (component applies via `:deep()`)
- `text-muted-foreground` and `font-medium` from individual `<th>` elements (component applies via `:deep()`)
- `text-muted-foreground` from `<thead>` element where applied at that level (component applies it to `<th>` individually)

**What parents keep:**
- Alignment classes (`text-left`, `text-right`, `text-center`) on individual `<th>` elements
- Width/display classes (`w-10`, `hidden sm:table-cell`, etc.) on `<th>` and `<td>` elements
- `hover:bg-muted/30 transition-colors` on `<tr>` elements
- All content classes (badges, avatars, link styles, etc.)

**Loading skeleton rows:**
- 5 rows
- Each: `<tr><td colspan="100" class="px-4 py-3"><div class="h-4 bg-muted animate-pulse rounded" /></td></tr>`

**Empty state `<td>`:**
- `<tr><td colspan="100" class="px-4 py-12 text-center text-sm text-muted-foreground">`
- Default text: "No results found."
- The `#empty` slot content renders inside this `<td>` — do not add extra padding wrappers inside the slot.

### Row hover

`hover:bg-muted/30 transition-colors` is **not** applied by the component. Parents place it on their `<tr>` elements directly. This gives pages flexibility (e.g. a future clickable-row variant).

### Example Usage

```vue
<DataTable :loading="false" :empty="posts.length === 0">
  <template #headers>
    <th>Title</th>
    <th>Author</th>
    <th>Status</th>
    <th class="text-right w-px" />
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
| `resources/js/Pages/Tags/Index.vue` | Has top-level `v-if`/`v-else` empty state — see notes below |
| `resources/js/Pages/Users/Index.vue` | Has `bg-muted/40` thead and `bg-card` outer wrapper — see notes below |
| `resources/js/Pages/Posts/Index.vue` | Has inline empty state in `<tbody>` and paginated data — see notes below |

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
- Remove `rounded-lg border overflow-hidden` (and `bg-card` if present) wrapper div
- Remove entire `<thead>` block — move `<th>` elements into `#headers` slot
- Remove entire `<tbody>` block — move `<tr>` elements into `#rows` slot
- Remove `px-4 py-3`, `font-medium`, `text-muted-foreground` from `<th>` elements (component applies them)
- Remove `px-4 py-3` from `<td>` elements (component applies them)
- Keep alignment classes (`text-right`, etc.) on `<th>` elements — component does not enforce alignment
- Keep width/display classes (`w-10`, `hidden sm:table-cell`) on `<th>` and `<td>` elements
- Move any inline empty-state markup into `#empty` slot (or rely on default "No results found.")
- Add `import DataTable from '@/Components/DataTable.vue'`

### Page-Specific Notes

#### Tags/Index.vue — top-level v-if/v-else structure

The current page uses a top-level conditional to show either a standalone empty-state `<div>` or the table:

```vue
<!-- Current structure -->
<div v-if="tags.length === 0" class="rounded-lg border p-12 text-center text-muted-foreground">
  <!-- SVG icon + "No tags yet." text -->
</div>

<div v-else class="rounded-lg border overflow-hidden">
  <table ...>
  ...
  </table>
</div>
```

**Migration:** collapse both branches into a single `<DataTable>`. Move the SVG icon and "No tags yet." text directly into the `#empty` slot — do not wrap in an extra `<div>` with padding (the component's `<td>` already provides `px-4 py-12 text-center`):

```vue
<DataTable :loading="false" :empty="tags.length === 0">
  <template #empty>
    <!-- paste the SVG and "No tags yet." text here, without outer padding wrapper -->
  </template>
  <template #headers>...</template>
  <template #rows>...</template>
</DataTable>
```

#### Users/Index.vue — thead variant and bg-card wrapper

The Users page differs from others in three ways:

1. **`bg-muted/40` thead** — the `<thead> <tr>` uses `bg-muted/40`. After migration the component applies `bg-muted/50` uniformly. Intentional visual normalisation.

2. **`border-b` on `<thead> <tr>`** — the Users thead row has `class="border-b bg-muted/40"`. After migration, the `border-b` is dropped from the parent (the entire `<thead>` moves into the component). The component's `<thead class="bg-muted/50 border-b border-border">` provides the separator line. Visual result is equivalent.

3. **`bg-card` outer wrapper** — the outer div has `class="rounded-lg border bg-card overflow-hidden"`. The `bg-card` class is dropped when the wrapper is replaced by `<DataTable>`. The DataTable wrapper does not add `bg-card` — the table background is transparent. This is correct for all pages.

4. **`divide-y` without `divide-border` on `<tbody>`** — Users uses `class="divide-y"` (without `divide-border`). The component applies `divide-y divide-border`. Minor normalisation that adds the theme-aware border color.

5. **Inline empty state in `<tbody>`** — the Users `<tbody>` has a `v-if` empty row (similar to Posts):
   ```vue
   <tr v-if="users.data.length === 0">
     <td colspan="5" class="px-4 py-10 text-center text-sm text-muted-foreground">No users found.</td>
   </tr>
   ```
   After migration: remove this row entirely and pass `:empty="users.data.length === 0"` to `DataTable`. The default "No results found." text replaces "No users found." — this minor wording change is acceptable. If the original wording is preferred, use the `#empty` slot with the text.

   Users also uses `users.data` (paginated), not `users` directly. Use `:empty="users.data.length === 0"` and `v-for="user in users.data"`.

#### Posts/Index.vue — paginated data and inline empty state

The Posts page has two structural differences:

1. **Paginated data** — rows are in `posts.data` (Laravel paginator), not `posts` directly. Use `:empty="posts.data.length === 0"` and `v-for="post in posts.data"`.

2. **Inline empty state in `<tbody>`** — the current `<tbody>` contains a `v-if` empty row alongside the `v-for` rows:
   ```vue
   <tr v-if="posts.data.length === 0">
     <td colspan="8" class="px-4 py-12 text-center text-muted-foreground">
       <svg ...>...</svg>
       No posts found.
     </td>
   </tr>
   <tr v-for="post in posts.data" ...>
   ```
   After migration: remove the `v-if` empty row entirely, move its SVG and "No posts found." text into `#empty` slot, and pass `:empty="posts.data.length === 0"` to `DataTable`. The `v-for` in `#rows` has no `v-if` guard.

3. **`group` class and selection highlight on data rows** — each `<tr v-for>` carries `class="... group"` and `:class="{ 'bg-muted/20': selectedIds.includes(post.id) }"`. Both must be preserved after migration. The `group` class is load-bearing: action buttons in the actions column use `group-hover:opacity-100` and will stay permanently hidden without it. The `:class` binding provides the selection highlight when rows are checked.

---

## Out of Scope

- Sorting, filtering, pagination — remain in parent pages
- Bulk action UI — remains in parent pages (Posts/Index)
- Column-level alignment beyond what parents specify — set via classes on `<th>`/`<td>` in the parent
- Server-side data fetching
- Virtualization or infinite scroll

---

## Acceptance Criteria

- [ ] `DataTable.vue` renders correctly with `#headers` and `#rows` slots
- [ ] `:deep(thead th)` and `:deep(tbody td)` styles apply padding and text size to slotted content
- [ ] Column alignment classes on `<th>` in parent are not overridden by the component
- [ ] `<thead>` has `border-b` separator between header and body rows
- [ ] Loading state shows 5 animated skeleton rows
- [ ] Empty state shows "No results found." by default; `#empty` slot overrides it
- [ ] `colspan="100"` correctly spans all columns for empty/loading states
- [ ] All four migrated pages render as expected per migration notes (accepted normalisations: `text-xs` header text, `bg-muted/50` thead on Users, `divide-border` on Users tbody)
- [ ] `Tags/Index.vue` custom empty state (SVG + "No tags yet.") is preserved in `#empty` slot without extra padding wrapper
- [ ] `Posts/Index.vue` uses `posts.data` for rows and empty check; inline empty state moved to `#empty` slot
- [ ] `Posts/Index.vue` bulk action bar continues to function after migration
- [ ] Dark mode background is visually desaturated (no Nord blue cast)
- [ ] Auth split-panel left canvas panel retains `bg-[#2e3440]`
- [ ] `npm run build` passes with no errors
- [ ] `php artisan test` passes (all existing tests)
