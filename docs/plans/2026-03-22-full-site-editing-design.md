# Full Site Editing (FSE) + Search — Design

**Date:** 2026-03-22
**Status:** Approved

---

## Overview

Extend Lambda CMS with Full Site Editing: admins build layouts for the blog index, single post, archive, and search results pages using the existing block editor. A new `search` block type lets visitors search posts from any page or template. If no template is published for a slot, the current hardcoded Vue views remain active — zero regression.

---

## 1. Data Model

### New `templates` table

| column | type | notes |
|---|---|---|
| `id` | bigint PK | |
| `type` | enum | `blog-index`, `single-post`, `archive`, `search-results` |
| `title` | string | Admin label, e.g. "My Blog Index" |
| `status` | enum | `draft` / `published` |
| `blocks` | JSON | Same structure as `pages.blocks` |
| `meta_title` | string(100) | nullable |
| `meta_description` | string(300) | nullable |
| `meta_keywords` | string(255) | nullable |
| `user_id` | FK → users | Creator |
| `created_at` / `updated_at` | timestamps | |

**Constraint:** only one `published` template per `type` at a time. Enforced in the controller: publishing a new template auto-drafts any other published template of the same type.

**Autosave + Revisions:** Template implements the `Autosaveable` and `Revisable` morph interfaces — reuses existing `autosaves` and `revisions` tables. Max 25 revisions per template.

### Migration

```
templates
  id bigIncrements
  type enum('blog-index','single-post','archive','search-results')
  title string
  status enum('draft','published') default 'draft'
  blocks json nullable
  meta_title string(100) nullable
  meta_description string(300) nullable
  meta_keywords string(255) nullable
  user_id foreignId → users (cascade delete)
  timestamps
```

---

## 2. New Block Types

### Post-context blocks
Only meaningful inside a `single-post` template. Usable anywhere in the editor but only render real data when a post context is provided. Shown in a dedicated **"Post"** group in the block palette with a `[post]` badge.

| type | renders | settings |
|---|---|---|
| `post-title` | Post title as a heading | Tag level (h1–h3) |
| `post-body` | Full post content (TipTap HTML or nested blocks) | none |
| `post-featured-image` | Featured image with alt text | Max width, aspect ratio |
| `post-meta` | Published date + author name + read time | Toggle each field on/off |
| `post-author` | Avatar + name + optional bio | Show avatar toggle |
| `post-taxonomy` | Pill list of categories and/or tags | Show categories / show tags toggles |
| `post-comments` | Full comments section (form + list) | none |

### Archive-context blocks
Only meaningful inside an `archive` template.

| type | renders | settings |
|---|---|---|
| `archive-title` | "Category: Design" / "Tag: Laravel" heading | Tag level (h1–h3) |
| `archive-loop` | Pre-wired Loop block scoped to current archive term | Columns, gap, limit — reuses existing LoopBlock |

### Search block
Usable in any template or page.

| type | renders | settings |
|---|---|---|
| `search` | `<form method="GET" action="/search">` with text input + submit button | Placeholder text, button label, scope (posts / posts + pages) |

---

## 3. Admin UI

### Sidebar
New **Templates** link (admin-only) between Pages and Navigation, with a `LayoutTemplate` lucide icon.

### Templates index (`/templates`)
- List grouped by type (blog-index, single-post, archive, search-results)
- Each row: title, type badge, status pill (draft/published), last updated, Edit + Delete actions
- "New template" button opens a type-picker modal before redirecting to `/templates/create?type=…`
- Admins only

### Template editor (`/templates/create`, `/templates/{id}/edit`)
- Identical layout to the Page editor: title input → meta card (status radios, SEO fields) → full-width block editor
- Block palette gets two new groups:
  - **Post** — the 7 post-context blocks, each with a muted `[post]` badge
  - **Search** — the search block
  - **Archive** — archive-title and archive-loop blocks
- Publishing behaviour: saving with `published` status triggers auto-draft of any other published template of the same type; a dismissable banner confirms this

### Autosave + Revisions
Same infrastructure as pages — autosave on change, revisions panel in the editor.

---

## 4. Rendering Architecture

### TemplateResolver service (`app/Services/TemplateResolver.php`)
```php
class TemplateResolver {
    public function resolve(string $type): ?Template // returns published template or null
}
```
Cached per request (singleton). Called at the top of each affected controller action.

### New `Blog/TemplatePage.vue`
Thin public-facing Vue page that:
- Accepts `blocks`, `postContext`, `archiveContext`, `searchContext` as Inertia props
- Calls `resolveBlocks()` (same as PublicPageController already does)
- Provides all context objects via Vue `provide()` at the root
- Renders `<BlockRenderer :blocks="blocks" />`

### Context injection pattern
Each context type is provided at the `TemplatePage` level and injected in the relevant block components:
- `postContext` → injected by all `post-*` block components
- `archiveContext` → injected by `archive-title` and `archive-loop`
- `searchContext` → injected by the search results rendering (a loop-style results list)

This mirrors the existing `LoopItemProvider` / `inject('loopItem')` pattern.

### BlogController changes

