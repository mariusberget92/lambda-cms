# Blog Frontend Redesign Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Visually refresh the public blog with a dark gradient hero, card lift/glow animations, and richer use of the existing Nord color palette — without touching any PHP or admin UI.

**Architecture:** Pure frontend changes across 6 Vue SFC files. All colors via existing CSS variables (`--primary`, `--accent`, `--card`, `--border`). No new dependencies. Vite rebuild at the end.

**Tech Stack:** Vue 3 SFCs, Tailwind CSS 4 (arbitrary values supported), no new npm packages.

---

### Task 1: Header & Hero — BlogLayout.vue

**Files:**
- Modify: `resources/js/Layouts/BlogLayout.vue`

**Step 1: Strengthen the sticky header**

In the `<header>` tag change the class from:
```
class="border-b bg-card/80 backdrop-blur-sm sticky top-0 z-10"
```
to:
```
class="border-b bg-gradient-to-b from-card/95 to-card/80 backdrop-blur-md sticky top-0 z-10 shadow-sm"
```

**Step 2: Replace the hero strip**

Replace the entire `<!-- Hero strip -->` section. FROM:
```html
<!-- Hero strip -->
<div class="bg-primary/5 border-b">
  <div class="max-w-5xl mx-auto px-4 py-10">
    <div class="border-l-[3px] border-primary pl-4">
      <h1 class="text-3xl font-bold tracking-tight">{{ appName }}</h1>
      <p class="mt-1 text-base text-muted-foreground">A simple, clean blog powered by Lambda CMS.</p>
    </div>
  </div>
</div>
```

TO:
```html
<!-- Hero strip -->
<div class="relative overflow-hidden bg-gradient-to-br from-[#2e3440] to-[#3b4252]">
  <!-- Radial glow blob -->
  <div class="absolute inset-0 pointer-events-none"
       style="background: radial-gradient(ellipse 60% 80% at 70% 50%, rgba(94,129,172,0.28) 0%, transparent 70%)" />
  <!-- Bottom fade into page -->
  <div class="absolute bottom-0 left-0 right-0 h-10 pointer-events-none bg-gradient-to-b from-transparent to-background" />
  <div class="relative max-w-5xl mx-auto px-4 py-14">
    <div class="border-l-4 border-primary pl-5"
         style="box-shadow: -3px 0 18px rgba(94,129,172,0.4)">
      <h1 class="text-4xl font-bold tracking-tight text-white">{{ appName }}</h1>
      <p class="mt-2 text-base" style="color: rgba(216,222,233,0.72)">
        A simple, clean blog powered by Lambda CMS.
      </p>
    </div>
  </div>
</div>
```

**Step 3: Verify visually**

Open the blog homepage. Confirm:
- Sticky header has a soft gradient + shadow when you scroll
- Hero is a dark navy-to-slate gradient with white text
- Left accent bar has a faint blue glow
- Bottom of hero fades smoothly into the light page background (no hard edge)

**Step 4: Commit**
```bash
git add resources/js/Layouts/BlogLayout.vue
git commit -m "style: dark gradient hero with glow accent for blog layout"
```

---

### Task 2: Post Cards — PostCard.vue

**Files:**
- Modify: `resources/js/Components/PostCard.vue`

**Step 1: Card container — lift + left accent on hover**

Replace the `<article>` opening tag. FROM:
```html
<article class="border rounded-xl overflow-hidden bg-card hover:shadow-sm hover:border-primary/40 hover:shadow-md transition-all duration-200">
```
TO:
```html
<article class="relative group border rounded-xl overflow-hidden bg-card transition-all duration-300 ease-out hover:-translate-y-1 hover:shadow-xl hover:border-primary/30">
  <!-- Left accent bar (appears on hover) -->
  <div class="absolute left-0 inset-y-0 w-[3px] bg-primary z-10 opacity-0 group-hover:opacity-100 transition-opacity duration-300" />
```

