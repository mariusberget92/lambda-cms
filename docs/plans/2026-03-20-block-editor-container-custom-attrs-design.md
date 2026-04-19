# Block Editor: Container Block, Custom Attributes & Font Selection — Design

## Goal

Extend the block editor with: (1) a Container block supporting true recursive nesting with configurable flex layout and width, (2) per-block custom CSS (scoped), custom classes, and custom ID, and (3) per-block font selection via Google Fonts (defaulting to the site font).

---

## Data Structure

Every block gains four new optional top-level fields alongside `id`, `type`, and `data`:

```js
{
  id: "uuid",
  type: "paragraph",       // or "container", etc.
  data: { /* block-specific */ },

  // NEW — shared across all block types
  customId: "",            // HTML id attribute applied to the block wrapper
  customClasses: "",       // space-separated CSS class string
  customCss: "",           // raw CSS rules; scoped to #block-{id} at render time
  fontFamily: "",          // Google Font name e.g. "Roboto" — empty = site default

  // Container blocks only
  children: []             // recursive array of blocks (same shape)
}
```

Container block-specific layout settings live in `data`:

```js
{
  type: "container",
  data: {
    direction: "row",      // "row" | "column"
    wrap: true,
    gap: 4,                // Tailwind gap unit 0–16
    justify: "start",      // "start" | "center" | "end" | "between" | "around"
    align: "start",        // "start" | "center" | "end" | "stretch"
    maxWidth: "full",      // "full" | "prose" | "sm" | "md" | "lg" | "xl" | "2xl"
    padding: 4,
  },
  children: [ /* nested blocks */ ]
}
```

---

## Block Editor Components

### BlockTypePanel

Add a "Container" entry to the palette using the `LayoutTemplate` Lucide icon.

### BlockCanvas (recursive)

`BlockCanvas.vue` stays unchanged for the root level. A new `ContainerBlock.vue` (editor-side) renders inside the canvas for container-type blocks. It contains its own nested `<BlockCanvas v-model="block.children" />` component, keeping the same `vue-draggable-plus` group name `"canvas"` so blocks can be freely dragged between containers and the root canvas. Selection events bubble up to the root `BlockEditor`.

### BlockLayers (recursive tree)

A new `LayerItem.vue` sub-component renders a single layer row (drag handle, type icon, label, delete button). If `block.type === 'container'` and `block.children.length`, it renders an indented `<ul class="pl-4">` of child `LayerItem`s recursively beneath it. Selection, reorder, and delete all emit to the root `BlockEditor` which owns the full nested block tree.

### Block Settings Panel — Advanced Tab

All block types get a new **Advanced** tab in the settings panel (below the existing type-specific settings) containing:

- **Custom ID** — text input → `block.customId`
- **Custom classes** — text input → `block.customClasses`
- **Custom CSS** — `<textarea>` (4 rows), placeholder: `color: red;\nfont-size: 1.2em;` → `block.customCss`
- **Font family** — searchable `<select>` or combobox of ~30 curated Google Fonts + "Site default" (empty string) → `block.fontFamily`

Container blocks get a **Layout** tab as their primary settings with controls for: direction, wrap, gap, justify, align, maxWidth, padding.

---

## Public Renderer

### BlockRenderer.vue

Each block is wrapped in:

```html
<div
  :id="block.customId || `block-${block.id}`"
  :class="block.customClasses"
  :style="block.fontFamily ? `font-family: '${block.fontFamily}', sans-serif` : undefined"
>
  <!-- block content -->
  <style v-if="block.customCss">#block-{{ block.id }} { {{ block.customCss }} }</style>
</div>
```

A `scopeCss(id, css)` utility wraps the raw CSS string: `#block-{id} { ...css }`.

### Font Loading

When `block.fontFamily` is non-empty, `BlockRenderer` injects a `<link>` tag into `<head>` pointing to `https://fonts.googleapis.com/css2?family=FontName:wght@400;600;700&display=swap`. A set of already-loaded fonts is tracked to prevent duplicate `<link>` tags.

### ContainerBlock.vue (public)

Renders a `<div>` with Tailwind flex classes derived from `data` (direction, wrap, gap, justify, align, maxWidth, padding), then loops `block.children` through `<BlockRenderer>` recursively.

### PublicPageController

`resolveBlocks()` becomes recursive — it walks into `children` arrays to resolve any nested component blocks before passing to Inertia.

---

## Curated Google Fonts List (~30)

Inter, Roboto, Open Sans, Lato, Montserrat, Poppins, Raleway, Nunito, Source Sans 3, Merriweather, Playfair Display, Lora, PT Serif, Libre Baskerville, EB Garamond, Oswald, Bebas Neue, DM Sans, DM Serif Display, Figtree, Plus Jakarta Sans, Outfit, Manrope, Sora, Space Grotesk, JetBrains Mono, Fira Code, Source Code Pro.

---

## Architecture Summary

- **Approach:** Recursive `children` array on Container blocks (Approach A)
- **Custom CSS scoping:** Scoped to `#block-{id}` — user writes plain rules, wrapper added automatically
- **Font loading:** Google Fonts via dynamic `<link>` injection, ~30 curated fonts, deduped by tracking loaded families
- **No backend changes** beyond `PublicPageController` recursion — all new fields serialize into the existing `blocks` JSON column
