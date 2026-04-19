# Background Consolidation Design

**Date:** 2026-04-10
**Status:** Approved

## Problem

Background settings are duplicated across the block editor:
- `ParagraphSettings` and `QuoteSettings` have a `bgColor` HEX picker on their Style tab
- `ContainerSettings`, `SectionSettings`, and `CtaSettings` have full background (color + gradient + image) on their Style tab
- `AdvancedSettings` also has background (color + gradient only, no image) under the Advanced tab

Result: every block type exposes background in two different places with inconsistent options.

## Goal

Single source of truth — background lives **only in the Advanced tab** for every block type, with full Color + Gradient + Image support.

## Approach: Option A — BlockRenderer owns all background rendering

All background state is written to `block.data` under the `advBg*` namespace. `blockWrapperStyle()` in `BlockRenderer.vue` reads and applies them as inline styles on the outer wrapper div. Block components (`SectionBlock`, `ContainerBlock`, `CtaBlock`) no longer manage background themselves.

## Data Model

```js
block.data.advBgType     // 'none' | 'color' | 'gradient' | 'image'
block.data.advBgColor    // '#rrggbb'
block.data.advBgGradient // { from, to, direction }
block.data.advBgImage    // { url, position, size, parallax }
```

`advBgImage` shape matches what Container/Section previously used internally:
- `url` — absolute URL (from MediaPicker or text input)
- `position` — `'center' | 'top' | 'bottom' | 'left' | 'right'`
- `size` — `'cover' | 'contain' | 'auto'`
- `parallax` — boolean (fixed attachment)

Old keys (`bgType`, `bgColor`, `bgImage`, `bgGradient`) on Container/Section/CTA become inert — no migration needed since DB was freshly seeded.

## AdvancedSettings UI

Add `image` as a 4th option in the background type toggle:

```
[ None ] [ Color ] [ Gradient ] [ Image ]
```

When `image` is selected, show:
- **Library button** — opens `<MediaPicker :dark="true">`, writes `advBgImage.url` on select
- **URL fallback** — text input for external image URLs
- **Position** — button group: Center / Top / Bottom / Left / Right
- **Size** — button group: Cover / Contain / Auto
- **Parallax** — checkbox for `background-attachment: fixed`

## BlockRenderer Rendering

`blockWrapperStyle()` already handles `color` and `gradient`. Add `image` branch:

```js
} else if (bgType === 'image' && block.data?.advBgImage?.url) {
  const img = block.data.advBgImage
  style.backgroundImage      = `url('${img.url}')`
  style.backgroundPosition   = img.position ?? 'center'
  style.backgroundSize       = img.size ?? 'cover'
  style.backgroundRepeat     = 'no-repeat'
  if (img.parallax) style.backgroundAttachment = 'fixed'
}
```

## Files Changed

| File | Change |
|------|--------|
| `resources/js/Components/BlockEditor/blocks/AdvancedSettings.vue` | Add `image` type + image controls (MediaPicker, position, size, parallax) |
| `resources/js/components/BlockRenderer.vue` | Add `image` branch in `blockWrapperStyle()` |
| `resources/js/Components/BlockEditor/blocks/ParagraphSettings.vue` | Remove `bgColor` field from Style tab |
| `resources/js/Components/BlockEditor/blocks/QuoteSettings.vue` | Remove `bgColor` field from Style tab |
| `resources/js/Components/BlockEditor/blocks/ContainerSettings.vue` | Remove entire background section from Style tab |
| `resources/js/Components/BlockEditor/blocks/SectionSettings.vue` | Remove entire background section from Style tab |
| `resources/js/Components/BlockEditor/blocks/CtaSettings.vue` | Remove background section (keep button bg controls) |
| `resources/js/Components/Blocks/SectionBlock.vue` | Remove `bgType/bgColor/bgImage/bgGradient` inline style logic |
| `resources/js/Components/Blocks/ContainerBlock.vue` | Remove same |
| `resources/js/Components/Blocks/CtaBlock.vue` | Remove same |