Note: this opens an extra `<div>` inside `<article>`. The closing `</article>` tag at the bottom stays — the accent div does not need its own closing tag because it is self-contained (no children). Add `/>` instead of `>`:
```html
<div class="absolute left-0 inset-y-0 w-[3px] bg-primary z-10 opacity-0 group-hover:opacity-100 transition-opacity duration-300" />
```

**Step 2: Featured image — taller + overlay + stronger zoom**

Replace the entire featured image block. FROM:
```html
<!-- Featured image -->
<div v-if="post.featured_image_url" class="w-full h-48 overflow-hidden group">
  <img
    :src="post.featured_image_url"
    :alt="post.title"
    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
    loading="lazy"
  />
</div>
```
TO:
```html
<!-- Featured image -->
<div v-if="post.featured_image_url" class="relative w-full h-56 overflow-hidden">
  <img
    :src="post.featured_image_url"
    :alt="post.title"
    class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500 ease-out"
    loading="lazy"
  />
  <!-- Gradient overlay — blends image into card body below -->
  <div class="absolute inset-x-0 bottom-0 h-16 bg-gradient-to-t from-card/80 to-transparent pointer-events-none" />
</div>
```

**Step 3: No-image card body — subtle gradient background**

Change the `<div class="p-6">` that wraps the card content. FROM:
```html
<div class="p-6">
```
TO:
```html
<div :class="['p-6 transition-colors duration-300', post.featured_image_url ? '' : 'bg-gradient-to-br from-primary/5 to-accent/5']">
```

**Step 4: Title — bolder**

Change:
```html
<h2 class="text-xl font-semibold leading-tight mb-2">
```
TO:
```html
<h2 class="text-2xl font-bold leading-tight mb-2">
```

**Step 5: Excerpt — relaxed line height**

Change:
```html
<p v-if="post.excerpt" class="text-sm text-muted-foreground mb-4 line-clamp-3">
```
TO:
```html
<p v-if="post.excerpt" class="text-sm text-muted-foreground mb-4 line-clamp-3 leading-relaxed">
```

**Step 6: "Read more" — pill button**

Replace the Read more `<Link>`. FROM:
```html
<Link
  :href="`/blog/${post.slug}`"
  class="text-xs font-medium text-primary hover:underline"
>
  Read more →
</Link>
```
TO:
```html
<Link
  :href="`/blog/${post.slug}`"
  class="text-xs font-medium border border-primary text-primary rounded-full px-3 py-1 transition-colors duration-200 hover:bg-primary hover:text-primary-foreground"
>
  Read more →
</Link>
```

**Step 7: Verify visually**

On the blog index:
- Cards lift (`-translate-y-1`) and cast a larger shadow on hover
- A blue left accent bar slides in on hover
- Featured image cards are taller (h-56) with a gradient fade at the bottom
- Images zoom to scale-110 on hover
- Cards without a featured image have a faint blue-to-teal gradient body
- Title is larger and bolder
- "Read more" is a bordered pill that fills with primary on hover

**Step 8: Commit**
```bash
git add resources/js/Components/PostCard.vue
git commit -m "style: post card hover lift, gradient overlay, pill read-more button"
```

---

### Task 3: Sidebar — BlogSidebar.vue

**Files:**
- Modify: `resources/js/Components/BlogSidebar.vue`

**Step 1: Section header pattern**

There are 4 section headers (Search, Categories, Tags, Recent Posts). All share this pattern:
```html
<h3 class="text-sm font-semibold uppercase tracking-wider text-muted-foreground mb-3">
  <span class="inline-block w-1 h-4 bg-primary rounded-full mr-2 align-middle"></span>LABEL
</h3>
```

Replace ALL four with this new pattern (change LABEL for each):
```html
<h3 class="text-xs font-semibold uppercase tracking-wider text-primary mb-3 flex items-center gap-2">
  <span class="w-1 h-3.5 bg-primary rounded-full flex-shrink-0"></span>
  <span class="bg-primary/10 px-2 py-0.5 rounded-md">LABEL</span>
</h3>
```

