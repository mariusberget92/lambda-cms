# DataTable Component & Dark Mode Fix Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Create a reusable `DataTable.vue` shell component, fix the dark mode background colour, and migrate all four existing index pages to use the component.

**Architecture:** `DataTable.vue` is a pure visual shell — it owns the outer frame (`rounded-lg border overflow-hidden`), table chrome (`<thead>`, `<tbody>`, styles via `:deep()`), and two presentation states (loading skeleton, empty state). Parents provide `<th>` via `#headers` and `<tr>` via `#rows` named slots. No column knowledge, no data management. The dark mode fix is a single CSS token change in `app.scss`. The four page migrations swap manual table markup for the component while keeping all existing logic untouched.

**Tech Stack:** Vue 3 Composition API, Tailwind CSS 4, `@apply` in `<style scoped>`, Vue scoped `:deep()` selector.

> **Spec:** `docs/superpowers/specs/2026-03-13-datatable-component-design.md`
> **Working directory:** project root `C:\Users\mariu\Herd\lambda-cms`

---

## File Structure

| Action | File | What changes |
|--------|------|-------------|
| Create | `resources/js/Components/DataTable.vue` | New shell component |
| Modify | `resources/scss/app.scss` (line 61) | Dark mode `--background` token |
| Modify | `resources/js/Pages/Categories/Index.vue` | Swap table markup for DataTable |
| Modify | `resources/js/Pages/Tags/Index.vue` | Swap table markup for DataTable; collapse v-if/v-else |
| Modify | `resources/js/Pages/Users/Index.vue` | Swap table markup for DataTable; note bg-card, border-b, hover differences |
| Modify | `resources/js/Pages/Posts/Index.vue` | Swap table markup for DataTable; preserve group + selection classes |

---

## Chunk 1: Foundation

### Task 1: Fix dark mode background colour

**Files:**
- Modify: `resources/scss/app.scss`

- [ ] **Step 1: Update the dark mode `--background` token**

Open `resources/scss/app.scss`. Find line 61 (inside the `.dark` CSS block):

```scss
--background: #2e3440;
```

Replace with:

```scss
--background: oklch(14% 0.01 260);
```

`oklch(14% 0.01 260)` ≈ `#191c21` — same lightness as Nord but with the blue saturation removed. No other tokens change.

- [ ] **Step 2: Commit**

```bash
git add resources/scss/app.scss
git commit -m "fix: desaturate dark mode background from Nord blue to neutral slate"
```

---

### Task 2: Create `DataTable.vue`

**Files:**
- Create: `resources/js/Components/DataTable.vue`

> No unit tests — this is pure visual/presentational code. Verification is manual in the browser (Task 7 of this plan).

- [ ] **Step 1: Create the component**

Create `resources/js/Components/DataTable.vue` with this exact content:

```vue
<script setup>
defineProps({
  loading: { type: Boolean, default: false },
  empty:   { type: Boolean, default: false },
})
</script>

<template>
  <div class="rounded-lg border overflow-hidden">
    <table class="w-full text-sm">
      <thead class="bg-muted/50 border-b border-border">
        <tr>
          <slot name="headers" />
        </tr>
      </thead>
      <tbody class="divide-y divide-border">
        <template v-if="loading">
          <tr v-for="i in 5" :key="i">
            <td colspan="100" class="px-4 py-3">
              <div class="h-4 bg-muted animate-pulse rounded" />
            </td>
          </tr>
        </template>
        <template v-else-if="empty">
          <tr>
            <td colspan="100" class="px-4 py-12 text-center text-sm text-muted-foreground">
              <slot name="empty">No results found.</slot>
            </td>
          </tr>
        </template>
        <template v-else>
          <slot name="rows" />
        </template>
      </tbody>
    </table>
  </div>
</template>

<style scoped>
:deep(thead th) {
  @apply px-4 py-3 text-xs font-medium text-muted-foreground;
}
:deep(tbody td) {
  @apply px-4 py-3;
}
</style>
```

