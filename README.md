# ⚡ Lambda CMS

> A modern, self-hosted CMS built with **Laravel 12**, **Inertia.js**, and **Vue 3**. Clean, fast, and developer-friendly — with a full-featured drag-and-drop block editor, template system, media library, comment moderation, webhooks, and a headless JSON API.

---

## 🧰 Tech Stack

| Layer | Technology |
|---|---|
| 🖥️ Backend | Laravel 12, PHP 8.2+ |
| 🎨 Frontend | Vue 3, Inertia.js 2, Tailwind CSS 4, Vite 7 |
| 🧩 UI Components | shadcn-vue, reka-ui, lucide-vue-next |
| ✍️ Rich Text | Tiptap |
| 🖱️ Drag & Drop | vue-draggable-plus |
| 🗄️ Database | SQLite (default) or MySQL |

---

## ✨ Features

### 📝 Content Management

- **Posts** — Full CRUD with rich text (Tiptap) or drag-and-drop block editor
- **Pages** — Custom static pages built entirely with the block editor
- **Categories & Tags** — Full CRUD, many-to-many on posts; categories support custom colors
- **Post scheduling** — Set a future publish date; posts go live automatically
- **Autosave** — Drafts are saved automatically while editing
- **Revisions** — Full revision history with one-click restore (up to 25 revisions)
- **Draft preview** — Shareable `/preview/posts/{token}` and `/preview/pages/{token}` URLs, no login required
- **Bulk actions** — Bulk publish, draft, or delete posts with author-scoped permission checks

### 🧱 Block Editor

55 block types organized by purpose:

| Category | Blocks |
|---|---|
| 📄 Content | Paragraph, Heading, Image, Video, Audio, Gallery, Quote, Code, CTA, HTML, Table, List, Divider, Spacer |
| 🗂️ Layout | Section, Container (flex / grid / inline-flex), Accordion, Tabs, Card, Hero, Banner, Feature, Pricing, Stats, Team Member, Testimonial, Timeline |
| 🔗 Interactive | Button, Link, Navigation, Search, Filter Link, Form, Embed, Map, Icon, Breadcrumb, Social Links, File Download, Pagination, Countdown, Progress Bar, TOC |
| 🔄 Dynamic | Loop (posts / categories / tags / pages), Post List |
| 📰 Post parts | Post Title, Post Body, Featured Image, Post Meta, Post Author, Post Taxonomy, Post Comments |
| 🗃️ Archive | Archive Title, Archive Loop |
| 🧩 Composition | Template (embed a saved partial or layout template) |

**Editor features:**
- 🖱️ Drag-and-drop canvas with cross-list nesting
- 📋 Layers panel with infinite-depth tree navigation and collapsible children
- 🎨 Per-block Style tab — typography, colors, background (solid / gradient / image), border & shadow, spacing
- 🔁 Dynamic field bindings — link block fields to loop or post-context data sources
- ⚙️ Per-block Advanced settings — custom CSS classes, inline CSS, animation, font family, margin
- 👁️ Conditional block visibility based on loop field values
- 🏷️ Block labels / custom names shown in canvas and layers panel
- ✨ Animated settings panel transitions and tab fades

### 🧩 Template System

- **Template types:** `partial`, `blog-index`, `single-post`, `archive`, `search-results`
- **5 pre-shipped system templates** — cannot be deleted, can be freely edited
  - Post Card (partial — used in the default blog index loop)
  - Default Blog Index
  - Default Single Post
  - Default Archive
  - Default Search Results
- Full CRUD for custom templates with the same block editor, autosave, and revisions
- Templates embedded in loop blocks correctly inherit loop context for dynamic field bindings

### 🖼️ Media Library

- Upload images, videos, documents, and audio (configurable size limit, default 10 MB)
- Auto-resize images larger than 1920 px wide
- Edit alt text and description per file
- Bulk delete; admins see all files, users see their own
- UUID-based filenames with dimension tracking
- External disk support (stores full URL when `disk = external`)

### 🌐 Public Frontend

- Block-driven pages — every public page is rendered from saved block templates
- RSS feed at `/feed` and XML sitemap at `/sitemap.xml`
- Admin bar visible to authenticated users (hidden from public visitors)
- Navigation items managed from the admin and rendered via the Navigation block
- **Reading time estimate** — automatically calculated and displayed on each post (200 wpm, HTML/block-aware)

