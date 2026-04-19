# New Block Types — Design Document

**Date:** 2026-03-29
**Scope:** 5 new block types + icon settings enhancement on existing blocks

---

## Overview

Add the following to the block editor:

| Block | Type key | Group | Notes |
|---|---|---|---|
| Link | `link` | Interactive | Wrapper block, renders as `<a>` |
| Accordion | `accordion` | Content | Children-based, `accordion-item` child type |
| Tabs | `tabs` | Content | Children-based, `tab-item` child type |
| Embed | `embed` | Content | oEmbed/iframe via URL pattern matching |
| Pagination | `pagination` | Interactive | Pairs with Loop via URL params |

**Enhancement:** Icon settings (picker / size / position / color) added to `link` block and existing `heading` block via a shared `IconSettings.vue` component.

---

## 1. Link Block

### Purpose
A wrapper block that renders its children inside an `<a>` tag. URL can be static or dynamically bound (e.g. `loop:url`, `loop:permalink`). Replaces the need for hardcoded social share buttons — users compose a Link block containing an icon.

### Data shape
```json
{
  "type": "link",
  "data": {
    "url": "https://...",
    "target": "_self",
    "rel": "",
    "icon": {
      "name": "",
      "position": "left",
      "size": "md",
      "color": "inherit"
    }
  },
  "bindings": {
    "url": "loop:permalink"
  },
  "children": []
}
```

### Settings

**Content tab:**
- URL — text input wrapped in `DynamicField` (supports `loop:*` bindings)
- Open in new tab — toggle (`target: _blank`)
- Rel — select (none / nofollow / noopener / sponsored)

**Style tab (shared `IconSettings.vue`):**
- Icon name — searchable Lucide icon picker
- Icon position — Left / Right
- Icon size — XS (12px) / SM (16px) / MD (20px) / LG (24px) / XL (32px)
- Icon color — inherit (default) / custom color picker

### Renderer
```html
<a :href="resolvedUrl" :target="data.target" :rel="data.rel" class="...">
  <component v-if="icon && position === 'left'" :is="iconComponent" :style="iconStyle" />
  <BlockRenderer :blocks="block.children" />
  <component v-if="icon && position === 'right'" :is="iconComponent" :style="iconStyle" />
</a>
```

### Canvas wireframe
Rendered as a subtle border card labelled "Link" with a chain-link icon, children nested inside.

---

## 2. Icon Settings (Shared Enhancement)

### Purpose
Reusable settings panel used by **Link** (Style tab) and **Heading** (Style tab). Encapsulates icon picker, position, size, and color in a single `IconSettings.vue` component.

### Component interface
```vue
<IconSettings :block="block" @update="$emit('update', $event)" />
```

### Icon picker
Searchable `<select>` (or `SelectBox`) populated with a curated list of common Lucide icon names. Not all ~1500 icons — a practical subset of ~80 commonly useful ones (arrows, social, UI, shapes).

### Size map
| Key | px |
|---|---|
| xs | 12 |
| sm | 16 |
| md | 20 |
| lg | 24 |
| xl | 32 |

### Heading integration
`HeadingSettings.vue` Style tab gains the `<IconSettings>` panel. The `HeadingBlock.vue` renderer injects the icon before or after the heading text using the same position/size/color logic.

---

## 3. Accordion Block

### Purpose
Collapsible content sections. Children-based: each item is an `accordion-item` child block with its own nested children.

### Child block: `accordion-item`
- Hidden from block palette (`hiddenFromPalette: true`)
- Settings — Content tab: `title` field (text, supports `DynamicField` binding)
- Children: any blocks (the expanded body content)

### Data shape (parent)
```json
{
  "type": "accordion",
  "data": {
    "defaultState": "first-open",
    "borderStyle": "bordered"
  },
  "children": [
    {
      "type": "accordion-item",
      "data": { "title": "Item 1" },
      "children": []
    }
  ]
}
```

### Default children
When dropped onto canvas, auto-populates with **3** default `accordion-item` children.

### Settings

**Style tab (parent):**
- Default state — First open / All collapsed / All open
- Border style — Bordered / Borderless / Separated

### Canvas wireframe
Each item shown as a card with title text + chevron icon. Collapsed by default in wireframe. **"+ Add Item"** button appended below the last item — clicking it dispatches an `update` event that pushes a new `accordion-item` child.

### Renderer
```html
<div class="accordion ...">
  <div v-for="(item, i) in block.children" :key="item.id">
    <button @click="toggle(i)">{{ item.data.title }}</button>
    <div v-show="openIndex === i">
      <BlockRenderer :blocks="item.children" />
    </div>
  </div>
</div>
```
`openIndex` is a `ref` initialised from `defaultState` setting.

---

## 4. Tabs Block

### Purpose
Tabbed content panels. Children-based: each tab is a `tab-item` child block.

### Child block: `tab-item`
- Hidden from block palette
- Settings — Content tab: `label` field (text, the tab bar label)
- Children: any blocks (the tab panel body)

