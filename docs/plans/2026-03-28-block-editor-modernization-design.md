# Block Editor Modernization — Design Document

**Date:** 2026-03-28
**Approach:** Structural + cosmetic (Approach B)

---

## Goals

- Force the entire block editor into dark mode regardless of the user's app/system theme
- Modernize all three panels using Radix/shadcn-style conventions
- Add a canvas toolbar with a Live Preview toggle
- Restructure the settings panel with Content / Style / Advanced tabs per block
- Fix native browser spinner arrows showing alongside custom chevrons in `NumberInput`

---

## Section 1 — Dark Scope & Overall Chrome

### Mechanism

`BlockEditor.vue` adds `data-theme="dark"` to its root wrapper element. Inside `app.css`, a scoped rule overrides the Tailwind CSS variable tokens when `data-theme="dark"` is present — `--background`, `--card`, `--sidebar`, `--border`, `--muted`, `--muted-foreground`, `--foreground`, `--accent`, etc. — to their dark-mode values. No other page or component is affected.

### Chrome changes

- **Outer border:** `border-white/10`
- **Panel dividers** (border-r, border-l): `border-white/8`
- **Panel header bars:** add `bg-black/20` tint to distinguish from panel body
- **Scrollbars:** already hidden, no change
- **Overall layout:** three-column flex structure unchanged; `min-height: 500px; max-height: calc(100vh - 220px)` unchanged

---

## Section 2 — Block Palette (Left Panel)

**File:** `BlockTypePanel.vue`

### Tile redesign

| Property | Before | After |
|---|---|---|
| Width | `w-48` | `w-48` (unchanged) |
| Tile padding | `py-3` | `py-4` |
| Icon size | `w-4 h-4` | `w-5 h-5` |
| Label size | `text-[10px]` | `text-[11px]` |
| Tile bg | `bg-background border-border` | `bg-white/5 border-white/10` |
| Tile hover | `hover:border-primary hover:text-primary` | `hover:bg-white/10 hover:border-white/20` |
| Drag active | same as hover | `border-primary/60 bg-primary/10` |

### Group headers

Change from plain `text-[10px] uppercase tracking-widest text-muted-foreground/60` text to pill labels:
```html
<span class="inline-flex bg-white/5 rounded-full px-2 py-0.5 text-[9px] uppercase tracking-widest text-white/40">
  {{ group.name }}
</span>
```

### Color-coded icons

Each group's block tiles use a different chart color token for the icon:

| Group | Token |
|---|---|
| Content | `text-[var(--color-chart-1)]` (blue) |
| Layout | `text-[var(--color-chart-2)]` (green) |
| Interactive | `text-[var(--color-chart-3)]` (orange) |
| Developer | `text-[var(--color-chart-4)]` (purple) |
| Post | `text-[var(--color-chart-5)]` (pink/red) |
| Archive | `text-[var(--color-chart-1)]` (blue, same as Content) |

Labels stay `text-muted-foreground`.

---

## Section 3 — Canvas (Middle Panel)

**File:** `BlockCanvas.vue`

### Background

- Replace `bg-background` with `bg-[#0f0f0f]`
- Add subtle dot-grid via CSS: `2px` dots at `24px` spacing in `rgba(255,255,255,0.04)` using `radial-gradient` background-image

```css
background-image: radial-gradient(circle, rgba(255,255,255,0.04) 1px, transparent 1px);
background-size: 24px 24px;
```

### Block cards (wireframe mode)

| Property | Before | After |
|---|---|---|
| Card bg | `bg-card` | `bg-white/4` |
| Card border | `border-border` | `border-white/10` |
| Hover border | `hover:border-muted-foreground` | `hover:border-white/20` |
| Selected | `border-primary ring-1 ring-primary` | `border-primary ring-1 ring-primary bg-primary/8` |
| Drag handle border | `border-transparent group-hover:border-border/50` | `border-transparent group-hover:border-white/8` |
| Label header bg | implicit card | `bg-white/3 border-white/8` |
| Label text | `text-muted-foreground` | `text-white/40` |
| Block preview content | full opacity | `opacity-60 pointer-events-none` |

### Canvas toolbar

A new slim toolbar (`h-9 border-b border-white/8 bg-black/20 px-3 flex items-center justify-between shrink-0`) is inserted **above** the scroll area:

- **Left:** Breadcrumb showing selected block ancestry — e.g. `Section › Container › Heading` — using `ChevronRight` separators, `text-xs text-white/40`. Shows "No block selected" when nothing is selected.
- **Right:** Live Preview toggle button — `EyeIcon` + "Preview" label, `text-xs`. When inactive: `bg-white/8 border-white/12 text-white/60`. When active: `bg-primary/20 border-primary/40 text-primary`.

### Live Preview mode

When the preview toggle is active, the scrollable canvas area replaces the `VueDraggable` block list with a `<div class="bg-white rounded-lg overflow-hidden">` containing a `<BlockRenderer :blocks="blocks" />` component — the same renderer used on public pages. This gives a pixel-accurate preview of the frontend output. Toggling back to wireframe mode restores the draggable canvas instantly (no re-fetch needed; `blocks` array is unchanged).

