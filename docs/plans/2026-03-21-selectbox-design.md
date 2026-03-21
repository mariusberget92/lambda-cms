# SelectBox Component — Design

**Date:** 2026-03-21
**Status:** Approved

---

## Overview

Replace all native `<select>` elements in the app with a custom `SelectBox` component — a dropdown select "on steroids" with optional search, multi-select with checkboxes, and a clear button.

---

## Component: `resources/js/Components/SelectBox.vue`

### Props

| Prop | Type | Default | Description |
|---|---|---|---|
| `modelValue` | `String\|Number\|Array\|null` | `null` | v-model binding |
| `data` | `Array<{value, label}>` | `[]` | Items to display |
| `multiple` | `Boolean` | `false` | Multi-select with checkboxes |
| `searchable` | `Boolean` | `false` | Search field above items |
| `placeholder` | `String` | `'Select...'` | Trigger placeholder text |
| `disabled` | `Boolean` | `false` | Disables the whole control |

### v-model contract

- Single mode → emits a single `value` (string/number) or `null` when cleared
- Multiple mode → emits an array of `value`s, `[]` when cleared

### Visual structure

**Trigger button** — full width, matches existing input styling (`rounded-md border bg-background px-3 py-2 text-sm focus:ring-2 focus:ring-ring`). Left: selected label, `"N selected"` for multiple, or placeholder. Right: `×` clear button (when something selected) + `⌄` chevron (rotates 180° when open).

**Dropdown panel** — appears directly below trigger, same width, `rounded-md border bg-background shadow-md z-50`. Closed with Escape or `onClickOutside`.

**Search input** (when `searchable`) — first element inside panel, auto-focused on open. Filters item list live by case-insensitive label match. Shows "No results" when empty.

**Item list** — `max-h-60 overflow-y-auto`.
- Single mode: click selects + closes. Selected item: `bg-primary text-primary-foreground`.
- Multiple mode: click toggles, panel stays open. Each item has Nord-green checkbox on left.
- Hover: `bg-accent text-accent-foreground`.

### State

- `open` — dropdown visibility (`ref(false)`)
- `search` — current search string, reset to `''` on each open

### Key computed / methods

- `filteredItems` — filters `data` by search when `searchable`, else returns `data` as-is
- `isSelected(value)` — handles both single and array modelValue
- `select(value)` — single: emit + close; multiple: toggle in array + emit
- `clear()` — emits `null` or `[]`, stops propagation
- `onClickOutside` from `@vueuse/core` on root element

### Keyboard

- `@keydown.escape` on root closes dropdown
- No arrow-key navigation (YAGNI)

### Positioning

No Teleport — dropdown is `position: absolute` inside `position: relative` root.

### Checkboxes

Nord-green checked state via `accent-color: var(--success)` (or equivalent CSS variable already present in the theme). Uses native `<input type="checkbox">` styled with CSS accent-color.

---

## Implementation Tasks

### Task 1 — Build `SelectBox.vue`

**File:** `resources/js/Components/SelectBox.vue` (new)
**Commit:** `feat: add SelectBox component`

Implement the full component per design above.

### Task 2 — Replace all 18 native `<select>` elements

**Commit:** `feat: replace native selects with SelectBox throughout app`

Replace every native `<select>` in these files:

| File | Selects | Notes |
|---|---|---|
| `Pages/Posts/Index.vue` | 1 — Status filter | Static options, v-model |
| `Pages/Settings/Index.vue` | 3 — Timezone, Mail Driver, Mail Encryption | Timezone: `searchable`, dynamic data; others static |
| `Pages/Users/Form.vue` | 1 — Role | Dynamic from `roles` prop; pass `disabled` |
| `Pages/Navigation/Index.vue` | 1 — Page selector | Dynamic; `@change` → `@update:modelValue`; no persistent selection |
| `Pages/Media/Index.vue` | 1 — Media type filter | Static options, v-model |
| `components/MediaPicker.vue` | 1 — Media type filter | Static options, v-model |
| `components/BlockEditor/blocks/HeadingSettings.vue` | 1 — Heading level | Dynamic (1–6); `:value`+`@change` → v-model |
| `components/BlockEditor/blocks/CodeSettings.vue` | 1 — Code language | Static; `:value`+`@change` → v-model |
| `components/BlockEditor/blocks/AdvancedSettings.vue` | 1 — Font family | Dynamic from `FONTS`; `:value`+`@change` → v-model |
| `components/BlockEditor/blocks/ContainerSettings.vue` | 4 — Direction, Justify, Align, Max Width | All static; `:value`+`@change` → v-model |
| `components/BlockEditor/blocks/ComponentSettings.vue` | 2 — Component type, Post order | Static; `:value`+`@change` → v-model |

**Conversion rules:**
- Static `<option>` lists → inline `[{ value: '...', label: '...' }]` array on `:data`
- Dynamic arrays → map to `{ value, label }` shape if not already
- `v-model` → v-model (direct)
- `:value` + `@change` → `v-model`
- Navigation `@change="onPageSelect"` → `@update:modelValue="onPageSelect"` + `:model-value="null"`