Component notes:
- `loading` takes priority over `empty` (template uses `v-if`/`v-else-if`/`v-else`)
- `colspan="100"` spans all columns regardless of count — browsers clamp to actual column count
- `:deep(thead th)` and `:deep(tbody td)` apply consistent padding and text styles to slotted elements. `text-left` is deliberately **not** included — alignment is the parent's responsibility
- `border-b border-border` on `<thead>` provides the header-to-body separator line

- [ ] **Step 2: Commit**

```bash
git add resources/js/Components/DataTable.vue
git commit -m "feat: add DataTable shell component with loading and empty states"
```

---

## Chunk 2: Migrate simple pages

### Task 3: Migrate `Categories/Index.vue`

**Files:**
- Modify: `resources/js/Pages/Categories/Index.vue`

Current state: lines 33–95 contain a `<div class="rounded-lg border overflow-hidden">` wrapping the table. The `<tbody>` has an inline `v-if` empty row (with SVG icon) followed by `v-for` rows.

- [ ] **Step 1: Add the import**

In `<script setup>` (around line 120), add after the existing imports:

```js
import DataTable from '@/Components/DataTable.vue'
```

- [ ] **Step 2: Replace the table block**

Replace lines 33–95 (from `<div class="rounded-lg border overflow-hidden">` through the closing `</div>`) with:

```vue
<DataTable :loading="false" :empty="categories.length === 0">
  <template #empty>
    <svg class="w-8 h-8 mx-auto mb-3 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
      <path stroke-linecap="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
    </svg>
    No categories yet.
  </template>
  <template #headers>
    <th class="text-left">Name</th>
    <th class="text-left hidden md:table-cell">Description</th>
    <th class="text-left hidden sm:table-cell w-24">Posts</th>
    <th class="w-10"></th>
  </template>
  <template #rows>
    <tr
      v-for="cat in categories"
      :key="cat.id"
      class="hover:bg-muted/30 transition-colors group"
    >
      <td>
        <div class="font-medium">{{ cat.name }}</div>
        <div class="text-xs text-muted-foreground font-mono mt-0.5">{{ cat.slug }}</div>
      </td>
      <td class="hidden md:table-cell text-muted-foreground text-sm">
        {{ cat.description ?? '—' }}
      </td>
      <td class="hidden sm:table-cell">
        <span class="inline-flex items-center rounded-full bg-muted px-2.5 py-0.5 text-xs font-medium">
          {{ cat.posts_count }}
        </span>
      </td>
      <td>
        <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
          <a
            :href="route('categories.edit', cat.id)"
            class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-accent hover:text-accent-foreground transition-colors"
            title="Edit"
          >
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
          </a>
          <button
            type="button"
            @click="confirmDelete(cat)"
            class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-destructive/10 hover:text-destructive transition-colors"
            title="Delete"
          >
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
          </button>
        </div>
      </td>
    </tr>
  </template>
</DataTable>
```

What changed vs. original:
- Outer `<div class="rounded-lg border overflow-hidden">` and `<table>` removed — component provides them
- `<thead class="bg-muted/50 text-muted-foreground"><tr>` removed — component provides it
- `<th>` elements: removed `px-4 py-3 font-medium` (component applies via `:deep()`); kept `text-left`, `hidden md:table-cell`, `w-24`, `w-10`
- `<tbody class="divide-y divide-border">` removed — component provides it
- Inline `v-if` empty row removed — replaced by `#empty` slot with the same SVG + text
- `<td>` elements: removed `px-4 py-3` (component applies via `:deep()`); kept all content and display classes
- `group` class on `<tr>` preserved — required for `group-hover:opacity-100` on actions

- [ ] **Step 3: Commit**

```bash
git add resources/js/Pages/Categories/Index.vue
git commit -m "refactor: migrate Categories/Index to DataTable component"
```

---

### Task 4: Migrate `Tags/Index.vue`

