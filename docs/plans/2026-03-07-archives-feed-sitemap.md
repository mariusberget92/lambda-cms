# Archives, RSS Feed & Sitemap Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add category/tag archive pages, an RSS feed, and an XML sitemap to the public blog; then reset the database to installer state and open a GitHub PR.

**Architecture:** `BlogController` gains `category()` and `tag()` methods reusing the existing `sidebarData()` helper. Two new invokable controllers handle XML responses: `FeedController` (RSS 2.0) and `SitemapController`. Vue side: extract `PostCard.vue` from `Blog/Index.vue`, create `Blog/Archive.vue`, fix sidebar links, and add auto-discovery `<link>` tags to `BlogLayout.vue`.

**Tech Stack:** Laravel 11, Inertia 2, Vue 3, Tailwind 4, Pest/PHPUnit, Blade (for XML templates).

---

## Task 1: Create a git worktree for this feature

**Files:**
- No code changes — worktree setup only.

**Step 1: Create worktree from master**

Run from the repo root (`C:\Users\mariu\Herd\lambda-cms`):
```bash
git worktree add .claude/worktrees/archives-feed-sitemap -b feature/archives-feed-sitemap
```
Expected: new directory `.claude/worktrees/archives-feed-sitemap` checked out on branch `feature/archives-feed-sitemap`.

**Step 2: Verify**
```bash
git worktree list
```
Expected: three entries — main repo, previous worktree (if still present), and the new one.

**Step 3: All remaining tasks run inside the worktree**

Set working directory to:
```
C:\Users\mariu\Herd\lambda-cms\.claude\worktrees\archives-feed-sitemap
```

---

## Task 2: Category archive — routes, controller method, tests

**Files:**
- Modify: `routes/web.php`
- Modify: `app/Http/Controllers/BlogController.php`
- Modify: `tests/Feature/BlogTest.php`

### Step 1: Write failing tests

Add to the bottom of `tests/Feature/BlogTest.php` (inside the class):

```php
// ── Category archive ──────────────────────────────────────────────────────────

public function test_category_archive_is_publicly_accessible(): void
{
    $category = Category::factory()->create();

    $this->get("/blog/category/{$category->slug}")->assertOk();
}

public function test_category_archive_renders_archive_component(): void
{
    $category = Category::factory()->create();

    $this->get("/blog/category/{$category->slug}")->assertInertia(
        fn ($page) => $page->component('Blog/Archive')
    );
}

public function test_category_archive_shows_only_posts_in_that_category(): void
{
    $category = Category::factory()->create();
    $other    = Category::factory()->create();

    $included = Post::factory()->published()->create(['title' => 'Included Post']);
    $excluded = Post::factory()->published()->create(['title' => 'Excluded Post']);

    $included->categories()->attach($category);
    $excluded->categories()->attach($other);

    $this->get("/blog/category/{$category->slug}")->assertInertia(
        fn ($page) => $page
            ->has('posts.data', 1)
            ->where('posts.data.0.title', 'Included Post')
    );
}

public function test_category_archive_excludes_draft_posts(): void
{
    $category = Category::factory()->create();
    $post     = Post::factory()->draft()->create();
    $post->categories()->attach($category);

    $this->get("/blog/category/{$category->slug}")->assertInertia(
        fn ($page) => $page->has('posts.data', 0)
    );
}

public function test_category_archive_returns_404_for_nonexistent_slug(): void
{
    $this->get('/blog/category/does-not-exist')->assertNotFound();
}

public function test_category_archive_heading_contains_correct_data(): void
{
    $category = Category::factory()->create(['name' => 'Laravel', 'slug' => 'laravel']);
    $post     = Post::factory()->published()->create();
    $post->categories()->attach($category);

    $this->get("/blog/category/{$category->slug}")->assertInertia(
        fn ($page) => $page
            ->where('heading.type', 'category')
            ->where('heading.name', 'Laravel')
            ->where('heading.slug', 'laravel')
            ->where('heading.postsCount', 1)
    );
}

public function test_category_archive_has_correct_seo_canonical(): void
{
    $category = Category::factory()->create(['slug' => 'laravel']);

    $this->get("/blog/category/{$category->slug}")->assertInertia(
        fn ($page) => $page->where('seo.canonical', url('/blog/category/laravel'))
    );
}

public function test_category_archive_includes_sidebar_data(): void
{
    $category = Category::factory()->create();

    $this->get("/blog/category/{$category->slug}")->assertInertia(
        fn ($page) => $page
            ->has('sidebar')
            ->has('sidebar.categories')
            ->has('sidebar.tags')
            ->has('sidebar.recentPosts')
    );
}
```

