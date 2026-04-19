# Spec: Block Editor & Custom Pages

**Date:** 2026-03-13
**Status:** Approved

---

## Overview

Add a visual block editor to Lambda CMS that lets authors compose post bodies and custom site pages from pre-built content blocks. The block editor coexists with the existing Tiptap rich-text editor — authors choose per post whether to use blocks or Tiptap. Custom pages (About, Contact, Landing pages) are always block-based and live at top-level slugs (e.g. `/about`).

---

## Scope

### In scope
- Block editor UI integrated into the post create/edit form (opt-in via tab switcher)
- New "Pages" admin section (admin-only) with block editor always active
- 10 initial block types: Paragraph, Heading, Image, Blockquote, Code, Gallery, Video, Divider, CTA, Raw HTML
- Public rendering of block-based posts and pages via `BlockRenderer.vue`
- Public catch-all route for custom pages (`/{slug}`)

### Out of scope
- Theme/colour customisation UI (separate feature)
- Block versioning or revision history
- Custom block type creation by admins
- User-created block templates or saved block groups
- Reordering blocks via keyboard accessibility (nice-to-have, not v1)

---

## Data Architecture

### Posts table changes
Two new columns added to the existing `posts` table:

| Column | Type | Default | Notes |
|---|---|---|---|
| `use_block_editor` | `boolean` | `false` | Toggles content mode per post |
| `blocks` | `json` (nullable) | `null` | Block array when block editor active |

Existing `body` column is preserved for Tiptap content. No existing posts are modified.

The `Post` model gains:
- `'blocks' => 'array'` cast
- `use_block_editor` fillable

### New `pages` table

```
id              bigint PK
title           string (required)
slug            string unique (required, auto-generated from title)
status          enum: published | draft (default: draft)
blocks          json (nullable)
meta_title      string nullable
meta_description text nullable
meta_keywords   string nullable
user_id         bigint FK → users.id (creator)
timestamps
```

The `Page` model:
- Casts `blocks` to array
- Has `generateSlug()` method (same pattern as Post)
- `belongsTo(User::class)` (author/creator)
- Scopes: `published()`, `draft()`

### Block JSON structure

Blocks are stored as an ordered JSON array. Each block is an object with three keys:

```json
[
  {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "type": "paragraph",
    "data": { "content": "<p>Rich text HTML from Tiptap</p>" }
  },
  {
    "id": "550e8400-e29b-41d4-a716-446655440001",
    "type": "heading",
    "data": { "level": 2, "text": "Section title" }
  },
  {
    "id": "550e8400-e29b-41d4-a716-446655440002",
    "type": "image",
    "data": { "media_id": 5, "url": "https://...", "caption": "Optional caption", "alt": "Alt text" }
  }
]
```

- `id` — UUID generated client-side on block creation; used as Vue `:key`
- `type` — one of the 10 block type strings (see Block Types section)
- `data` — type-specific object (schema defined per type below)

### Block data schemas

| Type | Data fields |
|---|---|
| `paragraph` | `content` (HTML string from Tiptap) |
| `heading` | `level` (int 1–6), `text` (plain string) |
| `image` | `media_id` (int), `url` (string), `caption` (string), `alt` (string) |
| `quote` | `text` (string), `attribution` (string, optional) |
| `code` | `code` (string), `language` (string, e.g. `"javascript"`) |
| `gallery` | `items` (array of `{media_id, url, alt}`) |
| `video` | `url` (YouTube or Vimeo URL string), `caption` (string, optional) |
| `divider` | `style` (enum: `line` \| `dots` \| `space`) |
| `cta` | `headline` (string), `text` (string), `button_label` (string), `button_url` (string) |
| `html` | `content` (raw HTML string — admin-only) |

---

## Block Editor UI (Admin)

### Interaction model: Side panel

The block editor uses a three-panel layout:

- **Left panel (160px):** Ordered list of blocks (title + type label). Draggable via `vuedraggable`. "＋ Add block" button at the bottom opens a block type picker popover (grid of 10 type cards with icons).
- **Centre panel (flex-1):** Live read-only preview of all blocks rendered in sequence, scrollable.
- **Right panel (240px):** Settings panel for the currently selected block. Content switches based on `selectedBlock.type`. Empty state if no block selected.