### Empty state

Replace the current plain text with:
```html
<div class="text-center">
  <LayoutTemplate class="w-8 h-8 mx-auto mb-3 text-white/20" />
  <p class="text-sm text-white/40">Drag a block from the left to get started</p>
</div>
```

---

## Section 4 — Settings Panel (Right Panel, Tabs)

**Files:** `BlockLayers.vue`, all `blocks/*Settings.vue`, `AdvancedSettings.vue`, `ConditionSettings.vue`

### Tab structure

Replace the flat settings scroll with a `<Tabs>` component (shadcn-vue):

```
┌─────────────────────────────┐
│ [Content] [Style] [Advanced]│  ← TabsList
├─────────────────────────────┤
│                             │
│   Tab content here          │  ← TabsContent
│                             │
└─────────────────────────────┘
```

Tabs per block type:

| Tab | Contents |
|---|---|
| **Content** | Primary data fields: text, URLs, media pickers, toggles for boolean flags |
| **Style** | Layout/visual fields: padding, spacing, max-width, direction, alignment, colors, background type |
| **Advanced** | Custom ID, CSS classes, custom CSS, animation (from `AdvancedSettings.vue`) + condition settings (from `ConditionSettings.vue`) if inside a loop |

For blocks with very little content (Divider, Spacer), **Style** is the default active tab. For blocks with no settings at all (post-comments), the tab bar is hidden.

### Settings form polish

- **Section group labels:** `text-[11px] font-semibold uppercase tracking-widest text-white/40 mb-2`
- **Input fields / textareas:** `bg-white/8 border-white/12 text-white/80 placeholder:text-white/30`
- **Select boxes:** same dark treatment
- **Related field groups:** wrapped in `bg-white/4 rounded-lg p-3 space-y-2`
- **Toggle groups** (Flex/Grid, etc.): icon-button rows with `bg-white/8` inactive, `bg-primary text-primary-foreground` active, `rounded-md` containers

### Block tab mapping (Content / Style split)

| Block | Content tab | Style tab |
|---|---|---|
| Paragraph | text content, dynamic field | alignment, font size |
| Heading | text, level (h1–h6), tag | alignment, font size |
| Image | media picker, alt, caption | max-width, aspect ratio |
| Quote | text, attribution | alignment |
| Code | code content, language | — |
| Gallery | items list | columns, gap |
| Video | URL, caption | aspect ratio |
| Divider | — | style (line/dots/none), color, thickness |
| Spacer | — | height (responsive) |
| CTA | headline, body text, button label/URL | alignment, button style |
| HTML | raw HTML | — |
| Container | mode (flex/grid) | direction, gap, justify, align, max-width, padding |
| Section | bg type/color/image/gradient | full-width, inner max-width, padding, min-height |
| Loop | source, filters, sort, limit, offset | columns, gap |
| Component | component type, limit, offset, order, filters | — |
| Post Title | tag | — |
| Post Body | — | — |
| Post Featured Image | — | max-width, aspect ratio |
| Post Meta | date/author/readTime toggles | — |
| Post Author | showAvatar toggle | — |
| Post Taxonomy | showCategories/showTags toggles | — |
| Post Comments | — | — |
| Archive Title | tag | — |
| Archive Loop | source, limit | columns, gap |
| Search | placeholder, button label, scope | — |

---

## Section 5 — NumberInput Caret Fix

**File:** `NumberInput.vue`

Add webkit pseudo-element classes to suppress native spinners in Chrome/Safari alongside the existing `[appearance:textfield]` (which handles Firefox):

```html
class="... [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
```

This ensures only the custom `ChevronUp` / `ChevronDown` buttons are visible across all browsers.

---

## Files Affected

### Modified
- `resources/js/components/BlockEditor/BlockEditor.vue` — `data-theme="dark"` on root
- `resources/js/components/BlockEditor/BlockTypePanel.vue` — tile redesign, pill headers, color-coded icons
- `resources/js/components/BlockEditor/BlockCanvas.vue` — dark bg, dot grid, toolbar, wireframe cards, live preview
- `resources/js/components/BlockEditor/BlockLayers.vue` — inject `<Tabs>` into settings section
- `resources/js/components/BlockEditor/blocks/*.vue` — split each into Content/Style tabs
- `resources/js/components/BlockEditor/blocks/AdvancedSettings.vue` — becomes Advanced tab content (no structural change)
- `resources/js/Components/NumberInput.vue` — webkit spin-button suppression
- `resources/css/app.css` — `[data-theme="dark"]` CSS variable override block

### No change
- `BlockLayers.vue` layers tree section (LayerItem, drag-to-reorder) — minor color polish only, no structural change
- All backend files
- All public-facing block renderer components (`Blocks/*.vue`)

---

## Out of Scope

- Layers panel structural redesign
- Responsive breakpoint preview (mobile/tablet/desktop canvas width toggle)
- Block search/filter in the palette
- Any backend changes