### Step 2: Run to confirm they fail

```bash
php artisan test tests/Feature/BlogTest.php --filter="category_archive"
```
Expected: 7 failures — routes don't exist yet (404/500).

### Step 3: Add routes to `routes/web.php`

Add at the top of the file with the other `use` imports:
```php
use App\Http\Controllers\FeedController;
use App\Http\Controllers\SitemapController;
```

In the public blog route group (after the `/blog/{post:slug}/comments` line, **before** `/blog/{slug}`):
```php
Route::get('/blog/category/{category:slug}', [BlogController::class, 'category'])->name('blog.category');
Route::get('/blog/tag/{tag:slug}',           [BlogController::class, 'tag'])->name('blog.tag');
Route::get('/feed',        FeedController::class)->name('feed');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
```

> ⚠️ These four routes MUST be defined before `Route::get('/blog/{slug}', ...)`. The `FeedController` and `SitemapController` classes don't exist yet — that's fine, routes can be registered before controllers exist.

### Step 4: Add a private `postData()` helper to `BlogController`

This avoids repeating the same transform in `index()`, `category()`, and `tag()`. Add this private method at the bottom of the class (just before the closing `}`), after `sidebarData()`:

```php
/**
 * Transform a Post model into the array shape used by Blog pages.
 */
private function postData(Post $post): array
{
    return [
        'id'                  => $post->id,
        'title'               => $post->title,
        'slug'                => $post->slug,
        'excerpt'             => $post->excerpt,
        'published_at'        => $post->published_at?->toDateString(),
        'featured_image_url'  => $post->featuredImage?->url,
        'author'              => [
            'name'       => $post->author->name,
            'avatar_url' => $post->author->avatar_url,
        ],
        'categories' => $post->categories
            ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'slug' => $c->slug])
            ->values(),
        'tags'       => $post->tags
            ->map(fn ($t) => ['name' => $t->name, 'slug' => $t->slug]),
    ];
}
```

Also update the existing `index()` method's `->through()` call to use the helper. Replace:
```php
->through(fn (Post $post) => [
    'id'                  => $post->id,
    'title'               => $post->title,
    'slug'                => $post->slug,
    'excerpt'             => $post->excerpt,
    'published_at'        => $post->published_at?->toDateString(),
    'featured_image_url'  => $post->featuredImage?->url,
    'author'              => [
        'name'       => $post->author->name,
        'avatar_url' => $post->author->avatar_url,
    ],
    'categories'          => $post->categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'slug' => $c->slug])->values(),
    'tags'                => $post->tags->map(fn ($t) => [
        'name' => $t->name,
        'slug' => $t->slug,
    ]),
]);
```
with:
```php
->through(fn (Post $post) => $this->postData($post));
```

### Step 5: Add `category()` method to `BlogController`

Add the following method after `show()` and before `comments()`. Also add `use App\Models\Category;` at the top if not already present (it is — check the existing imports).

```php
/**
 * Public category archive — paginated published posts for a given category.
 */
public function category(Category $category): Response
{
    $posts = Post::published()
        ->whereHas('categories', fn ($q) => $q->where('categories.id', $category->id))
        ->with(['author:id,name,avatar', 'categories:id,name,slug', 'tags:id,name,slug', 'featuredImage:id,path,disk'])
        ->orderByDesc('published_at')
        ->paginate(15)
        ->through(fn (Post $post) => $this->postData($post));

    $siteName  = Setting::get('site.name', config('app.name'));
    $separator = Setting::get('seo.title_separator', ' | ');

    $seo = [
        'title'       => "Posts in {$category->name}{$separator}{$siteName}",
        'description' => "All posts in the {$category->name} category.",
        'image'       => Setting::get('seo.default_og_image_url', ''),
        'canonical'   => url("/blog/category/{$category->slug}"),
        'type'        => 'website',
        'keywords'    => Setting::get('seo.default_keywords', ''),
    ];

    return Inertia::render('Blog/Archive', [
        'posts'   => $posts,
        'sidebar' => $this->sidebarData(),
        'seo'     => $seo,
        'heading' => [
            'type'       => 'category',
            'name'       => $category->name,
            'slug'       => $category->slug,
            'postsCount' => $posts->total(),
        ],
    ]);
}
```

### Step 6: Run tests — expect pass

```bash
php artisan test tests/Feature/BlogTest.php --filter="category_archive"
```
Expected: 7 passed.