### `BlockEditor.vue`

Top-level component. Props: `modelValue` (blocks array). Emits: `update:modelValue`.

Internal state:
- `selectedBlockId` — UUID of currently selected block (null = none)
- `localBlocks` — internal working copy of the blocks array

**State sync contract:**
- On mount and whenever `modelValue` changes externally (e.g. parent clears blocks on tab switch), a `watch(props.modelValue, ..., { deep: true })` updates `localBlocks` to match.
- Every mutation (`addBlock`, `removeBlock`, `moveBlock`, or a settings change emitted from a child) updates `localBlocks` and immediately emits `update:modelValue` with the new array. The parent never mutates blocks directly — it only writes down via prop, reads up via emit.
- `localBlocks` is never re-initialised mid-session except when the parent changes `modelValue` from outside (tab switch clear path).

Methods:
- `addBlock(type)` — pushes a new block with UUID + default data for the type
- `removeBlock(id)` — splices block from array (with confirmation if block has content)
- `selectBlock(id)` — sets selectedBlockId
- `moveBlock(oldIndex, newIndex)` — used by vuedraggable's `@change` event

### Child components

```
resources/js/Components/BlockEditor/
├── BlockEditor.vue            Main three-panel shell
├── BlockList.vue              Left panel — draggable list + add button
├── BlockPreview.vue           Centre panel — renders BlockRenderer in preview mode
├── BlockSettings.vue          Right panel — dynamic component switcher
└── blocks/
    ├── ParagraphSettings.vue  Tiptap instance (reuses existing TiptapEditor.vue)
    ├── HeadingSettings.vue    Level select (H1–H6) + text input
    ├── ImageSettings.vue      MediaPicker + caption + alt fields
    ├── QuoteSettings.vue      Textarea for quote text + attribution input
    ├── CodeSettings.vue       Textarea + language selector (plain select, no syntax in editor)
    ├── GallerySettings.vue    Multi-select MediaPicker
    ├── VideoSettings.vue      URL text input + embedded preview (oembed via iframe)
    ├── DividerSettings.vue    Three-option style picker (line / dots / space)
    ├── CtaSettings.vue        Headline + text + button label + button URL inputs
    └── HtmlSettings.vue       Raw textarea (admin-only; role-guarded at two points: the `html` option is hidden in the block-type picker for non-admins, AND `BlockSettings.vue` renders a disabled placeholder instead of `HtmlSettings.vue` if a `html` block is somehow present and the user is not an administrator)
```

### Post editor integration: Tab switcher

In `Posts/Create.vue` and `Posts/Edit.vue`, the content section has two tabs:

- **"Rich Text"** — shows existing `TiptapEditor`
- **"Block Editor"** — shows `BlockEditor`

The active tab is driven by the `use_block_editor` boolean (loaded from the post, saved on submit). When switching tabs while the other mode has content, a confirmation dialog warns that content in the other mode will be cleared if the user saves. The two modes are mutually exclusive in storage — only the active mode's content is submitted.

---

## Pages Admin

### Access
Admin-only (`administrator` role). Editors cannot access.

### Sidebar placement
New "Pages" entry in the sidebar, under Content, between Posts and Categories.

### Routes (admin)

```
GET    /pages              PageController@index  → Pages/Index.vue
GET    /pages/create       PageController@create → Pages/Create.vue
POST   /pages              PageController@store
GET    /pages/{page}/edit  PageController@edit   → Pages/Edit.vue
PUT    /pages/{page}       PageController@update
DELETE /pages/{page}       PageController@destroy
```

All routes protected by `auth`, `verified`, and a `role:administrator` middleware.

### Pages/Index.vue

Uses the existing `DataTable` component. Columns: Title, Slug, Status badge, Created date, Actions (Edit / Delete). Same patterns as Posts/Index, including the delete confirmation dialog before a page is removed.

### Pages/Create.vue and Pages/Edit.vue

Form fields:
- Title (text input — auto-generates slug)
- Slug (editable text input)
- Status (Published / Draft selector)
- Block editor (always active — no tab switcher, pages are always block-based)
- SEO accordion: meta title, meta description, meta keywords

---

## Frontend Rendering

### `BlockRenderer.vue`

