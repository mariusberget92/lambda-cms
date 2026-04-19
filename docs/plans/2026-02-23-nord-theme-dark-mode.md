# Nord Theme & Dark Mode Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Replace the current OKLCH color system with the Nord palette and add a user-toggleable dark/light mode toggle in the top header bar, persisted to localStorage.

**Architecture:** All 16 Nord hex values are mapped to the existing semantic CSS custom properties in `app.scss` — no component class renames needed. A lightweight `useTheme.js` composable toggles the `.dark` class on `<html>` and persists the preference. The existing `<header>` in `AppLayout.vue` gains a Sun/Moon icon button on the right. Hardcoded `green-*`/`amber-*`/`red-*` Tailwind classes in CRUD pages are replaced with new semantic status tokens.

**Tech Stack:** Vue 3 composables, Tailwind CSS 4 CSS custom properties (`@theme inline`), lucide-vue-next (already installed), localStorage API.

---

## Nord Palette Quick Reference

```
Polar Night:  nord0=#2e3440  nord1=#3b4252  nord2=#434c5e  nord3=#4c566a
Snow Storm:   nord4=#d8dee9  nord5=#e5e9f0  nord6=#eceff4
Frost:        nord7=#8fbcbb  nord8=#88c0d0  nord9=#81a1c1  nord10=#5e81ac
Aurora:       nord11=#bf616a nord12=#d08770 nord13=#ebcb8b nord14=#a3be8c nord15=#b48ead
```

---

## Task 1: Replace CSS tokens with Nord values in `app.scss`

**Files:**
- Modify: `resources/scss/app.scss`

This is a pure search-and-replace of all OKLCH values with Nord hex values. No tests needed — visual verification after. The file currently has three sections to update: `:root`, `.dark`, and `@theme inline`.

**Step 1: Replace the `:root` block**

Open `resources/scss/app.scss`. Replace the entire `:root { ... }` block (lines 4–36) with:

```scss
:root {
  --background: #eceff4;
  --foreground: #2e3440;
  --card: #e5e9f0;
  --card-foreground: #2e3440;
  --popover: #eceff4;
  --popover-foreground: #2e3440;
  --primary: #5e81ac;
  --primary-foreground: #eceff4;
  --secondary: #d8dee9;
  --secondary-foreground: #3b4252;
  --muted: #d8dee9;
  --muted-foreground: #4c566a;
  --accent: #88c0d0;
  --accent-foreground: #2e3440;
  --destructive: #bf616a;
  --destructive-foreground: #eceff4;
  --border: #d8dee9;
  --input: #d8dee9;
  --ring: #81a1c1;
  --chart-1: #5e81ac;
  --chart-2: #88c0d0;
  --chart-3: #a3be8c;
  --chart-4: #ebcb8b;
  --chart-5: #b48ead;
  --radius: 0.625rem;
  --sidebar: #e5e9f0;
  --sidebar-foreground: #3b4252;
  --sidebar-primary: #5e81ac;
  --sidebar-primary-foreground: #eceff4;
  --sidebar-accent: #d8dee9;
  --sidebar-accent-foreground: #2e3440;
  --sidebar-border: #d8dee9;
  --sidebar-ring: #81a1c1;

  /* Status tokens — light mode */
  --color-success-bg: color-mix(in srgb, #a3be8c 20%, transparent);
  --color-success-fg: #638a47;
  --color-success-border: color-mix(in srgb, #a3be8c 40%, transparent);
  --color-warning-bg: color-mix(in srgb, #ebcb8b 20%, transparent);
  --color-warning-fg: #a07c20;
  --color-warning-border: color-mix(in srgb, #ebcb8b 40%, transparent);
  --color-error-bg: color-mix(in srgb, #bf616a 15%, transparent);
  --color-error-fg: #bf616a;
  --color-error-border: color-mix(in srgb, #bf616a 35%, transparent);
}
```

**Step 2: Replace the `.dark` block**

