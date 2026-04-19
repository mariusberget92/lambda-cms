# Icon Picker Design

**Date:** 2026-04-19
**Status:** Approved

---

## Problem

Text blocks (Heading, Paragraph) have no way to display an icon alongside their content. The existing `IconSettings.vue` has a Lucide-only, hand-curated list — not a real picker, no search, ~70 icons only.

---

## Goals

- Add a proper searchable icon picker to HeadingBlock and ParagraphBlock
- Support Font Awesome 6 (solid, regular, brands), Lucide, and Tabler icon sets (~8,000+ icons total)
- Let editors choose prefix or suffix position, with independent size, color, and gap controls
- Zero impact on public frontend bundle (icon JSON stays in editor chunk only)

---

## Data Model

`block.data.icon` shape (same for all supported blocks):

```js
{
  name:     string | null,  // Iconify identifier e.g. "fa6-solid:star", null = disabled
  position: 'prefix' | 'suffix',
  size:     string,         // CSS value e.g. "1.25em"
  color:    string | null,  // null = inherit parent text color
  gap:      string,         // CSS value e.g. "0.5em" — space between icon and text
}
```

---

## Architecture

### Dependencies

```
@iconify/vue               — Icon render component (used on frontend + editor)
@iconify-json/fa6-solid    — FA6 Solid (~1,600 icons)
@iconify-json/fa6-regular  — FA6 Regular (~160 icons)
@iconify-json/fa6-brands   — FA6 Brands (~450 icons)
@iconify-json/lucide       — Lucide (~1,500 icons)
@iconify-json/tabler       — Tabler (~5,500 icons)
```

Icon JSON is imported **only inside `IconPickerInput.vue`** (the editor-only picker component). Public block components (`HeadingBlock`, `ParagraphBlock`) import only `@iconify/vue`'s `Icon` component — no JSON data, negligible size.

### New / Changed Files

| File | Action |
|---|---|
| `resources/js/components/BlockEditor/IconPickerInput.vue` | Create — searchable dialog picker |
| `resources/js/components/BlockEditor/blocks/IconSettings.vue` | Refactor — replace list with IconPickerInput, add size/color/gap fields |
| `resources/js/Components/Blocks/HeadingBlock.vue` | Modify — render icon prefix/suffix |
| `resources/js/Components/Blocks/ParagraphBlock.vue` | Modify — render icon prefix/suffix |
| `resources/js/components/BlockEditor/blocks/ParagraphSettings.vue` | Modify — add IconSettings to Style tab |
| `resources/js/components/BlockEditor/EditorContainerBlock.vue` | Fix canvas bug — apply flex-1 min-w-0 to container children in flex-row |

---

## IconPickerInput.vue

A reusable component that looks like a select input. Opens a Dialog on click.

**Props:** `modelValue: string | null`
**Emits:** `update:modelValue`

**Dialog contents:**
- Set filter tabs: `All` / `FA Solid` / `FA Regular` / `FA Brands` / `Lucide` / `Tabler`
- Search input — filters by icon name in real time
- Icon grid — 100 icons per page, each tile shows SVG + truncated name
- Pagination controls (prev/next page)
- "Clear" button to set value to null

**Icon data source:** Full JSON from each `@iconify-json/*` package, merged into a lookup at module level. Each entry is `{ prefix: string, name: string }` — the full identifier is `${prefix}:${name}`.

---

## IconSettings.vue (refactored)

Replaces the current Lucide-only icon list. New layout:

```
[IconPickerInput]          ← replaces old icon name list
Position: [Prefix] [Suffix]
Size:  [____1.25em____]
Color: [■] [inherit]
Gap:   [____0.5em_____]
```

---

## Block Rendering

### HeadingBlock.vue

When `block.data.icon?.name` is set, wraps content in a flex container:

```html
<component :is="tag" :style="flexStyle">
  <Icon v-if="isPre"  :icon="icon.name" :style="iconStyle" />
  <span>{{ resolvedText }}</span>
  <Icon v-if="isPost" :icon="icon.name" :style="iconStyle" />
</component>
```

When no icon: renders exactly as today (`<component :is="tag">{{ text }}</component>`).

### ParagraphBlock.vue

Same pattern — icon + `<div v-html="resolvedContent" />` inside a flex wrapper.

---

## Canvas Bug Fix

**Problem:** In `EditorContainerBlock.vue`, when a container is in flex-row mode, sibling container-type children (which render via `EditorContainerBlock` recursively) don't receive `flex-1 min-w-0`, causing them to shrink to content width.

**Fix:** In the `draggableClass` computed (or child item wrapper), apply `flex-1 min-w-0` to every direct child in flex-row mode — matching what `ContainerBlock.vue` already does for the public renderer.

---

## Out of Scope

- Icon support on CTA, Link, Navigation, or other non-text blocks
- Animated icons (line-md set)
- Custom SVG upload
- Icon color gradient
