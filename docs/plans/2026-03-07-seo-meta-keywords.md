# SEO Meta Keywords Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add `meta_keywords` as a nullable per-post field and a global `seo.default_keywords` setting, rendered as `<meta name="keywords">` in `SeoHead.vue`.

**Architecture:** One new migration column on `posts`, one new settings row, validation added to `PostController` and `SettingsController`, keywords resolved in `BlogController` with the same fallback pattern as description/OG image. Frontend changes are additive — one new input in three Vue files and one new tag in `SeoHead.vue`.

**Tech Stack:** Laravel 11, Inertia.js, Vue 3 (Composition API), Pest/PHPUnit, Tailwind CSS.

---

### Task 1: Migration — add `meta_keywords` to posts

**Files:**
- Create: `database/migrations/2026_03_07_000001_add_meta_keywords_to_posts_table.php`
- Test: `tests/Feature/PostTest.php`

**Step 1: Write the failing test**

Open `tests/Feature/PostTest.php`. Add this test at the bottom of the class (before the closing `}`):

```php
public function test_post_can_store_meta_keywords(): void
{
    $user = $this->makeUser();

    $this->actingAs($user)->post('/posts', [
        'title'         => 'Keywords Post',
        'status'        => 'draft',
        'meta_keywords' => 'laravel, cms, blog',
    ])->assertRedirect('/posts');

    $this->assertDatabaseHas('posts', [
        'title'         => 'Keywords Post',
        'meta_keywords' => 'laravel, cms, blog',
    ]);
}
```

**Step 2: Run the test to verify it fails**

```bash
cd /c/Users/mariu/Herd/lambda-cms/.claude/worktrees/tender-golick
php artisan test tests/Feature/PostTest.php --filter=test_post_can_store_meta_keywords
```

Expected: FAIL — column `meta_keywords` does not exist.

**Step 3: Create the migration**

```bash
php artisan make:migration add_meta_keywords_to_posts_table
```

Open the generated file in `database/migrations/` and replace its `up`/`down` bodies:

```php
public function up(): void
{
    Schema::table('posts', function (Blueprint $table) {
        $table->string('meta_keywords')->nullable()->after('meta_description');
    });
}

public function down(): void
{
    Schema::table('posts', function (Blueprint $table) {
        $table->dropColumn('meta_keywords');
    });
}
```

**Step 4: Run the migration**

```bash
php artisan migrate
```

**Step 5: Run the test to verify it still fails (model not fillable yet)**

```bash
php artisan test tests/Feature/PostTest.php --filter=test_post_can_store_meta_keywords
```

Expected: FAIL — assertDatabaseHas fails because the field isn't accepted yet (next task fixes this).

**Step 6: Commit the migration**

```bash
git add database/migrations/
git commit -m "$(cat <<'EOF'
Add meta_keywords column to posts table

Co-Authored-By: Claude Sonnet 4.6 <noreply@anthropic.com>
EOF
)"
```

---

### Task 2: Post model — add `meta_keywords` to `$fillable`

**Files:**
- Modify: `app/Models/Post.php`

**Step 1: Add to `$fillable`**

In `app/Models/Post.php`, find the `$fillable` array and add `"meta_keywords"` after `"meta_description"`:

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
    "meta_keywords",
];
```

**Step 2: Run the test — still fails (validation not added yet)**

```bash
php artisan test tests/Feature/PostTest.php --filter=test_post_can_store_meta_keywords
```

Expected: FAIL — validation rejects `meta_keywords`.

**Step 3: Commit the model change**

```bash
git add app/Models/Post.php
git commit -m "$(cat <<'EOF'
Add meta_keywords to Post fillable