Replace the entire `.dark { ... }` block (lines 38–71) with:

```scss
.dark {
  --background: #2e3440;
  --foreground: #d8dee9;
  --card: #3b4252;
  --card-foreground: #d8dee9;
  --popover: #3b4252;
  --popover-foreground: #d8dee9;
  --primary: #88c0d0;
  --primary-foreground: #2e3440;
  --secondary: #434c5e;
  --secondary-foreground: #d8dee9;
  --muted: #3b4252;
  --muted-foreground: #4c566a;
  --accent: #4c566a;
  --accent-foreground: #eceff4;
  --destructive: #bf616a;
  --destructive-foreground: #eceff4;
  --border: #434c5e;
  --input: #434c5e;
  --ring: #88c0d0;
  --chart-1: #88c0d0;
  --chart-2: #8fbcbb;
  --chart-3: #a3be8c;
  --chart-4: #ebcb8b;
  --chart-5: #b48ead;
  --sidebar: #3b4252;
  --sidebar-foreground: #d8dee9;
  --sidebar-primary: #88c0d0;
  --sidebar-primary-foreground: #2e3440;
  --sidebar-accent: #434c5e;
  --sidebar-accent-foreground: #d8dee9;
  --sidebar-border: #434c5e;
  --sidebar-ring: #88c0d0;

  /* Status tokens — dark mode */
  --color-success-bg: color-mix(in srgb, #a3be8c 15%, transparent);
  --color-success-fg: #a3be8c;
  --color-success-border: color-mix(in srgb, #a3be8c 30%, transparent);
  --color-warning-bg: color-mix(in srgb, #ebcb8b 15%, transparent);
  --color-warning-fg: #ebcb8b;
  --color-warning-border: color-mix(in srgb, #ebcb8b 30%, transparent);
  --color-error-bg: color-mix(in srgb, #bf616a 15%, transparent);
  --color-error-fg: #bf616a;
  --color-error-border: color-mix(in srgb, #bf616a 30%, transparent);
}
```

**Step 3: Add new status tokens to the `@theme inline` block**

At the end of the `@theme inline { ... }` block (before its closing `}`), add:

```scss
  --color-success-bg: var(--color-success-bg);
  --color-success-fg: var(--color-success-fg);
  --color-success-border: var(--color-success-border);
  --color-warning-bg: var(--color-warning-bg);
  --color-warning-fg: var(--color-warning-fg);
  --color-warning-border: var(--color-warning-border);
  --color-error-bg: var(--color-error-bg);
  --color-error-fg: var(--color-error-fg);
  --color-error-border: var(--color-error-border);
```

> **Note on `@theme inline`:** Tailwind 4 uses `@theme inline` to expose CSS variables as utility classes. The convention is `--color-<name>` → `bg-<name>`, `text-<name>`, `border-<name>`. However, since the status tokens are already named `--color-success-bg` etc., mapping `--color-success-bg: var(--color-success-bg)` in `@theme inline` creates a circular reference. Instead, register them under distinct names:

Replace the three lines above with:

```scss
  --color-status-success-bg: var(--color-success-bg);
  --color-status-success-fg: var(--color-success-fg);
  --color-status-success-border: var(--color-success-border);
  --color-status-warning-bg: var(--color-warning-bg);
  --color-status-warning-fg: var(--color-warning-fg);
  --color-status-warning-border: var(--color-warning-border);
  --color-status-error-bg: var(--color-error-bg);
  --color-status-error-fg: var(--color-error-fg);
  --color-status-error-border: var(--color-error-border);
```

This generates Tailwind utilities: `bg-status-success-bg`, `text-status-success-fg`, `border-status-success-border`, etc.

**Step 4: Verify the build compiles**

```bash
cd C:\Users\mariu\Herd\lambda-cms
npm run build
```

Expected: Build completes with no errors. If Vite reports a CSS error, double-check the `@theme inline` block for syntax issues (missing semicolons, unclosed braces).

**Step 5: Commit**

