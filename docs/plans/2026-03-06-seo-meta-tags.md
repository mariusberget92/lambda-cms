# SEO Meta Tags Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add two-level SEO/Open Graph meta tag support — global defaults in Settings, per-post `meta_title` and `meta_description` overrides — rendered via a shared `SeoHead.vue` component on all public blog pages.

**Architecture:** Two nullable columns on `posts`, a new `seo` settings group (three keys), server-side fallback resolution in `BlogController`, and a single `SeoHead.vue` component consumed by `Blog/Show.vue` and `Blog/Index.vue`. Post editor gets a SEO sidebar panel; Settings page gets a new SEO panel.

**Tech Stack:** Laravel 12 (migrations, Eloquent, Inertia), PHPUnit feature tests (`php artisan test`), Vue 3 (`<Head>` from `@inertiajs/vue3`), Vite 7 (`npm run build`).

---

### Task 1: Migration + Post model + PostFactory

**Files:**
- Create: `database/migrations/2026_03_06_000002_add_seo_fields_to_posts_table.php`
- Modify: `app/Models/Post.php`
- Modify: `database/factories/PostFactory.php`
- Modify: `tests/Feature/PostTest.php`

**Context:** Follow the exact same pattern as the `comments_enabled` column added previously. `PostTest` already has a `setUp()` calling `markAsInstalled()` and `seedRolesAndPermissions()`. The `loginAsAdmin()` helper is available on the TestCase base class.

---

**Step 1: Write the failing tests**

Add to `tests/Feature/PostTest.php` (inside the class, after existing tests):

```php
public function test_post_has_nullable_meta_title(): void
{
    $post = Post::factory()->create(['meta_title' => null]);
    $this->assertNull($post->fresh()->meta_title);
}

public function test_post_has_nullable_meta_description(): void
{
    $post = Post::factory()->create(['meta_description' => null]);
    $this->assertNull($post->fresh()->meta_description);
}

public function test_post_meta_title_is_fillable(): void
{
    $post = Post::factory()->create(['meta_title' => 'Custom SEO Title']);
    $this->assertSame('Custom SEO Title', $post->fresh()->meta_title);
}

public function test_post_meta_description_is_fillable(): void
{
    $post = Post::factory()->create(['meta_description' => 'Custom meta desc']);
    $this->assertSame('Custom meta desc', $post->fresh()->meta_description);
}
```

**Step 2: Run tests to verify they fail**

```bash
php artisan test --filter "test_post_has_nullable_meta_title|test_post_has_nullable_meta_description|test_post_meta_title_is_fillable|test_post_meta_description_is_fillable"
```

Expected: FAIL — column does not exist.

**Step 3: Create the migration**

```php
<?php
// database/migrations/2026_03_06_000002_add_seo_fields_to_posts_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->string('meta_title')->nullable()->after('meta_description');
            $table->text('meta_description')->nullable()->after('meta_title');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn(['meta_title', 'meta_description']);
        });
    }
};
```

**Step 4: Add both fields to `$fillable` in `app/Models/Post.php`**

Add `'meta_title'` and `'meta_description'` after `'comments_enabled'` in the `$fillable` array:

```php
protected $fillable = [
    "user_id",
    "featured_image_id",
    "title",
    "slug",
    "excerpt",
    "body",
    "status",
    "published_at",
    "comments_enabled",
    "meta_title",
    "meta_description",
];
```

No cast is needed — both are plain strings.

**Step 5: Add null defaults to PostFactory**

In `database/factories/PostFactory.php`, add to the `definition()` return array:

```php
'meta_title'       => null,
'meta_description' => null,
```

**Step 6: Run tests to verify they pass**

```bash
php artisan test --filter "test_post_has_nullable_meta_title|test_post_has_nullable_meta_description|test_post_meta_title_is_fillable|test_post_meta_description_is_fillable"
```

Expected: PASS (4 tests).

**Step 7: Run full suite to verify no regressions**

```bash
php artisan test
```

Expected: all existing tests pass.

**Step 8: Commit**

