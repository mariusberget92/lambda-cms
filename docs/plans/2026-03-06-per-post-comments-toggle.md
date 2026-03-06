# Per-Post Comments Toggle Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add a `comments_enabled` boolean column to `posts` so authors can disable comments on individual posts; the global `comments.enabled` setting remains the master switch.

**Architecture:** A new migration adds `comments_enabled` (boolean, default `true`) to `posts`. A `Post::commentsOpen()` helper centralises both checks (global setting AND post flag) — all controllers switch to calling this single method. The post editor gains a checkbox in the sidebar.

**Tech Stack:** Laravel 12, Inertia 2, Vue 3, Tailwind CSS 4. Tests via `php artisan test --filter=`.

---

## Critical context

- Run all commands from the worktree root (not the main repo).
- `Setting::get(key, fallback)` returns the typed/fallback value even when the key has never been seeded. Tests that do **not** seed `comments.enabled` will get `true` (the fallback), so no guard change will affect them.
- `seedCommentSettings()` in `CommentTest` seeds both `comments.enabled` and `comments.per_page` **and** calls `app(\App\Services\SettingService::class)->bust()` to clear the in-process cache. Always call `bust()` after manually seeding settings in tests.
- `Post::factory()->published()->create(['comments_enabled' => false])` is valid — factory state + attribute override.
- All tests use `RefreshDatabase`, `$this->markAsInstalled()`, and `$this->seedRolesAndPermissions()` in `setUp()`.

---

## Task 1: Migration + Post model

**Files:**
- Create: `database/migrations/2026_03_06_000001_add_comments_enabled_to_posts_table.php`
- Modify: `app/Models/Post.php`
- Modify: `tests/Feature/PostTest.php`

### Step 1: Write failing tests

Add to `tests/Feature/PostTest.php` inside the class, after the existing tests. No new imports needed — `Post` is already imported.

```php
// ── Comments enabled ──────────────────────────────────────────────────────────

public function test_post_comments_enabled_defaults_to_true(): void
{
    $post = Post::factory()->published()->create();

    $this->assertTrue((bool) $post->comments_enabled);
}

public function test_post_comments_open_returns_false_when_flag_is_false(): void
{
    $post = Post::factory()->published()->create(['comments_enabled' => false]);

    $this->assertFalse($post->commentsOpen());
}

public function test_post_comments_open_returns_true_when_flag_is_true(): void
{
    $post = Post::factory()->published()->create(['comments_enabled' => true]);

    $this->assertTrue($post->commentsOpen());
}
```

### Step 2: Run tests to verify they fail

```bash
php artisan test --filter="test_post_comments_enabled_defaults_to_true|test_post_comments_open_returns_false_when_flag_is_false|test_post_comments_open_returns_true_when_flag_is_true" 2>&1
```

Expected: 3 failures — column doesn't exist / method not found.

### Step 3: Create the migration

Create `database/migrations/2026_03_06_000001_add_comments_enabled_to_posts_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->boolean('comments_enabled')->default(true)->after('published_at');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('comments_enabled');
        });
    }
};
```

### Step 4: Update Post model

In `app/Models/Post.php`, add `use App\Models\Setting;` to the imports.

Add `'comments_enabled'` to `$fillable`:

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
];
```

Add `'comments_enabled' => 'boolean'` to `$casts`:

```php
protected $casts = [
    "published_at"     => "datetime",
    "comments_enabled" => "boolean",
];
```

Add the `commentsOpen()` helper after `isPublished()`:

```php
public function commentsOpen(): bool
{
    if (! Setting::get('comments.enabled', true)) {
        return false;
    }
    return (bool) $this->comments_enabled;
}
```

### Step 5: Run the migration

```bash
php artisan migrate 2>&1
```

Expected: `Migrating: 2026_03_06_000001_add_comments_enabled_to_posts_table` then `Migrated`.

### Step 6: Run tests to verify they pass

```bash
php artisan test --filter="test_post_comments_enabled_defaults_to_true|test_post_comments_open_returns_false_when_flag_is_false|test_post_comments_open_returns_true_when_flag_is_true" 2>&1
```

Expected: 3 passing.

### Step 7: Commit

```bash
git add database/migrations/2026_03_06_000001_add_comments_enabled_to_posts_table.php app/Models/Post.php tests/Feature/PostTest.php
git commit -m "feat: add comments_enabled column to posts with commentsOpen() helper"
```

---

## Task 2: PostController — accept comments_enabled in store/update

**Files:**
- Modify: `app/Http/Controllers/PostController.php`
- Modify: `tests/Feature/PostTest.php`

### Step 1: Write failing tests

Add to `tests/Feature/PostTest.php`:

```php
public function test_post_store_with_comments_disabled_persists(): void
{
    $user = $this->makeUser();

    $this->actingAs($user)->post('/posts', [
        'title'            => 'No Comments Post',
        'status'           => 'draft',
        'body'             => '<p>Hello</p>',
        'comments_enabled' => false,
    ])->assertRedirect('/posts');

    $this->assertDatabaseHas('posts', [
        'title'            => 'No Comments Post',
        'comments_enabled' => false,
    ]);
}