A presentational component that receives a `blocks` array prop and renders each block via a component map. No internal state.

```
resources/js/Components/Blocks/
├── ParagraphBlock.vue    v-html of content (safe: admin-authored)
├── HeadingBlock.vue      Dynamic :is="'h' + block.data.level"
├── ImageBlock.vue        <img> with caption, links to full size
├── QuoteBlock.vue        <blockquote> with attribution
├── CodeBlock.vue         <pre><code> with language class (highlight.js if available)
├── GalleryBlock.vue      CSS grid of images, click-to-enlarge
├── VideoBlock.vue        Responsive iframe wrapper (YouTube/Vimeo embed)
├── DividerBlock.vue      <hr> or styled div based on style
├── CtaBlock.vue          Styled card with headline, text, button link
└── HtmlBlock.vue         v-html (admin-authored, safe)
```

### Blog/Show.vue changes

When `post.use_block_editor` is `true`, replace the `v-html` body div with `<BlockRenderer :blocks="post.blocks" />`. Both paths remain in the template with a `v-if`/`v-else`.

`BlogController::show()` (the public blog handler at `GET /blog/{slug}`) must be updated to pass `use_block_editor` and `blocks` in the Inertia response for the post. Note: this is `BlogController`, not `PostController` — the admin `PostController` only handles the edit/create forms, not the public blog view.

The existing `BlogController::show()` builds an explicit associative array for the post. Add `use_block_editor` and `blocks` to that array at the same level as `body`:
```php
'use_block_editor' => (bool) $post->use_block_editor,
'blocks'           => $post->blocks, // null or array from JSON cast
```

### Public page rendering

A new `PublicPageController` (separate from the admin `PageController`) serves custom pages:

```php
// PublicPageController::show($slug)
// Finds published Page by slug, returns 404 if not found
// Renders Blog/Page.vue with { page, seo } props
```

`Blog/Page.vue` — a new page component using `BlogLayout`, rendering the page title, SEO head, and `<BlockRenderer :blocks="page.blocks" />`.

### Catch-all route

Registered **last inside** the `Route::middleware('installed')->group(...)` block in `web.php`, immediately before its closing brace. It must stay inside that group so the `EnsureInstalled` middleware still runs — placing it outside would serve page responses before the CMS is set up:

```php
Route::get('/{slug}', [PublicPageController::class, 'show'])
    ->where('slug', '^(?!login|logout|dashboard|blog|feed|sitemap\.xml|posts|categories|tags|users|profile|settings|media|comments|pages|calendar|password|register|verify).*$');
```

Returns 404 if no published `Page` matches the slug. **Important:** The exclusion list in the regex must be kept in sync with all named routes in `web.php`. Any future top-level route additions must also be added to the exclusion pattern.

---

## Error Handling

- **Block add with empty state:** New blocks are created with safe empty defaults (empty string for text, null for media). Empty blocks (where all data fields are empty/null) are filtered out **client-side** before form submission — the `blocks` array passed to the form's submit handler excludes them. No server-side deep-structure validation is applied (admin-only input, trusted).
- **Media deleted after block saved:** `ImageBlock.vue` and `GalleryBlock.vue` render with a fallback placeholder if `url` is empty or 404.
- **Video URL invalid:** `VideoSettings.vue` validates YouTube/Vimeo URL format client-side. Invalid URLs show an error state in the preview; the block is still saveable.
- **Slug collision on Pages:** Server-side unique validation on `pages.slug`. Edit form shows validation error if slug is taken.
- **Server-side block validation:** On `POST /pages` and `PUT /pages/{page}`, and on the block-editor path of `POST /posts` / `PUT /posts/{post}`: `use_block_editor` must be boolean; `blocks` must be a nullable JSON array. Individual block structure is not deep-validated server-side (admin-only input, trusted). Validation rules: `'use_block_editor' => 'boolean'`, `'blocks' => 'nullable|array'`.
- **Tab switch with content:** Client-side confirmation modal before switching modes. If user confirms, the previous mode's content is cleared from the form (not from the DB until saved).

---

## Testing