### Step 7: Commit

```bash
git add routes/web.php app/Http/Controllers/BlogController.php tests/Feature/BlogTest.php
git commit -m "feat: add category archive page with tests"
```

---

## Task 3: Tag archive — controller method, tests

**Files:**
- Modify: `app/Http/Controllers/BlogController.php`
- Modify: `tests/Feature/BlogTest.php`

### Step 1: Write failing tests

Add to `tests/Feature/BlogTest.php` after the category archive tests:

```php
// ── Tag archive ───────────────────────────────────────────────────────────────

public function test_tag_archive_is_publicly_accessible(): void
{
    $tag = Tag::factory()->create();

    $this->get("/blog/tag/{$tag->slug}")->assertOk();
}

public function test_tag_archive_renders_archive_component(): void
{
    $tag = Tag::factory()->create();

    $this->get("/blog/tag/{$tag->slug}")->assertInertia(
        fn ($page) => $page->component('Blog/Archive')
    );
}

public function test_tag_archive_shows_only_posts_with_that_tag(): void
{
    $tag   = Tag::factory()->create();
    $other = Tag::factory()->create();

    $included = Post::factory()->published()->create(['title' => 'Tagged Post']);
    $excluded = Post::factory()->published()->create(['title' => 'Other Post']);

    $included->tags()->attach($tag);
    $excluded->tags()->attach($other);

    $this->get("/blog/tag/{$tag->slug}")->assertInertia(
        fn ($page) => $page
            ->has('posts.data', 1)
            ->where('posts.data.0.title', 'Tagged Post')
    );
}

public function test_tag_archive_excludes_draft_posts(): void
{
    $tag  = Tag::factory()->create();
    $post = Post::factory()->draft()->create();
    $post->tags()->attach($tag);

    $this->get("/blog/tag/{$tag->slug}")->assertInertia(
        fn ($page) => $page->has('posts.data', 0)
    );
}

public function test_tag_archive_returns_404_for_nonexistent_slug(): void
{
    $this->get('/blog/tag/does-not-exist')->assertNotFound();
}

public function test_tag_archive_heading_contains_correct_data(): void
{
    $tag  = Tag::factory()->create(['name' => 'PHP', 'slug' => 'php']);
    $post = Post::factory()->published()->create();
    $post->tags()->attach($tag);

    $this->get("/blog/tag/{$tag->slug}")->assertInertia(
        fn ($page) => $page
            ->where('heading.type', 'tag')
            ->where('heading.name', 'PHP')
            ->where('heading.slug', 'php')
            ->where('heading.postsCount', 1)
    );
}

public function test_tag_archive_has_correct_seo_canonical(): void
{
    $tag = Tag::factory()->create(['slug' => 'php']);

    $this->get("/blog/tag/{$tag->slug}")->assertInertia(
        fn ($page) => $page->where('seo.canonical', url('/blog/tag/php'))
    );
}
```

### Step 2: Run to confirm they fail

```bash
php artisan test tests/Feature/BlogTest.php --filter="tag_archive"
```
Expected: 6 failures.

### Step 3: Add `tag()` method to `BlogController`

Add after `category()`, before `comments()`. Also ensure `use App\Models\Tag;` is in the imports (it already is).

```php
/**
 * Public tag archive — paginated published posts for a given tag.
 */
public function tag(Tag $tag): Response
{
    $posts = Post::published()
        ->whereHas('tags', fn ($q) => $q->where('tags.id', $tag->id))
        ->with(['author:id,name,avatar', 'categories:id,name,slug', 'tags:id,name,slug', 'featuredImage:id,path,disk'])
        ->orderByDesc('published_at')
        ->paginate(15)
        ->through(fn (Post $post) => $this->postData($post));

    $siteName  = Setting::get('site.name', config('app.name'));
    $separator = Setting::get('seo.title_separator', ' | ');

    $seo = [
        'title'       => "Posts tagged '{$tag->name}'{$separator}{$siteName}",
        'description' => "All posts tagged '{$tag->name}'.",
        'image'       => Setting::get('seo.default_og_image_url', ''),
        'canonical'   => url("/blog/tag/{$tag->slug}"),
        'type'        => 'website',
        'keywords'    => Setting::get('seo.default_keywords', ''),
    ];

    return Inertia::render('Blog/Archive', [
        'posts'   => $posts,
        'sidebar' => $this->sidebarData(),
        'seo'     => $seo,
        'heading' => [
            'type'       => 'tag',
            'name'       => $tag->name,
            'slug'       => $tag->slug,
            'postsCount' => $posts->total(),
        ],
    ]);
}
```