Co-Authored-By: Claude Sonnet 4.6 <noreply@anthropic.com>
EOF
)"
```

---

### Task 3: PostController — validate and pass `meta_keywords`

**Files:**
- Modify: `app/Http/Controllers/PostController.php`
- Test: `tests/Feature/PostTest.php`

**Step 1: Add validation to `store()`**

In `PostController::store()`, find the validation block and add after `'meta_description'`:

```php
'meta_keywords' => ['nullable', 'string', 'max:255'],
```

Also add after the `$validated['meta_description'] = ...` default line:

```php
$validated['meta_keywords'] = $validated['meta_keywords'] ?? null;
```

**Step 2: Add validation to `update()`**

Same change in `PostController::update()` — add the rule to validation:

```php
'meta_keywords' => ['nullable', 'string', 'max:255'],
```

And after the `$validated['meta_description'] = ...` line:

```php
$validated['meta_keywords'] = $validated['meta_keywords'] ?? $post->meta_keywords;
```

**Step 3: Expose `meta_keywords` in `edit()`**

In `PostController::edit()`, find the `'post' => [...]` array and add after `'meta_description'`:

```php
'meta_keywords'     => $post->meta_keywords,
```

**Step 4: Run the store test — now passes**

```bash
php artisan test tests/Feature/PostTest.php --filter=test_post_can_store_meta_keywords
```

Expected: PASS.

**Step 5: Write a test for update and edit**

Add to `tests/Feature/PostTest.php`:

```php
public function test_post_can_update_meta_keywords(): void
{
    $user = $this->makeUser();
    $post = Post::factory()->create(['user_id' => $user->id, 'meta_keywords' => 'old, keywords']);

    $this->actingAs($user)->put("/posts/{$post->id}", [
        'title'         => $post->title,
        'status'        => $post->status,
        'meta_keywords' => 'new, keywords',
    ])->assertRedirect('/posts');

    $this->assertDatabaseHas('posts', ['id' => $post->id, 'meta_keywords' => 'new, keywords']);
}

public function test_edit_page_includes_meta_keywords(): void
{
    $user = $this->makeUser();
    $post = Post::factory()->create(['user_id' => $user->id, 'meta_keywords' => 'test, keywords']);

    $this->actingAs($user)->get("/posts/{$post->id}/edit")
        ->assertInertia(
            fn ($page) => $page
                ->component('Posts/Edit')
                ->where('post.meta_keywords', 'test, keywords')
        );
}
```

**Step 6: Run the new tests**

```bash
php artisan test tests/Feature/PostTest.php --filter="test_post_can_update_meta_keywords|test_edit_page_includes_meta_keywords"
```

Expected: both PASS.

**Step 7: Run all PostTest to check nothing broke**

```bash
php artisan test tests/Feature/PostTest.php
```

Expected: all PASS.

**Step 8: Commit**

```bash
git add app/Http/Controllers/PostController.php tests/Feature/PostTest.php
git commit -m "$(cat <<'EOF'
Add meta_keywords validation and edit exposure to PostController

Co-Authored-By: Claude Sonnet 4.6 <noreply@anthropic.com>
EOF
)"
```

---

### Task 4: Settings seeder + SettingsController — `seo.default_keywords`

**Files:**
- Modify: `database/seeders/SettingsSeeder.php`
- Modify: `app/Http/Controllers/SettingsController.php`
- Test: `tests/Feature/SettingsTest.php`

**Step 1: Write the failing test**

In `tests/Feature/SettingsTest.php`, find the `seedSettings()` helper and add the new row:

```php
['group' => 'seo', 'key' => 'seo.default_keywords', 'value' => '', 'type' => 'string'],
```

(Add it after the `seo.default_og_image_url` row.)

Then add a test method at the bottom of the class:

```php
public function test_admin_can_save_seo_default_keywords(): void
{
    $admin = $this->makeAdmin();

    $this->actingAs($admin)->put('/settings/seo', [
        'seo.title_separator'      => ' | ',
        'seo.default_description'  => '',
        'seo.default_og_image_url' => '',
        'seo.default_keywords'     => 'laravel, cms',
    ])->assertRedirect();

    $this->assertDatabaseHas('settings', [
        'key'   => 'seo.default_keywords',
        'value' => 'laravel, cms',
    ]);
}
```

**Step 2: Run the test to verify it fails**

```bash
php artisan test tests/Feature/SettingsTest.php --filter=test_admin_can_save_seo_default_keywords
```

Expected: FAIL — validation rejects `seo.default_keywords`.

**Step 3: Add validation to SettingsController**

In `app/Http/Controllers/SettingsController.php`, find the `'seo'` match arm and add:

```php
'seo' => $request->validate([
    'seo\\.title_separator'      => ['required', 'string', 'max:10'],
    'seo\\.default_description'  => ['nullable', 'string', 'max:300'],
    'seo\\.default_og_image_url' => ['nullable', 'url', 'max:500'],
    'seo\\.default_keywords'     => ['nullable', 'string', 'max:255'],
]),
```

**Step 4: Add row to SettingsSeeder**

In `database/seeders/SettingsSeeder.php`, add after `seo.default_og_image_url`:

```php
['group' => 'seo', 'key' => 'seo.default_keywords', 'value' => '', 'type' => 'string'],
```

**Step 5: Insert the row for existing installs**

```bash
php artisan tinker --execute="DB::table('settings')->insertOrIgnore(['group'=>'seo','key'=>'seo.default_keywords','value'=>'','type'=>'string','created_at'=>now(),'updated_at'=>now()]);"
```

**Step 6: Run the test — now passes**

```bash
php artisan test tests/Feature/SettingsTest.php --filter=test_admin_can_save_seo_default_keywords
```

Expected: PASS.

**Step 7: Run all SettingsTest**

```bash
php artisan test tests/Feature/SettingsTest.php
```

Expected: all PASS.

**Step 8: Commit**

```bash
git add database/seeders/SettingsSeeder.php app/Http/Controllers/SettingsController.php tests/Feature/SettingsTest.php
git commit -m "$(cat <<'EOF'
Add seo.default_keywords setting

