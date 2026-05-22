# Lambda CMS — Project Summary

> Use this file to quickly orient a new Claude Code session. Keep it up to date as features are added.

---

## Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 12.60, PHP 8.2+ |
| Frontend | Vue 3 (Composition API, `<script setup>`), Inertia 2.3 |
| Build | Vite 7.3 |
| CSS | Tailwind CSS 4.3 (`@import "tailwindcss"` in `resources/css/app.css`) |
| UI Components | shadcn-vue 2.7 / reka-ui 2.9 / @lucide/vue 1.16 |
| Database | SQLite (default), MySQL supported |
| Auth | Laravel built-in + Spatie Permission 7.4 (roles: `administrator`, `user`) |

**Path aliases:** `@/` → `resources/js/`

---

## Key File Locations

```
resources/js/app.js                    — Inertia entry point
resources/css/app.css                  — Tailwind 4 styles
resources/views/app.blade.php          — Blade shell
routes/web.php                         — All web routes
routes/api.php                         — API v1 routes
app/Http/Middleware/HandleInertiaRequests.php — Shared props
resources/js/Layouts/AppLayout.vue     — Admin sidebar layout
resources/js/Layouts/BlogLayout.vue    — Public blog layout
resources/js/Layouts/InstallLayout.vue — 5-step install wizard layout
resources/js/Layouts/PageBuilderLayout.vue — Full-screen block editor layout
resources/js/Components/              — Shared components (PascalCase dir)
resources/js/components/              — Public blog + UI primitives (lowercase dir)
resources/js/Components/BlockRenderer.vue — Renders published blocks on the public frontend (BLOCK_MAP)
resources/js/Components/BlockEditor/  — Block editor (admin canvas + layers panel)
resources/js/Components/Blocks/       — Individual block render components
resources/js/lib/utils.js             — cn() helper
resources/js/composables/useLoopBinding.js — Dynamic field binding composable
resources/js/lib/loopSources.js       — Loop source field definitions
```

---

## Fully Implemented Features

### 🔐 Authentication & Users
- Login with rate limiting (5 attempts / 60 s per email+IP)
- Forgot / reset password via email token
- Email verification (required for post/page editing)
- Logout
- **Users CRUD** (admin-only): role assignment (`administrator` / `user`)
- User invite flow: auto-generated password + welcome email
- Admin count enforcement (min 1 admin; self-delete blocked)
- **Profile**: name/email edit, password change, avatar upload/delete
- Online status (`last_seen_at` + `isOnline()`)
- User banning (`BanController`, ban/unban with reason)

### 📝 Posts
- Full CRUD, author-scoped (owner OR admin can edit/delete)
- Status: `draft` / `scheduled` / `published`
- Scheduled posts with future `published_at`
- Bulk actions: publish, draft, delete (with per-post auth)
- Search (title/content) + filter by status/category + pagination (15/page)
- Featured image with alt text; `featured` flag
- Categories (searchable typeahead pill selector) and Tags (many-to-many)
- SEO fields: `meta_title`, `meta_description`, `meta_keywords`
- Comments enabled/disabled per post
- **Autosave** (POST/DELETE `/posts/{post}/autosave`)
- **Revisions**: list (max 25) + restore
- Block editor toggle (`use_block_editor` flag) — same block system as Pages
- TipTap editor with HTML source toggle
- **Preview**: `/preview/posts/{token}` — shareable preview URL for drafts
- **Save-in-place**: saving no longer remounts the page (uses `preserveState`)

### 📄 Pages (admin-only)
- Full CRUD with block editor (JSON `blocks` column)
- Status: `draft` / `published`
- SEO fields (same as posts)
- Autosave + Revisions (same infrastructure as posts)
- Public catch-all route: `/{slug}` serves published pages
- Full-width editor layout
- **Preview**: `/preview/pages/{token}` — shareable preview URL for drafts

### 🧱 Block Editor
Block types available:

