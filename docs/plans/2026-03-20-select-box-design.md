# SelectBox Component Design

**Date:** 2026-03-20
**Status:** Approved

## Overview

A custom dropdown select component to replace native `<select>` elements across the CMS. Supports single and multi-select, optional search filtering, and a clear button. Built from scratch using Vue 3 + `onClickOutside` from `@vueuse/core`.

## File

`resources/js/Components/SelectBox.vue`

## Props

| Prop | Type | Default | Description |
|---|---|---|---|
| `modelValue` | `String\|Number\|Array\|null` | `null` | v-model binding |
| `data` | `Array<{value, label}>` | `[]` | Items to display |
| `multiple` | `Boolean` | `false` | Multi-select with checkboxes |
| `searchable` | `Boolean` | `false` | Search field above items |
| `placeholder` | `String` | `'Select...'` | Trigger placeholder text |
| `disabled` | `Boolean` | `false` | Disables the whole control |

## Emits

- `update:modelValue` — single: emits a scalar value or `null`; multiple: emits an array of values or `[]`

## v-model Contract

- **Single mode** — modelValue is a single scalar (`String | Number | null`). Selecting an item emits its `value`. Clear emits `null`.
- **Multiple mode** — modelValue is an array. Toggling an item adds/removes its `value`. Clear emits `[]`.

## Visual Structure

```
┌──────────────────────────────────────┐
│  Selected label / N selected    [×][⌄]│  ← trigger button
└──────────────────────────────────────┘
          ↓ when open
┌──────────────────────────────────────┐
│  [🔍 Search...]                      │  ← only if searchable=true
│ ────────────────────────────────── │
│  ☐  Option A                         │  ← checkbox only if multiple=true
│  ☑  Option B                         │
│  ☐  Option C                         │
└──────────────────────────────────────┘
```

## Behaviour

### Trigger
- Full width, matches existing input styling: `rounded-md border bg-background px-3 py-2 text-sm focus:ring-2 focus:ring-ring`
- Left: selected label, `"N selected"` (multi), or placeholder
- Right: `×` clear button (visible only when selection non-empty) + `⌄` chevron (rotates 180° when open)
- Click toggles the dropdown; `×` clears without reopening

### Dropdown Panel
- Appears directly below the trigger, same width
- `position: absolute` (root is `position: relative`) — no Teleport
- `rounded-md border bg-background shadow-md z-50`
- Closed by: clicking outside (`onClickOutside`), pressing `Escape`, or selecting an item (single mode only)

### Search Input (searchable=true)
- First element inside the panel
- Auto-focused via `watch(open)` + `nextTick`
- Filters items live: case-insensitive `label.includes(search)`
- Shows "No results" message when filter returns empty
- Reset to `''` each time the dropdown opens

### Item List
- `max-h-60 overflow-y-auto`
- Hover: `bg-accent text-accent-foreground`
- **Single mode:** click → emit value → close dropdown. Selected item: `bg-primary text-primary-foreground`
- **Multiple mode:** click → toggle value in array → emit → dropdown stays open. Each item has a Nord-green checkbox (`accent-nord-green`) on the left

## Internal State

| Ref | Purpose |
|---|---|
| `open` | Dropdown visibility |
| `search` | Current search string, reset on open |
| `rootRef` | Template ref for `onClickOutside` |
| `searchRef` | Template ref for search auto-focus |

## Key Computed / Functions

- `filteredItems` — filters `data` by `search` when `searchable`, otherwise passthrough
- `isSelected(value)` — works for both scalar and array modelValue
- `select(value)` — single: emit + close; multiple: toggle in array + emit
- `clear(event)` — `event.stopPropagation()`, emit `null` / `[]`
- `displayLabel` — derived from modelValue: label string, "N selected", or placeholder

## Keyboard

- `Escape` on root element closes dropdown
- No arrow-key navigation (not needed for a CMS internal tool)

## Approach Rationale

Built from scratch (not reka-ui/headlessui) for full control and simplicity. The component is entirely self-contained and easy to understand. `onClickOutside` from `@vueuse/core` (already installed) handles the hardest part.