```bash
git add database/migrations/2026_03_06_000002_add_seo_fields_to_posts_table.php \
        app/Models/Post.php \
        database/factories/PostFactory.php \
        tests/Feature/PostTest.php
git commit -m "feat: add meta_title and meta_description columns to posts"
```

---

### Task 2: SettingsSeeder — seo group

**Files:**
- Modify: `database/seeders/SettingsSeeder.php`
- Modify: `tests/Feature/SettingsTest.php`

**Context:** `SettingsSeeder` uses `DB::table('settings')->insertOrIgnore(...)` rows. `SettingsTest` has a local `seedSettings()` helper that inserts rows via `Setting::create()`. Both need updating. The test for `Setting::set()` requires the key to already exist in the DB — `Setting::set()` throws `\InvalidArgumentException` if the key is absent.

---

**Step 1: Write the failing test**

In `tests/Feature/SettingsTest.php`, add a new test method:

```php
public function test_administrator_can_update_seo_settings(): void
{
    $admin = $this->makeAdmin();

    $this->actingAs($admin)->put('/settings/seo', [
        'seo.title_separator'      => ' – ',
        'seo.default_description'  => 'My site about things',
        'seo.default_og_image_url' => 'https://example.com/og.jpg',
    ])->assertRedirect();

    $this->assertDatabaseHas('settings', [
        'key'   => 'seo.title_separator',
        'value' => ' – ',
    ]);
}
```

**Step 2: Run test to verify it fails**

```bash
php artisan test --filter "test_administrator_can_update_seo_settings"
```

Expected: FAIL — likely a 404 (group not in match) or validation error.

**Step 3: Add seo keys to `seedSettings()` in `SettingsTest`**

Add to the `$defaults` array inside the `seedSettings()` private method:

```php
['group' => 'seo', 'key' => 'seo.title_separator',      'value' => ' | ',  'type' => 'string'],
['group' => 'seo', 'key' => 'seo.default_description',   'value' => '',     'type' => 'string'],
['group' => 'seo', 'key' => 'seo.default_og_image_url',  'value' => '',     'type' => 'string'],
```

**Step 4: Add seo keys to `SettingsSeeder`**

In `database/seeders/SettingsSeeder.php`, add to the `$defaults` array (after the Comments block):

```php
// SEO
['group' => 'seo', 'key' => 'seo.title_separator',      'value' => ' | ',  'type' => 'string'],
['group' => 'seo', 'key' => 'seo.default_description',   'value' => '',     'type' => 'string'],
['group' => 'seo', 'key' => 'seo.default_og_image_url',  'value' => '',     'type' => 'string'],
```

**Step 5: Add `'seo'` case to `SettingsController::update()`**

In `app/Http/Controllers/SettingsController.php`, the `update()` method has a `match ($group)` block. Add a new case before `default => abort(404)`:

```php
'seo' => $request->validate([
    'seo\\.title_separator'      => ['required', 'string', 'max:10'],
    'seo\\.default_description'  => ['nullable', 'string', 'max:300'],
    'seo\\.default_og_image_url' => ['nullable', 'url', 'max:500'],
]),
```

Note: the double-escaped dots (`\\.`) match the pattern used by all other cases in this controller (e.g. `'site\\.name'`).

**Step 6: Run test to verify it passes**

```bash
php artisan test --filter "test_administrator_can_update_seo_settings"
```

Expected: PASS.

**Step 7: Run full suite**

```bash
php artisan test
```

Expected: all pass.

**Step 8: Commit**

```bash
git add database/seeders/SettingsSeeder.php \
        app/Http/Controllers/SettingsController.php \
        tests/Feature/SettingsTest.php
git commit -m "feat: add seo settings group to seeder and SettingsController"
```

---

### Task 3: PostController — store / update / edit

**Files:**
- Modify: `app/Http/Controllers/PostController.php`
- Modify: `tests/Feature/PostTest.php`

**Context:** `PostController::store()` and `update()` call `$request->validate([...])`, then `unset()` certain keys before persisting. Study how `comments_enabled` is handled in both methods — the SEO fields follow the exact same pattern. Key difference: `update()` must fall back to the existing model value (not `null`) when the field is absent from the request, to avoid accidentally clearing fields.

