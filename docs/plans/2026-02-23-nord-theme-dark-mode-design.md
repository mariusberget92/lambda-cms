# Nord Theme & Dark Mode — Design Document

**Date:** 2026-02-23
**Status:** Approved

---

## Overview

Replace the current OKLCH color system with the [Nord color palette](https://www.nordtheme.com/docs/colors-and-palettes) and implement a user-toggleable dark/light mode. The toggle lives in a new top header bar in `AppLayout.vue`. Preference is persisted to `localStorage` with a fallback to the OS `prefers-color-scheme` on first visit.

---

## Nord Palette Reference

| Name   | Hex       | Group        |
|--------|-----------|--------------|
| nord0  | `#2e3440` | Polar Night  |
| nord1  | `#3b4252` | Polar Night  |
| nord2  | `#434c5e` | Polar Night  |
| nord3  | `#4c566a` | Polar Night  |
| nord4  | `#d8dee9` | Snow Storm   |
| nord5  | `#e5e9f0` | Snow Storm   |
| nord6  | `#eceff4` | Snow Storm   |
| nord7  | `#8fbcbb` | Frost        |
| nord8  | `#88c0d0` | Frost        |
| nord9  | `#81a1c1` | Frost        |
| nord10 | `#5e81ac` | Frost        |
| nord11 | `#bf616a` | Aurora (red)    |
| nord12 | `#d08770` | Aurora (orange) |
| nord13 | `#ebcb8b` | Aurora (yellow) |
| nord14 | `#a3be8c` | Aurora (green)  |
| nord15 | `#b48ead` | Aurora (purple) |

---

## Approach

**CSS Variables Only (Approach A).**
Replace all OKLCH values in `resources/scss/app.scss` for both `:root` (light) and `.dark` (dark) with Nord hex values, mapped to the existing semantic token names. No Tailwind config changes. No component class renames. The `.dark` class is toggled on `<html>` by a Vue composable.

---

## Color Token Mapping

### Light Mode (`:root`) — Snow Storm base

| CSS Variable                    | Nord   | Hex       | Rationale                          |
|---------------------------------|--------|-----------|------------------------------------|
| `--background`                  | nord6  | `#eceff4` | Lightest snow, main background     |
| `--foreground`                  | nord0  | `#2e3440` | Darkest polar night, body text     |
| `--card`                        | nord5  | `#e5e9f0` | Slightly off-white card surface    |
| `--card-foreground`             | nord0  | `#2e3440` | Same as foreground                 |
| `--popover`                     | nord6  | `#eceff4` | Matches background                 |
| `--popover-foreground`          | nord0  | `#2e3440` | Matches foreground                 |
| `--primary`                     | nord10 | `#5e81ac` | Deep frost blue, primary actions   |
| `--primary-foreground`          | nord6  | `#eceff4` | Light text on primary              |
| `--secondary`                   | nord4  | `#d8dee9` | Muted snow for secondary surfaces  |
| `--secondary-foreground`        | nord1  | `#3b4252` | Dark text on secondary             |
| `--muted`                       | nord4  | `#d8dee9` | Same as secondary                  |
| `--muted-foreground`            | nord3  | `#4c566a` | Subdued text                       |
| `--accent`                      | nord8  | `#88c0d0` | Bright frost for accents/hovers    |
| `--accent-foreground`           | nord0  | `#2e3440` | Dark text on accent                |
| `--destructive`                 | nord11 | `#bf616a` | Aurora red                         |
| `--destructive-foreground`      | nord6  | `#eceff4` | Light text on destructive          |
| `--border`                      | nord4  | `#d8dee9` | Snow border                        |
| `--input`                       | nord4  | `#d8dee9` | Input border                       |
| `--ring`                        | nord9  | `#81a1c1` | Focus ring frost                   |
| `--sidebar`                     | nord5  | `#e5e9f0` | Slightly darker than bg            |
| `--sidebar-foreground`          | nord1  | `#3b4252` | Dark sidebar text                  |
| `--sidebar-primary`             | nord10 | `#5e81ac` | Active nav items                   |
| `--sidebar-primary-foreground`  | nord6  | `#eceff4` | Text on active nav                 |
| `--sidebar-accent`              | nord4  | `#d8dee9` | Hover state in sidebar             |
| `--sidebar-accent-foreground`   | nord0  | `#2e3440` | Text on sidebar hover              |
| `--sidebar-border`              | nord4  | `#d8dee9` | Sidebar divider                    |

**Chart colors (light):** nord10, nord8, nord14, nord13, nord15

### Dark Mode (`.dark`) — Polar Night base

| CSS Variable                    | Nord   | Hex       | Rationale                          |
|---------------------------------|--------|-----------|------------------------------------|
| `--background`                  | nord0  | `#2e3440` | Darkest polar night, main bg       |
| `--foreground`                  | nord4  | `#d8dee9` | Light snow text                    |
| `--card`                        | nord1  | `#3b4252` | Slightly elevated surface          |
| `--card-foreground`             | nord4  | `#d8dee9` | Light text on cards                |
| `--popover`                     | nord1  | `#3b4252` | Matches card                       |
| `--popover-foreground`          | nord4  | `#d8dee9` | Light text                         |
| `--primary`                     | nord8  | `#88c0d0` | Bright frost, primary actions      |
| `--primary-foreground`          | nord0  | `#2e3440` | Dark text on primary               |
| `--secondary`                   | nord2  | `#434c5e` | Mid polar night                    |
| `--secondary-foreground`        | nord4  | `#d8dee9` | Light text                         |
| `--muted`                       | nord1  | `#3b4252` | Muted surface                      |
| `--muted-foreground`            | nord3  | `#4c566a` | Subdued text                       |
| `--accent`                      | nord3  | `#4c566a` | Hover accent                       |
| `--accent-foreground`           | nord6  | `#eceff4` | Light text on accent               |
| `--destructive`                 | nord11 | `#bf616a` | Aurora red (same)                  |
| `--destructive-foreground`      | nord6  | `#eceff4` | Light text on destructive          |
| `--border`                      | nord2  | `#434c5e` | Subtle border                      |
| `--input`                       | nord2  | `#434c5e` | Input border                       |
| `--ring`                        | nord8  | `#88c0d0` | Frost focus ring                   |
| `--sidebar`                     | nord1  | `#3b4252` | Slightly raised sidebar            |
| `--sidebar-foreground`          | nord4  | `#d8dee9` | Light sidebar text                 |
| `--sidebar-primary`             | nord8  | `#88c0d0` | Active nav                         |
| `--sidebar-primary-foreground`  | nord0  | `#2e3440` | Dark text on active                |
| `--sidebar-accent`              | nord2  | `#434c5e` | Nav hover                          |
| `--sidebar-accent-foreground`   | nord4  | `#d8dee9` | Text on nav hover                  |
| `--sidebar-border`              | nord2  | `#434c5e` | Sidebar divider                    |

**Chart colors (dark):** nord8, nord7, nord14, nord13, nord15

### New Semantic Status Tokens

Added to both `:root` and `.dark`:

| Token                   | Light                        | Dark                         |
|-------------------------|------------------------------|------------------------------|
| `--color-success-bg`    | nord14 at 20% opacity        | nord14 at 15% opacity        |
| `--color-success-fg`    | `#638a47` (darkened nord14)  | nord14 (`#a3be8c`)           |
| `--color-success-border`| nord14 at 40% opacity        | nord14 at 30% opacity        |
| `--color-warning-bg`    | nord13 at 20% opacity        | nord13 at 15% opacity        |
| `--color-warning-fg`    | `#a07c20` (darkened nord13)  | nord13 (`#ebcb8b`)           |
| `--color-warning-border`| nord13 at 40% opacity        | nord13 at 30% opacity        |
| `--color-error-bg`      | nord11 at 15% opacity        | nord11 at 15% opacity        |
| `--color-error-fg`      | nord11 (`#bf616a`)           | nord11 (`#bf616a`)           |
| `--color-error-border`  | nord11 at 35% opacity        | nord11 at 30% opacity        |

Registered in the `@theme inline` block so Tailwind generates `bg-success-bg`, `text-success-fg`, `border-success-border` etc. as real utilities.

---

## New Files

### `resources/js/composables/useTheme.js`

```
Responsibilities:
- isDark: ref<boolean>
- On init: read 'theme' from localStorage
    - If 'dark' or 'light': apply it
    - If absent: check window.matchMedia('(prefers-color-scheme: dark)')
- toggleTheme(): flip isDark, update <html> classList, write localStorage
- setTheme(value: 'dark'|'light'): explicit setter
- Apply theme by adding/removing 'dark' class on document.documentElement
```

---

## Modified Files

### `resources/scss/app.scss`
- Replace all OKLCH values in `:root` and `.dark` with Nord hex values per the token mapping above
- Add `--color-success-*`, `--color-warning-*`, `--color-error-*` tokens to both `:root` and `.dark`
- Register new tokens in `@theme inline` block

### `resources/js/Layouts/AppLayout.vue`
- Add a top header bar (`<header>`) above the main content area
- Height: `h-12` (48px)
- Layout: flex, space-between — left side empty or page title area, right side has theme toggle button
- Theme toggle: `<Button variant="ghost" size="icon">` with `<Sun>` icon in dark mode, `<Moon>` icon in light mode (both from lucide-vue-next)
- Import and initialise `useTheme()` composable on component mount
- Header background: `bg-background`, border-bottom: `border-b border-border`

### CRUD Pages — hardcoded color audit
Replace in all affected files:

| Old classes | New classes |
|---|---|
| `bg-green-50 border-green-200 text-green-700` | `bg-[var(--color-success-bg)] border-[var(--color-success-border)] text-[var(--color-success-fg)]` |
| `bg-green-100 text-green-700` (badges) | same success tokens |
| `bg-amber-100 text-amber-600` (badges) | `bg-[var(--color-warning-bg)] text-[var(--color-warning-fg)]` |
| `bg-red-50 border-red-200 text-red-700` | `bg-[var(--color-error-bg)] border-[var(--color-error-border)] text-[var(--color-error-fg)]` |

**Files affected:**
- `resources/js/Pages/Dashboard/Index.vue`
- `resources/js/Pages/Posts/Index.vue`
- `resources/js/Pages/Categories/Index.vue`
- `resources/js/Pages/Tags/Index.vue`
- `resources/js/Pages/Users/Index.vue`

---

## Implementation Order

1. Update `app.scss` — Nord tokens + new status tokens + `@theme` registration
2. Create `useTheme.js` composable
3. Update `AppLayout.vue` — add header bar + wire up composable
4. Fix hardcoded colors in CRUD pages (can be done in parallel per file)

---

## Out of Scope

- Saving theme preference to the database
- Per-page theme overrides
- Additional Nord-specific component styling beyond the token system
