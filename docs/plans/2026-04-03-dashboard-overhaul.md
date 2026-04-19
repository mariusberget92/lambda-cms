# Dashboard & Admin UI Overhaul Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Modernise the entire Lambda CMS admin UI in a shadcn-style with the Nord palette — new CSS tokens, 3 new shared components, refreshed AppLayout, Dashboard page, and consistent page structure across all list/form/settings pages.

**Architecture:** Token-first approach — update CSS variables, then create PageHeader / StatCard / ContentCard components (DataTable already exists and just needs style tweaks), then apply the patterns top-down: AppLayout → Dashboard → list pages → form pages → settings.

**Tech Stack:** Vue 3 `<script setup>`, Tailwind CSS 4, Inertia.js, Lucide Vue Next, Nord palette CSS tokens.

---

### Task 1: Update CSS tokens

**Files:**
- Modify: `resources/css/app.css`

**Step 1: Read the file**

Read `resources/css/app.css` in full. Confirm current values of `--background`, `--card`, `--border` in `:root`.

**Step 2: Update light mode tokens**

In the `:root` block, change:
```css
--background: #f7f8fa;
--card: #ffffff;
--border: #e4e7ec;
```

Also add two shadow tokens inside `:root` (after `--border`):
```css
--shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
--shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.07), 0 2px 4px -2px rgb(0 0 0 / 0.05);
```

Dark mode (`.dark` block) is unchanged.

**Step 3: Register shadow tokens in `@theme inline`**

Inside the `@theme inline { }` block, add:
```css
--shadow-sm: var(--shadow-sm);
--shadow-md: var(--shadow-md);
```

**Step 4: Commit**
```bash
git add resources/css/app.css
git commit -m "feat: update light mode tokens — white cards, neutral bg, shadow scale"
```

---

### Task 2: Upgrade DataTable component

The DataTable component already exists at `resources/js/Components/DataTable.vue`. It needs style upgrades to match the new design.

**Files:**
- Modify: `resources/js/Components/DataTable.vue`

**Step 1: Read the file**

Read it in full. Note current slot names (`headers`, `rows`, `empty`), props (`loading`, `empty`).

**Step 2: Rewrite with upgraded styles**

Replace the entire file with:

```vue
<script setup>
defineProps({
  loading: { type: Boolean, default: false },
  empty:   { type: Boolean, default: false },
})
</script>

<template>
  <div class="rounded-xl border bg-card overflow-hidden" style="box-shadow: var(--shadow-sm)">
    <table class="w-full text-sm">
      <thead>
        <tr class="border-b border-border bg-muted/40">
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
            <td colspan="100" class="px-4 py-16 text-center">
              <slot name="empty">
                <p class="text-sm text-muted-foreground">No results found.</p>
              </slot>
            </td>
          </tr>
        </template>
        <template v-else>
          <slot name="rows" />
        </template>
      </tbody>
    </table>
    <div v-if="$slots.footer" class="border-t border-border px-4 py-3 bg-muted/20">
      <slot name="footer" />
    </div>
  </div>
</template>

<style scoped>
:deep(thead th) {
  padding: 0.625rem 1rem;
  font-size: 0.7rem;
  font-weight: 600;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  color: var(--muted-foreground);
}
:deep(tbody td) {
  padding: 0.875rem 1rem;
}
:deep(tbody tr) {
  transition: background-color 0.15s;
}
:deep(tbody tr:hover) {
  background-color: color-mix(in srgb, var(--muted) 30%, transparent);
}
</style>
```

**Step 3: Commit**
```bash
git add resources/js/Components/DataTable.vue
git commit -m "feat: upgrade DataTable — rounded-xl, shadow, hover rows, footer slot"
```

---

### Task 3: Create PageHeader component

**Files:**
- Create: `resources/js/Components/PageHeader.vue`

**Step 1: Create the file**

```vue
<template>
  <div class="flex items-start justify-between gap-4 pb-6 mb-6 border-b border-border">
    <div>
      <h1 class="text-xl font-semibold text-foreground">{{ title }}</h1>
      <p v-if="description" class="text-sm text-muted-foreground mt-1">{{ description }}</p>
    </div>
    <div v-if="$slots.actions" class="shrink-0 flex items-center gap-2">
      <slot name="actions" />
    </div>
  </div>
</template>

<script setup>
defineProps({
  title:       { type: String, required: true },
  description: { type: String, default: '' },
})
</script>
```

**Step 2: Commit**
```bash
git add resources/js/Components/PageHeader.vue
git commit -m "feat: add PageHeader component"
```

---

### Task 4: Create StatCard component

**Files:**
- Create: `resources/js/Components/StatCard.vue`

**Step 1: Create the file**