### Step 4: Run tests — expect pass

```bash
php artisan test tests/Feature/BlogTest.php --filter="tag_archive"
```
Expected: 6 passed.

### Step 5: Commit

```bash
git add app/Http/Controllers/BlogController.php tests/Feature/BlogTest.php
git commit -m "feat: add tag archive page with tests"
```

---

## Task 4: RSS feed — FeedController, Blade template, tests

**Files:**
- Create: `app/Http/Controllers/FeedController.php`
- Create: `resources/views/feed.blade.php`
- Create: `tests/Feature/FeedTest.php`

### Step 1: Create the test file

Create `tests/Feature/FeedTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->markAsInstalled();
    }

    public function test_feed_is_publicly_accessible(): void
    {
        $this->get('/feed')->assertOk();
    }

    public function test_feed_returns_rss_content_type(): void
    {
        $this->get('/feed')->assertHeader('Content-Type', 'application/rss+xml; charset=utf-8');
    }

    public function test_feed_contains_rss_structure(): void
    {
        $content = $this->get('/feed')->getContent();

        $this->assertStringContainsString('<rss', $content);
        $this->assertStringContainsString('<channel>', $content);
        $this->assertStringContainsString('</channel>', $content);
    }

    public function test_feed_includes_published_post_title_and_link(): void
    {
        Post::factory()->published()->create([
            'title' => 'Hello World',
            'slug'  => 'hello-world',
        ]);

        $content = $this->get('/feed')->getContent();

        $this->assertStringContainsString('Hello World', $content);
        $this->assertStringContainsString('/blog/hello-world', $content);
    }

    public function test_feed_excludes_draft_posts(): void
    {
        Post::factory()->draft()->create(['title' => 'Secret Draft']);

        $content = $this->get('/feed')->getContent();

        $this->assertStringNotContainsString('Secret Draft', $content);
    }

    public function test_feed_is_limited_to_20_posts(): void
    {
        Post::factory()->published()->count(25)->create();

        $content = $this->get('/feed')->getContent();

        $this->assertSame(20, substr_count($content, '<item>'));
    }
}
```

### Step 2: Run to confirm they fail

```bash
php artisan test tests/Feature/FeedTest.php
```
Expected: failures — controller doesn't exist yet.

### Step 3: Create `app/Http/Controllers/FeedController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Setting;
use Illuminate\Http\Response;

class FeedController extends Controller
{
    public function __invoke(): Response
    {
        $posts = Post::published()
            ->orderByDesc('published_at')
            ->limit(20)
            ->get(['id', 'title', 'slug', 'excerpt', 'published_at', 'updated_at']);

        $siteName        = Setting::get('site.name', config('app.name'));
        $siteDescription = Setting::get('seo.default_description', '');

        $xml = view('feed', compact('posts', 'siteName', 'siteDescription'))->render();

        return response($xml, 200, [
            'Content-Type' => 'application/rss+xml; charset=utf-8',
        ]);
    }
}
```

### Step 4: Create `resources/views/feed.blade.php`

```xml
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
  <channel>
    <title>{{ $siteName }}</title>
    <link>{{ url('/') }}</link>
    <description>{{ $siteDescription }}</description>
    <language>en-us</language>
    <lastBuildDate>{{ now()->toRfc2822String() }}</lastBuildDate>
    <atom:link href="{{ url('/feed') }}" rel="self" type="application/rss+xml" />
    @foreach ($posts as $post)
    <item>
      <title><![CDATA[{{ $post->title }}]]></title>
      <link>{{ url("/blog/{$post->slug}") }}</link>
      <description><![CDATA[{{ $post->excerpt }}]]></description>
      <pubDate>{{ $post->published_at->toRfc2822String() }}</pubDate>
      <guid isPermaLink="true">{{ url("/blog/{$post->slug}") }}</guid>
    </item>
    @endforeach
  </channel>
