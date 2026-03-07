# Archives, RSS Feed & Sitemap — Design

**Date:** 2026-03-07

## Goal

Add three tightly related public-facing features to the blog frontend:

1. **Category & tag archive pages** — dedicated URLs per taxonomy with filtered, paginated post lists and proper SEO canonicals. Fixes the currently broken sidebar links.
2. **RSS feed** — `/feed` returning RSS 2.0 XML with the 20 most recent published posts (title, excerpt, link). Auto-discovered by browsers via a `<link rel="alternate">` in the blog layout.
3. **Sitemap** — `/sitemap.xml` listing all published posts plus category and tag archive pages (those with ≥1 published post), for search-engine discoverability.

Then: reset the database to installer state and open a GitHub PR.

---

## Architecture

**Approach: Option B — separate controllers per concern**

- `BlogController` gains `category()` and `tag()` methods (natural fit — reuses `sidebarData()`).
- New invokable `FeedController` returns RSS XML.
- New invokable `SitemapController` returns sitemap XML.
- XML responses rendered via Blade templates (not string concatenation).

---

## Section 1 — Routes

Added to the public `installed` middleware group in `routes/web.php`:

```php
// Archive pages (Inertia)
Route::get('/blog/category/{category:slug}', [BlogController::class, 'category'])->name('blog.category');
Route::get('/blog/tag/{tag:slug}',           [BlogController::class, 'tag'])->name('blog.tag');

// RSS feed
Route::get('/feed', FeedController::class)->name('feed');

// Sitemap
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
```

Defined before the `/blog/{slug}` post route. No conflict — `category` and `tag` are fixed path segments.

---

## Section 2 — Archive Pages

### BlogController additions

**`category(Category $category): Response`**
- Route–model binding on `{category:slug}`
- Returns 404 if category has no published posts (optional — leave live, just shows empty)
- Queries `Post::published()->whereHas('categories', ...)` paginated 15 per page
- Same eager-loads as `index()`: author, categories, tags, featuredImage
- Same `->through()` transform as `index()`
- SEO: `title = "Posts in {$category->name}"`, `canonical = url("/blog/category/{$category->slug}")`
- Renders `Blog/Archive` with `heading = ['type' => 'category', 'name' => ..., 'slug' => ..., 'postsCount' => ...]`

**`tag(Tag $tag): Response`**
- Identical structure, filtered by tag
- SEO: `title = "Posts tagged '{$tag->name}'"`, `canonical = url("/blog/tag/{$tag->slug}")`
- Renders `Blog/Archive` with `heading = ['type' => 'tag', ...]`

### Vue — PostCard.vue (extracted component)

Extract the post card template from `Blog/Index.vue` into `resources/js/Components/PostCard.vue`.
Accepts a single `post` prop. Used by both `Blog/Index.vue` and `Blog/Archive.vue`.

### Vue — Blog/Archive.vue (new page)

Structurally identical to `Blog/Index.vue` but adds a heading block above the post list:

```
[ Category ]          ← small muted label (type)
Laravel               ← taxonomy name (h1)
12 posts              ← post count (muted)
─────────────────────────────────────────────
[ PostCard ]
[ PostCard ]
...
[ Pagination ]        [ Sidebar ]
```

Props: `posts`, `sidebar`, `seo`, `heading`.

### BlogSidebar.vue — link fix

Update category links from `/?category=${cat.slug}` to `/blog/category/${cat.slug}`.
Update tag links from `/?tag=${tag.slug}` to `/blog/tag/${tag.slug}`.

---

## Section 3 — RSS Feed

**`app/Http/Controllers/FeedController.php`** (invokable)

- Queries 20 most recent published posts: `id, title, slug, excerpt, published_at, updated_at`
- Channel `<title>` and `<description>` from `Setting::get('site.name')` and `Setting::get('site.description')`
- Renders `resources/views/feed.blade.php`
- Returns `response($xml, 200, ['Content-Type' => 'application/rss+xml; charset=utf-8'])`

**`resources/views/feed.blade.php`** — RSS 2.0 structure:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
  <channel>
    <title>{{ $siteName }}</title>
    <link>{{ url('/') }}</link>
    <description>{{ $siteDescription }}</description>
    <lastBuildDate>{{ now()->toRfc2822String() }}</lastBuildDate>
    @foreach ($posts as $post)
    <item>
      <title>{{ $post->title }}</title>
      <link>{{ url("/blog/{$post->slug}") }}</link>
      <description>{{ $post->excerpt }}</description>
      <pubDate>{{ $post->published_at->toRfc2822String() }}</pubDate>
      <guid>{{ url("/blog/{$post->slug}") }}</guid>
    </item>
    @endforeach
  </channel>
</rss>
```

**BlogLayout.vue** — add RSS auto-discovery link in `<Head>`:

```html
<link rel="alternate" type="application/rss+xml" :title="siteName" href="/feed" />
```

---

## Section 4 — Sitemap

**`app/Http/Controllers/SitemapController.php`** (invokable)

- Fetches all published posts (`slug`, `updated_at`)
- Fetches categories with `posts_count > 0` (published posts only)
- Fetches tags with `posts_count > 0` (published posts only)
- Renders `resources/views/sitemap.blade.php`
- Returns `response($xml, 200, ['Content-Type' => 'application/xml; charset=utf-8'])`

**URL table:**

| URL | `<changefreq>` | `<lastmod>` |
|-----|----------------|-------------|
| `/` | `daily` | today |
| `/blog/{slug}` (all published posts) | `monthly` | post `updated_at` |
| `/blog/category/{slug}` (categories with ≥1 post) | `weekly` | today |
| `/blog/tag/{slug}` (tags with ≥1 post) | `weekly` | today |

**BlogLayout.vue** — also add sitemap discovery link:

```html
<link rel="sitemap" type="application/xml" title="Sitemap" href="/sitemap.xml" />
```

---

## Section 5 — Database Reset + PR

After all features pass tests:

```bash
php artisan migrate:fresh
rm storage/app/installed
php artisan config:clear && php artisan cache:clear
```

Then push a feature branch and open a GitHub PR with a summary and test plan checklist.

---

## Testing

- **Archive pages:** feature tests for category and tag routes — 200 response, correct posts shown, excluded posts from other categories not shown, pagination, SEO canonical in Inertia props.
- **RSS feed:** feature test asserting 200, `Content-Type: application/rss+xml`, presence of `<rss`, `<item>`, correct post title/link in output.
- **Sitemap:** feature test asserting 200, `Content-Type: application/xml`, `<urlset`, presence of post slug URLs, category and tag URLs, absence of unpublished posts.
- **Sidebar links:** confirmed by archive route tests passing end-to-end.
