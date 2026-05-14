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
Block types available (56 total):
`container`, `section`, `heading`, `paragraph`, `image`, `video`, `audio`, `gallery`, `code`, `quote`, `divider`, `spacer`, `cta`, `html`, `list`, `table`, `loop`, `post-list`, `post-title`, `post-body`, `post-featured-image`, `post-meta`, `post-author`, `post-taxonomy`, `post-comments`, `archive-title`, `archive-loop`, `search`, `navigation`, `link`, `filter-link`, `button`, `icon`, `alert`, `card`, `hero`, `banner`, `breadcrumb`, `social-links`, `progress-bar`, `file-download`, `feature`, `stats`, `team-member`, `timeline`, `toc`, `form`, `pricing`, `map`, `embed`, `countdown`, `accordion`, `tabs`, `testimonial`, `template`, `pagination`, `newsletter`

**Canvas features:**
- Drag-and-drop with cross-list nesting
- Block labels / `blockName` (shown in canvas + layers panel)
- Layers panel with infinite depth nesting and collapsible children (JS height animation)
- Real-time preview in canvas
- Animated settings panel transitions (fade + slide between blocks, tab fade)

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
- **Confirmed newsletter subscribers count** (admin-only stat card)
- Upcoming scheduled posts (next 5)
- Recent posts (last 5 updated)
- **Top Posts by Views** — top 5 published posts by `views` column, descending

### 🌐 Public Frontend
- Blog index: paginated published posts, sidebar (categories, tags, recent posts)
- Single post: full content, featured image, author, categories/tags, comments
- **Reading time estimate** — `Post::readingTime()` at 200 wpm; exposed as `reading_time` in `BlogController::postData()` and `show()`; displayed in `Blog/Show.vue` next to publish date
- **Post view tracking** — `BlogController::show()` increments `posts.views` on each visit; cookie `viewed_post_{id}` (24 h TTL) prevents duplicate counts from the same visitor
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
- HMAC-SHA256 signature header (`X-Lambda-Signature`) when secret set; secret stored encrypted (`'encrypted'` cast)
- Dispatched via queued job (`DispatchWebhookJob`)
- PostObserver + PageObserver fire events on lifecycle hooks
- `last_triggered_at` tracked per webhook
- SSRF protection: hostname resolved to IP, private/reserved ranges rejected via `FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE`

### 📥 Form Submissions
- `FormSubmission` model — `form_name`, `page_slug`, `data` (JSON), `ip_address`
- Public `POST /form-submissions` (10 req/min throttle); returns `{ success: true }` JSON
- `FormBlock.vue` posts to this endpoint when no custom action URL is set; collects field values keyed by label with CSRF header
- Admin inbox: `GET /form-submissions` → `Forms/Index.vue` — expandable rows, delete with confirmation
- Admin `DELETE /form-submissions/{submission}`

### 📈 Post View Analytics
- `posts.views` — `unsignedBigInteger` column (default 0) added via migration
- `BlogController::show()` calls `$post->increment('views')` after checking cookie `viewed_post_{id}` (24 h); no auth required
- **Admin Posts table** — Views column visible at xl breakpoint (`Posts/Index.vue`)
- **Dashboard widget** — `DashboardController` passes `top_posts_by_views` (top 5 published by `views DESC`); rendered as ranked list in `Dashboard/Index.vue`
- Views included in `PostController::index()` mapping so it appears in the admin table

### 🔍 Admin Global Search
- `AdminSearchController` (single-action) — `GET /admin/search?q=`; requires auth + verified; returns JSON with groups: `posts`, `pages`, `media`, `users`; each result has `{type, id, label, meta, url}`; minimum query length 2
- `CommandPalette.vue` — Teleport'd modal; opens on Cmd+K / Ctrl+K (or topbar search button); closes on ESC or backdrop click; keyboard navigation with ↑↓↵; debounced fetch (250 ms); grouped result display with type icons
- `AppLayout.vue` — imports and mounts `CommandPalette`; search button in topbar dispatches a synthetic keyboard event to open the palette