---

**Step 1: Write the failing tests**

Add to `tests/Feature/PostTest.php`:

```php
public function test_post_store_persists_meta_title_and_meta_description(): void
{
    $this->loginAsAdmin();
    $category = \App\Models\Category::factory()->create();

    $this->post(route('posts.store'), [
        'title'            => 'SEO Test Post',
        'body'             => '<p>body</p>',
        'status'           => 'draft',
        'categories'       => [$category->id],
        'tags'             => [],
        'meta_title'       => 'Custom SEO Title',
        'meta_description' => 'Custom meta description',
    ])->assertRedirect();

    $this->assertDatabaseHas('posts', [
        'title'            => 'SEO Test Post',
        'meta_title'       => 'Custom SEO Title',
        'meta_description' => 'Custom meta description',
    ]);
}

public function test_post_store_defaults_meta_fields_to_null_when_absent(): void
{
    $this->loginAsAdmin();
    $category = \App\Models\Category::factory()->create();

    $this->post(route('posts.store'), [
        'title'      => 'No SEO Post',
        'body'       => '<p>body</p>',
        'status'     => 'draft',
        'categories' => [$category->id],
        'tags'       => [],
    ])->assertRedirect();

    $post = Post::where('title', 'No SEO Post')->first();
    $this->assertNull($post->meta_title);
    $this->assertNull($post->meta_description);
}

public function test_post_update_persists_meta_fields(): void
{
    $user = $this->loginAsAdmin();
    $post = Post::factory()->for($user)->create(['meta_title' => null]);

    $this->put(route('posts.update', $post), [
        'title'            => $post->title,
        'body'             => $post->body,
        'status'           => $post->status,
        'categories'       => [],
        'tags'             => [],
        'meta_title'       => 'Updated SEO Title',
        'meta_description' => 'Updated meta desc',
    ])->assertRedirect();

    $this->assertSame('Updated SEO Title', $post->fresh()->meta_title);
    $this->assertSame('Updated meta desc', $post->fresh()->meta_description);
}

public function test_post_update_preserves_meta_fields_when_absent_from_request(): void
{
    $user = $this->loginAsAdmin();
    $post = Post::factory()->for($user)->create([
        'meta_title'       => 'Existing SEO Title',
        'meta_description' => 'Existing meta desc',
    ]);

    $this->put(route('posts.update', $post), [
        'title'      => $post->title,
        'body'       => $post->body,
        'status'     => $post->status,
        'categories' => [],
        'tags'       => [],
        // meta fields intentionally absent
    ])->assertRedirect();

    $this->assertSame('Existing SEO Title', $post->fresh()->meta_title);
    $this->assertSame('Existing meta desc', $post->fresh()->meta_description);
}

public function test_post_edit_includes_meta_fields_in_props(): void
{
    $user = $this->loginAsAdmin();
    $post = Post::factory()->for($user)->create([
        'meta_title'       => 'My SEO Title',
        'meta_description' => 'My meta desc',
    ]);

    $this->get(route('posts.edit', $post))
        ->assertInertia(fn ($page) => $page
            ->where('post.meta_title', 'My SEO Title')
            ->where('post.meta_description', 'My meta desc')
        );
}
```

**Step 2: Run tests to verify they fail**

```bash
php artisan test --filter "test_post_store_persists_meta|test_post_store_defaults_meta|test_post_update_persists_meta|test_post_update_preserves_meta|test_post_edit_includes_meta"
```

Expected: FAIL.

**Step 3: Update `PostController::store()`**

Add to the validation rules array in `store()`:

```php
'meta_title'       => ['nullable', 'string', 'max:100'],
'meta_description' => ['nullable', 'string', 'max:300'],
```

After the `unset()` calls in `store()`, add:

```php
$validated['meta_title']       = $validated['meta_title']       ?? null;
$validated['meta_description'] = $validated['meta_description'] ?? null;
```

**Step 4: Update `PostController::update()`**

Add the same two validation rules to `update()`. After the `unset()` calls:

```php
$validated['meta_title']       = $validated['meta_title']       ?? $post->meta_title;
$validated['meta_description'] = $validated['meta_description'] ?? $post->meta_description;
```

