# Introduction

Lambda CMS is a modern, self-hosted content management system built for bloggers and content creators. It combines a clean admin interface with a powerful block-based page builder, giving you full control over both content and presentation — without needing a developer.

## What you can do

- **Write posts and pages** using either a familiar rich-text editor or a drag-and-drop block editor.
- **Build custom layouts** with 30+ block types: text, images, video, code, tables, loops, navigations, and more.
- **Create reusable templates** for your blog index, single post view, archive pages, and search results.
- **Manage your team** with role-based access control, user invitations, and banning.
- **Moderate comments** with built-in spam protection, rate limiting, and bulk actions.
- **Automate publishing** by scheduling posts to go live at a future date and time.
- **Connect external services** via webhooks with optional HMAC-SHA256 request signing.
- **Power any frontend** using the public headless JSON API.

## Tech stack

Lambda CMS is built on a modern PHP and JavaScript stack:

| Layer | Technology |
|---|---|
| Backend | Laravel 12, PHP 8.2+ |
| Frontend | Vue 3 (Composition API), Inertia.js 2 |
| Build | Vite 7 |
| CSS | Tailwind CSS 4 |
| Database | SQLite (default) or MySQL 8+ |
| Auth | Laravel auth + Spatie Permission 7.2 |
| Rich text | Tiptap 3 |
| Drag & drop | vue-draggable-plus |

## Project structure

```
lambda-cms/
├── app/                  # Laravel application (models, controllers, services)
├── resources/js/         # Vue 3 components and pages (Inertia)
├── routes/               # web.php and api.php
├── database/             # Migrations, seeders, factories
├── config/               # Laravel config files
└── docs/                 # This documentation site
```