Co-Authored-By: Claude Sonnet 4.6 <noreply@anthropic.com>
EOF
)"
```

---

### Task 5: BlogController — add `keywords` to SEO prop

**Files:**
- Modify: `app/Http/Controllers/BlogController.php`
- Test: `tests/Feature/BlogTest.php`

**Step 1: Write failing tests**

Add to `tests/Feature/BlogTest.php`:

```php
public function test_blog_show_uses_post_meta_keywords_when_set(): void
{
    \App\Models\Setting::insert([
        ['group' => 'seo', 'key' => 'seo.title_separator',     'value' => ' | ', 'type' => 'string', 'created_at' => now(), 'updated_at' => now()],
        ['group' => 'seo', 'key' => 'seo.default_keywords',    'value' => 'global, keywords', 'type' => 'string', 'created_at' => now(), 'updated_at' => now()],
        ['group' => 'site', 'key' => 'site.name',              'value' => 'Lambda CMS', 'type' => 'string', 'created_at' => now(), 'updated_at' => now()],
        ['group' => 'seo', 'key' => 'seo.default_description', 'value' => '', 'type' => 'string', 'created_at' => now(), 'updated_at' => now()],
        ['group' => 'seo', 'key' => 'seo.default_og_image_url','value' => '', 'type' => 'string', 'created_at' => now(), 'updated_at' => now()],
    ]);

    $post = Post::factory()->published()->create(['meta_keywords' => 'post, keywords']);

    $this->get("/blog/{$post->slug}")
        ->assertInertia(
            fn ($page) => $page->where('seo.keywords', 'post, keywords')
        );
}