(Uses `$post->meta_title` as fallback — preserves the existing value when the field is absent from the request, instead of accidentally clearing it.)

**Step 5: Update `PostController::edit()`**

Add both fields to the Inertia props array passed to `Posts/Edit`:

```php
'meta_title'       => $post->meta_title,
'meta_description' => $post->meta_description,
```

**Step 6: Run tests to verify they pass**

```bash
php artisan test --filter "test_post_store_persists_meta|test_post_store_defaults_meta|test_post_update_persists_meta|test_post_update_preserves_meta|test_post_edit_includes_meta"
```

Expected: PASS (5 tests).

**Step 7: Run full suite**

```bash
php artisan test
```

Expected: all pass.

**Step 8: Commit**

```bash
git add app/Http/Controllers/PostController.php tests/Feature/PostTest.php
git commit -m "feat: add meta_title and meta_description to PostController"
```

---

### Task 4: BlogController — seo prop

**Files:**
- Modify: `app/Http/Controllers/BlogController.php`
- Modify: `tests/Feature/BlogTest.php`

**Context:** `BlogController::show()` already imports `Setting`. `BlogTest::setUp()` only calls `markAsInstalled()` — no settings are seeded by default. Tests that need specific setting values must seed them inline using `Setting::create([...])` + `app(\App\Services\SettingService::class)->bust()` (to invalidate the in-process settings cache). The blog index route is `/` and the show route is `/blog/{slug}`.

---

**Step 1: Add `seedSeoSettings()` helper to `BlogTest`**

Add this private method to `tests/Feature/BlogTest.php`:

```php
private function seedSeoSettings(
    string $separator        = ' | ',
    string $defaultDesc      = '',
    string $defaultOgImage   = '',
    string $siteName         = 'Test Site'
): void {
    \App\Models\Setting::insert([
        ['group' => 'site', 'key' => 'site.name',                'value' => $siteName,       'type' => 'string',  'created_at' => now(), 'updated_at' => now()],
        ['group' => 'seo',  'key' => 'seo.title_separator',      'value' => $separator,      'type' => 'string',  'created_at' => now(), 'updated_at' => now()],
        ['group' => 'seo',  'key' => 'seo.default_description',  'value' => $defaultDesc,    'type' => 'string',  'created_at' => now(), 'updated_at' => now()],
        ['group' => 'seo',  'key' => 'seo.default_og_image_url', 'value' => $defaultOgImage, 'type' => 'string',  'created_at' => now(), 'updated_at' => now()],
    ]);
    app(\App\Services\SettingService::class)->bust();
}
```

**Step 2: Write the failing tests**

Add to `tests/Feature/BlogTest.php`:

