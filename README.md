# ⚡ Lambda CMS

> A modern, self-hosted CMS built with **Laravel 12**, **Inertia.js**, and **Vue 3**. Clean, fast, and developer-friendly — with a full-featured drag-and-drop block editor, media library, comment moderation, and a headless JSON API.

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
- **Categories & Tags** — Full CRUD, many-to-many on posts
- **Post scheduling** — Set a future publish date; posts go live automatically
- **Autosave** — Drafts are saved automatically while editing
- **Revisions** — Full revision history with one-click restore (up to 25 revisions)
- **Bulk actions** — Bulk publish, draft, or delete posts with author-scoped guards

### 🧱 Block Editor

30+ block types organized by purpose:

| Category | Blocks |
|---|---|
| 📄 Content | Paragraph, Heading, Image, Quote, Code, Gallery, Video, Divider, CTA, HTML |
| 🗂️ Layout | Section, Container (flex/grid), Spacer |
| 🔗 Interactive | Link (wrapper), Accordion, Tabs, Embed, Pagination |
| 🔄 Dynamic | Loop (posts/categories/tags/pages), Post List component |
| 📰 Post parts | Post Title, Post Body, Featured Image, Post Meta, Author, Categories & Tags, Comments |
| 🗃️ Archive | Archive Title, Archive Loop |
| 🔍 Utility | Search, Navigation |

**Editor features:**
- 🖱️ Drag-and-drop canvas with cross-list nesting
- 📋 Layers panel with infinite depth tree navigation
- 🎨 Per-block Style tab — typography, colors, background (solid/image/gradient), border, shadow, spacing
- 🔁 Dynamic field bindings (link block fields to loop data sources)
- ⚙️ Per-block Advanced settings — custom CSS classes, custom CSS, animation, font family, margin
- 👁️ Conditional block visibility (show/hide based on loop field values)
- 🏷️ Block labels / custom names shown in canvas and layers panel

### 🖼️ Media Library

- Upload images, videos, documents, and audio (configurable size limit, default 10 MB)
- Auto-resize images larger than 1920 px wide
- Edit alt text and description per file
- Bulk delete; admins see all files, users see their own
- UUID-based filenames with dimension tracking

### 🌐 Public Blog

- Paginated post listing with featured images, excerpts, and author info
- Single post view with full content, categories/tags, and comment section
- Category and tag archive pages
- Custom page rendering (fully block-based)
- RSS feed at `/feed` and XML sitemap at `/sitemap.xml`

### 💬 Comments

- Public submission with honeypot spam protection and rate limiting
- Admin moderation — approve, reject, or delete individually or in bulk
- Nested replies with email notifications to the commenter
- Per-post toggle to enable/disable comments
- Pending badge in the admin header

### 👥 Users & Roles

- Two roles: **administrator** and **user**
- Administrators manage all content, users, settings, and pages
- Users manage their own posts, categories, and tags
- Avatar upload per user profile
- Online status tracking (`last_seen_at`)
- Admin count enforcement — at least one admin must always exist

### 🔒 Auth & Security

- Email + password login, rate-limited (5 attempts / 60 s)
- Forgot / reset password via email token
- Email verification required before accessing the dashboard
- Author-scoped post edit/delete (admins can override)
- User invite flow — auto-generated password + welcome email

### 🗓️ Editorial Calendar

- Month-view calendar showing published, scheduled, and draft posts by date
- Unscheduled drafts panel alongside the calendar
- Admins see all content; users see their own

### 📊 Dashboard

- Stats: total posts, published, scheduled, drafts, pending comments
- Upcoming scheduled posts (next 5)
- Recent posts with status badges and quick actions

### ⚙️ Settings (Admin)

- Site name, URL, timezone, date format
- SEO: title separator, default meta description, OG image, keywords
- SMTP mail configuration with test-send button
- Media: max upload size, image resize width
- Comments: enabled toggle, items per page

### 🔌 REST API (Headless)

```
GET /api/v1/posts
GET /api/v1/posts/{slug}
GET /api/v1/categories
GET /api/v1/categories/{slug}
GET /api/v1/tags
GET /api/v1/tags/{slug}
POST /api/v1/query          ← block editor loop data source
```

### 🧙 Installer

- 4-step browser-based setup wizard: **Database → Site → Admin → Mail**
- Supports SQLite and MySQL
- Tests DB connection before writing `.env`
- Runs migrations, seeds defaults, and creates the first admin automatically

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

Then visit `/install` in your browser and complete the 4-step setup wizard.

### Local Development

```bash
php artisan serve   # or use Laravel Herd / Valet
npm run dev
```

---

## 🗺️ Roadmap

- [ ] Public full-text search across posts and pages
- [ ] Draft preview — shareable preview URL without publishing
- [ ] Post import/export — JSON or Markdown
- [ ] Two-factor authentication — TOTP-based 2FA
- [ ] API write access — token-based auth for creating/updating content
- [ ] Webhook support — fire events on publish/update for external integrations
- [ ] Multi-language / i18n content support
- [ ] Featured image in-browser crop before saving to media library
- [ ] More component block types — related posts, category grid, newsletter embed
- [ ] Tag/category typeahead — autocomplete when assigning to posts

---

## 📄 License

MIT
