# Design: Installer Wizard & Public Blog Frontend

**Date:** 2026-02-20
**Status:** Approved

---

## Overview

Two connected features:
1. **Installer wizard** — a multi-step web UI that configures the `.env`, runs migrations, creates the admin account, and marks the app as installed.
2. **Public blog frontend** — a clean public-facing blog with a post list homepage, a single-post reading view, and a sidebar. Replaces the current placeholder `Index.vue`.

---

## 1. Installer

### "Installed" Detection

A file at `storage/app/installed` is written as the very last step of installation.

- `EnsureNotInstalled` middleware: if `storage/app/installed` exists, redirect `/install/*` → `/`
- `EnsureInstalled` middleware: if `storage/app/installed` does NOT exist, redirect all non-install routes → `/install`

### Routes

```
GET  /install              → redirect → /install/database
GET  /install/database     → InstallController@showDatabase    → Install/Database.vue
POST /install/database     → InstallController@database
GET  /install/site         → InstallController@showSite        → Install/Site.vue
POST /install/site         → InstallController@site
GET  /install/admin        → InstallController@showAdmin       → Install/Admin.vue
POST /install/admin        → InstallController@admin
GET  /install/mail         → InstallController@showMail        → Install/Mail.vue
POST /install/mail         → InstallController@mail  ← final step
```

All install routes are wrapped in `EnsureNotInstalled` middleware.
All non-install routes are wrapped in `EnsureInstalled` middleware.

### InstallController — Step Responsibilities

**Step 1 — Database (`POST /install/database`)**
- Fields: `driver` (sqlite|mysql), `host`, `port`, `database`, `prefix`, `username`, `password` (last 5 required only for mysql)
- Validate connection with a real PDO attempt before accepting
- Write `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_TABLE_PREFIX`, `DB_USERNAME`, `DB_PASSWORD` to `.env`
- Run `Artisan::call('config:clear')` after writing
- Redirect → `/install/site`

**Step 2 — Site (`POST /install/site`)**
- Fields: `site_name`, `site_url` (pre-filled with current request URL)
- Write `APP_NAME`, `APP_URL` to `.env`
- Run `config:clear`
- Redirect → `/install/admin`

**Step 3 — Admin (`POST /install/admin`)**
- Fields: `name`, `email`, `password`, `password_confirmation`
- Store in session only — DB doesn't exist yet
- Redirect → `/install/mail`

**Step 4 — Mail (`POST /install/mail`)** ← runs everything
- Fields: `mailer` (smtp|log), `host`, `port`, `username`, `password`, `from_address`, `from_name` (last 6 required only for smtp)
- Write `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` to `.env`
- Run `config:clear`
- Run `Artisan::call('migrate', ['--force' => true])`
- Run `Artisan::call('db:seed', ['--force' => true])`
- Create admin `User` from session data, assign `administrator` role, mark email as verified
- Log the admin in (`Auth::login($user)`)
- Write `storage/app/installed`
- Redirect → `/dashboard`

### Helper: `.env` Writer

A private `writeEnv(array $values)` method on `InstallController` reads the existing `.env`, replaces or appends each key, and writes it back. Uses regex replacement per key so existing values are updated safely.

### Install Layout (`InstallLayout.vue`)

- Full-page centred card, no sidebar
- Lambda CMS logo at top
- 4-step progress indicator: **Database → Site → Admin → Mail** (completed steps shown as filled, current step highlighted, future steps muted)
- Form fields for current step
- "Next →" button (or "Install Lambda CMS" on final step)
- Back link to previous step on steps 2–4

### Seeder (`DatabaseSeeder`)

Run during install. Creates:
- A "General" category
- A "Hello World" published post assigned to the admin user and the General category, with a welcoming body explaining they've set up Lambda CMS

---

## 2. Public Blog Frontend

### Layout (`BlogLayout.vue`)

- Top nav: site name (`config('app.name')`) on the left, "Sign in" link on the right
- Hero strip below nav: `<h1>` site name + tagline (hardcoded initially, can be a config value later)
- Main content area (slot)
- Footer: `© {year} {site name}`
- No AppLayout chrome (sidebar etc.) — uses `defineOptions({ layout: null })`

### Routes

```
GET /              → BlogController@index   → Pages/Blog/Index.vue
GET /blog/{slug}   → BlogController@show    → Pages/Blog/Show.vue
```

These replace the existing placeholder `/` route.

### BlogController

**`index`**
- Paginated published posts (15/page), ordered by `published_at` desc
- Each post: `id`, `title`, `slug`, `excerpt`, `published_at`, `author.name`, `author.avatar_url`, `category.name`, `category.slug`, `tags[].name`, `tags[].slug`
- Sidebar data: all categories with `posts_count`, all tags with `posts_count`, latest 5 published post titles+slugs

**`show`**
- Find post by `slug` where `status = published`, or abort 404
- Same fields as above plus `body` (HTML)
- Same sidebar data

### Pages/Blog/Index.vue

Two-column layout (desktop: 2/3 main + 1/3 sidebar):
- **Main:** paginated post cards — title (linked to `/blog/{slug}`), excerpt, author name + avatar, category badge, date, "Read more →"
- **Sidebar:** Categories (name + count), Tags cloud (name + count, smaller for low-count tags), Recent Posts (title + date)
- Empty state if no published posts
- Pagination links at bottom of main column

### Pages/Blog/Show.vue

- Post title `<h1>`, author avatar + name, date, category badge, tags
- `v-html` rendered body (safe: admin-authored Tiptap HTML, not user input)
- "← Back to posts" link
- Same sidebar as Index

---

## Files to Create / Modify

### New files
- `app/Http/Middleware/EnsureInstalled.php`
- `app/Http/Middleware/EnsureNotInstalled.php`
- `app/Http/Controllers/InstallController.php`
- `app/Http/Controllers/BlogController.php`
- `resources/js/Layouts/InstallLayout.vue`
- `resources/js/Layouts/BlogLayout.vue`
- `resources/js/Pages/Install/Database.vue`
- `resources/js/Pages/Install/Site.vue`
- `resources/js/Pages/Install/Admin.vue`
- `resources/js/Pages/Install/Mail.vue`
- `resources/js/Pages/Blog/Index.vue`
- `resources/js/Pages/Blog/Show.vue`

### Modified files
- `routes/web.php` — add install + blog routes, apply middleware
- `bootstrap/app.php` — register new middleware aliases
- `database/seeders/DatabaseSeeder.php` — add default post/category seeding
- `resources/js/Pages/Index.vue` — replaced by Blog/Index.vue (can be deleted)