```php
// ── SEO prop: show ────────────────────────────────────────────────────────

public function test_blog_show_seo_title_uses_meta_title_when_set(): void
{
    $this->seedSeoSettings();
    $post = Post::factory()->published()->create([
        'title'      => 'Post Title',
        'meta_title' => 'Custom SEO Title',
    ]);

    $this->get("/blog/{$post->slug}")
        ->assertInertia(fn ($page) => $page
            ->where('seo.title', 'Custom SEO Title | Test Site')
        );
}

public function test_blog_show_seo_title_falls_back_to_post_title_when_meta_title_absent(): void
{
    $this->seedSeoSettings();
    $post = Post::factory()->published()->create([
        'title'      => 'Post Title',
        'meta_title' => null,
    ]);

    $this->get("/blog/{$post->slug}")
        ->assertInertia(fn ($page) => $page
            ->where('seo.title', 'Post Title | Test Site')
        );
}

public function test_blog_show_seo_description_uses_meta_description_when_set(): void
{
    $this->seedSeoSettings();
    $post = Post::factory()->published()->create([
        'excerpt'          => 'Post excerpt',
        'meta_description' => 'Custom meta desc',
    ]);

    $this->get("/blog/{$post->slug}")
        ->assertInertia(fn ($page) => $page
            ->where('seo.description', 'Custom meta desc')
        );
}

public function test_blog_show_seo_description_falls_back_to_excerpt(): void
{
    $this->seedSeoSettings();
    $post = Post::factory()->published()->create([
        'excerpt'          => 'Post excerpt',
        'meta_description' => null,
    ]);

    $this->get("/blog/{$post->slug}")
        ->assertInertia(fn ($page) => $page
            ->where('seo.description', 'Post excerpt')
        );
}

public function test_blog_show_seo_description_falls_back_to_global_default(): void
{
    $this->seedSeoSettings(defaultDesc: 'Site-wide default desc');
    $post = Post::factory()->published()->create([
        'excerpt'          => null,
        'meta_description' => null,
    ]);

    $this->get("/blog/{$post->slug}")
        ->assertInertia(fn ($page) => $page
            ->where('seo.description', 'Site-wide default desc')
        );
}

public function test_blog_show_seo_image_falls_back_to_global_default_when_no_featured_image(): void
{
    $this->seedSeoSettings(defaultOgImage: 'https://example.com/default.jpg');
    $post = Post::factory()->published()->create(['featured_image_id' => null]);

    $this->get("/blog/{$post->slug}")
        ->assertInertia(fn ($page) => $page
            ->where('seo.image', 'https://example.com/default.jpg')
        );
}

public function test_blog_show_seo_type_is_article(): void
{
    $this->seedSeoSettings();
    $post = Post::factory()->published()->create();

    $this->get("/blog/{$post->slug}")
        ->assertInertia(fn ($page) => $page
            ->where('seo.type', 'article')
        );
}

public function test_blog_show_seo_canonical_is_post_url(): void
{
    $this->seedSeoSettings();
    $post = Post::factory()->published()->create(['slug' => 'my-post']);

    $this->get('/blog/my-post')
        ->assertInertia(fn ($page) => $page
            ->where('seo.canonical', url('/blog/my-post'))
        );
}

// ── SEO prop: index ───────────────────────────────────────────────────────

public function test_blog_index_seo_title_uses_site_name(): void
{
    $this->seedSeoSettings(siteName: 'My Blog');

    $this->get('/')
        ->assertInertia(fn ($page) => $page
            ->where('seo.title', 'My Blog')
        );
}

public function test_blog_index_seo_type_is_website(): void
{
    $this->seedSeoSettings();

    $this->get('/')
        ->assertInertia(fn ($page) => $page
            ->where('seo.type', 'website')
        );
}

public function test_blog_index_seo_uses_global_default_description(): void
{
    $this->seedSeoSettings(defaultDesc: 'Welcome to the blog');

    $this->get('/')
        ->assertInertia(fn ($page) => $page
            ->where('seo.description', 'Welcome to the blog')
        );
}
```

**Step 3: Run tests to verify they fail**

```bash
php artisan test --filter "test_blog_show_seo|test_blog_index_seo"
```

Expected: FAIL — `seo` prop not present on Inertia response.

**Step 4: Update `BlogController::show()`**

Add seo resolution before `Inertia::render()` in `show()`:

```php
$separator = Setting::get('seo.title_separator', ' | ');
$siteName  = Setting::get('site.name', config('app.name'));

$seo = [
    'title'       => ($post->meta_title ?: $post->title) . $separator . $siteName,
    'description' => $post->meta_description ?: $post->excerpt ?: Setting::get('seo.default_description', ''),
    'image'       => $post->featuredImage?->url ?: Setting::get('seo.default_og_image_url', ''),
    'canonical'   => url("/blog/{$post->slug}"),
    'type'        => 'article',
];
```

Add `'seo' => $seo` to the `Inertia::render('Blog/Show', [...])` props array.

**Step 5: Update `BlogController::index()`**

Add inside `index()` before `Inertia::render()`:

```php
$siteName = Setting::get('site.name', config('app.name'));

$seo = [
    'title'       => $siteName,
    'description' => Setting::get('seo.default_description', ''),
    'image'       => Setting::get('seo.default_og_image_url', ''),
    'canonical'   => url('/blog'),
    'type'        => 'website',
];
```

Add `'seo' => $seo` to the `Inertia::render('Blog/Index', [...])` props array.