```bash
git add resources/scss/app.scss
git commit -m "feat: replace OKLCH tokens with Nord palette in app.scss"
```

---

## Task 2: Create `useTheme.js` composable

**Files:**
- Create: `resources/js/composables/useTheme.js`

**Step 1: Create the file**

Create `resources/js/composables/useTheme.js` with this content:

```js
import { ref } from 'vue'

const STORAGE_KEY = 'lambda-cms-theme'

// Module-level singleton so all consumers share the same state
const isDark = ref(false)

function applyTheme(dark) {
  isDark.value = dark
  if (dark) {
    document.documentElement.classList.add('dark')
  } else {
    document.documentElement.classList.remove('dark')
  }
  localStorage.setItem(STORAGE_KEY, dark ? 'dark' : 'light')
}

function initTheme() {
  const saved = localStorage.getItem(STORAGE_KEY)
  if (saved === 'dark' || saved === 'light') {
    applyTheme(saved === 'dark')
  } else {
    // Fall back to OS preference on first visit
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches
    applyTheme(prefersDark)
  }
}

export function useTheme() {
  return {
    isDark,
    initTheme,
    toggleTheme: () => applyTheme(!isDark.value),
    setTheme: (value) => applyTheme(value === 'dark'),
  }
}
```

**Why a module-level singleton?** Vue composables run once per call site by default. By hoisting `isDark` outside the function, all components that call `useTheme()` share the same reactive ref — the toggle button and any other consumer stay in sync without a store.

**Step 2: Commit**

```bash
git add resources/js/composables/useTheme.js
git commit -m "feat: add useTheme composable for Nord dark/light toggle"
```

---

## Task 3: Update `AppLayout.vue` — add theme toggle to header

**Files:**
- Modify: `resources/js/Layouts/AppLayout.vue`

The existing layout already has a `<header>` topbar (h-16, flex, items-center, justify-between). We need to:
1. Add the Sun/Moon toggle button on the right side of the header
2. Wire up `useTheme` and call `initTheme()` on mount

**Step 1: Add imports to the `<script setup>` block**

The current `<script setup>` imports are:
```js
import { computed } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import SidebarLink from "@/Components/SidebarLink.vue";
```

Replace with:
```js
import { computed, onMounted } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { Sun, Moon } from "lucide-vue-next";
import SidebarLink from "@/Components/SidebarLink.vue";
import { useTheme } from "@/composables/useTheme.js";
```

**Step 2: Initialise the composable in `<script setup>`**

After the existing `function logout() { ... }` definition, add:

```js
const { isDark, initTheme, toggleTheme } = useTheme()

onMounted(() => {
  initTheme()
})
```

**Step 3: Update the `<header>` template**

The existing header is:
```html
<header class="flex items-center justify-between h-16 px-6 border-b shrink-0">
  <h1 class="text-sm font-semibold">{{ title }}</h1>
</header>
```

Replace it with:
```html
<header class="flex items-center justify-between h-16 px-6 border-b border-border bg-background shrink-0">
  <h1 class="text-sm font-semibold">{{ title }}</h1>
  <button
    @click="toggleTheme"
    class="inline-flex items-center justify-center w-9 h-9 rounded-md text-muted-foreground hover:text-foreground hover:bg-accent transition-colors"
    :aria-label="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
    :title="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
  >
    <Sun v-if="isDark" class="w-4 h-4" />
    <Moon v-else class="w-4 h-4" />
  </button>
</header>
```

**Step 4: Start dev server and verify visually**

```bash
npm run dev
```

1. Open the browser at `http://lambda-cms.test` (or your local dev URL) and log in
2. You should see a Moon icon in the top-right of the header
3. Click it — the page should switch to a dark Nord theme (Polar Night backgrounds, Snow Storm text)
4. Refresh the page — the dark theme should persist (check localStorage key `lambda-cms-theme`)
5. Click the Sun icon — switches back to light Nord theme
6. Open DevTools → Application → Local Storage and confirm the `lambda-cms-theme` key updates