| Group | Types | Public Renderer |
|---|---|---|
| Content | `paragraph`, `heading`, `image`, `video`, `gallery`, `code`, `quote` | ✓ |
| Content | `accordion`, `tabs`, `embed` | ✓ |
| Layout | `container`, `columns`, `section`, `divider`, `spacer`, `navigation` | ✓ |
| Interactive | `button`, `icon-list`, `cta`, `search`, `loop`, `link`, `filter-link`, `template`, `pagination`, `table` | ✓ |
| Developer | `html` (admin-only) | ✓ |
| Post | `post-title`, `post-body`, `post-featured-image`, `post-meta`, `post-author`, `post-taxonomy`, `post-comments` | ✓ |
| Archive | `archive-title`, `archive-loop` | ✓ |

> `accordion-item` and `tab-item` are auto-inserted children of their parent blocks, hidden from the palette.
> `columns` is a preset of `container` with a column-count control; it maps to `ContainerBlock` for rendering.

**Canvas features:**
- Drag-and-drop with cross-list nesting
- Block labels / `blockName` (shown in canvas + layers panel)
- Layers panel with infinite depth nesting
- Real-time preview in canvas
- **Undo / Redo** via the layers panel toolbar

**Settings tabs (per block):**
- **Content tab** — block-type-specific fields (text, URLs, media, etc.). Many text-rendering blocks also include a `TypographyControl` (font-size, weight, line-height, letter-spacing, text decoration, transform, text-shadow, alignment, color).
- **Style tab** — shared `StyleSettings.vue` for all blocks:
  - Font family
  - Display (flex/grid/inline-flex mode, direction, wrap, justify, align, gap, max-width)
  - Spacing (padding, margin — per-side via SpacingControl)
  - Border & Shadow — per-corner radius, stroke style/width/color, box-shadow presets
  - Background (color, gradient with direction, image with position/size/parallax + dynamic binding)
  - Effects (opacity, cursor, overflow, z-index, transition)
- **Advanced tab** — block label, custom HTML ID, custom CSS classes, custom CSS (CodeMirror editor, scoped automatically)
- **Conditions tab** — conditional visibility: show/hide block based on a field/operator/value rule (evaluated against loop context)

**Dynamic field binding:**
- Loop fields (post title, slug, excerpt, featured image URL, author, URL, etc.)
- Post context fields (single-post template)
- Both Library and URL modes on image blocks support binding
- Alt text, caption, heading text, paragraph text all bindable

**Loop block:**
- Query sources: posts / categories / tags / pages
- Filters (field, operator, value), sort, limit
- Resolved server-side via `QueryBuilder` service + `/api/v1/query` endpoint

**Template block / partials:**
- Partial templates render inline and re-provide `loopItem`/`postContext` so bindings work inside templates used in loops

**Pre-shipped system templates** (protected — cannot be deleted, can be edited):
- `Post Card` (partial, loop_source=posts)
- `Default Blog Index` (blog-index)
- `Default Single Post` (single-post)
- `Default Archive` (archive)
- `Default Search Results` (search-results)

### 🗂️ Categories & Tags
- Full CRUD (authenticated users)
- Auto-generated slugs; description on categories
- **Category color** (custom picker with Nord palette presets)
- Post count aggregation
- **Searchable typeahead selector** in post editor (CategoryInput.vue)
- Public archive pages: `/blog/category/{slug}`, `/blog/tag/{slug}`

### 🖼️ Media Library
- Upload (configurable MIME types, configurable max MB — default 10 MB)
- Auto-resize images > 1920 px wide
- List (40/page) with type/search filters; non-admins see own uploads only
- Update alt text / description; delete single or bulk
- UUID-based filenames; dimension tracking
- External disk support (`disk='external'` → stores full URL in `path`)

### 💬 Comments
- Public submission (rate-limited + honeypot)
- Pending by default; admin moderation (approve/reject/delete/bulk)
- Nested replies with email notification to commenter
- Pending count badge in admin header
- Per-post `comments_enabled` flag

### 🧭 Navigation
- Custom nav items: `type = page` (internal) or `custom` (arbitrary URL)
- Drag-and-drop reorder (`sort_order`)
- Published-pages-only selector for internal links
- Available as shared prop (`navItems`) on all pages via Inertia middleware
- Admin page: `/navigation`