</rss>
```

### Step 5: Run tests — expect pass

```bash
php artisan test tests/Feature/FeedTest.php
```
Expected: 6 passed.

### Step 6: Commit

```bash
git add app/Http/Controllers/FeedController.php resources/views/feed.blade.php tests/Feature/FeedTest.php
git commit -m "feat: add RSS feed at /feed"
```

---

## Task 5: Sitemap — SitemapController, Blade template, tests

**Files:**
- Create: `app/Http/Controllers/SitemapController.php`
- Create: `resources/views/sitemap.blade.php`
- Create: `tests/Feature/SitemapTest.php`

### Step 1: Create the test file

Create `tests/Feature/SitemapTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->markAsInstalled();
    }

    public function test_sitemap_is_publicly_accessible(): void
    {
        $this->get('/sitemap.xml')->assertOk();
    }

    public function test_sitemap_returns_xml_content_type(): void
    {
        $this->get('/sitemap.xml')->assertHeader('Content-Type', 'application/xml; charset=utf-8');
    }

    public function test_sitemap_contains_urlset(): void
    {
        $content = $this->get('/sitemap.xml')->getContent();

        $this->assertStringContainsString('<urlset', $content);
        $this->assertStringContainsString('</urlset>', $content);
    }

    public function test_sitemap_includes_published_post_urls(): void
    {
        Post::factory()->published()->create(['slug' => 'hello-world']);

        $content = $this->get('/sitemap.xml')->getContent();

        $this->assertStringContainsString('/blog/hello-world', $content);
    }

    public function test_sitemap_excludes_draft_post_urls(): void
    {
        Post::factory()->draft()->create(['slug' => 'draft-post']);

        $content = $this->get('/sitemap.xml')->getContent();

        $this->assertStringNotContainsString('/blog/draft-post', $content);
    }

    public function test_sitemap_includes_category_with_published_posts(): void
    {
        $category = Category::factory()->create(['slug' => 'my-category']);
        $post     = Post::factory()->published()->create();
        $post->categories()->attach($category);

        $content = $this->get('/sitemap.xml')->getContent();

        $this->assertStringContainsString('/blog/category/my-category', $content);
    }

    public function test_sitemap_excludes_empty_categories(): void
    {
        Category::factory()->create(['slug' => 'empty-category']);

        $content = $this->get('/sitemap.xml')->getContent();

        $this->assertStringNotContainsString('/blog/category/empty-category', $content);
    }

    public function test_sitemap_includes_tag_with_published_posts(): void
    {
        $tag  = Tag::factory()->create(['slug' => 'my-tag']);
        $post = Post::factory()->published()->create();
        $post->tags()->attach($tag);

        $content = $this->get('/sitemap.xml')->getContent();

        $this->assertStringContainsString('/blog/tag/my-tag', $content);
    }

    public function test_sitemap_excludes_empty_tags(): void
    {
        Tag::factory()->create(['slug' => 'empty-tag']);

        $content = $this->get('/sitemap.xml')->getContent();

        $this->assertStringNotContainsString('/blog/tag/empty-tag', $content);
    }
}
```

### Step 2: Run to confirm they fail

```bash
php artisan test tests/Feature/SitemapTest.php
```
Expected: failures — controller doesn't exist yet.

### Step 3: Create `app/Http/Controllers/SitemapController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $posts = Post::published()
            ->orderByDesc('published_at')
            ->get(['slug', 'updated_at']);

        $categories = Category::withCount(['posts' => fn ($q) => $q->published()])
            ->having('posts_count', '>', 0)
            ->get(['slug', 'updated_at']);

        $tags = Tag::withCount(['posts' => fn ($q) => $q->published()])
            ->having('posts_count', '>', 0)
            ->get(['slug', 'updated_at']);

        $xml = view('sitemap', compact('posts', 'categories', 'tags'))->render();

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
        ]);
    }
}
```

### Step 4: Create `resources/views/sitemap.blade.php`

```xml
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc>{{ url('/') }}</loc>
    <changefreq>daily</changefreq>
    <lastmod>{{ now()->toDateString() }}</lastmod>
  </url>
  @foreach ($posts as $post)
  <url>
    <loc>{{ url("/blog/{$post->slug}") }}</loc>
    <changefreq>monthly</changefreq>
    <lastmod>{{ $post->updated_at->toDateString() }}</lastmod>
  </url>
  @endforeach
  @foreach ($categories as $category)
  <url>
    <loc>{{ url("/blog/category/{$category->slug}") }}</loc>
    <changefreq>weekly</changefreq>
    <lastmod>{{ now()->toDateString() }}</lastmod>
  </url>
  @endforeach
  @foreach ($tags as $tag)
  <url>
    <loc>{{ url("/blog/tag/{$tag->slug}") }}</loc>
    <changefreq>weekly</changefreq>
    <lastmod>{{ now()->toDateString() }}</lastmod>
  </url>
  @endforeach