**Files:**
- Modify: `resources/js/Pages/Tags/Index.vue`

Current state: lines 34–95 use a top-level `v-if`/`v-else` — a standalone `<div>` for the empty state when `tags.length === 0`, and a separate `<div class="rounded-lg border overflow-hidden">` containing the table otherwise. Both must be collapsed into a single `<DataTable>` call.

- [ ] **Step 1: Add the import**

In `<script setup>` (line 115), add after the existing imports:

```js
import DataTable from '@/Components/DataTable.vue'
```

- [ ] **Step 2: Replace the v-if/v-else block**

Replace lines 34–95 (from `<div v-if="tags.length === 0"` through the closing `</div>` of the `v-else` block) with:

```vue
<DataTable :loading="false" :empty="tags.length === 0">
  <template #empty>
    <svg class="w-8 h-8 mx-auto mb-3 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
      <path stroke-linecap="round" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
    </svg>
    No tags yet.
  </template>
  <template #headers>
    <th class="text-left">Tag</th>
    <th class="text-left hidden sm:table-cell w-24">Posts</th>
    <th class="w-10"></th>
  </template>
  <template #rows>
    <tr
      v-for="tag in tags"
      :key="tag.id"
      class="hover:bg-muted/30 transition-colors group"
    >
      <td>
        <div class="flex items-center gap-2">
          <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium">
            {{ tag.name }}
          </span>
          <span class="text-xs text-muted-foreground font-mono">{{ tag.slug }}</span>
        </div>
      </td>
      <td class="hidden sm:table-cell">
        <span class="inline-flex items-center rounded-full bg-muted px-2.5 py-0.5 text-xs font-medium">
          {{ tag.posts_count }}
        </span>
      </td>
      <td>
        <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
          <a
            :href="route('tags.edit', tag.id)"
            class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-accent hover:text-accent-foreground transition-colors"
            title="Edit"
          >
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
          </a>
          <button
            type="button"
            @click="confirmDelete(tag)"
            class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-destructive/10 hover:text-destructive transition-colors"
            title="Delete"
          >
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
          </button>
        </div>
      </td>
    </tr>
  </template>
</DataTable>
```