### 📬 Newsletter
- **`newsletter_subscribers`** table: `id`, `email` (unique), `name` (nullable), `token` (unique 64-char random), `confirmed_at` (nullable timestamp), `ip_address`, timestamps
- **`newsletter_campaigns`** table: `id`, `title`, `subject`, `body`, `blocks` (JSON, nullable), `sent_at` (nullable), `scheduled_at` (nullable), `recipients_count`, timestamps
- **`NewsletterSubscriber`** model — `confirmed()` scope, `isConfirmed()`, token-based unsubscribe
- **`NewsletterCampaign`** model — `isSent()`, `isScheduled()`, `blocks` cast to array, `sent_at`/`scheduled_at` cast to datetime
- **`NewsletterSubscriptionController`** (public):
  - `POST /newsletter/subscribe` (throttle 5/min) — validates email/name, creates subscriber with random token, queues `NewsletterConfirmMail`; silently ignores already-confirmed; JSON or redirect response
  - `GET /newsletter/confirm/{token}` — marks `confirmed_at = now()`
  - `GET /newsletter/unsubscribe/{token}` — deletes subscriber record
- **`NewsletterController`** (admin-only):
  - `GET /newsletter/subscribers` — paginated list, filter by confirmed/pending/all; totals passed as props
  - `DELETE /newsletter/subscribers/{id}` — single delete with activity log
  - `DELETE /newsletter/subscribers/bulk` — bulk delete by IDs
  - `GET /newsletter/subscribers/export` — streamed CSV download (email, name, confirmed_at)
  - `GET /newsletter/campaigns` — paginated campaign list
  - `POST /newsletter/campaigns` — create draft (title + subject only), redirect to editor
  - `GET /newsletter/campaigns/{id}/edit` — returns `Newsletter/CampaignEditor` Inertia page (blocks + metadata)
  - `PUT /newsletter/campaigns/{id}` — save blocks, title, subject, scheduled_at
  - `POST /newsletter/campaigns/{id}/send` — send immediately to all confirmed subscribers; queues `NewsletterCampaignMail` per subscriber; sets `sent_at`
  - `DELETE /newsletter/campaigns/{id}` — delete campaign
- **`BlockEmailRenderer`** service (`app/Services/BlockEmailRenderer.php`) — converts block JSON array to table-layout email HTML with inline styles; supported blocks: `paragraph`, `heading`, `image`, `divider`, `button`, `cta`, `html`, `quote`, `alert`, `spacer`, `list`, `container`, `section`; appends unsubscribe footer
- **`NewsletterCampaignMail`** — uses `BlockEmailRenderer` when `blocks` is non-empty; falls back to `emails.newsletter.campaign` blade view for legacy plain-text body; uses `Content(htmlString:)` to pass rendered HTML directly
- **`NewsletterConfirmMail`** — simple confirm link email (`emails.newsletter.confirm` blade view)
- **`SendScheduledNewslettersCommand`** (`newsletter:send-scheduled`) — finds campaigns where `scheduled_at <= now()` and `sent_at IS NULL`; queues emails to all confirmed subscribers; updates `sent_at` and `recipients_count`; registered in scheduler (every minute, without overlapping)
- **`Newsletter/Subscribers.vue`** — table with confirmed/pending/all filter tabs, bulk checkbox selection, bulk delete modal, single delete modal, CSV export button, pagination
- **`Newsletter/Campaigns.vue`** — create-draft form (title + subject); campaign list with Draft/Scheduled/Sent badges and scheduled datetime display; edit link for unsent campaigns; delete modal
- **`Newsletter/CampaignEditor.vue`** — full-screen `PageBuilderLayout` + `BlockEditor` (same pattern as Templates/Edit); top bar has inline title/subject fields, schedule date-picker toggle, Save / Schedule / Send Now buttons; save → PUT update; send now → PUT update then `router.post()` to send endpoint
- **`NewsletterBlock.vue`** — public subscribe form block; email + optional name field; posts JSON to `/newsletter/subscribe` with CSRF token; shows success message on confirmed submission; configurable heading, description, button label, placeholder text, disclaimer
- **Sidebar** — Newsletter section (admin-only): Subscribers link + Campaigns link
- **Scheduler** — `bootstrap/app.php` registers `newsletter:send-scheduled` every minute alongside `posts:publish-scheduled`; production deployment requires `* * * * * php artisan schedule:run` cron entry
- **`NewsletterCampaignSeeder`** (`database/seeders/NewsletterCampaignSeeder.php`) — seeds one draft "Welcome to Lambda CMS" campaign on every fresh install (14 blocks: hero image, H1 heading, intro paragraph, feature list, quote, CTA, footer note); uses `firstOrCreate` so re-seeding is safe; called from `DatabaseSeeder`