- **PHPUnit (Feature tests):**
  - `PageTest.php` — full CRUD: index requires admin, create/update stores valid blocks JSON, delete removes page, non-admin gets 403. Uses `PageFactory` for seeding test data.
  - `PublicPageTest.php` — published page is accessible at `/{slug}`, draft returns 404, unknown slug returns 404. Uses `PageFactory`.
  - `PostBlockTest.php` — post with `use_block_editor=true` returns `blocks` in Inertia response; `use_block_editor=false` returns `body` HTML.
- **`PageFactory`** is used by the above feature tests to create Page records. It is not intended for seeders in v1.
- **No bulk-delete for Pages in v1** — Pages/Index has single-row delete only (no bulk-action endpoint). Expected page counts are small.
- **No Dusk/E2E tests** for the block editor UI itself (drag-and-drop testing is out of scope for v1)

---

## Dependencies

- **`vue-draggable-plus`** — drag-to-reorder in BlockList. Must be installed before block editor component work begins: `npm install vue-draggable-plus`. Vue 3 compatible, actively maintained.
- **No new backend packages required** — JSON column is native SQLite/MySQL, no separate package needed.
- **Tiptap** — already installed, reused as-is for ParagraphSettings.vue.
- **MediaPicker** — already exists as `resources/js/Components/MediaPicker.vue`, reused as-is.

> **Component path casing:** The existing project uses `resources/js/Components/` (uppercase `C`). All new component directories (`BlockEditor/`, `Blocks/`) follow this same convention.

---

## File Summary

### New files
```
app/Http/Controllers/PageController.php        (admin CRUD)
app/Http/Controllers/PublicPageController.php  (public show)
app/Models/Page.php
database/migrations/create_pages_table.php
database/migrations/add_block_editor_to_posts_table.php
database/factories/PageFactory.php

resources/js/Pages/Pages/Index.vue
resources/js/Pages/Pages/Create.vue
resources/js/Pages/Pages/Edit.vue
resources/js/Pages/Blog/Page.vue

resources/js/Components/BlockEditor/BlockEditor.vue
resources/js/Components/BlockEditor/BlockList.vue
resources/js/Components/BlockEditor/BlockPreview.vue
resources/js/Components/BlockEditor/BlockSettings.vue
resources/js/Components/BlockEditor/blocks/ParagraphSettings.vue
resources/js/Components/BlockEditor/blocks/HeadingSettings.vue
resources/js/Components/BlockEditor/blocks/ImageSettings.vue
resources/js/Components/BlockEditor/blocks/QuoteSettings.vue
resources/js/Components/BlockEditor/blocks/CodeSettings.vue
resources/js/Components/BlockEditor/blocks/GallerySettings.vue
resources/js/Components/BlockEditor/blocks/VideoSettings.vue
resources/js/Components/BlockEditor/blocks/DividerSettings.vue
resources/js/Components/BlockEditor/blocks/CtaSettings.vue
resources/js/Components/BlockEditor/blocks/HtmlSettings.vue

resources/js/Components/Blocks/ParagraphBlock.vue
resources/js/Components/Blocks/HeadingBlock.vue
resources/js/Components/Blocks/ImageBlock.vue
resources/js/Components/Blocks/QuoteBlock.vue
resources/js/Components/Blocks/CodeBlock.vue
resources/js/Components/Blocks/GalleryBlock.vue
resources/js/Components/Blocks/VideoBlock.vue
resources/js/Components/Blocks/DividerBlock.vue
resources/js/Components/Blocks/CtaBlock.vue
resources/js/Components/Blocks/HtmlBlock.vue
resources/js/Components/BlockRenderer.vue
```

### Modified files
```
app/Http/Controllers/BlogController.php        (pass use_block_editor + blocks in show() response for Blog/Show.vue)
app/Http/Controllers/PostController.php        (add use_block_editor + blocks to store/update validation and fillable; also add both fields to edit() Inertia response so Posts/Edit.vue can pre-populate the block editor)
routes/web.php                                 (add pages routes + catch-all)
resources/js/Layouts/AppLayout.vue             (add Pages sidebar entry)
resources/js/Pages/Posts/Create.vue            (add tab switcher + BlockEditor)
resources/js/Pages/Posts/Edit.vue              (add tab switcher + BlockEditor)
resources/js/Pages/Blog/Show.vue               (v-if block vs Tiptap rendering)
tests/Feature/PostTest.php                     (extend with block editor cases)
```