The `color` prop maps to Nord palette pairs. When `href` is provided the card is an `<a>` tag, otherwise a `<div>`.

```vue
<template>
  <component
    :is="href ? 'a' : 'div'"
    :href="href || undefined"
    class="rounded-xl border bg-card p-5 flex flex-col"
    :class="href ? 'hover:shadow-md transition-shadow cursor-pointer' : ''"
    style="box-shadow: var(--shadow-sm)"
  >
    <div class="flex items-center justify-between">
      <p class="text-sm font-medium text-muted-foreground">{{ label }}</p>
      <div
        class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0"
        :style="{ backgroundColor: colorMap[color]?.bg, color: colorMap[color]?.fg }"
      >
        <slot name="icon" />
      </div>
    </div>
    <p class="text-2xl font-bold mt-3 text-foreground">{{ value }}</p>
  </component>
</template>

<script setup>
defineProps({
  label: { type: String, required: true },
  value: { type: [String, Number], required: true },
  color: { type: String, default: 'blue' },
  href:  { type: String, default: '' },
})

const colorMap = {
  blue:   { bg: 'color-mix(in srgb, #5e81ac 15%, transparent)', fg: '#5e81ac' },
  green:  { bg: 'color-mix(in srgb, #a3be8c 20%, transparent)', fg: '#638a47' },
  cyan:   { bg: 'color-mix(in srgb, #88c0d0 15%, transparent)', fg: '#4a8fa0' },
  yellow: { bg: 'color-mix(in srgb, #ebcb8b 20%, transparent)', fg: '#a07c20' },
  red:    { bg: 'color-mix(in srgb, #bf616a 15%, transparent)', fg: '#bf616a' },
  purple: { bg: 'color-mix(in srgb, #b48ead 15%, transparent)', fg: '#8a5f89' },
}
</script>
```

**Step 2: Commit**
```bash
git add resources/js/Components/StatCard.vue
git commit -m "feat: add StatCard component with Nord color variants"
```

---

### Task 5: Create ContentCard component

**Files:**
- Create: `resources/js/Components/ContentCard.vue`

**Step 1: Create the file**

```vue
<template>
  <div
    class="rounded-xl border bg-card overflow-hidden"
    style="box-shadow: var(--shadow-sm)"
  >
    <div v-if="title || $slots.actions" class="flex items-center justify-between px-6 py-4 border-b border-border">
      <div>
        <h3 class="text-sm font-semibold text-foreground">{{ title }}</h3>
        <p v-if="description" class="text-xs text-muted-foreground mt-0.5">{{ description }}</p>
      </div>
      <div v-if="$slots.actions" class="shrink-0 flex items-center gap-2">
        <slot name="actions" />
      </div>
    </div>
    <div class="px-6 py-5">
      <slot />
    </div>
    <div v-if="$slots.footer" class="px-6 py-4 border-t border-border bg-muted/20 flex items-center justify-end gap-3">
      <slot name="footer" />
    </div>
  </div>
</template>

<script setup>
defineProps({
  title:       { type: String, default: '' },
  description: { type: String, default: '' },
})
</script>
```

**Step 2: Commit**
```bash
git add resources/js/Components/ContentCard.vue
git commit -m "feat: add ContentCard component"
```

---

### Task 6: Refresh AppLayout — SidebarLink + topbar

**Files:**
- Modify: `resources/js/Components/SidebarLink.vue`
- Modify: `resources/js/Layouts/AppLayout.vue`

**Step 1: Update SidebarLink.vue**

Read `resources/js/Components/SidebarLink.vue`. Replace the `:class` binding with the new active style — left border indicator + tinted background:

```vue
<template>
  <a
    :href="href"
    class="flex items-center gap-3 py-2 rounded-md text-sm font-medium transition-colors"
    :class="active
      ? 'bg-sidebar-primary/10 text-sidebar-primary font-semibold pl-[10px] border-l-2 border-sidebar-primary'
      : 'text-sidebar-foreground/70 hover:bg-sidebar-accent/60 hover:text-sidebar-foreground px-3'"
  >
    <span class="shrink-0">
      <slot name="icon" />
    </span>
    <slot />
  </a>
</template>

<script setup>
defineProps({
  href:   { type: String, required: true },
  active: { type: Boolean, default: false },
})
</script>
```

**Step 2: Update topbar in AppLayout.vue**

Read `resources/js/Layouts/AppLayout.vue`. Find the `<header>` element (around line 191). Change its classes from `border-b border-border bg-background` to add a shadow:

```
class="flex items-center justify-between h-16 px-6 border-b border-border bg-background shrink-0"
style="box-shadow: var(--shadow-sm)"
```