**Step 5: Commit**

```bash
git add resources/js/Layouts/AppLayout.vue
git commit -m "feat: add Nord theme toggle button to AppLayout header"
```

---

## Task 4: Fix hardcoded colors in Dashboard

**Files:**
- Modify: `resources/js/Pages/Dashboard/Index.vue`

**Step 1: Fix the error flash**

Find:
```html
class="flex items-center gap-2 rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700 mb-6"
```

Replace with:
```html
class="flex items-center gap-2 rounded-md bg-status-error-bg border border-status-error-border px-4 py-3 text-sm text-status-error-fg mb-6"
```

**Step 2: Fix the Published stat card icon background**

Find:
```html
<div class="w-8 h-8 rounded-md bg-green-100 flex items-center justify-center">
  <svg class="w-4 h-4 text-green-600"
```

Replace with:
```html
<div class="w-8 h-8 rounded-md bg-status-success-bg flex items-center justify-center">
  <svg class="w-4 h-4 text-status-success-fg"
```

**Step 3: Fix the Drafts stat card icon background**

Find:
```html
<div class="w-8 h-8 rounded-md bg-amber-100 flex items-center justify-center">
  <svg class="w-4 h-4 text-amber-600"
```

Replace with:
```html
<div class="w-8 h-8 rounded-md bg-status-warning-bg flex items-center justify-center">
  <svg class="w-4 h-4 text-status-warning-fg"
```

**Step 4: Commit**

```bash
git add resources/js/Pages/Dashboard/Index.vue
git commit -m "fix: replace hardcoded green/amber/red with Nord status tokens in Dashboard"
```

---

## Task 5: Fix hardcoded colors in Posts/Index.vue

**Files:**
- Modify: `resources/js/Pages/Posts/Index.vue`

**Step 1: Fix the flash alert**

Find (line ~26):
```html
class="mb-4 flex items-center gap-2 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700"
```

Replace with:
```html
class="mb-4 flex items-center gap-2 rounded-md bg-status-success-bg border border-status-success-border px-4 py-3 text-sm text-status-success-fg"
```

**Step 2: Fix the status badge ternary**

Find (lines ~99–103):
```html
:class="post.status === 'published'
  ? 'bg-green-100 text-green-700'
  : 'bg-amber-100 text-amber-700'"
```

Replace with:
```html
:class="post.status === 'published'
  ? 'bg-status-success-bg text-status-success-fg'
  : 'bg-status-warning-bg text-status-warning-fg'"
```

**Step 3: Fix the status dot**

Find (line ~103):
```html
<span class="w-1.5 h-1.5 rounded-full" :class="post.status === 'published' ? 'bg-green-500' : 'bg-amber-500'"></span>
```

Replace with:
```html
<span class="w-1.5 h-1.5 rounded-full" :class="post.status === 'published' ? 'bg-status-success-fg' : 'bg-status-warning-fg'"></span>
```

**Step 4: Commit**

```bash
git add resources/js/Pages/Posts/Index.vue
git commit -m "fix: replace hardcoded green/amber with Nord status tokens in Posts index"
```

---

## Task 6: Fix hardcoded colors in Categories, Tags, Users

**Files:**
- Modify: `resources/js/Pages/Categories/Index.vue`
- Modify: `resources/js/Pages/Tags/Index.vue`
- Modify: `resources/js/Pages/Users/Index.vue`

### Categories/Index.vue

**Step 1: Fix flash alert**

Find:
```html
class="mb-4 flex items-center gap-2 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700"
```

Replace with:
```html
class="mb-4 flex items-center gap-2 rounded-md bg-status-success-bg border border-status-success-border px-4 py-3 text-sm text-status-success-fg"
```

**Step 2: Fix the warning text in the delete dialog**

Find (line ~106):
```html
<p v-if="deleteTarget.posts_count > 0" class="text-sm text-amber-600 mb-4">
```