**Step 6: Run tests to verify they pass**

```bash
php artisan test --filter "test_blog_show_seo|test_blog_index_seo"
```

Expected: PASS (11 tests).

**Step 7: Run full suite**

```bash
php artisan test
```

Expected: all pass.

**Step 8: Commit**

```bash
git add app/Http/Controllers/BlogController.php tests/Feature/BlogTest.php
git commit -m "feat: add seo prop to BlogController show and index"
```

---

### Task 5: Frontend — SeoHead component, Blog views, Post editor, Settings panel

**Files:**
- Create: `resources/js/Components/SeoHead.vue`
- Modify: `resources/js/Pages/Blog/Show.vue`
- Modify: `resources/js/Pages/Blog/Index.vue`
- Modify: `resources/js/Pages/Posts/Create.vue`
- Modify: `resources/js/Pages/Posts/Edit.vue`
- Modify: `resources/js/Pages/Settings/Index.vue`

**Context:**
- `<Head>` from `@inertiajs/vue3` is Inertia's mechanism for injecting into the page `<head>`. When you put `<title>`, `<meta>`, or `<link>` tags inside `<Head>`, Inertia hoists them into the real `<head>` during SSR and client-side navigation.
- `Blog/Show.vue` uses `defineOptions({ layout: BlogLayout })` and currently has **no** `<Head>` at all.
- `Blog/Index.vue` — same: no existing Head usage.
- `Posts/Create.vue` and `Posts/Edit.vue` have a sidebar with cards. The SEO panel goes **after the Comments panel**. Follow the exact HTML structure of the existing Comments panel for visual consistency.
- `Settings/Index.vue` — add the SEO `<form>` panel after the Comments panel and before the "Send test email" panel. Follow the exact same card structure (`rounded-lg border bg-card p-6 space-y-4`) used by all other panels.
- No backend tests are needed for this task — verify correctness with `npm run build`.

---

**Step 1: Create `resources/js/Components/SeoHead.vue`**

```vue
<script setup>
import { Head } from '@inertiajs/vue3'

defineProps({ seo: { type: Object, required: true } })
</script>

<template>
  <Head>
    <title>{{ seo.title }}</title>
    <meta name="description"        :content="seo.description"       v-if="seo.description" />
    <link rel="canonical"           :href="seo.canonical" />
    <meta property="og:type"        :content="seo.type ?? 'website'" />
    <meta property="og:url"         :content="seo.canonical" />
    <meta property="og:title"       :content="seo.title" />
    <meta property="og:description" :content="seo.description"       v-if="seo.description" />
    <meta property="og:image"       :content="seo.image"             v-if="seo.image" />
  </Head>
</template>
```

**Step 2: Update `Blog/Show.vue`**

Add `seo: { type: Object, required: true }` to `defineProps`. Import `SeoHead` at the top of `<script setup>`:

```js
import SeoHead from '@/Components/SeoHead.vue'
```

Add `<SeoHead :seo="seo" />` as the **very first element** inside `<template>` (before the existing `<div class="grid...">`).

**Step 3: Update `Blog/Index.vue`**

Same as Show: add `seo` prop, import `SeoHead`, add `<SeoHead :seo="seo" />` as the first element in `<template>`.

**Step 4: Update `Posts/Create.vue`**

Add to `useForm({...})`:

```js
meta_title:       null,
meta_description: null,
```

Add the SEO panel in the sidebar **after the Comments panel** (look for the closing `</div>` of the Comments panel and insert after it):

```html
<!-- SEO -->
<div class="rounded-lg border bg-card p-4">
  <h3 class="text-sm font-medium mb-3">SEO</h3>
  <div class="space-y-3">
    <div>
      <label class="block text-xs font-medium mb-1">Meta title</label>
      <input
        v-model="form.meta_title"
        type="text"
        maxlength="100"
        class="w-full rounded-md border bg-background px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        placeholder="Leave blank to use post title"
      />
      <p class="text-xs text-muted-foreground mt-1">{{ (form.meta_title ?? '').length }}/100</p>
    </div>
    <div>
      <label class="block text-xs font-medium mb-1">Meta description</label>
      <textarea
        v-model="form.meta_description"
        rows="3"
        maxlength="300"
        class="w-full rounded-md border bg-background px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring resize-none"
        placeholder="Leave blank to use excerpt"
      />
      <p class="text-xs text-muted-foreground mt-1">{{ (form.meta_description ?? '').length }}/300</p>
    </div>
  </div>
</div>
```

