# Block Editor Settings Overhaul — Design

**Date:** 2026-04-18
**Status:** Approved

## Goal

Replace raw Tailwind class strings typed into Custom Classes with proper UI inputs for all commonly-used visual properties. Every block that renders text gets full typography settings. All blocks get an Effects section for opacity, cursor, overflow, z-index, and transition controls.

Hover/responsive variants are out of scope — Custom Classes remains the escape hatch for those edge cases.

---

## Section 1 — Extended TypographyControl

The existing `TypographyControl.vue` already covers: text alignment, color, font size, font weight, line height.

**New fields to add:**

| Field | Input type | Values |
|---|---|---|
| Letter spacing | SelectBox | Tighter / Normal / Wide / Wider / Widest |
| Text decoration | Button group | None / Underline / Line-through |
| Text transform | Button group | None / Uppercase / Lowercase / Capitalize |
| Text shadow | ColorPicker + X/Y offset + blur inputs | Color + numeric offsets |

All new fields follow the same `modelValue` / emit pattern as existing fields.

---

## Section 2 — Blocks Getting Style Tab + TypographyControl

These blocks currently have no Style tab and need one added, each containing `TypographyControl`:

| Block type | Notes |
|---|---|
| `filter-link` | Category/tag filter links |
| `navigation` | Nav links |
| `search` | Placeholder text + button label |
| `post-title` | The h1 on single post pages |
| `post-meta` | Date, author, read time text |
| `post-author` | Author name / bio |
| `post-taxonomy` | Category and tag labels |
| `archive-title` | Archive page heading |

Additionally, `link` already has a Style tab but is **missing** `TypographyControl` — it gets added.

**Deliberately excluded** (render uncontrolled HTML or have no text):
`post-body`, `post-comments`, `html`, `component`, `template`

**Implementation steps per block:**
1. Add block type to the `STYLE_BLOCKS` Set in `BlockLayers.vue`
2. Add a `v-show="tab === 'style'"` section to the block's `*Settings.vue` file
3. Wire up `TypographyControl` with the block's existing `update()` emit pattern

---

## Section 3 — Effects Section in AdvancedSettings

A new collapsible **Effects** section added below the existing Background section in `AdvancedSettings.vue`. Available on every block.

| Field | Input type | Values / Range |
|---|---|---|
| Opacity | Slider + number input | 0–100 (maps to `opacity: N%` inline style) |
| Cursor | SelectBox | Default / Pointer / Not-allowed / Wait / Text / Grab |
| Overflow | SelectBox | Visible / Hidden / Auto / Scroll |
| Z-index | Number input | Free integer |
| Transition duration | SelectBox | None / 75ms / 150ms / 300ms / 500ms / 700ms / 1000ms |
| Transition easing | SelectBox | Linear / Ease / Ease-in / Ease-out / Ease-in-out |

Values are stored in `block.data` (e.g. `block.data.opacity`, `block.data.cursor`) and applied in `BlockRenderer.vue`'s `blockWrapperStyle()` function as inline styles — same pattern as spacing and background already use.

---

## Files Affected

### Modified
- `resources/js/components/BlockEditor/TypographyControl.vue` — add letter spacing, text decoration, text transform, text shadow fields
- `resources/js/components/BlockEditor/BlockLayers.vue` — add 8 block types to `STYLE_BLOCKS`
- `resources/js/Components/BlockRenderer.vue` — apply new effects properties in `blockWrapperStyle()`
- `resources/js/components/BlockEditor/blocks/AdvancedSettings.vue` — add Effects section
- `resources/js/components/BlockEditor/blocks/LinkSettings.vue` — add `TypographyControl` to Style tab

### Modified (Style tab added)
- `FilterLinkSettings.vue`
- `NavigationSettings.vue`
- `SearchSettings.vue`
- `PostTitleSettings.vue`
- `PostMetaSettings.vue`
- `PostAuthorSettings.vue`
- `PostTaxonomySettings.vue`
- `ArchiveTitleSettings.vue`
