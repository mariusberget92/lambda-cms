<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;

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
        if (!$admin) return;

        // ── Categories ────────────────────────────────────────────────────────
        $categories = [
            'open-source'   => $this->cat('Open Source',   '#88c0d0', 'Open source software, community, and collaboration.'),
            'features'      => $this->cat('Features',      '#81a1c1', 'Deep dives into Lambda CMS features and capabilities.'),
            'getting-started' => $this->cat('Getting Started', '#a3be8c', 'Installation guides, quick starts, and first steps.'),
            'design'        => $this->cat('Design',        '#b48ead', 'Design systems, UI patterns, and visual craftsmanship.'),
            'release-notes' => $this->cat('Release Notes', '#ebcb8b', 'Changelogs and version announcements.'),
        ];

        // ── Tags ──────────────────────────────────────────────────────────────
        $tags = [
            'laravel'      => $this->tag('laravel'),
            'vue'          => $this->tag('vue'),
            'inertia'      => $this->tag('inertia'),
            'tailwind'     => $this->tag('tailwind'),
            'block-editor' => $this->tag('block-editor'),
            'open-source'  => $this->tag('open-source'),
            'cms'          => $this->tag('cms'),
            'seo'          => $this->tag('seo'),
            'templates'    => $this->tag('templates'),
            'design-system' => $this->tag('design-system'),
        ];

        // ── Posts ─────────────────────────────────────────────────────────────
        $posts = [
            [
                'title'      => 'Introducing Lambda CMS',
                'slug'       => 'introducing-lambda-cms',
                'excerpt'    => 'Lambda CMS is an open-source blog content management system built on Laravel 12, Vue 3, and Inertia.js — designed for developers who want full control without sacrificing a beautiful editing experience.',
                'body'       => $this->postIntroducing(),
                'categories' => ['open-source'],
                'tags'       => ['laravel', 'vue', 'inertia', 'open-source', 'cms'],
                'featured'   => true,
            ],
            [
                'title'      => 'The Block Editor: Build Any Layout Without Code',
                'slug'       => 'block-editor-build-any-layout',
                'excerpt'    => 'Lambda CMS ships with a powerful block-based page editor. Sections, containers, loops, partials — every layout you can imagine, built visually and stored as clean JSON.',
                'body'       => $this->postBlockEditor(),
                'categories' => ['features'],
                'tags'       => ['block-editor', 'templates', 'cms'],
                'featured'   => true,
            ],
            [
                'title'      => 'Installing Lambda CMS in Under 5 Minutes',
                'slug'       => 'installing-lambda-cms',
                'excerpt'    => 'A step-by-step walkthrough of the browser-based installer — from cloning the repo to your first published post.',
                'body'       => $this->postInstalling(),
                'categories' => ['getting-started'],
                'tags'       => ['laravel', 'open-source', 'cms'],
                'featured'   => false,
            ],
            [
                'title'      => 'Design Tokens: How the Lambda CMS Frontend Stays Consistent',
                'slug'       => 'design-tokens-frontend-system',
                'excerpt'    => 'Every colour, border, and radius in the Lambda CMS blog frontend flows through a small set of CSS custom properties. Here\'s how the system works and why it matters.',
                'body'       => $this->postDesignTokens(),
                'categories' => ['design'],
                'tags'       => ['tailwind', 'design-system', 'vue'],
                'featured'   => false,
            ],
            [
                'title'      => 'Lambda CMS v1.0 — Release Notes',
                'slug'       => 'lambda-cms-v1-release-notes',
                'excerpt'    => 'The first stable release of Lambda CMS. What shipped, what we cut, and what\'s coming next.',
                'body'       => $this->postReleaseNotes(),
                'categories' => ['release-notes', 'open-source'],
                'tags'       => ['open-source', 'laravel', 'vue', 'cms'],
                'featured'   => false,
            ],
        ];

        foreach ($posts as $i => $p) {
            if (Post::where('slug', $p['slug'])->exists()) continue;

            $post = Post::create([
                'user_id'      => $admin->id,
                'title'        => $p['title'],
                'slug'         => $p['slug'],
                'excerpt'      => $p['excerpt'],
                'body'         => $p['body'],
                'status'       => 'published',
                'featured'     => $p['featured'],
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
            ['slug' => \Illuminate\Support\Str::slug($name)],
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

    // ── Post bodies ───────────────────────────────────────────────────────────

    private function postIntroducing(): string
    {
        return <<<'HTML'
<p>We built Lambda CMS because we wanted something that didn't make us choose between developer control and editorial simplicity. Every CMS we tried either locked us into opinionated templates, required heavy JavaScript frameworks to do basic things, or made customising the frontend feel like surgery.</p>

<p>Lambda CMS takes a different approach. It's a <strong>self-hosted, open-source blog CMS</strong> that puts the block editor front and centre — but keeps the underlying data clean, the API headless-friendly, and the frontend entirely token-driven.</p>

<h2>The Stack</h2>

<p>Lambda CMS is built on four technologies that we believe are the right defaults for a modern web application in 2024:</p>

<ul>
  <li><strong>Laravel 12</strong> — battle-tested PHP backend with a clean ORM, queue system, and first-class scheduling.</li>
  <li><strong>Vue 3</strong> — reactive component model with the Composition API. No class components, no lifecycle soup.</li>
  <li><strong>Inertia.js v2</strong> — server-driven routing without a full API. Controllers return typed page props directly to Vue components.</li>
  <li><strong>Tailwind CSS 4</strong> — utility-first styling with a single-token design system for both the admin and the blog frontend.</li>
</ul>

<h2>What Ships Out of the Box</h2>

<p>A fresh Lambda CMS install gives you a complete blogging platform:</p>

<ul>
  <li>A block-based page editor with 30+ block types</li>
  <li>Full post, category, tag, and comment management</li>
  <li>A media library with auto-resizing and alt text</li>
  <li>A template system for blog index, single post, archive, and search pages</li>
  <li>RSS feed at <code>/feed</code> and XML sitemap at <code>/sitemap.xml</code></li>
  <li>A headless REST API at <code>/api/v1/</code></li>
  <li>A browser-based 5-step installer</li>
  <li>Role-based access control (administrator / user)</li>
  <li>Webhook support with HMAC signatures</li>
</ul>

<h2>Why Open Source?</h2>

<p>Because the best tools get better when more people use them and push on them. Lambda CMS is MIT licensed — use it for your personal blog, your agency's client sites, or fork it and build something completely new. We'll keep maintaining it, shipping improvements, and listening to the community.</p>

<p>The source is on GitHub. If you find a bug, open an issue. If you build something cool with it, we'd love to see it.</p>
HTML;
    }

    private function postBlockEditor(): string
    {
        return <<<'HTML'
<p>The block editor is the heart of Lambda CMS. Every page, post, and template in the system is stored as a JSON array of blocks — and the same editor is used whether you're writing a blog post, configuring your header, or building a custom archive page.</p>

<h2>The Block Model</h2>

<p>Each block has a <code>type</code>, a <code>data</code> object, an optional <code>children</code> array, and an optional <code>bindings</code> map. That's it. The renderer in Vue reads the tree and maps each type to its component. Simple at the core, infinitely composable in practice.</p>

<h2>Block Types</h2>

<p>Lambda CMS ships with over 30 block types, organised into groups:</p>

<ul>
  <li><strong>Content</strong>: Heading, Paragraph, Quote, Divider, Code, HTML</li>
  <li><strong>Media</strong>: Image, Gallery, Video, Embed</li>
  <li><strong>Layout</strong>: Section, Container, Spacer</li>
  <li><strong>Interactive</strong>: Button, CTA, Accordion, Tabs, Search, Filter Link, Active Filter</li>
  <li><strong>Data</strong>: Loop, Pagination, Post Card, Post Title, Post Meta, Post Body, Post Taxonomy, Post Featured Image, Post Comments, Archive Title</li>
  <li><strong>Navigation</strong>: Nav Header, Site Footer, Navigation</li>
  <li><strong>Partials</strong>: Template (embed another template by ID)</li>
</ul>

<h2>The Loop Block</h2>

<p>The most powerful block is the Loop block. It fetches data from a source (<code>posts</code>, <code>categories</code>, <code>tags</code>), applies filters and sorting, and renders its child blocks once per item — injecting the item's data via a Vue <code>provide/inject</code> context.</p>

<p>Child blocks use <strong>bindings</strong> to map their fields to the loop context. For example, a Filter Link block inside a loop of categories can bind its <code>label</code> field to <code>loop:name</code> — so it automatically shows each category's name.</p>

<h2>Templates and Partials</h2>

<p>Any collection of blocks can be saved as a named template. The system ships with default templates for every blog page type — but you can customise them fully in the block editor, or create custom ones. A Partial template (like the Post Card) can be embedded into other templates via the Template block, letting you update the card design in one place and have it propagate everywhere.</p>

<h2>Custom CSS and Classes</h2>

<p>Every block has optional <code>customClasses</code> and <code>customCss</code> fields. Drop in a Tailwind class to adjust spacing, or write a scoped CSS rule for a one-off visual treatment. The design system's CSS tokens are always in scope.</p>
HTML;
    }

    private function postInstalling(): string
    {
        return <<<'HTML'
<p>Lambda CMS uses a browser-based installer so you don't need to touch the command line to get started. Here's the full process from clone to first post.</p>

<h2>Requirements</h2>

<ul>
  <li>PHP 8.2 or higher</li>
  <li>Composer</li>
  <li>Node.js 20+ and npm</li>
  <li>SQLite (zero-config) or MySQL 8+</li>
</ul>

<h2>Step 1: Clone and Install</h2>

<pre><code>git clone https://github.com/mariusberget92/lambda-cms.git
cd lambda-cms
composer install
npm install && npm run build</code></pre>

<h2>Step 2: Environment File</h2>

<pre><code>cp .env.example .env
php artisan key:generate</code></pre>

<p>Open <code>.env</code> and set <code>APP_URL</code> to your domain. If you're using SQLite (the default), no further database config is needed.</p>

<h2>Step 3: Open the Installer</h2>

<p>Start a local server and navigate to your site. If Lambda CMS hasn't been installed yet, you'll be automatically redirected to <code>/install</code>. The five-step wizard will guide you through:</p>

<ol>
  <li><strong>Database</strong> — choose SQLite or MySQL and verify the connection</li>
  <li><strong>Site</strong> — set your site name and URL</li>
  <li><strong>Admin</strong> — create your administrator account</li>
  <li><strong>Mail</strong> — configure SMTP or leave as log driver for now</li>
  <li><strong>Genre</strong> — pick a content theme to seed demo posts, or start empty</li>
</ol>

<h2>Step 4: Log In</h2>

<p>Once the installer completes, you're redirected to the login page. Sign in with the admin credentials you just created. You'll land on the dashboard, and your blog is live at <code>/blog</code>.</p>

<h2>Production Deployment</h2>

<p>For a production server, run the full optimisation stack after deploying:</p>

<pre><code>php artisan config:cache
php artisan route:cache
php artisan view:cache
npm run build</code></pre>

<p>Set up a cron job to run the Laravel scheduler every minute:</p>

<pre><code>* * * * * cd /path/to/lambda-cms && php artisan schedule:run >> /dev/null 2>&1</code></pre>

<p>This handles auto-publishing of scheduled posts and any future background tasks.</p>
HTML;
    }

    private function postDesignTokens(): string
    {
        return <<<'HTML'
<p>The Lambda CMS blog frontend is styled entirely through CSS custom properties — no hardcoded hex values, no scattered magic numbers. Every block component reads from the same small token vocabulary.</p>

<h2>The Token Set</h2>

<p>Twelve tokens cover everything the frontend needs:</p>

<pre><code>--bg           Background surface (page canvas)
--panel        Raised surface (cards, header, footer)
--ink          Primary text
--soft         Secondary / muted text
--line         Subtle separator (low contrast)
--line-strong  Visible separator (borders, dividers)
--accent       Interactive colour (links, buttons, active states)
--accent-ink   Text on accent backgrounds
--code         Dark panel background (code blocks, post card covers)
--code-ink     Text on dark panels
--blog-radius  Border radius (applied consistently to all cards)
--gutter       Horizontal rhythm spacing</code></pre>

<h2>Why Not Tailwind Utilities?</h2>

<p>The admin panel uses Tailwind's <code>--primary</code>, <code>--muted</code>, and <code>--border</code> tokens. Those tokens don't exist in the blog scope — the blog renders inside a <code>.lambda-blog-root</code> element that defines its own token cascade. Using admin tokens in blog block components would cause colours to resolve to the wrong values depending on the current theme.</p>

<p>By building an explicit, narrow token set for the blog, every block component is guaranteed to render consistently regardless of which Tailwind theme is applied to the admin.</p>

<h2>Accent Color from Settings</h2>

<p>The admin settings panel exposes an accent colour picker. When saved, the chosen colour is stored in the database and shared to every Inertia page as <code>accentColor</code>. The blog layout applies it via <code>watchEffect</code>:</p>

<pre><code>watchEffect(() => {
  const color = page.props.accentColor
  if (color && /^#[0-9a-fA-F]{6}$/.test(color)) {
    document.documentElement.style.setProperty('--accent', color)
    document.documentElement.style.setProperty('--accent-ink', '#ffffff')
  }
})</code></pre>

<p>Every component that reads <code>var(--accent)</code> updates instantly — buttons, links, post card hover states, the active filter indicator, the masthead accent rule — all without a page reload.</p>

<h2>The Teleport Problem</h2>

<p>Vue's <code>&lt;Teleport to="body"&gt;</code> moves elements outside the <code>.lambda-blog-root</code> DOM tree. CSS custom properties only cascade downward, so teleported elements like the search overlay would lose access to the blog tokens. Lambda CMS solves this with a companion class <code>.lambda-blog-tokens</code> that carries the same token definitions but no base styles — applied directly to the overlay div.</p>
HTML;
    }

    private function postReleaseNotes(): string
    {
        return <<<'HTML'
<p>Lambda CMS 1.0 is the first stable, publicly available release. Here's a summary of what shipped, what we decided to cut, and where we're heading.</p>

<h2>What Shipped</h2>

<h3>Core Platform</h3>
<ul>
  <li>Full post, page, category, tag, and comment management</li>
  <li>Media library with auto-resize, alt text, and bulk operations</li>
  <li>Scheduled post publishing via Laravel scheduler</li>
  <li>Draft autosave and revision history (up to 25 revisions)</li>
  <li>Role-based access control: administrator and user roles</li>
  <li>User banning with optional expiry</li>
</ul>

<h3>Block Editor</h3>
<ul>
  <li>30+ block types covering content, layout, media, and data</li>
  <li>Loop block with dynamic filtering, sorting, and pagination</li>
  <li>Template system for all blog page types</li>
  <li>Partial templates with cross-template embedding</li>
  <li>Custom CSS and class overrides per block</li>
  <li>Field bindings for loop-driven dynamic content</li>
</ul>

<h3>Blog Frontend</h3>
<ul>
  <li>Blog index with category/tag filtering and pagination</li>
  <li>Single post view with comments and related posts</li>
  <li>Category and tag archive pages</li>
  <li>Full-text search (title, excerpt, body)</li>
  <li>RSS feed and XML sitemap</li>
  <li>Token-based draft preview URLs</li>
  <li>Admin bar for authenticated users</li>
</ul>

<h3>Developer Features</h3>
<ul>
  <li>Headless REST API (<code>/api/v1/</code>) for posts, categories, and tags</li>
  <li>Webhook system with HMAC-SHA256 signatures</li>
  <li>Browser-based 5-step installer</li>
  <li>Content seeding by genre (20 built-in themes)</li>
  <li>Accent colour theming via admin settings</li>
</ul>

<h2>What We Cut for 1.0</h2>

<p>Some features were scoped out to keep the 1.0 release focused:</p>

<ul>
  <li><strong>Two-factor authentication</strong> — planned for 1.1</li>
  <li><strong>Content import/export</strong> — JSON and Markdown round-trips</li>
  <li><strong>Multi-language support</strong> — the system is English-only for now</li>
  <li><strong>API write access</strong> — read-only in 1.0; token-based writes coming</li>
  <li><strong>In-browser image cropping</strong> — upload is straight resize only</li>
</ul>

<h2>What's Next</h2>

<p>The roadmap for 1.x focuses on hardening and expanding the block editor: more interactive block types, a drag-to-reorder column system, and richer loop filter conditions. We're also planning a public theme registry so designers can share block editor templates.</p>

<p>If you want to contribute, the repository is open. Check the issues tab for anything tagged <code>good first issue</code>.</p>
HTML;
    }
}