### 📋 Activity Log
- `ActivityLog` model — `user_id` (nullable FK), `action`, `model_type`, `model_id`, `description`, `metadata` (JSON), `ip_address`
- `ActivityLogger::log(action, description, modelType?, modelId?, metadata?)` static helper — reads user from `auth()` and IP from request
- Wired to all admin controllers: PostController, PageController, UserController, BanController, MediaController, CommentController, CategoryController, TagController, NavigationController, WebhookController, SettingsController, TemplateController, RevisionController, NewsletterController
- Admin timeline: `GET /activity-log?action=` → `ActivityLog/Index.vue` — colored badges, filter tabs (All / Created / Updated / Deleted / Published / Banned / Restored), paginated 50/page

### 🔒 Security
- `SecurityHeaders` middleware on every web response: `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`, `X-XSS-Protection`
- `InstallController::writeEnv()` strips newlines from values, uses `preg_quote` on keys, and `preg_replace_callback` to prevent `.env` injection
- `FeedController::escapeCdata()` prevents CDATA injection in RSS output
- `Post::scopePublished()` enforces `published_at <= now()` to block future-dated posts
- `APP_DEBUG=false`, `LOG_LEVEL=error` in `.env.example`

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
users, posts (+ views unsignedBigInteger default 0), categories, tags, pages, comments, media, nav_items
templates, webhooks, settings
autosaves (morphable), revisions (morphable)
activity_logs, form_submissions
newsletter_subscribers (email, name, token, confirmed_at, ip_address)
newsletter_campaigns (title, subject, body, blocks JSON, sent_at, scheduled_at, recipients_count)
Pivots: category_post, post_tag
Spatie: roles, permissions, model_has_roles, ...
```

---

## Routes Reference

### Public
```
GET  /                          blog index
GET  /blog/{slug}               single post (includes reading_time)
GET  /blog/category/{slug}      category archive
GET  /blog/tag/{slug}           tag archive
POST /blog/{post:slug}/comments submit comment (rate-limited)
POST /form-submissions          submit form (rate-limited: 10/min)
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
/posts      (resource + bulk + autosave + revisions)
/categories (resource)
/tags       (resource)
/profile    (info, password, avatar)
/media      (resource + bulk-destroy + usage)
/calendar   (index + data)
GET  /admin/search                 global search (JSON, debounced)
GET  /search
POST /logout
```

### Admin only (auth + verified + administrator role)
```
/pages              (resource + autosave + revisions)
/templates          (resource + autosave + revisions)
/users              (resource + ban/unban)
/comments           (list, approve, reject, delete, bulk, reply)
/settings           (index + update by group + test-email)
/navigation         (list, store, update, delete, reorder)
/webhooks           (index, store, update, destroy)
GET    /activity-log               activity log timeline (filterable by action)
GET    /form-submissions           form submissions inbox
DELETE /form-submissions/{id}      delete a submission
GET    /newsletter/subscribers                  subscriber list (filter: all/confirmed/pending)
DELETE /newsletter/subscribers/bulk             bulk delete by IDs
GET    /newsletter/subscribers/export           CSV download
DELETE /newsletter/subscribers/{id}             delete single subscriber
GET    /newsletter/campaigns                    campaign list
POST   /newsletter/campaigns                    create draft campaign, redirect to editor
GET    /newsletter/campaigns/{id}/edit          campaign block editor
PUT    /newsletter/campaigns/{id}               save blocks, title, subject, scheduled_at
POST   /newsletter/campaigns/{id}/send          send now to all confirmed subscribers
DELETE /newsletter/campaigns/{id}               delete campaign
```

### Public Newsletter
```
POST /newsletter/subscribe          subscribe (throttle 5/min); queues confirm email
GET  /newsletter/confirm/{token}    set confirmed_at
GET  /newsletter/unsubscribe/{token} delete subscriber record
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

*Last updated: 2026-05-14 — pushed at commit `d5bb1f0`*
