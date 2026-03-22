# Page Builder — Layout Blocks Design

**Date:** 2026-03-22
**Scope:** Extend the existing block-based page builder with improved layout primitives — responsive Grid/Flex container, Section wrapper, and Spacer block.

---

## Context

The page builder currently has 12 block types. The `ContainerBlock` supports flexbox layouts (row/column, gap, justify, align, padding, max-width). Three gaps exist in the layout system:

1. No CSS grid support
2. No semantic section wrapper with background styling
3. No spacer block
4. No responsive breakpoint controls on any layout block

---

## Approach

Use **Tailwind class generation** from per-breakpoint data objects. Block data stores plain values per breakpoint; the renderer computes Tailwind responsive class strings at render time. This is native to the stack and requires no new dependencies.

Three breakpoints: `default` (mobile-first), `sm` (≥640px), `lg` (≥1024px).

---

## Data Schema

### Container block — extended

New `mode` field switches between flex and grid. `direction` becomes a responsive object. All existing fields remain.

```js
{
  type: 'container',
  data: {
    mode: 'flex' | 'grid',           // NEW — default 'flex'

    // Flex-only
    direction: { default: 'row', sm: 'column' },  // was plain string
    wrap: true,
    justify: 'start' | 'center' | 'end' | 'between',
    align: 'start' | 'center' | 'end' | 'stretch',

    // Grid-only
    columns: { default: 2, sm: 1, lg: 3 },   // grid-cols-*

    // Shared
    gap: 4,
    padding: 4,
    maxWidth: 'full' | 'sm' | 'md' | 'lg' | 'xl' | '2xl' | 'prose',
  },
  children: []
}
```

### Section block — new

Top-level semantic wrapper. Renders as `<section>`. Supports background color, image, or gradient. Holds children like a container.

```js
{
  type: 'section',
  data: {
    bgType: 'none' | 'color' | 'image' | 'gradient',
    bgColor: '#ffffff',
    bgImage: { url: '', position: 'center', size: 'cover' },
    bgGradient: { from: '#3b4252', to: '#4c566a', direction: 'to-r' },
    fullWidth: false,
    innerMaxWidth: 'xl',   // 'sm'|'md'|'lg'|'xl'|'2xl'|'full'
    paddingY: { default: 16, sm: 8 },
    paddingX: { default: 8, sm: 4 },
    minHeight: 'auto',     // 'auto'|'screen'|'1/2'|custom Tailwind min-h value
  },
  children: []
}
```

### Spacer block — new

Simple height spacer, responsive.

```js
{
  type: 'spacer',
  data: {
    height: { default: 8, sm: 4 }   // Tailwind spacing units 1–96
  }
}
```

---

## Settings Panels

### ContainerSettings.vue — extended

- **Mode toggle** — two-button pill at top: `Flex` / `Grid`
- **Flex mode:**
  - Direction: three compact selectors (Default / SM / LG) — `row` or `column`
  - Wrap, Justify, Align — unchanged
- **Grid mode:**
  - Columns: three number inputs (Default / SM / LG, range 1–12)
  - Column gap / Row gap: number inputs
- **Shared** — Gap, Padding, Max Width unchanged
- Breakpoint controls use a compact 3-column layout with device icons (mobile / tablet / desktop)

### SectionSettings.vue — new

Four grouped sections:
1. **Background** — radio: None / Color / Image / Gradient
   - Color: color picker
   - Image: MediaPicker + position select + size select
   - Gradient: two color pickers + direction select (to-r, to-b, to-br, etc.)
2. **Width** — Full Width toggle + Inner Max Width select
3. **Spacing** — Padding Y and Padding X, each with Default / SM / LG inputs
4. **Min Height** — select: auto / screen / half-screen / custom

### SpacerSettings.vue — new

Three number inputs in a row: Default / SM / LG height (Tailwind spacing units).

---

## Block Renderer

### `resolveResponsive(value, prefix)` helper

Converts breakpoint objects to Tailwind class strings. Handles backward compat with old flat string/number values.

```js
function resolveResponsive(value, prefix) {
  if (typeof value === 'string' || typeof value === 'number') {
    // backward compat: old flat value treated as default only
    return `${prefix}-${value}`
  }
  return Object.entries(value)
    .map(([bp, v]) => v != null
      ? (bp === 'default' ? `${prefix}-${v}` : `${bp}:${prefix}-${v}`)
      : null
    )
    .filter(Boolean)
    .join(' ')
}
```

### ContainerBlock.vue — updated render logic

```
mode === 'flex' → flex + resolveResponsive(direction, 'flex') + existing classes
mode === 'grid' → grid + resolveResponsive(columns, 'grid-cols') + gap classes
```

### SectionBlock.vue — new

- Outer `<section>`: full viewport width, background via inline style (image/gradient) or Tailwind class (color), responsive padding classes
- Inner `<div>`: `max-w-{innerMaxWidth} mx-auto` when `fullWidth` is false
- Renders `children` via `BlockRenderer` recursively

### SpacerBlock.vue — new

Single `<div>` with responsive height class (`h-{n}` per breakpoint).

---

## Canvas (Editor)

- **Section** — blue dashed full-width border in canvas, labelled `Section`, distinct from Container to signal top-level scope
- **Grid mode Container** — faint column-line overlay in canvas matching the column count
- **Spacer** — hatched/striped placeholder with height value centred

## BlockTypePanel

| Icon (lucide-vue-next) | Label | Type |
|---|---|---|
| `Columns` | Section | `section` |
| `ArrowUpDown` | Spacer | `spacer` |

Container entry updated: label → `Container / Grid`, subtitle notes flex + grid support.

---

## Backward Compatibility

- No database migration required — `blocks` is already a JSON column
- `resolveResponsive` handles old flat `direction: 'row'` strings transparently
- Existing pages continue to render identically

## Tailwind Safelist

Dynamically generated responsive classes must be safelisted to survive Tailwind's purge:

```js
safelist: [
  { pattern: /^(sm:|lg:)?(grid-cols|flex-row|flex-col|h-|py-|px-|gap-)/ }
]
```

---

## Files Changed

**Modified:**
- `resources/js/Components/BlockEditor/blocks/ContainerBlock.vue`
- `resources/js/Components/BlockEditor/blocks/ContainerSettings.vue`
- `resources/js/Components/BlockEditor/BlockRenderer.vue`
- `resources/js/Components/BlockEditor/BlockTypePanel.vue`
- `resources/js/Components/BlockEditor/BlockEditor.vue`
- `vite.config.js` (safelist)

**New:**
- `resources/js/Components/BlockEditor/blocks/SectionBlock.vue`
- `resources/js/Components/BlockEditor/blocks/SectionSettings.vue`
- `resources/js/Components/BlockEditor/blocks/SpacerBlock.vue`
- `resources/js/Components/BlockEditor/blocks/SpacerSettings.vue`