What changed vs. original:
- Top-level `v-if`/`v-else` structure collapsed into a single `<DataTable :empty="tags.length === 0">`
- SVG icon and "No tags yet." from the old `v-if` div moved into `#empty` slot (no padding wrapper — component's `<td>` already provides `px-4 py-12 text-center`)
- Old `v-else` table structure replaced by `#headers`/`#rows` slots
- `<th>` elements: removed `px-4 py-3 font-medium`; kept `text-left`, `hidden sm:table-cell`, `w-24`, `w-10`
- `<td>` elements: removed `px-4 py-3`; kept content and display classes
- `group` class on `<tr>` preserved

- [ ] **Step 3: Commit**

```bash
git add resources/js/Pages/Tags/Index.vue
git commit -m "refactor: migrate Tags/Index to DataTable component"
```

---

## Chunk 3: Migrate complex pages + verify

### Task 5: Migrate `Users/Index.vue`

**Files:**
- Modify: `resources/js/Pages/Users/Index.vue`

Current state: lines 35–125. Differences from other pages:
- Outer wrapper has `bg-card`: `<div class="rounded-lg border bg-card overflow-hidden">`
- `<thead>` has no class; the `<tr>` inside it has `class="border-b bg-muted/40"`
- `<th>` elements carry `text-muted-foreground` individually (not on `<thead>`)
- `<tbody class="divide-y">` (no `divide-border`)
- Row hover is `hover:bg-muted/20` (not `hover:bg-muted/30`) — preserve this
- No `group` class on rows — action buttons are always visible (no `group-hover`)
- Paginated: iterate `users.data`, check `users.data.length === 0`
- Inline empty row at end of `<tbody>` (after `v-for`): `<tr v-if="users.data.length === 0">`

- [ ] **Step 1: Add the import**

In `<script setup>` (line 173), add after the existing imports:

```js
import DataTable from '@/Components/DataTable.vue'
```

- [ ] **Step 2: Replace the table block**

Replace lines 35–125 (from `<div class="rounded-lg border bg-card overflow-hidden">` through `</div>`) with:

```vue
<DataTable :loading="false" :empty="users.data.length === 0">
  <template #empty>No users found.</template>
  <template #headers>
    <th class="text-left">User</th>
    <th class="text-left">Role</th>
    <th class="text-left">Verified</th>
    <th class="text-left">Last seen</th>
    <th class="w-10"></th>
  </template>
  <template #rows>
    <tr
      v-for="user in users.data"
      :key="user.id"
      class="hover:bg-muted/20 transition-colors"
    >
      <!-- User -->
      <td>
        <div class="flex items-center gap-3">
          <div class="relative shrink-0">
            <div class="w-8 h-8 rounded-full overflow-hidden bg-muted flex items-center justify-center text-xs font-semibold uppercase">
              <img v-if="user.avatar_url" :src="user.avatar_url" :alt="user.name" class="w-full h-full object-cover" />
              <span v-else>{{ initials(user.name) }}</span>
            </div>
            <span
              v-if="user.is_online"
              class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-online-dot ring-2 ring-card"
            ></span>
          </div>
          <div>
            <p class="font-medium">{{ user.name }}</p>
            <p class="text-xs text-muted-foreground">{{ user.email }}</p>
          </div>
        </div>
      </td>
      <!-- Role -->
      <td>
        <span
          class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
          :class="user.role === 'administrator'
            ? 'bg-role-admin-bg text-role-admin-fg'
            : 'bg-role-user-bg text-role-user-fg'"
        >
          {{ user.role === 'administrator' ? 'Administrator' : 'User' }}
        </span>
      </td>
      <!-- Verified -->
      <td>
        <span v-if="user.email_verified" class="text-status-success-fg">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
          </svg>
        </span>
        <span v-else class="text-muted-foreground text-xs">Pending</span>
      </td>
      <!-- Last seen -->
      <td class="text-muted-foreground text-xs">
        {{ user.last_seen_at ?? 'Never' }}
      </td>
      <!-- Actions -->
      <td>
        <div class="flex items-center justify-end gap-2">
          <a
            :href="route('users.edit', user.id)"
            class="rounded-md p-1.5 text-muted-foreground hover:bg-accent hover:text-foreground transition-colors"
            title="Edit"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
          </a>
          <button
            v-if="user.id !== currentUserId"
            type="button"
            @click="handleDeleteClick(user)"
            :disabled="isLastAdmin(user)"
            :aria-label="isLastAdmin(user) ? 'Cannot delete the only administrator' : 'Delete ' + user.name"
            class="rounded-md p-1.5 text-muted-foreground transition-colors hover:bg-destructive/10 hover:text-destructive disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-transparent disabled:hover:text-muted-foreground"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
          </button>
        </div>
      </td>
    </tr>
  </template>
</DataTable>
```

What changed vs. original:
- `bg-card` wrapper removed (component wrapper is transparent — correct)
- `<thead><tr class="border-b bg-muted/40">` removed; component provides `<thead class="bg-muted/50 border-b border-border">` (intentional normalisation: bg-muted/50, border moved to thead)
- `<th>` elements: removed `px-4 py-3 font-medium text-muted-foreground`; kept `text-left` and `w-10`
- `<tbody class="divide-y">` removed; component provides `divide-y divide-border` (adds divide-border — minor normalisation)
- `v-if="users.data.length === 0"` empty row removed; replaced by `#empty` slot with "No users found."
- `<td>` elements: removed `px-4 py-3`; kept all other content and display classes
- Row hover preserved as `hover:bg-muted/20` (Users uses slightly lower opacity than other pages — intentional)
- No `group` class needed — Users actions are always visible (no group-hover on this page)

- [ ] **Step 3: Commit**

```bash
git add resources/js/Pages/Users/Index.vue
git commit -m "refactor: migrate Users/Index to DataTable component"
```

---

### Task 6: Migrate `Posts/Index.vue`

**Files:**
- Modify: `resources/js/Pages/Posts/Index.vue`

Current state: lines 62–174. Most complex page. Differences:
- Paginated: iterate `posts.data`, check `posts.data.length === 0`
- Inline `v-if` empty row at top of `<tbody>` (before `v-for`)
- `v-for` rows carry `class="hover:bg-muted/30 transition-colors group"` and `:class="{ 'bg-muted/20': selectedIds.includes(post.id) }"`
- `group` class is **load-bearing** — action buttons use `group-hover:opacity-100`
- Comments `<th>` is right-aligned: `class="text-right font-medium px-4 py-3 hidden lg:table-cell"`

- [ ] **Step 1: Add the import**

In `<script setup>` (line 301), add after the existing imports:

```js
import DataTable from '@/Components/DataTable.vue'
```

- [ ] **Step 2: Replace the table block**

Replace lines 62–174 (from `<div class="rounded-lg border overflow-hidden">` through its closing `</div>`) with:

```vue
<DataTable :loading="false" :empty="posts.data.length === 0">
  <template #empty>
    <svg class="w-8 h-8 mx-auto mb-3 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
      <path stroke-linecap="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
    </svg>
    No posts found.
  </template>
  <template #headers>
    <!-- Select-all checkbox -->
    <th class="w-10">
      <input
        type="checkbox"
        :checked="isAllSelected"
        :indeterminate.prop="selectedIds.length > 0 && !isAllSelected"
        @change="toggleAll"
        class="rounded border-border"
        aria-label="Select all posts"
      />
    </th>
    <th class="text-left">Title</th>
    <th class="text-left hidden sm:table-cell">Author</th>
    <th class="text-left hidden md:table-cell">Categories</th>
    <th class="text-left hidden md:table-cell">Status</th>
    <th class="text-left hidden lg:table-cell">Date</th>
    <th class="text-right hidden lg:table-cell">Comments</th>
    <th class="w-10"></th>
  </template>
  <template #rows>
    <tr
      v-for="post in posts.data"
      :key="post.id"
      class="hover:bg-muted/30 transition-colors group"
      :class="{ 'bg-muted/20': selectedIds.includes(post.id) }"
    >
      <!-- Per-row checkbox -->
      <td>
        <input
          type="checkbox"
          :checked="selectedIds.includes(post.id)"
          @change="toggleRow(post.id)"
          class="rounded border-border"
          :aria-label="`Select ${post.title}`"
        />
      </td>
      <td>
        <div class="font-medium line-clamp-1">{{ post.title }}</div>
        <div v-if="post.excerpt" class="text-xs text-muted-foreground line-clamp-1 mt-0.5 hidden sm:block">{{ post.excerpt }}</div>
      </td>
      <td class="hidden sm:table-cell text-muted-foreground">{{ post.author }}</td>
      <td class="hidden md:table-cell text-muted-foreground text-xs">
        <span v-if="post.categories?.length">{{ post.categories.map(c => c.name).join(', ') }}</span>
        <span v-else class="text-muted-foreground/50">—</span>
      </td>
      <td class="hidden md:table-cell">
        <span
          class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium"
          :class="{
            'bg-status-success-bg text-status-success-fg': post.status === 'published',
            'bg-indigo-50 text-indigo-700':                 post.status === 'scheduled',
            'bg-status-warning-bg text-status-warning-fg': post.status === 'draft',
          }"
        >
          <span
            class="w-1.5 h-1.5 rounded-full"
            :class="{
              'bg-status-success-fg': post.status === 'published',
              'bg-indigo-500':         post.status === 'scheduled',
              'bg-status-warning-fg': post.status === 'draft',
            }"
          ></span>
          <template v-if="post.status === 'published'">Published</template>
          <template v-else-if="post.status === 'scheduled'">Scheduled · {{ post.published_at }}</template>
          <template v-else>Draft</template>
        </span>
      </td>
      <td class="hidden lg:table-cell text-muted-foreground text-xs">
        {{ post.published_at ?? post.created_at }}
      </td>
      <td class="hidden lg:table-cell text-right text-muted-foreground text-xs">
        {{ post.comments_count }}
      </td>
      <td>
        <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
          <a
            :href="route('posts.edit', post.id)"
            class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-accent hover:text-accent-foreground transition-colors"
            title="Edit"
          >
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
          </a>
          <button
            type="button"
            @click="confirmDelete(post)"
            class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-destructive/10 hover:text-destructive transition-colors"
            title="Delete"
          >
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
          </button>
        </div>
      </td>
    </tr>
  </template>
</DataTable>
```

What changed vs. original:
- Outer `<div class="rounded-lg border overflow-hidden">` removed — component provides it
- `<thead class="bg-muted/50 text-muted-foreground"><tr>` removed — component provides it
- `<th>` elements: removed `px-4 py-3 font-medium`; kept `text-left`, `text-right`, `hidden *:table-cell`, `w-10`
- `<tbody class="divide-y divide-border">` removed — component provides it
- Inline `v-if` empty row removed — replaced by `#empty` slot (same SVG + text)
- `<td>` elements: removed `px-4 py-3`; kept all other content and display classes
- `class="hover:bg-muted/30 transition-colors group"` and `:class="{ 'bg-muted/20': selectedIds.includes(post.id) }"` on `<tr>` preserved — both are load-bearing
- All bulk action and pagination markup below the table is unchanged

- [ ] **Step 3: Commit**

```bash
git add resources/js/Pages/Posts/Index.vue
git commit -m "refactor: migrate Posts/Index to DataTable component"
```

---

### Task 7: Build and verify

**Files:** none (verification only)

- [ ] **Step 1: Build assets**

```bash
npm run build
```

Expected: exits 0 with no errors. A chunk-size warning for the main bundle is normal and harmless.

- [ ] **Step 2: Run the test suite**

```bash
php artisan test
```

Expected: all tests pass (baseline is 307 tests).

- [ ] **Step 3: Visual verification**

Start a dev server: `php artisan serve` and open `http://localhost:8000` in a browser.

Check each page in both light and dark mode:

**Dark mode background** (`/dashboard` or any page):
- [ ] Background colour is a desaturated dark slate — no Nord blue cast

**`/categories`:**
- [ ] Table renders with rounded border, consistent padding on all cells
- [ ] Header row: small text (`text-xs`), muted colour — visible separator line between header and rows
- [ ] Hover on a row: subtle highlight
- [ ] Hover over a row: edit/delete icons appear (group-hover working)
- [ ] With no categories: SVG icon + "No categories yet." centred in the table area

**`/tags`:**
- [ ] Same structural checks as Categories
- [ ] With no tags: SVG hashtag icon + "No tags yet."

**`/users`:**
- [ ] Table renders (avatars, role badges, verified icons all visible)
- [ ] Row hover is slightly subtler (`bg-muted/20`) than other pages — this is correct
- [ ] Actions (edit/delete icons) always visible (no group-hover on Users)
- [ ] With no users: "No users found."

**`/posts`:**
- [ ] Select-all checkbox in header row works
- [ ] Per-row checkboxes work; selected rows highlight (`bg-muted/20`)
- [ ] Bulk action toolbar appears when rows are selected; Publish/Draft/Delete all function
- [ ] Hover: edit/delete icons appear in the actions column (`group-hover:opacity-100`)
- [ ] Comments column header is right-aligned
- [ ] With no posts: SVG icon + "No posts found."
- [ ] Pagination links render below the table (unchanged)

- [ ] **Step 4: Final commit if fixes were needed**

If any visual issue was found and fixed during verification, commit the fix. If all looks good, no additional commit is needed — the feature is complete.