**Step 5: Update `Posts/Edit.vue`**

Add to `useForm({...})`:

```js
meta_title:       props.post.meta_title       ?? null,
meta_description: props.post.meta_description ?? null,
```

Add the **identical** SEO panel from Step 4 after the Comments panel.

**Step 6: Update `Settings/Index.vue` — template**

Add after the Comments `</form>` and before the "Send test email" `<form>`:

```html
<!-- ── SEO panel ──────────────────────────────────────────────────── -->
<form @submit.prevent="submitSeo">
  <div class="rounded-lg border bg-card p-6 space-y-4">
    <div>
      <h3 class="text-sm font-semibold">SEO</h3>
      <p class="text-xs text-muted-foreground mt-0.5">Default meta tags for public blog pages.</p>
    </div>

    <div class="space-y-1">
      <label for="seo_title_separator" class="text-sm font-medium">Title separator</label>
      <input
        id="seo_title_separator"
        v-model="seoForm['seo.title_separator']"
        type="text"
        class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
      />
      <p class="text-xs text-muted-foreground">Characters between post title and site name, e.g. " | ".</p>
    </div>

    <div class="space-y-1">
      <label for="seo_default_description" class="text-sm font-medium">Default meta description</label>
      <textarea
        id="seo_default_description"
        v-model="seoForm['seo.default_description']"
        rows="3"
        class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring resize-none"
        placeholder="Used when a post has no excerpt or custom meta description"
      />
    </div>

    <div class="space-y-1">
      <label for="seo_og_image_url" class="text-sm font-medium">Default OG image URL</label>
      <input
        id="seo_og_image_url"
        v-model="seoForm['seo.default_og_image_url']"
        type="url"
        class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        placeholder="https://example.com/og-default.jpg"
      />
      <p class="text-xs text-muted-foreground">Used on pages with no featured image.</p>
    </div>

    <div class="flex justify-end pt-1">
      <button
        type="submit"
        :disabled="seoForm.processing"
        class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 disabled:opacity-50"
      >
        {{ seoForm.processing ? 'Saving...' : 'Save changes' }}
      </button>
    </div>
  </div>
</form>
```

**Step 7: Update `Settings/Index.vue` — script**

Add after the `commentsForm` / `submitComments` block in `<script setup>`:

```js
// ── SEO form ──────────────────────────────────────────────────────────────────
const seoForm = useForm({
  'seo.title_separator':      props.settings['seo.title_separator']      ?? ' | ',
  'seo.default_description':  props.settings['seo.default_description']  ?? '',
  'seo.default_og_image_url': props.settings['seo.default_og_image_url'] ?? '',
})

function submitSeo() {
  seoForm.put(route('settings.update', 'seo'), { preserveScroll: true })
}
```

**Step 8: Build to verify no errors**

```bash
npm run build
```

Expected: exits 0 with no TypeScript/Vue errors.

**Step 9: Run full test suite**

```bash
php artisan test
```

Expected: all tests pass.

**Step 10: Commit**

```bash
git add resources/js/Components/SeoHead.vue \
        resources/js/Pages/Blog/Show.vue \
        resources/js/Pages/Blog/Index.vue \
        resources/js/Pages/Posts/Create.vue \
        resources/js/Pages/Posts/Edit.vue \
        resources/js/Pages/Settings/Index.vue
git commit -m "feat: add SeoHead component, SEO panels in post editor and settings"
```

---

### Task 6: Final verification + finish branch

**Step 1: Run the full test suite**

```bash
php artisan test
```

Expected: all tests pass. The count should be noticeably higher than the pre-feature baseline (203 tests → ~220+).

**Step 2: Invoke the finishing-a-development-branch skill**

Use `superpowers:finishing-a-development-branch` to merge, push, and clean up.