public function test_blog_show_falls_back_to_default_keywords(): void
{
    \App\Models\Setting::insert([
        ['group' => 'seo', 'key' => 'seo.title_separator',     'value' => ' | ', 'type' => 'string', 'created_at' => now(), 'updated_at' => now()],
        ['group' => 'seo', 'key' => 'seo.default_keywords',    'value' => 'global, keywords', 'type' => 'string', 'created_at' => now(), 'updated_at' => now()],
        ['group' => 'site', 'key' => 'site.name',              'value' => 'Lambda CMS', 'type' => 'string', 'created_at' => now(), 'updated_at' => now()],
        ['group' => 'seo', 'key' => 'seo.default_description', 'value' => '', 'type' => 'string', 'created_at' => now(), 'updated_at' => now()],
        ['group' => 'seo', 'key' => 'seo.default_og_image_url','value' => '', 'type' => 'string', 'created_at' => now(), 'updated_at' => now()],
    ]);

    $post = Post::factory()->published()->create(['meta_keywords' => null]);

    $this->get("/blog/{$post->slug}")
        ->assertInertia(
            fn ($page) => $page->where('seo.keywords', 'global, keywords')
        );
}
```

**Step 2: Run the tests to verify they fail**

```bash
php artisan test tests/Feature/BlogTest.php --filter="test_blog_show_uses_post_meta_keywords|test_blog_show_falls_back_to_default_keywords"
```

Expected: FAIL — `seo.keywords` key does not exist.

**Step 3: Update BlogController `show()`**

In `app/Http/Controllers/BlogController.php`, find the `$seo` array in `show()` and add after `'type' => 'article'`:

```php
'keywords' => $post->meta_keywords ?: Setting::get('seo.default_keywords', ''),
```

The full updated `$seo` array should look like:

```php
$seo = [
    'title'       => ($post->meta_title ?: $post->title) . $separator . $siteName,
    'description' => $post->meta_description ?: $post->excerpt ?: Setting::get('seo.default_description', ''),
    'image'       => $post->featuredImage?->url ?: Setting::get('seo.default_og_image_url', ''),
    'canonical'   => url("/blog/{$post->slug}"),
    'type'        => 'article',
    'keywords'    => $post->meta_keywords ?: Setting::get('seo.default_keywords', ''),
];
```

**Step 4: Update BlogController `index()`**

In `index()`, add to the `$seo` array:

```php
'keywords' => Setting::get('seo.default_keywords', ''),
```

**Step 5: Run the tests — now pass**

```bash
php artisan test tests/Feature/BlogTest.php --filter="test_blog_show_uses_post_meta_keywords|test_blog_show_falls_back_to_default_keywords"
```

Expected: both PASS.

**Step 6: Run all BlogTest**

```bash
php artisan test tests/Feature/BlogTest.php
```

Expected: all PASS.

**Step 7: Commit**

```bash
git add app/Http/Controllers/BlogController.php tests/Feature/BlogTest.php
git commit -m "$(cat <<'EOF'
Add keywords to BlogController SEO prop with fallback chain

Co-Authored-By: Claude Sonnet 4.6 <noreply@anthropic.com>
EOF
)"
```

---

### Task 6: SeoHead.vue — render `<meta name="keywords">`

**Files:**
- Modify: `resources/js/Components/SeoHead.vue`

**Step 1: Add the meta tag**

Open `resources/js/Components/SeoHead.vue`. After the `og:image` meta tag, add:

```html
<meta name="keywords" :content="seo.keywords" v-if="seo.keywords" />
```

The full template should now be:

```html
<template>
  <Head>
    <title>{{ seo.title }}</title>
    <meta name="description"        :content="seo.description"       v-if="seo.description" />
    <meta name="keywords"           :content="seo.keywords"           v-if="seo.keywords" />
    <link rel="canonical"           :href="seo.canonical" />
    <meta property="og:type"        :content="seo.type ?? 'website'" />
    <meta property="og:url"         :content="seo.canonical" />
    <meta property="og:title"       :content="seo.title" />
    <meta property="og:description" :content="seo.description"       v-if="seo.description" />
    <meta property="og:image"       :content="seo.image"             v-if="seo.image" />
  </Head>
</template>
```

**Step 2: Run the full test suite to confirm no regressions**

```bash
php artisan test
```

Expected: all PASS.

**Step 3: Commit**

```bash
git add resources/js/Components/SeoHead.vue
git commit -m "$(cat <<'EOF'
Render meta keywords tag in SeoHead component

Co-Authored-By: Claude Sonnet 4.6 <noreply@anthropic.com>
EOF
)"
```

---

### Task 7: Settings/Index.vue — add keywords input to SEO panel

**Files:**
- Modify: `resources/js/Pages/Settings/Index.vue`

**Step 1: Add the input to the SEO panel template**

In `resources/js/Pages/Settings/Index.vue`, find the SEO panel's `<div class="space-y-4">` section. After the `seo_og_image_url` block (closing `</div>`), add a new field before the save button `<div class="flex justify-end pt-1">`:

```html
<div class="space-y-1">
  <label for="seo_default_keywords" class="text-sm font-medium">Default keywords</label>
  <input
    id="seo_default_keywords"
    v-model="seoForm['seo.default_keywords']"
    type="text"
    class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
    placeholder="e.g. laravel, cms, blog"
  />
  <p class="text-xs text-muted-foreground">Comma-separated. Used on pages with no post-specific keywords.</p>