</urlset>
```

### Step 5: Run tests — expect pass

```bash
php artisan test tests/Feature/SitemapTest.php
```
Expected: 9 passed.

### Step 6: Commit

```bash
git add app/Http/Controllers/SitemapController.php resources/views/sitemap.blade.php tests/Feature/SitemapTest.php
git commit -m "feat: add XML sitemap at /sitemap.xml"
```

---

## Task 6: Extract PostCard.vue + update Blog/Index.vue

**Files:**
- Create: `resources/js/Components/PostCard.vue`
- Modify: `resources/js/Pages/Blog/Index.vue`

### Step 1: Create `resources/js/Components/PostCard.vue`

This is the post card markup extracted verbatim from `Blog/Index.vue`. The `formatDate` function moves here too.

```vue
<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
  post: { type: Object, required: true },
})

function formatDate(date) {
  if (!date) return ''
  return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })
}
</script>

<template>
  <article class="border rounded-xl overflow-hidden bg-card hover:shadow-sm transition-shadow">
    <!-- Featured image -->
    <div v-if="post.featured_image_url" class="w-full h-48 overflow-hidden">
      <img
        :src="post.featured_image_url"
        :alt="post.title"
        class="w-full h-full object-cover"
        loading="lazy"
      />
    </div>

    <div class="p-6">
      <!-- Category badges -->
      <div v-if="post.categories?.length" class="mb-2 flex flex-wrap gap-1">
        <span
          v-for="cat in post.categories"
          :key="cat.slug"
          class="inline-block text-xs font-medium bg-primary/10 text-primary px-2 py-0.5 rounded-full"
        >
          {{ cat.name }}
        </span>
      </div>

      <!-- Title -->
      <h2 class="text-xl font-semibold leading-tight mb-2">
        <Link :href="`/blog/${post.slug}`" class="hover:text-primary transition-colors">
          {{ post.title }}
        </Link>
      </h2>

      <!-- Excerpt -->
      <p v-if="post.excerpt" class="text-sm text-muted-foreground mb-4 line-clamp-3">
        {{ post.excerpt }}
      </p>

      <!-- Meta row -->
      <div class="flex items-center justify-between flex-wrap gap-2">
        <div class="flex items-center gap-2">
          <img
            v-if="post.author.avatar_url"
            :src="post.author.avatar_url"
            :alt="post.author.name"
            class="w-6 h-6 rounded-full object-cover"
          />
          <div
            v-else
            class="w-6 h-6 rounded-full bg-primary/20 flex items-center justify-center text-xs font-semibold text-primary"
          >
            {{ post.author.name.charAt(0).toUpperCase() }}
          </div>
          <span class="text-xs text-muted-foreground">{{ post.author.name }}</span>
          <span class="text-xs text-muted-foreground">·</span>
          <span class="text-xs text-muted-foreground">{{ formatDate(post.published_at) }}</span>
        </div>

        <Link
          :href="`/blog/${post.slug}`"
          class="text-xs font-medium text-primary hover:underline"
        >
          Read more →
        </Link>
      </div>

      <!-- Tags -->
      <div v-if="post.tags?.length" class="mt-3 flex flex-wrap gap-1.5">
        <span
          v-for="tag in post.tags"
          :key="tag.slug"
          class="text-xs border rounded-full px-2 py-0.5 text-muted-foreground"
        >
          {{ tag.name }}
        </span>
      </div>
    </div>
  </article>
</template>
```

### Step 2: Replace `Blog/Index.vue` entirely

The entire file becomes:

```vue
<script setup>
import BlogLayout from '@/Layouts/BlogLayout.vue'
import BlogSidebar from '@/Components/BlogSidebar.vue'
import PostCard from '@/Components/PostCard.vue'
import SeoHead from '@/Components/SeoHead.vue'
import { Link } from '@inertiajs/vue3'

defineOptions({ layout: BlogLayout })

defineProps({
  posts:  Object,
  sidebar: Object,
  seo:    { type: Object, required: true },
})
</script>