### ⚙️ Settings (admin-only)
- Site name & URL
- **Accent color** — Nord aurora palette swatches; applies via CSS custom properties (`--primary`, `--sidebar-primary`, etc.)
- Locale: timezone, date_format
- Media: max_upload_mb, resize_max_width
- Mail: driver (smtp/log/mailgun), host, port, username, password, encryption, from/name; test email send
- Comments: enabled toggle, per_page (5–100)
- SEO: title_separator, default_description, default_og_image_url, default_keywords

### 📅 Calendar
- Monthly editorial calendar; posts grouped by `published_at` date
- Unscheduled drafts section
- Admin sees all; regular users see own published + anyone's drafts

### 📊 Dashboard
- Post counts (total/published/scheduled/draft)
- Pending comments count
- Upcoming scheduled posts (next 5)
- Recent posts (last 5 updated)

### 🌐 Public Frontend
- Blog index: paginated published posts, sidebar (search, categories, tags, recent posts)
- Single post: full content, featured image, author, categories/tags, comments with load-more
- Category + tag archive pages
- Full-text search page (`/search`)
- RSS feed (`/feed`) — 20 most recent published posts
- Sitemap XML (`/sitemap.xml`)
- Admin bar visible to authenticated users (hidden from public visitors)
- Nav items from the Navigation admin shown in public header

### 🔗 REST API (`/api/v1/`)
- `GET /posts` — paginated; filters: category slug, tag slug, search
- `GET /posts/{slug}` — full post with body
- `GET /categories` + `GET /categories/{slug}`
- `GET /tags` + `GET /tags/{slug}`
- `POST /query` — QueryBuilder endpoint (loop block data source)

### 🪝 Webhooks (admin-only)
- CRUD at `/webhooks`
- Events: `post.published`, `post.updated`, `post.deleted`, `page.published`, `page.updated`, `page.deleted`
- HMAC-SHA256 signature header (`X-Lambda-Signature`) when secret set
- Dispatched via queued job (`DispatchWebhookJob`)
- PostObserver + PageObserver fire events on lifecycle hooks
- `last_triggered_at` tracked per webhook

### 🧩 Templates
- CRUD at `/templates` (admin-only)
- Types: `partial`, `blog-index`, `single-post`, `archive`, `search-results`
- System templates (`is_system = true`) cannot be deleted
- `TemplateSeeder` seeds 5 system templates on fresh install
- Shared as `sharedTemplates` prop via Inertia middleware

### 🎨 Installation Wizard
- 5-step wizard: Database → Site → Admin → Mail → Theme (Genre)
- Tests DB connection before saving; writes `.env`; runs migrations
- **Genre/Theme selection (Step 5)**: 20 genre options (food, travel, technology, health, gardening, programming, gaming, AI, fashion, finance, science, movies, books, music, sports, art, cars, anime, DIY, empty)
- Seeds 10 themed posts with picsum.photos placeholder images, categories, and tags
- "None / Start Empty" option skips seeding
- `not_installed` / `installed` middleware gate
- Marks installed via `storage/app/installed` file

---

## Database Tables

```
users, posts, categories, tags, pages, comments, media, nav_items
templates, webhooks, settings
autosaves (morphable), revisions (morphable)
Pivots: category_post, post_tag
Spatie: roles, permissions, model_has_roles, ...
```

---

## Routes Reference

### Public (no auth required)
```
GET  /                               blog index
GET  /blog/{slug}                    single post
GET  /blog/category/{slug}           category archive
GET  /blog/tag/{slug}                tag archive
GET  /blog/{post:slug}/comments      paginated approved comments (JSON)
POST /blog/{post:slug}/comments      submit comment (rate-limited, honeypot)
GET  /search                         full-text search results
GET  /feed                           RSS feed
GET  /sitemap.xml                    sitemap
GET  /preview/posts/{token}          draft post preview (token-based)
GET  /preview/pages/{token}          draft page preview (token-based)
GET  /{slug}                         public page (catch-all)
```

### Guest-only
```
GET/POST /login
GET/POST /forgot-password
GET/POST /reset-password/{token}
```

### Auth only
```
GET  /email/verify                        verification notice
GET  /email/verify/{id}/{hash}            verification link (signed)
POST /email/verification-notification     resend verification email
POST /logout
```

