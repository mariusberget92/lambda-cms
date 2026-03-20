# Lambda CMS

A modern, self-hosted blog and content management system built with Laravel 12, Inertia.js, and Vue 3. Designed for developers who want a clean, fast CMS with a great editing experience — including a drag-and-drop block editor, media library, comment moderation, and a headless JSON API.

## Stack

| Layer | Technology |
|-------|-----------|
| Backend | Laravel 12, PHP 8.2+ |
| Frontend | Vue 3, Inertia.js 2, Tailwind CSS 4, Vite 7 |
| UI | shadcn-vue, reka-ui, lucide-vue-next |
| Rich text | Tiptap |
| Drag & drop | vue-draggable-plus |
| Database | SQLite (default) or MySQL |

## Features

### Content
- **Posts** — Rich text editor (Tiptap) or drag-and-drop block editor with 11 block types
- **Block editor** — Paragraph, Heading, Image, Quote, Code, Gallery, Video, Divider, CTA, Component (dynamic post list with filters), and HTML (admin only)
- **Pages** — Custom static pages built with the same block editor
- **Categories & Tags** — Full CRUD, attached to posts
- **Post scheduling** — Set a future publish date; posts go live automatically
- **Bulk actions** — Bulk publish, draft, or delete posts

### Media
- **Media library** — Upload images, videos, documents, and audio (up to 10 MB)
- **Image processing** — Auto-resizes large images (max 1920 px) via Intervention Image
- **Metadata** — Edit alt text and description per file; bulk delete

### Public Blog
- Paginated post listing with featured images, excerpts, and author info
- Single post view with full content, categories/tags, and comment section
- Category and tag archive pages
- Custom page rendering (block-based)
- RSS feed at `/feed` and XML sitemap at `/sitemap.xml`
- Headless-ready JSON API (v1)

### Comments
- Public submission with honeypot spam protection and rate limiting
- Admin moderation — approve, reject, or delete individually or in bulk
- Per-post toggle to enable/disable comments
- Email notification to admin on new comment submission

### Users & Roles
- Two roles: **administrator** and **user**
- Administrators manage all content, users, settings, and pages
- Users manage their own posts, categories, and tags
- Avatar upload per user
- Email verification required before accessing the dashboard

### Navigation Builder
- Drag-to-reorder menu editor
- Add custom links or links to published pages
- Managed at `/navigation`

### Post Calendar
- Month-view calendar for post planning
- Color-coded dots for published, scheduled, and draft posts
- Unscheduled drafts panel alongside the calendar

### Dashboard
- Stats: total posts, published, scheduled, drafts, pending comments
- Upcoming scheduled posts list
- Recent posts with status badges
- Quick action buttons

### Settings
- Site name, SEO meta defaults, OG image URL, title separator
- SMTP email configuration with test-send
- Comment pagination and moderation settings

### API (Headless)
```
GET /api/v1/posts
GET /api/v1/posts/{slug}
GET /api/v1/categories
GET /api/v1/categories/{slug}
GET /api/v1/tags
GET /api/v1/tags/{slug}
```

### Auth & Security
- Email + password login, rate-limited (5 attempts / 60 s)
- Forgot/reset password via email
- Email verification flow
- Author-scoped post edit/delete (admins can override)

### Installer
- 4-step browser-based setup wizard: database → site name → admin user → mail
- Supports SQLite and MySQL
- Runs migrations, seeds defaults, and creates the first admin automatically

## Getting Started

### Requirements

- PHP 8.2+
- Composer
- Node.js 18+ & npm
- SQLite (built into PHP) or MySQL

### Install

```bash
git clone https://github.com/your-username/lambda-cms.git
cd lambda-cms

composer install
npm install

cp .env.example .env
php artisan key:generate

npm run build
```

Visit `/install` in your browser and complete the 4-step setup wizard.

### Local development

```bash
php artisan serve   # or use Laravel Herd / Valet
npm run dev
```

## Todo

- [ ] Post revisions — track edit history and allow rollback
- [ ] Tag/category typeahead — autocomplete when assigning to posts
- [ ] More component block types — related posts, category grid, newsletter signup embed
- [ ] Featured image crop — in-browser crop before saving to media library
- [ ] Public search — full-text search across published posts and pages
- [ ] Draft preview — shareable preview URL for drafts without publishing
- [ ] Post import/export — JSON or Markdown
- [ ] Two-factor authentication — TOTP-based 2FA for admin accounts
- [ ] API write access — token-based auth for creating/updating content via API
- [ ] Webhook support — fire events on publish/update for external integrations
- [ ] Multi-language support — content in multiple languages
- [ ] Dark mode preference — persist theme choice in user profile

## License

MIT