Replace with:
```html
<p v-if="deleteTarget.posts_count > 0" class="text-sm text-status-warning-fg mb-4">
```

### Tags/Index.vue

**Step 3: Fix flash alert**

Find:
```html
class="mb-4 flex items-center gap-2 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700"
```

Replace with:
```html
class="mb-4 flex items-center gap-2 rounded-md bg-status-success-bg border border-status-success-border px-4 py-3 text-sm text-status-success-fg"
```

### Users/Index.vue

**Step 4: Fix flash alert**

Find:
```html
class="flex items-center gap-2 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700 mb-4"
```

Replace with:
```html
class="flex items-center gap-2 rounded-md bg-status-success-bg border border-status-success-border px-4 py-3 text-sm text-status-success-fg mb-4"
```

**Step 5: Keep `bg-green-500` online indicator and `text-green-600` verified icon**

The online presence dot (`bg-green-500 ring-2 ring-card`) and the verified checkmark (`text-green-600`) in Users are semantic green indicators that are fine to keep as-is — they convey "online" and "verified" which are always green regardless of theme. Do **not** change these.

**Step 6: Commit**

```bash
git add resources/js/Pages/Categories/Index.vue resources/js/Pages/Tags/Index.vue resources/js/Pages/Users/Index.vue
git commit -m "fix: replace hardcoded green/amber with Nord status tokens in Categories, Tags, Users"
```

---

## Task 7: Final visual verification

**Step 1: Run the dev server**

```bash
npm run dev
```

**Step 2: Verify light mode (Snow Storm)**

Visit each page and confirm:
- [ ] Background is `#eceff4` (light grayish-white)
- [ ] Sidebar is `#e5e9f0` (slightly off-white)
- [ ] Primary buttons are `#5e81ac` (deep frost blue)
- [ ] Flash success alerts show in muted green (semi-transparent nord14)
- [ ] Status badges (Published/Draft) use the new status tokens
- [ ] No `bg-green-*`, `bg-amber-*`, `bg-red-*` classes remain (search in DevTools)

**Step 3: Toggle to dark mode**

Click the Moon → Sun toggle. Confirm:
- [ ] Background is `#2e3440` (darkest Polar Night)
- [ ] Sidebar is `#3b4252` (slightly lighter Polar Night)
- [ ] Primary buttons are `#88c0d0` (bright Frost)
- [ ] Text is `#d8dee9` (Snow Storm)
- [ ] Status badges update correctly in dark mode
- [ ] Refresh the page — dark mode persists

**Step 4: Test system preference detection**

1. Clear `lambda-cms-theme` from localStorage (DevTools → Application → Local Storage → Delete)
2. In DevTools → Rendering → Emulate CSS media feature `prefers-color-scheme` → `dark`
3. Refresh — dark mode should activate automatically
4. Set emulation back to `light` → refresh — light mode should activate

**Step 5: Run production build**

```bash
npm run build
```

Expected: Build completes with no errors or warnings about undefined CSS variables.

**Step 6: Final commit (if any last-minute tweaks were made)**

```bash
git add -p
git commit -m "fix: final Nord theme visual adjustments"
```

---

## Summary of All Files Changed

| File | Change |
|---|---|
| `resources/scss/app.scss` | Replace OKLCH with Nord hex; add status tokens; register in `@theme inline` |
| `resources/js/composables/useTheme.js` | **New file** — theme toggle composable |
| `resources/js/Layouts/AppLayout.vue` | Add Sun/Moon toggle button; wire `useTheme` |
| `resources/js/Pages/Dashboard/Index.vue` | Fix hardcoded status colors |
| `resources/js/Pages/Posts/Index.vue` | Fix flash + badge colors |
| `resources/js/Pages/Categories/Index.vue` | Fix flash + delete warning colors |
| `resources/js/Pages/Tags/Index.vue` | Fix flash colors |
| `resources/js/Pages/Users/Index.vue` | Fix flash colors (keep online/verified green) |