### Auth + verified
```
GET  /dashboard
/posts     (resource + bulk + autosave + revisions)
/categories (resource)
/tags       (resource)
/profile    (info, password, avatar)
/media      (resource + bulk-destroy + usage)
/calendar   (index + data)
```

### Admin only (auth + verified + administrator role)
```
/pages         (resource + autosave + revisions)
/templates     (resource + autosave + revisions)
/users         (resource + ban/unban)
/comments      (list, approve, reject, delete, bulk, reply)
/settings      (index + update by group + test-email)
/navigation    (list, store, update, delete, reorder)
/webhooks      (index, store, update, destroy)
```

### API
```
GET  /api/v1/posts
GET  /api/v1/posts/{slug}
GET  /api/v1/categories
GET  /api/v1/categories/{slug}
GET  /api/v1/tags
GET  /api/v1/tags/{slug}
POST /api/v1/query
```

### Installer
```
/install/database (GET/POST)
/install/site     (GET/POST)
/install/admin    (GET/POST)
/install/mail     (GET/POST)
/install/genre    (GET/POST)
```

---

## Inertia Shared Props

Available on every page via `usePage().props`:

| Prop | Type | Description |
|---|---|---|
| `appName` | `string` | Site name from `config('app.name')` |
| `auth.user` | `{id,name,email,role,avatar_url,email_verified}` or `null` | Authenticated user |
| `flash.status` | `string\|null` | Generic success flash |
| `flash.error` | `string\|null` | Generic error flash |
| `flash.mail_status` | `string\|null` | Mail test success message |
| `flash.mail_error` | `string\|null` | Mail test error message |
| `currentRoute` | `string` | Current Laravel route name |
| `pendingCommentsCount` | `number\|null` | Pending comment count (admins only) |
| `accentColor` | `string\|null` | Hex accent color from site settings |
| `navItems` | `Array<{label,url}>` | Published nav items |
| `sharedTemplates` | `Array<Template>` | All templates (for block editor) |

---

## Conventions & Patterns

- **Block data**: stored as JSON in `blocks` column; each block has `{id, type, data, bindings?, children?, blockName?, fontFamily?, customId?, customClasses?, customCss?, condition?, ...}`
- **Dynamic bindings**: `block.bindings[fieldName] = 'loop:field'` or `'post:field'`; resolved in `useLoopBinding.js` composable
- **Block rendering**: `BlockRenderer.vue` (public) maps type → component; `blockWrapperStyle()` computes inline styles from block data
- **Block settings**: per-type Settings component for Content tab; shared `StyleSettings.vue` for Style tab; `AdvancedSettings.vue` for Advanced tab; `ConditionSettings.vue` for Conditions tab
- **Notifications**: `useNotifications()` composable → `Notifications.vue` component
- **Theming**: dark/light via `useTheme()` composable; `data-theme` attribute on root
- **Accent color**: `accentColor` shared prop → `watchEffect` in AppLayout sets `--primary` and related CSS vars
- **Media URLs**: `Media::getUrlAttribute()` handles both `Storage::disk()` and `disk='external'` (returns `path` directly as full URL)
- **System templates**: `is_system = true` blocks deletion; `TemplateSeeder` marks them
- **Preview tokens**: auto-generated 64-char random string on Post/Page create; routes are public
- **Webhooks**: fired via observers → `WebhookService::dispatch()` → `DispatchWebhookJob` (queued)

---

## Open Issues / TODOs

Potential future improvements:
- Add more genres to GenreSeeder (interior, daily life, crypto, writing, etc.)
- VLOG / video-focused genre template
- Import/export pages and templates

### Pending major-version upgrades (deferred — require additional review)
- `inertia-laravel` 2 → 3 + `@inertiajs/vue3` 2 → 3 (coupled upgrade)
- `@vueuse/core` 13 → 14
- `intervention/image` 3 → 4 (may require image-processing code changes)
- `laravel/framework` 12 → 13 (full framework upgrade)

---

*Last updated: 2026-05-22 — added AccordionBlock, TabsBlock, EmbedBlock public renderers; Button, Icon List, and Columns blocks*
