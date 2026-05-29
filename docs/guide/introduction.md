# Introduction

Lambda CMS is a self-hosted, open-source blog content management system built on **Laravel 12**, **Vue 3**, and **Inertia.js**. It combines a developer-friendly backend with a visual block editor that lets you build any page layout without writing HTML.

## Why Lambda CMS?

Most CMSs make you choose between control and convenience. Lambda CMS doesn't. You get:

- A **block-based page editor** for posts, pages, and every blog template
- A **headless REST API** so you can consume your content from anywhere
- A clean **Laravel backend** you can extend like any other Laravel application
- A **token-driven blog frontend** that applies your brand colors with zero CSS overrides

## The Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 12, PHP 8.2+ |
| Frontend | Vue 3, Inertia.js 2, Tailwind CSS 4, Vite 7 |
| Database | SQLite (default) or MySQL 8+ |
| Auth | Laravel built-in + Spatie Permission (roles: `administrator`, `user`) |

## Key Concepts

**Blocks** — the unit of content. Every post, page, and template is stored as a JSON array of blocks. Each block has a `type`, a `data` object, and an optional `children` array.

**Templates** — named collections of blocks for a specific page type (`blog-index`, `single-post`, `archive`, `search-results`, `header`, `footer`, `partial`). The active published template for each type is rendered automatically on the corresponding public page.

**Loop block** — fetches data from a source (`posts`, `categories`, `tags`, `pages`), applies filters and sorting, and renders its child blocks once per item — with full dynamic field binding.

**Partials** — a `partial` template can be embedded in any other template via the Template block, letting you update a component (like the Post Card) in one place and have it propagate everywhere.

## What Ships Out of the Box

- Full CRUD for posts, pages, categories, tags, and comments
- Media library with auto-resize and alt text
- 7 pre-built system templates (blog index, single post, archive, search, header, footer, post card)
- RSS feed (`/feed`) and XML sitemap (`/sitemap.xml`)
- Role-based access control (administrator / user)
- Webhooks with HMAC signatures
- Draft preview URLs (shareable, no login required)
- Post scheduling with automatic publishing via Laravel scheduler
- Autosave and revision history (up to 25 revisions)
- Browser-based 4-step installer

## Next Steps

- [Install Lambda CMS →](/guide/installation)
- [Explore the block editor →](/guide/block-editor)
- [Browse the REST API →](/api/overview)