public function test_post_store_defaults_comments_enabled_to_true(): void
{
    $user = $this->makeUser();

    $this->actingAs($user)->post('/posts', [
        'title'  => 'Default Comments Post',
        'status' => 'draft',
        'body'   => '<p>Hello</p>',
        // no comments_enabled field
    ])->assertRedirect('/posts');

    $this->assertDatabaseHas('posts', [
        'title'            => 'Default Comments Post',
        'comments_enabled' => true,
    ]);
}

public function test_post_update_can_disable_comments(): void
{
    $user = $this->makeUser();
    $post = Post::factory()->create(['user_id' => $user->id, 'comments_enabled' => true]);

    $this->actingAs($user)->put("/posts/{$post->id}", [
        'title'            => $post->title,
        'status'           => $post->status,
        'body'             => $post->body ?? '',
        'comments_enabled' => false,
    ])->assertRedirect('/posts');

    $this->assertDatabaseHas('posts', [
        'id'               => $post->id,
        'comments_enabled' => false,
    ]);
}
```

### Step 2: Run tests to verify they fail

```bash
php artisan test --filter="test_post_store_with_comments_disabled_persists|test_post_store_defaults_comments_enabled_to_true|test_post_update_can_disable_comments" 2>&1
```

Expected: 3 failures — `comments_enabled` not in validation, so it won't be saved.

### Step 3: Update PostController

In `app/Http/Controllers/PostController.php`, add `'comments_enabled'` to the `$request->validate()` call in **both** `store()` and `update()`:

In `store()`, add inside the validate array:
```php
'comments_enabled' => ['nullable', 'boolean'],
```

Then after the `unset()` line in `store()`, default it if missing:
```php
$validated['comments_enabled'] = $validated['comments_enabled'] ?? true;
```

In `update()`, add inside the validate array:
```php
'comments_enabled' => ['nullable', 'boolean'],
```

Then after the `unset()` line in `update()`, default it if missing:
```php
$validated['comments_enabled'] = $validated['comments_enabled'] ?? true;
```

### Step 4: Run tests to verify they pass

```bash
php artisan test --filter="test_post_store_with_comments_disabled_persists|test_post_store_defaults_comments_enabled_to_true|test_post_update_can_disable_comments" 2>&1
```

Expected: 3 passing.

### Step 5: Commit

```bash
git add app/Http/Controllers/PostController.php tests/Feature/PostTest.php
git commit -m "feat: accept comments_enabled in PostController store/update"
```

---

## Task 3: Guard CommentController::store() and BlogController::comments()

Replace the raw `Setting::get()` checks with `$post->commentsOpen()` and add the guard to the JSON endpoint.

**Files:**
- Modify: `app/Http/Controllers/CommentController.php`
- Modify: `app/Http/Controllers/BlogController.php`
- Modify: `tests/Feature/CommentTest.php`

### Step 1: Write failing tests

Add to `tests/Feature/CommentTest.php`:

```php
public function test_comments_store_rejected_when_post_comments_disabled(): void
{
    // Global setting ON — post flag OFF → should still 403
    $this->seedCommentSettings(enabled: true);
    $post = Post::factory()->published()->create(['comments_enabled' => false]);

    $this->post(route('comments.store', $post->slug), [
        'author_name' => 'Alice',
        'body'        => 'Hello!',
    ])->assertForbidden();
}