### 💬 Comments

- Public submission with honeypot spam protection and rate limiting
- Admin moderation — approve, reject, or delete individually or in bulk
- Nested replies with email notifications to the commenter
- Per-post toggle to enable/disable comments
- Pending badge in the admin header

### 👥 Users & Roles

- Two roles: **administrator** and **user**
- Administrators manage all content, users, settings, pages, and templates
- Users manage their own posts, categories, and tags
- Avatar upload per user profile
- Online status tracking (`last_seen_at`)
- User banning with reason tracking (ban / unban)
- Admin count enforcement — at least one admin must always exist
- User invite flow — auto-generated password + welcome email

### 📥 Form Submissions

- The **Form block** captures submissions and stores them in the database when no custom action URL is configured
- Admin inbox at `/form-submissions` — expandable rows showing all field values, per-submission delete
- Rate-limited public endpoint (10 submissions / minute per IP)
- Stores form name, originating page slug, field data (JSON), and submitter IP

### 📋 Activity Log

- Every significant admin action is recorded automatically (create, update, delete, publish, ban/unban)
- Admin timeline view at `/activity-log` — colored action badges, user attribution, IP address, timestamps
- Filterable by action type (All / Created / Updated / Deleted / Published)

### 🔒 Auth & Security

- Email + password login, rate-limited (5 attempts / 60 s)
- Forgot / reset password via email token
- Email verification required before accessing the dashboard
- Author-scoped post edit/delete (admins can override)
- Security headers on every response (`X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`, `X-XSS-Protection`)
- SSRF protection on webhook URLs — private/reserved IP ranges blocked
- Webhook secrets stored encrypted at rest

### 🪝 Webhooks

- CRUD at `/webhooks` (admin-only)
- Events: `post.published`, `post.updated`, `post.deleted`, `page.published`, `page.updated`, `page.deleted`
- HMAC-SHA256 request signature (`X-Lambda-Signature`) when a secret is set
- Dispatched via queued jobs; `last_triggered_at` tracked per webhook

### 🗓️ Editorial Calendar

- Month-view calendar showing published, scheduled, and draft posts by date
- Unscheduled drafts panel alongside the calendar
- Admins see all content; users see their own

### 📊 Dashboard

- Stats: total posts, published, scheduled, drafts, pending comments
- Upcoming scheduled posts (next 5)
- Recent posts with status badges

### ⚙️ Settings (Admin)

- Site name, URL, timezone, date format
- SEO: title separator, default meta description, OG image, keywords
- SMTP mail configuration with test-send button
- Media: max upload size, image resize width
- Comments: enabled toggle, items per page

### 🔌 REST API (Headless)

```
GET  /api/v1/posts
GET  /api/v1/posts/{slug}
GET  /api/v1/categories
GET  /api/v1/categories/{slug}
GET  /api/v1/tags
GET  /api/v1/tags/{slug}
POST /api/v1/query          ← block editor loop data source
```

### 🧙 Installer

- 5-step browser-based setup wizard: **Database → Site → Admin → Mail → Genre**
- Supports SQLite and MySQL; tests DB connection before writing `.env`
- Runs migrations, seeds system templates, and creates the first admin automatically
- **Genre / theme selection** — choose from 20 content themes (food, travel, tech, gaming, AI, …) to pre-seed 10 themed posts with placeholder images, or start empty

---

## 🚀 Getting Started

### Requirements

- PHP 8.2+
- Composer
- Node.js 18+ & npm
- SQLite (built into PHP) or MySQL 8+

### Install

```bash
git clone https://github.com/mariusberget92/lambda-cms.git
cd lambda-cms

composer install
npm install

cp .env.example .env
php artisan key:generate

npm run build
```

Then visit `/install` in your browser and complete the 5-step setup wizard.

### Local Development

```bash
php artisan serve   # or use Laravel Herd / Valet
npm run dev
```

---

## 🗺️ Roadmap

- [ ] Post import / export — JSON or Markdown
- [ ] Two-factor authentication — TOTP-based 2FA
- [ ] API write access — token-based auth for creating/updating content
- [ ] Multi-language / i18n content support
- [ ] Featured image in-browser crop before saving to media library
- [ ] Pagination block for the public frontend
- [ ] User-configurable accent color
- [ ] Import / export pages and templates

---

## 📄 License

MIT