<template>
  <SeoHead :seo="seo" />
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
    <!-- Main: Post list -->
    <div class="lg:col-span-2">
      <!-- Empty state -->
      <div v-if="!posts.data.length" class="text-center py-20 text-muted-foreground">
        <svg class="mx-auto mb-4 w-10 h-10 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <p class="text-sm">No posts published yet.</p>
      </div>

      <!-- Post cards -->
      <div v-else class="space-y-8">
        <PostCard v-for="post in posts.data" :key="post.id" :post="post" />
      </div>

      <!-- Pagination -->
      <div v-if="posts.links?.length > 3" class="flex items-center justify-center gap-1 mt-10">
        <template v-for="link in posts.links" :key="link.label">
          <Link
            v-if="link.url"
            :href="link.url"
            class="px-3 py-1.5 text-sm rounded-md border transition-colors"
            :class="link.active
              ? 'bg-primary text-primary-foreground border-primary'
              : 'bg-card text-muted-foreground hover:text-foreground hover:border-foreground'"
          >
            {{ link.label.replace('&laquo;', '«').replace('&raquo;', '»') }}
          </Link>
          <span
            v-else
            class="px-3 py-1.5 text-sm rounded-md border text-muted-foreground/40 cursor-not-allowed"
          >
            {{ link.label.replace('&laquo;', '«').replace('&raquo;', '»') }}
          </span>
        </template>
      </div>
    </div>

    <!-- Sidebar -->
    <BlogSidebar :sidebar="sidebar" />
  </div>
</template>
```

### Step 3: Commit

```bash
git add resources/js/Components/PostCard.vue resources/js/Pages/Blog/Index.vue
git commit -m "refactor: extract PostCard.vue from Blog/Index.vue"
```

---

## Task 7: Create Blog/Archive.vue

**Files:**
- Create: `resources/js/Pages/Blog/Archive.vue`

### Step 1: Create `resources/js/Pages/Blog/Archive.vue`

```vue
<script setup>
import { Link } from '@inertiajs/vue3'
import BlogLayout from '@/Layouts/BlogLayout.vue'
import BlogSidebar from '@/Components/BlogSidebar.vue'
import PostCard from '@/Components/PostCard.vue'
import SeoHead from '@/Components/SeoHead.vue'

defineOptions({ layout: BlogLayout })

defineProps({
  posts:   Object,
  sidebar: Object,
  seo:     { type: Object, required: true },
  heading: { type: Object, required: true },
})
</script>

<template>
  <SeoHead :seo="seo" />
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
    <!-- Main -->
    <div class="lg:col-span-2">

      <!-- Archive heading -->
      <div class="mb-8 pb-6 border-b">
        <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground mb-1">
          {{ heading.type === 'category' ? 'Category' : 'Tag' }}
        </p>
        <h1 class="text-3xl font-bold tracking-tight">{{ heading.name }}</h1>
        <p class="text-sm text-muted-foreground mt-1">
          {{ heading.postsCount }} {{ heading.postsCount === 1 ? 'post' : 'posts' }}
        </p>
      </div>

      <!-- Empty state -->
      <div v-if="!posts.data.length" class="text-center py-20 text-muted-foreground">
        <p class="text-sm">No posts found.</p>
      </div>

      <!-- Post cards -->
      <div v-else class="space-y-8">
        <PostCard v-for="post in posts.data" :key="post.id" :post="post" />
      </div>

      <!-- Pagination -->
      <div v-if="posts.links?.length > 3" class="flex items-center justify-center gap-1 mt-10">
        <template v-for="link in posts.links" :key="link.label">
          <Link
            v-if="link.url"
            :href="link.url"
            class="px-3 py-1.5 text-sm rounded-md border transition-colors"
            :class="link.active
              ? 'bg-primary text-primary-foreground border-primary'
              : 'bg-card text-muted-foreground hover:text-foreground hover:border-foreground'"
          >
            {{ link.label.replace('&laquo;', '«').replace('&raquo;', '»') }}
          </Link>
          <span
            v-else
            class="px-3 py-1.5 text-sm rounded-md border text-muted-foreground/40 cursor-not-allowed"
          >
            {{ link.label.replace('&laquo;', '«').replace('&raquo;', '»') }}
          </span>
        </template>
      </div>
    </div>

    <!-- Sidebar -->
    <BlogSidebar :sidebar="sidebar" />
  </div>