### Data shape (parent)
```json
{
  "type": "tabs",
  "data": {
    "alignment": "left",
    "tabStyle": "underline"
  },
  "children": [
    {
      "type": "tab-item",
      "data": { "label": "Tab 1" },
      "children": []
    }
  ]
}
```

### Default children
Auto-populates with **2** default `tab-item` children when dropped.

### Settings

**Style tab (parent):**
- Alignment — Left / Center / Right
- Tab style — Underline / Pills / Buttons

### Canvas wireframe
Mock tab bar showing tab labels as buttons (first tab active), content area below. **"+ Add Tab"** button at the end of the tab bar.

### Renderer
```html
<div class="tabs">
  <div class="tab-bar">
    <button v-for="(tab, i) in block.children" @click="activeTab = i"
      :class="{ active: activeTab === i }">
      {{ tab.data.label }}
    </button>
  </div>
  <div v-for="(tab, i) in block.children" v-show="activeTab === i">
    <BlockRenderer :blocks="tab.children" />
  </div>
</div>
```

---

## 5. Embed Block

### Purpose
Paste any URL and render the appropriate embed (YouTube, Vimeo, Google Maps, Twitter/X, or generic iframe).

### Data shape
```json
{
  "type": "embed",
  "data": {
    "url": "https://youtube.com/watch?v=abc123",
    "caption": "",
    "aspectRatio": "16/9",
    "maxWidth": ""
  }
}
```

### Settings

**Content tab:**
- URL — text input
- Caption — text input (optional)

**Style tab:**
- Aspect ratio — 16:9 / 4:3 / 1:1 / 21:9
- Max width — text input (e.g. `800px`, `100%`)

### Provider detection (client-side)

| Provider | URL pattern | Embed URL |
|---|---|---|
| YouTube | `youtube.com/watch?v=ID` or `youtu.be/ID` | `https://www.youtube.com/embed/ID` |
| Vimeo | `vimeo.com/ID` | `https://player.vimeo.com/video/ID` |
| Google Maps | `maps.google.com` or `goo.gl/maps` | Direct iframe src |
| Twitter/X | `twitter.com/*` or `x.com/*` | `<blockquote>` + Twitter widget script |
| Fallback | Any other URL | `<iframe :src="url">` |

### Renderer
```html
<figure :style="{ maxWidth: data.maxWidth }">
  <div :style="{ aspectRatio: data.aspectRatio }">
    <iframe v-if="!isTwitter" :src="embedUrl" allowfullscreen />
    <blockquote v-else class="twitter-tweet">...</blockquote>
  </div>
  <figcaption v-if="data.caption">{{ data.caption }}</figcaption>
</figure>
```

### Canvas wireframe
Provider logo/icon + truncated URL in a bordered aspect-ratio box. Caption below if set.

---

## 6. Pagination Block

### Purpose
Adds prev/next or numbered page controls that pair with a Loop block via URL params. No direct block-to-block reference needed — they communicate through the URL.

### How it connects to Loop
The Loop block already supports URL param filters. Pagination writes `?page=N` to the URL. The Loop's `hasUrlParamFilters` watcher catches the change and re-fetches with the correct `offset` (`(page - 1) * limit`).

### Data shape
```json
{
  "type": "pagination",
  "data": {
    "pageParam": "page",
    "style": "numbered",
    "prevLabel": "← Previous",
    "nextLabel": "Next →",
    "alignment": "center",
    "buttonStyle": "outline"
  }
}
```

### Settings

**Content tab:**
- Page param name — text input (default: `page`)
- Style — Prev/Next only / Numbered
- Prev label / Next label — text inputs

**Style tab:**
- Alignment — Left / Center / Right
- Button style — Outline / Ghost / Solid

### Total count source
The Loop's `/api/v1/query` response already returns `total`, `limit`, `offset`. The Pagination block reads these from the Loop's resolved data (passed via Inertia shared props or a `provide/inject` from the nearest Loop block).

### Renderer
Reads `?page` from URL, computes current page. Uses Inertia `router.visit()` to update the URL param on click (preserves other params, triggers Loop re-fetch without full page reload).

### Canvas wireframe
Static mock: `← Previous  1  2  3  …  Next →` centered in a row.

---

## Architecture Notes

### Hidden palette blocks
`accordion-item` and `tab-item` get `hiddenFromPalette: true` in the `ALL_TYPES` array in `BlockTypePanel.vue`. The palette filters these out. They still appear in the Layers panel.

### LABELS map
Both child types need entries in the `LABELS` constant in `BlockCanvas.vue` and `BlockLayers.vue` so the wireframe headers display correctly.

### BlockRenderer map
All 5 new block types + 2 child types need entries in `BlockRenderer.vue`'s component map.

### STYLE_BLOCKS
`link`, `accordion`, `tabs`, `embed`, `pagination` added to `STYLE_BLOCKS` in `BlockLayers.vue` (all have Style tab settings).

### Default tab
No new `DEFAULT_TAB` overrides needed — all new blocks default to `'content'`.