</div>
```

**Step 2: Add the key to `seoForm`**

In the `<script setup>` section, find `const seoForm = useForm({...})` and add:

```js
'seo.default_keywords': props.settings['seo.default_keywords'] ?? '',
```

The full `seoForm` should now be:

```js
const seoForm = useForm({
  'seo.title_separator':      props.settings['seo.title_separator']      ?? ' | ',
  'seo.default_description':  props.settings['seo.default_description']  ?? '',
  'seo.default_og_image_url': props.settings['seo.default_og_image_url'] ?? '',
  'seo.default_keywords':     props.settings['seo.default_keywords']     ?? '',
})
```

**Step 3: Run the full test suite**

```bash
php artisan test
```

Expected: all PASS.

**Step 4: Commit**

```bash
git add resources/js/Pages/Settings/Index.vue
git commit -m "$(cat <<'EOF'
Add default keywords input to SEO settings panel

Co-Authored-By: Claude Sonnet 4.6 <noreply@anthropic.com>
EOF
)"
```

---

### Task 8: Posts/Edit.vue — add keywords input to SEO sidebar panel

**Files:**
- Modify: `resources/js/Pages/Posts/Edit.vue`

**Step 1: Add the input to the SEO panel template**

In `resources/js/Pages/Posts/Edit.vue`, find the SEO sidebar panel. After the `meta_description` block's closing `</div>`, add:

```html
<div>
  <label class="block text-xs font-medium mb-1">Keywords</label>
  <input
    v-model="form.meta_keywords"
    type="text"
    maxlength="255"
    class="w-full rounded-md border bg-background px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
    placeholder="Leave blank to use site defaults"
  />
  <p class="text-xs text-muted-foreground mt-1">{{ (form.meta_keywords ?? '').length }}/255</p>
</div>
```

**Step 2: Add `meta_keywords` to `useForm`**

In `<script setup>`, find `const form = useForm({...})` and add after `meta_description`:

```js
meta_keywords: props.post.meta_keywords ?? null,
```

**Step 3: Run the full test suite**

```bash
php artisan test
```

Expected: all PASS.

**Step 4: Commit**

```bash
git add resources/js/Pages/Posts/Edit.vue
git commit -m "$(cat <<'EOF'
Add meta keywords input to post editor SEO panel (edit)

Co-Authored-By: Claude Sonnet 4.6 <noreply@anthropic.com>
EOF
)"
```

---

### Task 9: Posts/Create.vue — add keywords input to SEO sidebar panel

**Files:**
- Modify: `resources/js/Pages/Posts/Create.vue`

**Step 1: Add the input to the SEO panel template**

In `resources/js/Pages/Posts/Create.vue`, find the SEO sidebar panel. After the `meta_description` block's closing `</div>`, add (identical to Edit.vue):

```html
<div>
  <label class="block text-xs font-medium mb-1">Keywords</label>
  <input
    v-model="form.meta_keywords"
    type="text"
    maxlength="255"
    class="w-full rounded-md border bg-background px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
    placeholder="Leave blank to use site defaults"
  />
  <p class="text-xs text-muted-foreground mt-1">{{ (form.meta_keywords ?? '').length }}/255</p>
</div>
```

**Step 2: Add `meta_keywords` to `useForm`**

In `<script setup>`, find `const form = useForm({...})` and add after `meta_description`:

```js
meta_keywords: null,
```

**Step 3: Run the full test suite**

```bash
php artisan test
```

Expected: all PASS.

**Step 4: Commit**

```bash
git add resources/js/Pages/Posts/Create.vue
git commit -m "$(cat <<'EOF'
Add meta keywords input to post editor SEO panel (create)

Co-Authored-By: Claude Sonnet 4.6 <noreply@anthropic.com>
EOF
)"
```

---

### Task 10: Final verification

**Step 1: Run the complete test suite one final time**

```bash
php artisan test
```

Expected: all PASS, zero failures.

**Step 2: Build frontend assets**

```bash
npm run build
```

Expected: no errors.

**Step 3: Smoke test in browser**

1. Log in to the admin
2. Go to Settings → SEO panel → enter "laravel, cms" as default keywords → save
3. Create a new post with keywords "specific, post, keywords" → publish
4. View source of the published post page — confirm `<meta name="keywords" content="specific, post, keywords">`
5. Edit the post, clear the keywords field → save
6. View source again — confirm `<meta name="keywords" content="laravel, cms">` (falls back to global)
7. Clear global keywords in settings too → view source — confirm no `<meta name="keywords">` tag at all