Labels: `Search`, `Categories`, `Tags`, `Recent Posts`.

**Step 2: Category rows — hover wash**

Change the `<li>` in the categories list. FROM:
```html
<li
  v-for="cat in sidebar.categories"
  :key="cat.slug"
  class="flex items-center justify-between"
>
```
TO:
```html
<li
  v-for="cat in sidebar.categories"
  :key="cat.slug"
  class="flex items-center justify-between rounded-md px-2 -mx-2 transition-colors duration-150 hover:bg-primary/5"
>
```

**Step 3: Tag pills — filled hover**

Change the tag `<Link>` class. FROM:
```html
class="inline-block rounded-full border px-2.5 py-0.5 text-muted-foreground hover:text-foreground hover:border-foreground transition-colors"
```
TO:
```html
class="inline-block rounded-full border px-2.5 py-0.5 text-muted-foreground transition-colors duration-200 hover:bg-primary hover:text-primary-foreground hover:border-primary"
```

**Step 4: Verify visually**

In the sidebar:
- Each section label sits inside a light blue-tinted pill
- Hovering a category row shows a soft blue wash
- Tag cloud pills fill with the primary blue on hover

**Step 5: Commit**
```bash
git add resources/js/Components/BlogSidebar.vue
git commit -m "style: sidebar section header pills, category hover wash, tag fill on hover"
```

---

### Task 4: Pagination gradient — Index, Archive, Search

**Files:**
- Modify: `resources/js/Pages/Blog/Index.vue`
- Modify: `resources/js/Pages/Blog/Archive.vue`
- Modify: `resources/js/Pages/Blog/Search.vue`

The same `:class` binding appears in all three files. In each file find:

```
link.active
  ? 'bg-primary text-primary-foreground border-primary'
  : 'bg-card text-muted-foreground hover:text-foreground hover:border-foreground'
```

Replace with:
```
link.active
  ? 'bg-gradient-to-r from-primary to-accent text-primary-foreground border-primary shadow-sm'
  : 'bg-card text-muted-foreground hover:bg-muted hover:text-foreground hover:border-foreground'
```

Do this in all three files.

**Step 5: Verify visually**

Navigate to a route with enough posts to trigger pagination. The current page button should show a blue-to-teal gradient. Other buttons get a muted background on hover.

**Step 6: Commit**
```bash
git add resources/js/Pages/Blog/Index.vue resources/js/Pages/Blog/Archive.vue resources/js/Pages/Blog/Search.vue
git commit -m "style: gradient active state and hover fill for blog pagination"
```

---

### Task 5: Build production assets

**Step 1: Run Vite build**
```bash
npm run build
```
Expected output ends with `✓ built in X.XXs` — no errors.

**Step 2: Final visual check**

Visit these URLs and verify all changes are live:
- `/` — hero gradient, card hover effects, sidebar pills
- `/blog/<any-slug>` — single post (no changes, but verify nothing broke)
- `/blog/category/<slug>` — archive heading + sidebar + pagination gradient
- `/search?q=test` — search results with sidebar pre-filled

**Step 3: Commit**
```bash
git add public/build/
git commit -m "build: rebuild frontend assets for blog visual redesign"
```

---

## Summary of all changed files

| File | What changes |
|------|-------------|
| `resources/js/Layouts/BlogLayout.vue` | Header gradient+shadow, dark gradient hero with glow |
| `resources/js/Components/PostCard.vue` | Lift animation, left accent, taller image+overlay, bolder title, pill button |
| `resources/js/Components/BlogSidebar.vue` | Header pills, category hover wash, tag fill |
| `resources/js/Pages/Blog/Index.vue` | Pagination gradient active, hover fill |
| `resources/js/Pages/Blog/Archive.vue` | Pagination gradient active, hover fill |
| `resources/js/Pages/Blog/Search.vue` | Pagination gradient active, hover fill |