| action | with template | without template |
|---|---|---|
| `index()` | Render `Blog/TemplatePage` with `blog-index` blocks + paginated posts as `postContext` | Existing `Blog/Index.vue` (unchanged) |
| `show()` | Render `Blog/TemplatePage` with `single-post` blocks + current post as `postContext` | Existing `Blog/Show.vue` (unchanged) |
| `category()` / `tag()` | Render `Blog/TemplatePage` with `archive` blocks + term as `archiveContext` | Existing `Blog/Archive.vue` (unchanged) |

### New SearchController (`app/Http/Controllers/SearchController.php`)
- Route: `GET /search?q=`
- Queries published posts (title + excerpt + body, LIKE search), paginated 15/page
- Scope flag: if `scope=all`, also queries published pages
- If `search-results` template exists → render `Blog/TemplatePage` with blocks + results as `searchContext`
- Otherwise → render new `Blog/Search.vue` fallback (simple list, same style as Archive)

### PublicPageController
No changes — custom pages at `/{slug}` are unaffected.

---

## 5. Search Feature Detail

### Route
```
GET /search?q={query}&page={n}
```
Added to `routes/web.php` inside the `installed` middleware group, before the `/{slug}` catch-all.

### SearchController logic
1. Validate `q` (string, max 200 chars); empty query returns empty results
2. `Post::published()->where(fn($q) => $q->where('title','LIKE',"%{$term}%")->orWhere('excerpt','LIKE',"%{$term}%")->orWhere('body','LIKE',"%{$term}%"))->paginate(15)`
3. If scope includes pages: same LIKE on `Page::published()`
4. Merge results, pass to template or fallback view

### Fallback `Blog/Search.vue`
- Heading: "Search results for '{q}'"
- If no results: "No results found."
- Otherwise: same post card list as Archive.vue
- Pagination links
- Sidebar (categories, tags, recent posts) — same as blog index

### Search block (public render)
```html
<form method="GET" action="/search">
  <input type="text" name="q" placeholder="{placeholder}" value="{current q if on search page}" />
  <button type="submit">{button label}</button>
</form>
```
Pre-populates `value` with the current `q` query param when rendered on the search results page.

---

## 6. Fallback Strategy

| template slot | fallback (no published template) |
|---|---|
| `blog-index` | `Blog/Index.vue` — unchanged |
| `single-post` | `Blog/Show.vue` — unchanged |
| `archive` | `Blog/Archive.vue` — unchanged |
| `search-results` | `Blog/Search.vue` — new, built alongside FSE |

All existing URLs and views remain 100% functional until an admin publishes a template.

---

## 7. Out of Scope

- Theme switching / multiple active themes
- Template inheritance or parent/child templates
- Visual drag-resize of columns in the editor (existing block system handles layout)
- Template export/import
- Header and footer templates (nav is managed separately via Navigation)

---

## 8. File Summary

**New files:**
- `database/migrations/…_create_templates_table.php`
- `app/Models/Template.php`
- `app/Http/Controllers/TemplateController.php`
- `app/Http/Controllers/SearchController.php`
- `app/Services/TemplateResolver.php`
- `resources/js/Pages/Templates/Index.vue`
- `resources/js/Pages/Templates/Create.vue`
- `resources/js/Pages/Templates/Edit.vue`
- `resources/js/Pages/Blog/TemplatePage.vue`
- `resources/js/Pages/Blog/Search.vue`
- `resources/js/Components/Blocks/PostTitleBlock.vue`
- `resources/js/Components/Blocks/PostBodyBlock.vue`
- `resources/js/Components/Blocks/PostFeaturedImageBlock.vue`
- `resources/js/Components/Blocks/PostMetaBlock.vue`
- `resources/js/Components/Blocks/PostAuthorBlock.vue`
- `resources/js/Components/Blocks/PostTaxonomyBlock.vue`
- `resources/js/Components/Blocks/PostCommentsBlock.vue`
- `resources/js/Components/Blocks/ArchiveTitleBlock.vue`
- `resources/js/Components/Blocks/SearchBlock.vue`
- `resources/js/Components/BlockEditor/blocks/PostTitleSettings.vue`
- `resources/js/Components/BlockEditor/blocks/PostBodySettings.vue`
- `resources/js/Components/BlockEditor/blocks/PostFeaturedImageSettings.vue`
- `resources/js/Components/BlockEditor/blocks/PostMetaSettings.vue`
- `resources/js/Components/BlockEditor/blocks/PostAuthorSettings.vue`
- `resources/js/Components/BlockEditor/blocks/PostTaxonomySettings.vue`
- `resources/js/Components/BlockEditor/blocks/ArchiveTitleSettings.vue`
- `resources/js/Components/BlockEditor/blocks/SearchSettings.vue`

**Modified files:**
- `routes/web.php` — add `/search` route + `/templates` resource
- `app/Http/Controllers/BlogController.php` — template resolution in index/show/category/tag
- `resources/js/Components/BlockEditor/BlockTypePanel.vue` — new block groups + types
- `resources/js/Components/BlockRenderer.vue` — register 9 new block types
- `resources/js/Components/BlockEditor/BlockCanvas.vue` — register new block types for canvas
- `resources/js/Layouts/AppLayout.vue` — add Templates nav link
