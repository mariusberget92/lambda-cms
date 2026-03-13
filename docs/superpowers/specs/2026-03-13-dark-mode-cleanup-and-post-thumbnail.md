# Dark Mode Cleanup & Post Featured Image Thumbnail

**Date:** 2026-03-13
**Scope:** Four targeted fixes — three dark mode colour corrections and one UI addition to the Posts index table.

---

## 1. Dark Mode — `--muted-foreground` token

**Problem:** `--muted-foreground: #4c566a` is identical in light and dark mode. In dark mode the card surface is `oklch(27% 0.01 260)` (≈ L 27%), so the current muted foreground at L ≈ 37% gives only ~10% lightness contrast — far too low for readable secondary text.

**Fix:** Change line 72 of `resources/scss/app.scss` (inside `.dark`) from:

```scss
--muted-foreground: #4c566a;
```

to:

```scss
--muted-foreground: oklch(60% 0.01 260);
```

`oklch(60% 0.01 260)` ≈ `#8898aa` — a desaturated slate that sits comfortably above all dark surfaces (L 14–37%) and is consistent with the oklch palette approach applied to all other dark tokens. No other foreground tokens change.

---

## 2. Dark Mode — `AuthLayout.vue` particle panel

**Problem:** The left split-panel of the auth layout has `bg-[#2e3440]` hardcoded (Nord blue-gray). The panel is intentionally always dark regardless of theme (it is a decorative element, not a semantic surface), so it is not theme-adaptive by design — but the colour must be desaturated.

**Fix:** Change line 24 of `resources/js/Layouts/AuthLayout.vue` from:

```vue
<div class="hidden md:flex w-1/2 bg-[#2e3440]" aria-hidden="true">
```

to:

```vue
<div class="hidden md:flex w-1/2 bg-[oklch(14%_0.01_260)]" aria-hidden="true">
```

Tailwind CSS 4 supports `oklch()` in arbitrary value syntax; spaces must be written as underscores (`oklch(14%_0.01_260)`). This matches the `--background` token value exactly and makes the panel a neutral dark slate.

---

## 3. Dark Mode — `useParticleCanvas.js` canvas fill colour

**Problem:** `const BG_COLOR = '#2e3440'` (line 6) is used to clear the canvas on every animation frame. The canvas is inside the `AuthLayout` panel (always dark, not theme-adaptive), so only the colour value needs updating — no reactivity required.

**Fix:** Change lines 6–8 of `resources/js/composables/useParticleCanvas.js` from:

```js
const BG_COLOR             = '#2e3440'
const PARTICLE_RGB         = '216, 222, 233'
const PARTICLE_COLOR       = `rgba(${PARTICLE_RGB}, 0.85)`
```

to:

```js
const BG_COLOR             = 'oklch(14% 0.01 260)'
const PARTICLE_RGB         = '216, 222, 233'
const PARTICLE_COLOR       = `rgba(${PARTICLE_RGB}, 0.85)`
```

Canvas 2D `fillStyle` accepts `oklch()` in all modern browsers. `PARTICLE_RGB` and `PARTICLE_COLOR` are unchanged — Nord snow storm (`#d8dee9`) provides good contrast on the dark background.

---

## 4. Posts index — featured image thumbnail

**Goal:** Show a small thumbnail of a post's featured image in the Posts/Index table, alongside the title.

### 4a. Backend — `PostController::index()`

Two changes to `app/Http/Controllers/PostController.php`:

1. **Add eager-load** — append `'featuredImage:id,path,disk'` to the existing `with()` call on line 17:

   ```php
   $posts = Post::with('author:id,name', 'categories:id,name', 'tags:id,name', 'featuredImage:id,path,disk')
   ```

   All three columns are required: `id` is needed by Eloquent's constrained eager-loading; `path` and `disk` are consumed by the `url` accessor (`Storage::disk($this->disk)->url($this->path)`). Do not reduce the select — omitting either `path` or `disk` silently breaks the URL.

2. **Expose URL in transform** — add one line to the `->through()` closure (after `'comments_count'`):

   ```php
   'featured_image_url' => $post->featuredImage?->url,
   ```

   The `->url` accessor on `Media` calls `Storage::disk($this->disk)->url($this->path)` — no extra logic needed. Value is `null` when no featured image is set.

### 4b. Frontend — `Posts/Index.vue`

The title cell currently contains:

```vue
<td>
  <div class="font-medium line-clamp-1">{{ post.title }}</div>
  <div v-if="post.excerpt" class="text-xs text-muted-foreground line-clamp-1 mt-0.5 hidden sm:block">{{ post.excerpt }}</div>
</td>
```

Replace with a flex row that optionally prepends a thumbnail:

```vue
<td>
  <div class="flex items-center gap-3">
    <img
      v-if="post.featured_image_url"
      :src="post.featured_image_url"
      alt=""
      class="hidden sm:block w-10 h-7 rounded object-cover shrink-0 bg-muted"
    />
    <div>
      <div class="font-medium line-clamp-1">{{ post.title }}</div>
      <div v-if="post.excerpt" class="text-xs text-muted-foreground line-clamp-1 mt-0.5 hidden sm:block">{{ post.excerpt }}</div>
    </div>
  </div>
</td>
```

Design decisions:
- `hidden sm:block` — thumbnail hidden on mobile to avoid clutter; same breakpoint as excerpt
- `w-10 h-7` (40×28 px) — small enough to keep row height compact; `object-cover` handles any aspect ratio
- `bg-muted` — placeholder fill while image loads
- `alt=""` — decorative image; screen readers skip it (the title text provides context)
- No new column added — the thumbnail lives inside the existing Title cell, keeping the column count and responsive hiding unchanged

---

## File Summary

| File | Change |
|------|--------|
| `resources/scss/app.scss` | Line 72: `--muted-foreground` dark value |
| `resources/js/Layouts/AuthLayout.vue` | Line 24: `bg-[#2e3440]` → `bg-[oklch(14%_0.01_260)]` |
| `resources/js/composables/useParticleCanvas.js` | Line 6: `BG_COLOR` value |
| `app/Http/Controllers/PostController.php` | `index()`: add eager-load + `featured_image_url` to transform |
| `resources/js/Pages/Posts/Index.vue` | Title `<td>`: wrap in flex row, prepend conditional thumbnail |

No migrations, no new routes, no new components.
