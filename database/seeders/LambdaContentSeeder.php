<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds a fresh Lambda CMS installation with a small set of polished,
 * real-content posts that showcase the platform and make it look alive.
 * Designed for production demos and first-launch installs.
 */
class LambdaContentSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::role('administrator')->first();
        if (! $admin) {
            return;
        }

        // ── Categories ────────────────────────────────────────────────────────
        $categories = [
            'open-source' => $this->cat('Open Source', '#88c0d0', 'Open source software, community, and collaboration.'),
            'features' => $this->cat('Features', '#81a1c1', 'Deep dives into Lambda CMS features and capabilities.'),
            'getting-started' => $this->cat('Getting Started', '#a3be8c', 'Installation guides, quick starts, and first steps.'),
            'design' => $this->cat('Design', '#b48ead', 'Design systems, UI patterns, and visual craftsmanship.'),
            'release-notes' => $this->cat('Release Notes', '#ebcb8b', 'Changelogs and version announcements.'),
        ];

        // ── Tags ──────────────────────────────────────────────────────────────
        $tags = [
            'laravel' => $this->tag('laravel'),
            'vue' => $this->tag('vue'),
            'inertia' => $this->tag('inertia'),
            'tailwind' => $this->tag('tailwind'),
            'block-editor' => $this->tag('block-editor'),
            'open-source' => $this->tag('open-source'),
            'cms' => $this->tag('cms'),
            'seo' => $this->tag('seo'),
            'templates' => $this->tag('templates'),
            'design-system' => $this->tag('design-system'),
        ];

        // ── Posts ─────────────────────────────────────────────────────────────
        $posts = [
            [
                'title' => 'Introducing Lambda CMS',
                'slug' => 'introducing-lambda-cms',
                'excerpt' => 'Lambda CMS is an open-source blog CMS built on Laravel 12, Vue 3, and Inertia.js — designed for developers who want full control without sacrificing a beautiful editing experience.',
                'body' => $this->postIntroducing(),
                'body_format' => 'markdown',
                'categories' => ['open-source'],
                'tags' => ['laravel', 'vue', 'inertia', 'open-source', 'cms'],
                'featured' => true,
            ],
            [
                'title' => 'The Block Editor: Build Any Layout Without Code',
                'slug' => 'block-editor-build-any-layout',
                'excerpt' => 'Lambda CMS ships with a powerful block-based page editor. Sections, containers, loops, partials — every layout you can imagine, built visually and stored as clean JSON.',
                'body' => $this->postBlockEditor(),
                'body_format' => 'markdown',
                'categories' => ['features'],
                'tags' => ['block-editor', 'templates', 'cms'],
                'featured' => true,
            ],
            [
                'title' => 'Installing Lambda CMS in Under 5 Minutes',
                'slug' => 'installing-lambda-cms',
                'excerpt' => 'A step-by-step walkthrough of the browser-based installer — from cloning the repo to your first published post.',
                'body' => $this->postInstalling(),
                'body_format' => 'markdown',
                'categories' => ['getting-started'],
                'tags' => ['laravel', 'open-source', 'cms'],
                'featured' => false,
            ],
            [
                'title' => 'Design Tokens: How the Lambda CMS Frontend Stays Consistent',
                'slug' => 'design-tokens-frontend-system',
                'excerpt' => 'Every colour, border, and radius in the Lambda CMS blog frontend flows through a small set of CSS custom properties. Here\'s how the system works and why it matters.',
                'body' => $this->postDesignTokens(),
                'body_format' => 'markdown',
                'categories' => ['design'],
                'tags' => ['tailwind', 'design-system', 'vue'],
                'featured' => false,
            ],
            [
                'title' => 'Lambda CMS v1.0 — Release Notes',
                'slug' => 'lambda-cms-v1-release-notes',
                'excerpt' => 'The first stable release of Lambda CMS. What shipped, what we cut, and what\'s coming next.',
                'body' => $this->postReleaseNotes(),
                'body_format' => 'markdown',
                'categories' => ['release-notes', 'open-source'],
                'tags' => ['open-source', 'laravel', 'vue', 'cms'],
                'featured' => false,
            ],
        ];

        foreach ($posts as $i => $p) {
            if (Post::where('slug', $p['slug'])->exists()) {
                Post::where('slug', $p['slug'])->update([
                    'body' => $p['body'],
                    'body_format' => 'markdown',
                ]);

                continue;
            }

            $post = Post::create([
                'user_id' => $admin->id,
                'title' => $p['title'],
                'slug' => $p['slug'],
                'excerpt' => $p['excerpt'],
                'body' => $p['body'],
                'body_format' => $p['body_format'],
                'status' => 'published',
                'featured' => $p['featured'],
                'published_at' => now()->subDays(count($posts) - $i),
            ]);

            $catIds = array_map(fn ($k) => $categories[$k]->id, $p['categories']);
            $tagIds = array_map(fn ($k) => $tags[$k]->id, $p['tags']);
            $post->categories()->sync($catIds);
            $post->tags()->sync($tagIds);
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function cat(string $name, string $color, string $description): Category
    {
        return Category::firstOrCreate(
            ['slug' => Str::slug($name)],
            ['name' => $name, 'color' => $color, 'description' => $description]
        );
    }

    private function tag(string $name): Tag
    {
        return Tag::firstOrCreate(
            ['slug' => $name],
            ['name' => $name]
        );
    }

    // ── Post bodies (Markdown) ────────────────────────────────────────────────

    private function postIntroducing(): string
    {
        return <<<'MD'
We built Lambda CMS because we wanted something that didn't make us choose between developer control and editorial simplicity. Every CMS we tried either locked us into opinionated templates, required heavy JavaScript frameworks to do basic things, or made customising the frontend feel like surgery.

Lambda CMS takes a different approach. It's a **self-hosted, open-source blog CMS** that puts the block editor front and centre — but keeps the underlying data clean, the API headless-friendly, and the frontend entirely token-driven.

## The Stack

Lambda CMS is built on four technologies that we believe are the right defaults for a modern web application:

| Layer | Technology |
|---|---|
| Backend | Laravel 12 |
| Frontend | Vue 3 + Composition API |
| Routing | Inertia.js v2 |
| Styling | Tailwind CSS 4 |

- **Laravel 12** — battle-tested PHP backend with a clean ORM, queue system, and first-class scheduling.
- **Vue 3** — reactive component model with the Composition API. No class components, no lifecycle soup.
- **Inertia.js v2** — server-driven routing without a full API. Controllers return typed page props directly to Vue components.
- **Tailwind CSS 4** — utility-first styling with a single-token design system for both the admin and the blog frontend.

## What Ships Out of the Box

A fresh Lambda CMS install gives you a complete blogging platform:

- A block-based page editor with 30+ block types
- Full post, category, tag, and comment management
- A media library with auto-resizing and alt text
- A template system for blog index, single post, archive, and search pages
- RSS feed at `/feed` and XML sitemap at `/sitemap.xml`
- A headless REST API at `/api/v1/`
- A browser-based installer
- Role-based access control (administrator / user)
- Webhook support with HMAC signatures
- Markdown editor with GitHub-Flavored Markdown support

## Why Open Source?

Because the best tools get better when more people use them and push on them. Lambda CMS is MIT licensed — use it for your personal blog, your agency's client sites, or fork it and build something completely new. We'll keep maintaining it, shipping improvements, and listening to the community.

> The source is on GitHub. If you find a bug, open an issue. If you build something cool with it, we'd love to see it.
MD;
    }

    private function postBlockEditor(): string
    {
        return <<<'MD'
The block editor is the heart of Lambda CMS. Every page, post, and template in the system is stored as a JSON array of blocks — and the same editor is used whether you're writing a blog post, configuring your header, or building a custom archive page.

## The Block Model

Each block has a `type`, a `data` object, an optional `children` array, and an optional `bindings` map. That's it. The renderer in Vue reads the tree and maps each type to its component. Simple at the core, infinitely composable in practice.

```json
{
  "type": "heading",
  "data": {
    "text": "Hello world",
    "level": 2
  }
}
```

## Block Types

Lambda CMS ships with over 30 block types, organised into groups:

| Group | Blocks |
|---|---|
| **Content** | Heading, Paragraph, Quote, Divider, Code, HTML |
| **Media** | Image, Gallery, Video, Embed |
| **Layout** | Section, Container, Spacer |
| **Interactive** | Button, CTA, Accordion, Tabs, Search, Filter Link |
| **Data** | Loop, Pagination, Post Card, Post Title, Post Body, Post Meta |
| **Navigation** | Nav Header, Site Footer, Navigation |
| **Partials** | Template (embed another template by ID) |

## The Loop Block

The most powerful block is the **Loop block**. It fetches data from a source (`posts`, `categories`, `tags`), applies filters and sorting, and renders its child blocks once per item — injecting the item's data via a Vue `provide/inject` context.

Child blocks use **bindings** to map their fields to the loop context. For example, a Filter Link block inside a loop of categories can bind its `label` field to `loop:name` — so it automatically shows each category's name.

## Templates and Partials

Any collection of blocks can be saved as a named template. The system ships with default templates for every blog page type — but you can customise them fully in the block editor, or create custom ones.

A **Partial** template (like the Post Card) can be embedded into other templates via the Template block, letting you update the card design in one place and have it propagate everywhere.

## Custom CSS and Classes

Every block has optional `customClasses` and `customCss` fields. Drop in a Tailwind class to adjust spacing, or write a scoped CSS rule for a one-off visual treatment. The design system's CSS tokens are always in scope.
MD;
    }

    private function postInstalling(): string
    {
        return <<<'MD'
Lambda CMS uses a browser-based installer so you don't need to touch the command line to get started. Here's the full process from clone to first post.

## Requirements

- PHP 8.2 or higher
- Composer
- Node.js 20+ and npm
- SQLite (zero-config) or MySQL 8+

## Step 1: Clone and Install

```bash
git clone https://github.com/mariusberget92/lambda-cms.git
cd lambda-cms
composer install
npm install && npm run build
```

## Step 2: Environment File

```bash
cp .env.example .env
php artisan key:generate
```

Open `.env` and set `APP_URL` to your domain. If you're using SQLite (the default), no further database config is needed.

## Step 3: Open the Installer

Start a local server and navigate to your site. If Lambda CMS hasn't been installed yet, you'll be automatically redirected to `/install`. The four-step wizard will guide you through:

1. **Database** — choose SQLite or MySQL and verify the connection
2. **Site** — set your site name and URL
3. **Admin** — create your administrator account
4. **Mail** — configure SMTP or leave as log driver for now

After the mail step, migrations run automatically, the database is seeded with the Lambda CMS showcase posts, and your admin account is created. You're redirected straight to the dashboard.

## Step 4: Log In

Sign in with the admin credentials you just created. You'll land on the dashboard, and your blog is live at `/blog`.

## Production Deployment

For a production server, run the full optimisation stack after deploying:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
npm run build
```

Set up a cron job to run the Laravel scheduler every minute to handle scheduled post publishing:

```
* * * * * cd /path/to/lambda-cms && php artisan schedule:run >> /dev/null 2>&1
```
MD;
    }

    private function postDesignTokens(): string
    {
        return <<<'MD'
The Lambda CMS blog frontend is styled entirely through CSS custom properties — no hardcoded hex values, no scattered magic numbers. Every block component reads from the same small token vocabulary.

## The Token Set

Twelve tokens cover everything the frontend needs:

| Token | Purpose |
|---|---|
| `--bg` | Background surface (page canvas) |
| `--panel` | Raised surface (cards, header, footer) |
| `--ink` | Primary text |
| `--soft` | Secondary / muted text |
| `--line` | Subtle separator (low contrast) |
| `--line-strong` | Visible separator (borders, dividers) |
| `--accent` | Interactive colour (links, buttons, active states) |
| `--accent-ink` | Text on accent backgrounds |
| `--code` | Dark panel background (code blocks) |
| `--code-ink` | Text on dark panels |
| `--blog-radius` | Border radius (applied consistently to all cards) |
| `--gutter` | Horizontal rhythm spacing |

## Why Not Tailwind Utilities?

The admin panel uses Tailwind's `--primary`, `--muted`, and `--border` tokens. Those tokens don't exist in the blog scope — the blog renders inside a `.lambda-blog-root` element that defines its own token cascade. Using admin tokens in blog block components would cause colours to resolve to the wrong values depending on the current theme.

By building an explicit, narrow token set for the blog, every block component is guaranteed to render consistently regardless of which Tailwind theme is applied to the admin.

## Accent Color from Settings

The admin settings panel exposes an accent colour picker. When saved, the chosen colour is stored in the database and shared to every Inertia page as `accentColor`. The blog layout applies it via `watchEffect`:

```js
watchEffect(() => {
  const color = page.props.accentColor
  if (color && /^#[0-9a-fA-F]{6}$/.test(color)) {
    document.documentElement.style.setProperty('--accent', color)
    document.documentElement.style.setProperty('--accent-ink', '#ffffff')
  }
})
```

Every component that reads `var(--accent)` updates instantly — buttons, links, post card hover states, the active filter indicator, the masthead accent rule — all without a page reload.

## The Teleport Problem

Vue's `<Teleport to="body">` moves elements outside the `.lambda-blog-root` DOM tree. CSS custom properties only cascade downward, so teleported elements like the search overlay would lose access to the blog tokens.

Lambda CMS solves this with a companion class `.lambda-blog-tokens` that carries the same token definitions but no base styles — applied directly to the overlay div.
MD;
    }

    private function postReleaseNotes(): string
    {
        return <<<'MD'
Lambda CMS 1.0 is the first stable, publicly available release. Here's a summary of what shipped, what we decided to cut, and where we're heading.

## What Shipped

### Core Platform

- Full post, page, category, tag, and comment management
- Media library with auto-resize, alt text, and bulk operations
- Scheduled post publishing via Laravel scheduler (Pro)
- Draft autosave and revision history (up to 25 revisions)
- Role-based access control: administrator and user roles
- User banning with optional expiry

### Block Editor

- 30+ block types covering content, layout, media, and data
- Loop block with dynamic filtering, sorting, and pagination
- Template system for all blog page types
- Partial templates with cross-template embedding
- Custom CSS and class overrides per block
- Field bindings for loop-driven dynamic content

### Writing Modes

- Rich text editor (Tiptap) for WYSIWYG editing
- Markdown editor with GitHub-Flavored Markdown (CommonMark + GFM)
- Import `.md` files directly into the editor
- Full prose typography for rendered Markdown output

### Blog Frontend

- Blog index with category/tag filtering and pagination
- Single post view with comments
- Category and tag archive pages
- Full-text search (title, excerpt, body)
- RSS feed and XML sitemap
- Token-based draft preview URLs

### Developer Features

- Headless REST API (`/api/v1/`) for posts, categories, and tags
- Webhook system with HMAC-SHA256 signatures (Pro)
- Browser-based 4-step installer
- Accent colour theming via admin settings
- VitePress documentation site

### Pro Tier

- Scheduled publishing
- Content calendar
- Webhooks
- Custom JS injection

## What We Cut for 1.0

Some features were scoped out to keep the 1.0 release focused:

- **Two-factor authentication** — planned for 1.1
- **Multi-language support** — English-only for now
- **API write access** — read-only in 1.0; token-based writes coming
- **In-browser image cropping** — upload is straight resize only

## What's Next

The roadmap for 1.x focuses on hardening and expanding the block editor: more interactive block types, a drag-to-reorder column system, and richer loop filter conditions. We're also planning a public theme registry so designers can share block editor templates.

If you want to contribute, the repository is open. Check the issues tab for anything tagged `good first issue`.
MD;
    }
}