</template>
```

### Step 2: Commit

```bash
git add resources/js/Pages/Blog/Archive.vue
git commit -m "feat: add Blog/Archive.vue page for category and tag archives"
```

---

## Task 8: Fix sidebar links + add discovery links to BlogLayout.vue

**Files:**
- Modify: `resources/js/Components/BlogSidebar.vue`
- Modify: `resources/js/Layouts/BlogLayout.vue`

### Step 1: Fix category links in `BlogSidebar.vue`

In `BlogSidebar.vue`, find and replace the category `<Link>` href:

Old:
```html
:href="`/?category=${cat.slug}`"
```
New:
```html
:href="`/blog/category/${cat.slug}`"
```

### Step 2: Fix tag links in `BlogSidebar.vue`

Find and replace the tag `<Link>` href:

Old:
```html
:href="`/?tag=${tag.slug}`"
```
New:
```html
:href="`/blog/tag/${tag.slug}`"
```

### Step 3: Add discovery links to `BlogLayout.vue`

In `BlogLayout.vue`, in `<script setup>`, the `appName` computed is already defined. No changes needed to the script.

In the `<template>`, the layout currently has no `<Head>` component. Add a `<Head>` import and tag. First add the import at the top of `<script setup>`:

```js
import { Head, usePage, Link } from '@inertiajs/vue3'
```

(Replace the existing `import { usePage, Link } from '@inertiajs/vue3'` line.)

Then add `<Head>` as the first element inside `<template>`, before the outer `<div>`:

```html
<Head>
  <link rel="alternate" type="application/rss+xml" :title="appName" href="/feed" />
  <link rel="sitemap" type="application/xml" title="Sitemap" href="/sitemap.xml" />
</Head>
```

### Step 4: Commit

```bash
git add resources/js/Components/BlogSidebar.vue resources/js/Layouts/BlogLayout.vue
git commit -m "fix: update sidebar links to archive pages; add RSS and sitemap discovery to blog layout"
```

---

## Task 9: Build assets and run full test suite

### Step 1: Build frontend assets

```bash
npm run build
```
Expected: build completes with no errors. Output in `public/build/`.

### Step 2: Run full test suite

```bash
php artisan test
```
Expected: all tests pass (the suite will have grown from 233 to ~250+ tests).

Note the exact count — record it for the PR description.

### Step 3: If any tests fail, fix before continuing

Do not proceed to Task 10 until all tests pass.

---

## Task 10: Reset database to installer state

> **Note:** This destroys all data in the local database. It's intentional — the goal is a clean slate.

### Step 1: Drop and re-run all migrations

```bash
php artisan migrate:fresh
```
Expected: `Migration table created successfully.` followed by all migrations running.

### Step 2: Remove the installed marker

```bash
rm storage/app/installed
```
Verify: `ls storage/app/` should no longer list `installed`.

### Step 3: Clear all caches

```bash
php artisan config:clear && php artisan cache:clear && php artisan view:clear
```

### Step 4: Confirm installer redirect

Visit `https://lambda-cms.test/` in the browser. It should redirect to `/install/database`.

> The database reset does not need a commit — `storage/app/installed` and `storage/` are gitignored.

---

## Task 11: Final verification, push, and PR

### Step 1: Run tests one final time from the worktree

```bash
php artisan test
```
Expected: all tests pass.

### Step 2: Check git log

```bash
git log --oneline
```
Expected: 7 new commits on `feature/archives-feed-sitemap`, all sitting on top of `master`.

### Step 3: Push branch

```bash
git push -u origin feature/archives-feed-sitemap
```

### Step 4: Create GitHub PR

```bash
gh pr create \
  --title "feat: category/tag archives, RSS feed, XML sitemap" \
  --body "$(cat <<'EOF'
## Summary

- **Category & tag archive pages** — `/blog/category/{slug}` and `/blog/tag/{slug}` show filtered, paginated post lists with per-archive SEO canonicals and headings. Fixes broken sidebar links that previously used query params going nowhere.
- **RSS feed** — `/feed` returns RSS 2.0 XML (20 most recent posts: title, excerpt, link). Auto-discovered via `<link rel=\"alternate\">` in the blog layout.
- **XML sitemap** — `/sitemap.xml` lists the blog index, all published posts, and all non-empty category/tag archive pages. Auto-discovered via `<link rel=\"sitemap\">` in the blog layout.
- **Refactor** — extracted `PostCard.vue` from `Blog/Index.vue` to share with `Blog/Archive.vue`.

## Test plan

- [ ] Visit `/blog/category/{slug}` — shows only posts in that category, correct heading, pagination works
- [ ] Visit `/blog/tag/{slug}` — shows only posts with that tag, correct heading
- [ ] Click a category in the blog sidebar — navigates to the archive page (not a filtered index)
- [ ] Click a tag in the blog sidebar — navigates to the archive page
- [ ] Visit `/feed` — valid RSS XML, browser feed reader detects it
- [ ] View page source on blog index — `<link rel="alternate">` and `<link rel="sitemap">` present in `<head>`
- [ ] Visit `/sitemap.xml` — contains post URLs, category URLs, tag URLs; no draft posts
- [ ] All automated tests pass

🤖 Generated with [Claude Code](https://claude.com/claude-code)
EOF
)"
```

### Step 5: Record the PR URL

Output the PR URL so the user can find it on GitHub.
