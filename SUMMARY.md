# Lambda CMS — Project Summary

> Use this file to quickly orient a new Claude Code session. Keep it up to date as features are added.

---

## Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 12.28, PHP 8.2+ |
| Frontend | Vue 3 (Composition API, `<script setup>`), Inertia 2 |
| Build | Vite 7 |
| CSS | Tailwind CSS 4 (`@import "tailwindcss"` in `resources/css/app.css`) |
| UI Components | shadcn-vue / reka-ui / lucide-vue-next |
| Database | SQLite (default), MySQL supported |
| Auth | Laravel built-in + Spatie Permission (roles: `administrator`, `user`) |

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
resources/js/components/              — Block renderer + UI primitives (lowercase dir)
resources/js/components/BlockRenderer.vue — Renders blocks on the public frontend
resources/js/Components/BlockEditor/  — Block editor (admin canvas + layers panel)
resources/js/components/Blocks/       — Individual block render components
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
`container`, `section`, `heading`, `paragraph`, `image`, `video`, `gallery`, `code`, `quote`, `divider`, `spacer`, `cta`, `html`, `loop`, `post-list`, `post-title`, `post-body`, `post-featured-image`, `post-meta`, `post-author`, `post-taxonomy`, `post-comments`, `archive-title`, `archive-loop`, `search`, `navigation`, `link`, `filter-link`, `template`, `table`

**Canvas features:**
- Drag-and-drop with cross-list nesting
- Block labels / `blockName` (shown in canvas + layers panel)
- Layers panel with infinite depth nesting
- Real-time preview in canvas

**Settings panels (Style tab — shared for all blocks):**
- Font family
- Display (flex/grid/inline-flex mode, direction, wrap, justify, align, gap, max-width)
- Spacing (padding, margin — per-side via SpacingControl)
- **Border & Shadow** — per-corner radius, stroke style/width/color, box-shadow presets
- Background (color, gradient with direction, image with position/size/parallax + dynamic binding)
- Effects (opacity, cursor, overflow, z-index, transition)

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
- Blog index: paginated published posts, sidebar (categories, tags, recent posts)
- Single post: full content, featured image, author, categories/tags, comments
- Category + tag archive pages
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

### Public
```
GET  /                          blog index
GET  /blog/{slug}               single post
GET  /blog/category/{slug}      category archive
GET  /blog/tag/{slug}           tag archive
POST /blog/{post:slug}/comments submit comment (rate-limited)
GET  /feed                      RSS feed
GET  /sitemap.xml               sitemap
GET  /preview/posts/{token}     draft post preview
GET  /preview/pages/{token}     draft page preview
GET  /{slug}                    public page (catch-all)
```

### Guest-only
```
GET/POST /login
GET/POST /forgot-password
GET/POST /reset-password/{token}
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
GET  /search
POST /logout
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
| `auth.user` | `{id,name,email,role,avatar}` or `null` | Authenticated user |
| `flash.status` | `string` or `null` | Session flash message |
| `currentRoute` | `string` | Current Laravel route name |
| `navItems` | `Array<{label,url}>` | Published nav items |
| `sharedTemplates` | `Array<Template>` | All templates (for block editor) |

---

## Conventions & Patterns

- **Block data**: stored as JSON in `blocks` column; each block has `{id, type, data, bindings?, children?, blockName?, fontFamily?, ...}`
- **Dynamic bindings**: `block.bindings[fieldName] = 'loop:field'` or `'post:field'`; resolved in `useLoopBinding.js` composable
- **Block rendering**: `BlockRenderer.vue` (public) maps type → component; `blockWrapperStyle()` computes inline styles from block data
- **Block settings**: per-type Settings component for Content tab; shared `StyleSettings.vue` always shown in Style tab
- **Notifications**: `useNotifications()` composable → `Notifications.vue` component
- **Theming**: dark/light via `useTheme()` composable; `data-theme` attribute on root
- **Media URLs**: `Media::getUrlAttribute()` handles both `Storage::disk()` and `disk='external'` (returns `path` directly as full URL)
- **System templates**: `is_system = true` blocks deletion; `TemplateSeeder` marks them
- **Preview tokens**: auto-generated 64-char random string on Post/Page create; routes are public
- **Webhooks**: fired via observers → `WebhookService::dispatch()` → `DispatchWebhookJob` (queued)

---

## Open Issues / TODOs

All items in `FIX.txt` are marked `[done]`. No open tracked issues at time of last push (`017aaea`).

Potential future improvements:
- Add more genres to GenreSeeder (interior, daily life, crypto, writing, etc.)
- VLOG / video-focused genre template
- Pagination block in the public frontend
- User-configurable accent color (Nord aurora palette)
- Import/export pages and templates

---

*Last updated: 2026-04-19 — pushed at commit `017aaea`*
