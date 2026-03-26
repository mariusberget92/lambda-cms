# Dynamic Field Bindings — Design

**Date:** 2026-03-26
**Status:** Approved

## Problem

Block editor fields (Heading text, Image URL, etc.) can be typed manually or bound to a dynamic data source — but the current system has two gaps:

1. **Limited source coverage** — binding only works inside a Loop block. Post context (available on single-post templates and posts using the block editor) is not exposed as a binding source.
2. **Incomplete block coverage** — Quote, Video, HTML blocks, and several CTA fields have no Bind button and their renderers read `block.data` directly, ignoring any binding that might be stored.

The goal is WordPress-style field bindings: a Heading block shows a dropdown of all available dynamic fields ("Post Title", "Post Excerpt", "Category Name", …) regardless of whether the block is inside a loop or on a post template.

---

## Approved Design

### 1. Data Model — Prefixed binding values

No structural change to the block schema. `block.bindings` remains a flat map of `fieldName → bindingValue`. The value now carries a source prefix:

| Binding value | Resolves from |
|---|---|
| `loop:title` | Current loop item's `title` field |
| `loop:author_name` | Current loop item's `author_name` field |
| `post:title` | `postContext.title` |
| `post:author_name` | `postContext.author.name` (flattened) |
| `title` *(legacy)* | Treated as `loop:title` — backward compat preserved |

### 2. Available Fields — `loopSources.js`

`SOURCE_FIELDS` changes from arrays of plain strings to arrays of `{ value, label }` objects. Labels are human-readable and include the source type for clarity.

**Posts loop fields:**

| value | label |
|---|---|
| `title` | Post Title |
| `slug` | Post Slug |
| `excerpt` | Excerpt |
| `body` | Body Content |
| `featured` | Is Featured |
| `published_at` | Published Date |
| `author_name` | Author |
| `featured_image_url` | Featured Image |
| `url` | Post URL |

*(Categories, tags, and pages follow the same pattern with source-appropriate labels.)*

New named export **`POST_CONTEXT_FIELDS`** — fields available from `postContext`:

| value | label |
|---|---|
| `post:title` | Post Title |
| `post:slug` | Post Slug |
| `post:excerpt` | Excerpt |
| `post:body` | Body Content |
| `post:published_at` | Published Date |
| `post:author_name` | Author Name |
| `post:author_avatar_url` | Author Avatar |
| `post:featured_image_url` | Featured Image |
| `post:url` | Post URL |

Note: values are pre-prefixed (`post:*`) so consumers can use them directly as binding values.

### 3. Passing Context Fields to the Editor

`BlockEditor.vue` receives a new optional prop **`contextFields`** — an array of `{ value, label }` for whatever context is available at the current edit surface. Page components pass this in when appropriate:

- **PostEditor** (post with `use_block_editor = true`) → passes `POST_CONTEXT_FIELDS`
- **TemplateEditor** (single-post template type) → passes `POST_CONTEXT_FIELDS`
- **PageEditor** → passes `[]` (no post context)
- Other template types → passes the relevant context fields when defined

`BlockEditor` merges `contextFields` + loop-derived fields into a single **`availableFields`** computed and passes it down through `BlockLayers` to individual settings components (replacing the current `loopFields` prop, which is renamed).

### 4. Runtime Resolution — `useFieldBinding.js`

```
binding = 'post:title'        →  inject('postContext')  → context.title
binding = 'post:author_name'  →  inject('postContext')  → context.author?.name
binding = 'loop:slug'         →  inject('loopItem')     → item.slug
binding = 'title'  (legacy)   →  inject('loopItem')     → item.title
```

A `resolvePostField(context, key)` helper handles the two nested paths:
- `author_name` → `context.author?.name`
- `author_avatar_url` → `context.author?.avatar_url`

All other keys are direct property access on the context object.

No changes to `LoopItemProvider` or `TemplatePage` — they already provide `loopItem` and `postContext` correctly.

### 5. Editor UI — `DynamicField.vue`

Prop `loopFields` renamed to `availableFields`. Options rendered as a grouped SelectBox:

```
── Current Post ──────────────────
  Post Title
  Post Excerpt
  Post URL
  Author Name
  …
── Loop Items (Posts) ────────────
  Post Title
  Post Slug
  Excerpt
  …
```

Groups only render when they have entries. If `availableFields` is empty the Bind button stays hidden (existing behaviour unchanged).

### 6. Block Coverage Additions

**Settings files** — gain DynamicField wrapping:

| File | New bindable fields |
|---|---|
| `QuoteSettings.vue` | `text`, `attribution` |
| `VideoSettings.vue` | `url` |
| `HtmlSettings.vue` | `content` |
| `CtaSettings.vue` | `text`, `button_label` *(headline + button_url already done)* |

**Block renderers** — switch to `useFieldBinding`:

| File | Fields |
|---|---|
| `QuoteBlock.vue` | `text`, `attribution` |
| `VideoBlock.vue` | `url` (resolved value fed into existing embed URL computed) |
| `HtmlBlock.vue` | `content` |
| `CtaBlock.vue` | `text`, `button_label` |

---

## Files Changed

| File | Change |
|---|---|
| `resources/js/lib/loopSources.js` | `SOURCE_FIELDS` → `{ value, label }` objects; add `POST_CONTEXT_FIELDS` |
| `resources/js/composables/useLoopBinding.js` | Parse prefix; resolve from correct provider |
| `resources/js/Components/BlockEditor/BlockEditor.vue` | Add `contextFields` prop; compute `availableFields`; pass down |
| `resources/js/Components/BlockEditor/BlockLayers.vue` | Rename `loopFields` → `availableFields`; pass through |
| `resources/js/Components/BlockEditor/blocks/DynamicField.vue` | Rename prop; render grouped options |
| `resources/js/Components/BlockEditor/blocks/HeadingSettings.vue` | Update prop name |
| `resources/js/Components/BlockEditor/blocks/ParagraphSettings.vue` | Update prop name |
| `resources/js/Components/BlockEditor/blocks/ImageSettings.vue` | Update prop name |
| `resources/js/Components/BlockEditor/blocks/CtaSettings.vue` | Update prop name; add `text`, `button_label` |
| `resources/js/Components/BlockEditor/blocks/QuoteSettings.vue` | Add DynamicField for `text`, `attribution` |
| `resources/js/Components/BlockEditor/blocks/VideoSettings.vue` | Add DynamicField for `url` |
| `resources/js/Components/BlockEditor/blocks/HtmlSettings.vue` | Add DynamicField for `content` |
| `resources/js/Components/Blocks/QuoteBlock.vue` | `useFieldBinding` for `text`, `attribution` |
| `resources/js/Components/Blocks/VideoBlock.vue` | `useFieldBinding` for `url` |
| `resources/js/Components/Blocks/HtmlBlock.vue` | `useFieldBinding` for `content` |
| `resources/js/Components/Blocks/CtaBlock.vue` | `useFieldBinding` for `text`, `button_label` |
| `resources/js/Pages/Posts/Edit.vue` (or equivalent) | Pass `contextFields` to BlockEditor |
| `resources/js/Pages/Templates/Edit.vue` | Pass `contextFields` to BlockEditor for single-post templates |