Also update the theme toggle button to ensure it has rounded hover:
```
class="inline-flex items-center justify-center w-9 h-9 rounded-md text-muted-foreground hover:text-foreground hover:bg-accent transition-colors"
```
(already has this — verify it's correct and leave unchanged if so)

**Step 3: Commit**
```bash
git add resources/js/Components/SidebarLink.vue resources/js/Layouts/AppLayout.vue
git commit -m "feat: refresh sidebar active state with left-border indicator, add topbar shadow"
```

---

### Task 7: Rebuild Dashboard page

**Files:**
- Modify: `resources/js/Pages/Dashboard/Index.vue`

**Step 1: Read the file**

Read `resources/js/Pages/Dashboard/Index.vue` in full.

**Step 2: Rewrite the template**

Replace the entire `<template>` with:

```vue
<template>
  <AppLayout title="Dashboard">
    <Head title="Dashboard" />

    <PageHeader title="Dashboard" description="Overview of your blog." />

    <!-- Stat cards -->
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5 mb-6">
      <StatCard label="Total Posts" :value="stats.total" color="blue">
        <template #icon>
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
        </template>
      </StatCard>

      <StatCard label="Published" :value="stats.published" color="green">
        <template #icon>
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </template>
      </StatCard>

      <StatCard label="Scheduled" :value="stats.scheduled" color="cyan">
        <template #icon>
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
          </svg>
        </template>
      </StatCard>

      <StatCard label="Drafts" :value="stats.drafts" color="yellow">
        <template #icon>
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
          </svg>
        </template>
      </StatCard>

      <StatCard
        label="Pending Comments"
        :value="stats.pendingCommentsCount"
        color="red"
        :href="route('comments.index') + '?filter=pending'"
      >
        <template #icon>
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
          </svg>
        </template>
      </StatCard>
    </div>

    <!-- Two-column panels -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">

      <ContentCard title="Upcoming scheduled posts">
        <template #actions>
          <a :href="route('calendar')" class="text-xs text-primary hover:underline">View calendar →</a>
        </template>
        <div v-if="upcoming_scheduled.length === 0" class="text-center py-6">
          <svg class="w-8 h-8 text-muted-foreground/40 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
          </svg>
          <p class="text-sm text-muted-foreground">No posts scheduled.</p>
        </div>
        <ul v-else class="divide-y divide-border -mx-6 -mb-5">
          <li v-for="post in upcoming_scheduled" :key="post.id" class="px-6 py-3 first:pt-0">
            <a :href="route('posts.edit', post.id)" class="block font-medium text-sm line-clamp-1 hover:text-primary transition-colors">{{ post.title }}</a>
            <div class="flex items-center gap-2 mt-0.5 text-xs text-muted-foreground">
              <span>{{ formatScheduled(post.published_at) }}</span>
              <span>·</span>
              <span>{{ post.author_name }}</span>
            </div>
          </li>
        </ul>
      </ContentCard>

      <ContentCard title="Recent posts">
        <template #actions>
          <a :href="route('posts.index')" class="text-xs text-primary hover:underline">View all →</a>
        </template>
        <div v-if="recent_posts.length === 0" class="text-center py-6">
          <svg class="w-8 h-8 text-muted-foreground/40 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
          <p class="text-sm text-muted-foreground">No posts yet.</p>
        </div>
        <ul v-else class="divide-y divide-border -mx-6 -mb-5">
          <li v-for="post in recent_posts" :key="post.id" class="px-6 py-3 first:pt-0 flex items-center justify-between gap-3">
            <a :href="route('posts.edit', post.id)" class="font-medium text-sm line-clamp-1 hover:text-primary transition-colors flex-1 min-w-0">{{ post.title }}</a>
            <div class="flex items-center gap-2 shrink-0">
              <StatusBadge :status="post.status" />
              <span class="text-xs text-muted-foreground">{{ timeAgo(post.updated_at) }}</span>
            </div>
          </li>
        </ul>
      </ContentCard>
    </div>

    <!-- Quick actions -->
    <ContentCard title="Quick actions">
      <div class="flex flex-wrap gap-3">
        <a :href="route('posts.create')" class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)]">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
          New post
        </a>
        <a :href="route('posts.index')" class="inline-flex items-center gap-2 rounded-md border px-4 py-2 text-sm font-medium transition-colors hover:bg-accent">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
          All posts
        </a>
        <a :href="route('media.index')" class="inline-flex items-center gap-2 rounded-md border px-4 py-2 text-sm font-medium transition-colors hover:bg-accent">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
          Media library
        </a>
        <a v-if="user.role === 'administrator'" :href="route('pages.index')" class="inline-flex items-center gap-2 rounded-md border px-4 py-2 text-sm font-medium transition-colors hover:bg-accent">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10l6 6v8a2 2 0 01-2 2z"/></svg>
          Pages
        </a>
      </div>
    </ContentCard>
  </AppLayout>
</template>
```

**Step 3: Update `<script setup>`**

Keep all existing script logic. Add imports for the new components:
```js
import PageHeader from '@/Components/PageHeader.vue'
import StatCard from '@/Components/StatCard.vue'
import ContentCard from '@/Components/ContentCard.vue'
```

Remove the `watch` on `page.props.flash` — this is now handled globally by `Notifications.vue`.

**Step 4: Commit**
```bash
git add resources/js/Pages/Dashboard/Index.vue
git commit -m "feat: rebuild dashboard with StatCard, ContentCard, PageHeader"
```

---

### Task 8: Add PageHeader + ContentCard to list pages

Apply the consistent page structure to: Posts/Index, Categories/Index, Tags/Index, Users/Index, Comments/Index, Media/Index.

**Files:**
- Modify: `resources/js/Pages/Posts/Index.vue`
- Modify: `resources/js/Pages/Categories/Index.vue`
- Modify: `resources/js/Pages/Tags/Index.vue`
- Modify: `resources/js/Pages/Users/Index.vue`
- Modify: `resources/js/Pages/Comments/Index.vue`
- Modify: `resources/js/Pages/Media/Index.vue`

**For each page:**

1. Read the file in full
2. Add imports: `import PageHeader from '@/Components/PageHeader.vue'`
3. Replace any ad-hoc `<h1>` / `<h2>` title + button row at the top with `<PageHeader>`:
   - Move the page title into `:title`
   - Move the primary action button (e.g. "New Post") into `<template #actions>`
   - Add a short `:description` (e.g. `"Manage your blog posts"`, `"Manage categories"`, etc.)
4. Do NOT restructure or rewrite the rest of the page — only swap in the header pattern

**Commit after all 6 pages:**
```bash
git add resources/js/Pages/Posts/Index.vue resources/js/Pages/Categories/Index.vue resources/js/Pages/Tags/Index.vue resources/js/Pages/Users/Index.vue resources/js/Pages/Comments/Index.vue resources/js/Pages/Media/Index.vue
git commit -m "feat: add PageHeader to all list pages"
```

---

### Task 9: Add PageHeader to form pages

Apply PageHeader to: Posts/Create, Posts/Edit (or Form), Categories/Form, Profile pages.

**Files:**
- Modify: `resources/js/Pages/Posts/Edit.vue` (or wherever the post form lives — check)
- Modify: `resources/js/Pages/Categories/Form.vue`
- Modify: `resources/js/Pages/Profile/Index.vue` (or equivalent — check)

**For each page:**

1. Read the file
2. Import `PageHeader`
3. Replace the top-level title area with `<PageHeader :title="..." :description="...">`
4. Leave form fields and card structure as-is

**Commit:**
```bash
git add resources/js/Pages/Posts/ resources/js/Pages/Categories/Form.vue resources/js/Pages/Profile/
git commit -m "feat: add PageHeader to form pages"
```

---

### Task 10: Wrap Settings page sections in ContentCard

**Files:**
- Modify: `resources/js/Pages/Settings/Index.vue`

**Step 1: Read the file**

Read `resources/js/Pages/Settings/Index.vue` in full. Identify how tabs are structured and where each tab's body content starts/ends.

**Step 2: Apply ContentCard per tab section**

For each tab panel (General, Locale, Media, Mail, Comments, SEO, Appearance):
- Wrap the group of fields in a `<ContentCard :title="'...'" >` where the title is the logical group name
- Move the save button for each tab into `<template #footer>` on the outermost ContentCard

Add import: `import ContentCard from '@/Components/ContentCard.vue'`

Also add `<PageHeader title="Settings" description="Configure your site." />` at the very top (before the tab bar).

**Step 3: Commit**
```bash
git add resources/js/Pages/Settings/Index.vue
git commit -m "feat: wrap settings sections in ContentCard, add PageHeader"
```

---

### Task 11: Build and verify

**Step 1: Run npm build**
```bash
cd C:/Users/mariu/Herd/lambda-cms && npm run build 2>&1 | tail -10
```
Expected: `✓ built in X.XXs`. Chunk size warnings are pre-existing and fine. No errors.

**Step 2: Run PHP tests**
```bash
cd C:/Users/mariu/Herd/lambda-cms && php artisan test --no-coverage 2>&1 | tail -8
```
Expected: all tests pass (currently 449). These are all UI changes so no PHP tests should be affected.

**Step 3: Commit if any test cleanup needed**

If tests fail, investigate — it should not happen for this UI-only change.
