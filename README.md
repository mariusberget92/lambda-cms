<div align="center">

# ⚡ Lambda CMS

**A modern, self-hosted CMS built for developers.**
Block editor · In-browser image editor · 2FA · Headless API · No cloud lock-in.

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20.svg?logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4.svg?logo=php&logoColor=white)](https://php.net)
[![Vue](https://img.shields.io/badge/Vue-3-42b883.svg?logo=vue.js&logoColor=white)](https://vuejs.org)

**[📖 Read the Docs →](https://lambdacms.darkleaks.net)**

</div>

---

## 🤔 What is Lambda CMS?

Lambda CMS is a clean, fast, fully self-hosted content management system. No subscriptions, no SaaS lock-in — you own your data and your stack. Built with **Laravel 12**, **Vue 3**, and **Inertia.js**, it feels like a modern SPA while keeping the server-side simplicity of a traditional Laravel app.

## 🧰 Tech Stack

| Layer | Technology |
|---|---|
| 🔧 Backend | Laravel 12, PHP 8.2+ |
| 🎨 Frontend | Vue 3 (Composition API), Inertia.js 2, Tailwind CSS 4, Vite 7 |
| 🧩 UI | shadcn-vue, reka-ui, Lucide icons |
| ✍️ Rich Text | Tiptap |
| 🗄️ Database | SQLite (default) or MySQL |

---

## ✨ Features

### 📝 Content Management

- **Posts** — full CRUD with Tiptap rich text or the drag-and-drop block editor
- **Pages** — static pages built entirely with the block editor
- **Categories & Tags** — many-to-many on posts; categories support custom colors
- **Scheduled publishing** — set a future date and posts go live automatically via the scheduler
- **Autosave** — drafts save quietly in the background while you write
- **Revisions** — full history with one-click restore (up to 25 per post/page)
- **Draft previews** — shareable token-based URLs, no login required
- **Bulk actions** — publish, draft, or delete multiple posts at once

### 🧱 Block Editor

30+ block types across six categories:

| Category | Blocks |
|---|---|
| 📄 Content | Paragraph, Heading, Quote, Code, Divider, Spacer, HTML, Accordion, Tabs, Embed |
| 🖼️ Media | Image, Gallery, Video |
| 📐 Layout | Section, Container (flex / grid / inline-flex) |
| 🖱️ Interactive | Button, CTA, Link, Navigation, Search, Filter Link, Active Filter, Icon List |
| 📊 Data | Loop, Pagination, Post Card, Post Title, Post Body, Post Featured Image, Post Meta, Post Author, Post Taxonomy, Post Comments, Archive Title |
| 🌐 Site | Nav Header, Site Footer, Masthead, Band, Template |

**What makes it nice to use:**

- 🖱️ Drag-and-drop canvas with cross-list nesting and a layers panel tree
- 🎨 Per-block Style tab — typography, colors, background (solid / gradient / image), border, shadow, spacing
- 🔗 Dynamic field bindings — link block content to loop or post-context data sources
- 💻 Per-block Advanced settings — custom CSS classes, inline CSS, font family
- 👁️ Conditional block visibility based on loop field values
- 🏷️ Block labels shown in canvas and layers panel

### 🖼️ Media Library & Image Editor

- Upload images, documents, video, and audio (configurable size limit, default 10 MB)
- **In-browser image editor** — crop with 9 aspect ratio presets, rotate, flip, 8 filter presets (Normal, Vivid, Muted, B&W, Warm, Cool, Fade, Drama), and manual brightness / contrast / saturation / blur controls. Opens before upload and on any existing library item.
- Auto-resize images larger than the configured limit (default 1920 px wide)
- Alt text and description per file; UUID-based filenames with dimension tracking
- Admins see all files; users see their own

### 🎨 Template System

- **7 pre-shipped system templates** — Post Card, Default Blog Index, Default Single Post, Default Archive, Default Search Results, Default Header, Default Footer
- Custom templates share the same block editor, autosave, and revisions
- Loop blocks correctly inherit context for dynamic bindings

### 🌍 Public Frontend

- Every public page rendered from editable block templates
- Header and footer rendered from dedicated block templates
- Blog index, single post, category/tag archives, and full-text search
- RSS feed at `/feed` and XML sitemap at `/sitemap.xml`
- Design token system — accent color applies live to both admin and blog
- Admin bar visible to authenticated users only

### 💬 Comments

- Public submission with honeypot spam protection and rate limiting
- Admin moderation — approve, reject, or delete individually or in bulk
- Nested replies with email notifications
- Per-post toggle to enable/disable comments

### 👥 Users & Roles

- Two roles: **administrator** and **user**
- Admins manage all content, users, settings, pages, and templates
- Users manage their own posts, categories, tags, and media
- Avatar upload per profile
- User invite flow — auto-generated password + welcome email
- User banning with reason and optional expiry (auto-lifted on next login)
- Online status tracking

### 🔒 Auth & Security

- Email + password login with rate limiting (5 attempts / 60 s per email + IP)
- **Two-factor authentication** — TOTP-based 2FA compatible with Google Authenticator, Authy, 1Password, and any RFC 6238 app. Includes 8 single-use recovery codes.
- Forgot / reset password via email token
- Email verification required before dashboard access
- Author-scoped post and media access

### 🔔 Webhooks

- Events: `post.published`, `post.updated`, `post.deleted`, `page.published`, `page.updated`, `page.deleted`
- HMAC-SHA256 request signature (`X-Lambda-Signature`) when a secret is set
- Dispatched via queued jobs; `last_triggered_at` tracked per webhook

### 📅 Editorial Calendar

- Month-view calendar with published, scheduled, and draft posts
- Unscheduled drafts panel alongside the calendar
- Admins see all; users see their own

### ⚙️ Settings

- Site name, URL, timezone, and date format
- SEO defaults (meta description, OG image, keywords, title separator)
- SMTP mail with test-send
- Media upload limits and image resize threshold
- Comments (enabled toggle, items per page)
- Appearance: accent color applied live to admin and blog frontend
- Custom JS injection on every public page

### 🔌 REST API (Headless)

```
GET  /api/v1/posts
GET  /api/v1/posts/{slug}
GET  /api/v1/categories
GET  /api/v1/categories/{slug}
GET  /api/v1/tags
GET  /api/v1/tags/{slug}
POST /api/v1/query        ← block editor loop data source
```

### 🧙 Installer

- 5-step browser wizard: **Database → Site → Admin → Mail → Genre**
- Supports SQLite and MySQL; tests the DB connection before writing `.env`
- Runs migrations, seeds system templates, and creates the first admin account
- 20 genre/theme options to pre-seed themed demo posts, or start completely empty

---

## 🚀 Getting Started

### Requirements

- PHP 8.2+
- Composer
- Node.js 20+ and npm
- SQLite (bundled with PHP) or MySQL 8+

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

Open the site in a browser — you'll be redirected to `/install` to run the setup wizard.

### Local Development

```bash
php artisan serve
npm run dev
```

### Production

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
npm run build
```

Add a cron entry to run the scheduler (handles auto-publishing):

```
* * * * * cd /path/to/lambda-cms && php artisan schedule:run >> /dev/null 2>&1
```

---

## 🗺️ Roadmap

- [ ] Post and template import / export
- [ ] API write access — token-based auth for creating and updating content
- [ ] Multi-language / i18n content support

---

## 📄 License

MIT — do whatever you want with it. ✌️