public function test_comments_json_endpoint_returns_403_when_post_comments_disabled(): void
{
    $this->seedCommentSettings(enabled: true);
    $post = Post::factory()->published()->create(['comments_enabled' => false]);

    $this->getJson("/blog/{$post->slug}/comments")->assertForbidden();
}
```

### Step 2: Run tests to verify they fail

```bash
php artisan test --filter="test_comments_store_rejected_when_post_comments_disabled|test_comments_json_endpoint_returns_403_when_post_comments_disabled" 2>&1
```

Expected: 2 failures — store returns 302 (redirects), JSON endpoint returns 200.

### Step 3: Update CommentController::store()

In `app/Http/Controllers/CommentController.php`, replace the existing guard:

```php
if (! Setting::get('comments.enabled', true)) {
    abort(403, 'Comments are disabled.');
}
```

With:

```php
if (! $post->commentsOpen()) {
    abort(403, 'Comments are disabled.');
}
```

Remove the `use App\Models\Setting;` import from this file if it's no longer used elsewhere in the class (check — `Setting` is only used in that one guard, so remove it).

### Step 4: Update BlogController::comments()

In `app/Http/Controllers/BlogController.php`, after the existing `abort_unless($post->isPublished(), 404);` line, add:

```php
abort_unless($post->commentsOpen(), 403);
```

### Step 5: Run tests to verify they pass

```bash
php artisan test --filter="test_comments_store_rejected_when_post_comments_disabled|test_comments_json_endpoint_returns_403_when_post_comments_disabled" 2>&1
```

Expected: 2 passing.

### Step 6: Run full CommentTest suite to check nothing broke

```bash
php artisan test --filter=CommentTest 2>&1
```

Expected: all passing.

### Step 7: Commit

```bash
git add app/Http/Controllers/CommentController.php app/Http/Controllers/BlogController.php tests/Feature/CommentTest.php
git commit -m "feat: guard comment store and JSON endpoint via Post::commentsOpen()"
```

---

## Task 4: Update BlogController::show() commentsEnabled prop

**Files:**
- Modify: `app/Http/Controllers/BlogController.php`

No new tests — the Inertia prop feeds the existing Vue disabled-notice logic which is already tested visually. The model-level behaviour is covered by Task 1 tests.

### Step 1: Update the prop in BlogController::show()

In `app/Http/Controllers/BlogController.php`, in the `show()` method, replace:

```php
'commentsEnabled' => (bool) Setting::get('comments.enabled', true),
```

With:

```php
'commentsEnabled' => $post->commentsOpen(),
```

### Step 2: Remove now-unused Setting import (if applicable)

Check whether `Setting` is still referenced anywhere else in `BlogController.php`. It's used in `show()` for `comments.per_page` — so **keep** the import.

### Step 3: Run full test suite to confirm nothing broke

```bash
php artisan test 2>&1 | tail -5
```

Expected: all tests passing, 0 failures.

### Step 4: Commit

```bash
git add app/Http/Controllers/BlogController.php
git commit -m "feat: use Post::commentsOpen() for commentsEnabled Inertia prop"
```

---

## Task 5: Frontend — Add "Allow comments" checkbox

**Files:**
- Modify: `resources/js/Pages/Posts/Create.vue`
- Modify: `resources/js/Pages/Posts/Edit.vue`

### Step 1: Update Create.vue script setup

In the `useForm({...})` call in `Create.vue`, add:

```js
comments_enabled: true,
```

So the full form becomes:

```js
const form = useForm({
  title:             "",
  excerpt:           "",
  body:              "",
  status:            "draft",
  category_ids:      [],
  tag_ids:           [],
  featured_image_id: null,
  comments_enabled:  true,
})
```

### Step 2: Add checkbox to Create.vue template

In `Create.vue`, inside the sidebar `<div class="space-y-4">`, add this block **after** the Featured Image panel and **before** the closing `</div>` of the sidebar:

```html
<!-- Comments -->
<div class="rounded-lg border bg-card p-4">
  <h3 class="text-sm font-medium mb-3">Comments</h3>
  <label class="flex items-center gap-3 cursor-pointer">
    <input
      type="checkbox"
      v-model="form.comments_enabled"
      class="w-4 h-4 rounded border-border accent-primary"
    />
    <div>
      <span class="text-sm font-medium">Allow comments</span>
      <p class="text-xs text-muted-foreground">Let readers comment on this post</p>
    </div>
  </label>
</div>
```

### Step 3: Update Edit.vue script setup

In the `useForm({...})` call in `Edit.vue`, add:

```js
comments_enabled: props.post.comments_enabled ?? true,
```

So the full form becomes:

```js
const form = useForm({
  title:             props.post.title,
  excerpt:           props.post.excerpt ?? "",
  body:              props.post.body ?? "",
  status:            props.post.status,
  category_ids:      props.post.category_ids ?? [],
  tag_ids:           props.post.tag_ids ?? [],
  featured_image_id: props.post.featured_image_id ?? null,
  comments_enabled:  props.post.comments_enabled ?? true,
})
```

### Step 4: Add checkbox to Edit.vue template

In `Edit.vue`, add the same Comments panel after the Featured Image panel and before the Details panel:

```html
<!-- Comments -->
<div class="rounded-lg border bg-card p-4">
  <h3 class="text-sm font-medium mb-3">Comments</h3>
  <label class="flex items-center gap-3 cursor-pointer">
    <input
      type="checkbox"
      v-model="form.comments_enabled"
      class="w-4 h-4 rounded border-border accent-primary"
    />
    <div>
      <span class="text-sm font-medium">Allow comments</span>
      <p class="text-xs text-muted-foreground">Let readers comment on this post</p>
    </div>
  </label>
</div>
```

### Step 5: Expose comments_enabled in PostController::edit()

In `app/Http/Controllers/PostController.php`, in the `edit()` method, add `'comments_enabled'` to the post array passed to Inertia:

```php
'comments_enabled'  => $post->comments_enabled,
```

### Step 6: Run frontend build

```bash
npm run build 2>&1 | tail -5
```

Expected: `✓ built in Xs` with no errors.

### Step 7: Run full test suite

```bash
php artisan test 2>&1 | tail -5
```

Expected: all tests passing.

### Step 8: Commit

```bash
git add resources/js/Pages/Posts/Create.vue resources/js/Pages/Posts/Edit.vue app/Http/Controllers/PostController.php
git commit -m "feat: add Allow comments checkbox to post editor sidebar"
```

---

## Task 6: Final verification + finish branch

### Step 1: Run full test suite

```bash
php artisan test 2>&1 | tail -5
```

Expected: all tests passing (196+ tests, 0 failures).

### Step 2: Use finishing-a-development-branch skill

Invoke `superpowers:finishing-a-development-branch` to handle merge/PR/cleanup.
