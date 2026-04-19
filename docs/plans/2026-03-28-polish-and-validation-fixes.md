# Polish & Validation Fixes Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Fix 7 identified issues — Nord colour consistency on the calendar, meta description validation gaps, scheduled-post frontend guard, calendar year bounds, null-safe post author, category/tag soft-delete warning, and loop block AND/OR filter logic.

**Architecture:** Mix of backend (Laravel validation + query logic) and frontend (Vue 3 reactivity) changes. No new tables or migrations needed. Backend changes get PHPUnit coverage; frontend-only changes verified visually.

**Tech Stack:** Laravel 12, PHPUnit, Vue 3 `<script setup>`, Tailwind CSS 4 custom properties (`@theme inline`).

---

### Task 1: Add Nord `info` colour tokens + fix calendar + StatusBadge

All "scheduled" UI still uses hardcoded Tailwind indigo colours. Add a semantic info token pair and wire everything to it.

**Files:**
- Modify: `resources/scss/app.scss`
- Modify: `resources/js/Components/StatusBadge.vue`
- Modify: `resources/js/Pages/Calendar/Index.vue`

**Step 1: Add light-mode and dark-mode `--color-info-*` variables to `app.scss`**

In `app.scss`, find the light-mode block that contains `--color-success-bg` (around line 83). Add immediately after the `--color-error-*` lines:

```scss
  --color-info-bg:     color-mix(in srgb, #81a1c1 20%, transparent);
  --color-info-fg:     #3d6080;
  --color-info-border: color-mix(in srgb, #81a1c1 40%, transparent);
```

Find the dark-mode block that contains `--color-success-bg` (around line 139). Add after its `--color-error-*` lines:

```scss
  --color-info-bg:     color-mix(in srgb, #81a1c1 15%, transparent);
  --color-info-fg:     #81a1c1;
  --color-info-border: color-mix(in srgb, #81a1c1 30%, transparent);
```

**Step 2: Register as Tailwind utilities in the `@theme inline` block**

In the `@theme inline { ... }` block (around line 150), after the `--color-status-error-*` lines add:

```scss
  --color-status-info-bg:     var(--color-info-bg);
  --color-status-info-fg:     var(--color-info-fg);
  --color-status-info-border: var(--color-info-border);
```

**Step 3: Update `StatusBadge.vue` scheduled entry**

Replace the `scheduled` map entry:

```js
// Before
scheduled: {
  classes:  'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300',
  dotClass: 'bg-indigo-500 dark:bg-indigo-400',
  label:    'Scheduled',
},
```

```js
// After
scheduled: {
  classes:  'bg-status-info-bg text-status-info-fg',
  dotClass: 'bg-status-info-fg',
  label:    'Scheduled',
},
```

**Step 4: Fix calendar dot colours in `Calendar/Index.vue`**

Replace the `dotColorForPosts` function:

```js
// Before
function dotColorForPosts(posts) {
  if (!posts || posts.length === 0) return null
  if (posts.some(p => p.status === 'scheduled'))  return 'bg-blue-500'
  if (posts.some(p => p.status === 'published'))  return 'bg-green-500'
  return 'bg-amber-500'
}
```

```js
// After
function dotColorForPosts(posts) {
  if (!posts || posts.length === 0) return null
  if (posts.some(p => p.status === 'scheduled'))  return 'bg-status-info-fg'
  if (posts.some(p => p.status === 'published'))  return 'bg-status-success-fg'
  return 'bg-status-warning-fg'
}
```

**Step 5: Fix calendar legend colours**

Replace the three legend `<span>` colour classes:

```html
<!-- Before -->
<span class="w-2 h-2 rounded-full bg-green-500 inline-block"></span>Published
...
<span class="w-2 h-2 rounded-full bg-blue-500 inline-block"></span>Scheduled
...
<span class="w-2 h-2 rounded-full bg-amber-500 inline-block"></span>Draft
```

```html
<!-- After -->
<span class="w-2 h-2 rounded-full bg-status-success-fg inline-block"></span>Published
...
<span class="w-2 h-2 rounded-full bg-status-info-fg inline-block"></span>Scheduled
...
<span class="w-2 h-2 rounded-full bg-status-warning-fg inline-block"></span>Draft
```

**Step 6: Fix the scheduled badge in the calendar detail panel**

Find the badge `:class` binding in the `<ul>` section for selected-day posts:

```html
<!-- Before -->
'bg-indigo-50 text-indigo-700': post.status === 'scheduled',
```

```html
<!-- After -->
'bg-status-info-bg text-status-info-fg': post.status === 'scheduled',
```

**Step 7: Commit**

```bash
git add resources/scss/app.scss \
        resources/js/Components/StatusBadge.vue \
        resources/js/Pages/Calendar/Index.vue
git commit -m "fix: calendar + StatusBadge — replace hardcoded indigo/green/amber with Nord info token"
```

---

### Task 2: Consistent `meta_description` max:300 validation

`PostController` caps at 300 chars; `PageController` and `TemplateController` have no cap.

**Files:**
- Modify: `app/Http/Controllers/PageController.php`
- Modify: `app/Http/Controllers/TemplateController.php`
- Test: `tests/Feature/PageTest.php`
- Test: `tests/Feature/TemplateTest.php`

**Step 1: Write failing tests**

In `tests/Feature/PageTest.php`, find the store/update section and add:

```php
public function test_meta_description_max_300_on_store(): void
{
    $admin = $this->makeAdmin();
    $this->actingAs($admin)
        ->post('/pages', [
            'title'            => 'Test',
            'slug'             => 'test',
            'status'           => 'draft',
            'meta_description' => str_repeat('a', 301),
        ])
        ->assertSessionHasErrors('meta_description');
}

public function test_meta_description_max_300_on_update(): void
{
    $admin = $this->makeAdmin();
    $page  = \App\Models\Page::factory()->create();
    $this->actingAs($admin)
        ->put("/pages/{$page->id}", [
            'title'            => 'Test',
            'slug'             => 'test',
            'status'           => 'draft',
            'meta_description' => str_repeat('a', 301),
        ])
        ->assertSessionHasErrors('meta_description');
}
```

In `tests/Feature/TemplateTest.php`, add analogous tests for `/templates` store and update.

**Step 2: Run tests to confirm they fail**

```bash
php artisan test --filter="meta_description_max_300"
```
Expected: FAIL — `assertSessionHasErrors` not triggered.

**Step 3: Add `max:300` to PageController**

In `app/Http/Controllers/PageController.php`, find both `store` and `update` validation arrays. Change:

```php
'meta_description' => ['nullable', 'string'],
```
to:
```php
'meta_description' => ['nullable', 'string', 'max:300'],
```

(Two occurrences — one in `store`, one in `update`.)

**Step 4: Add `max:300` to TemplateController**

Same change in `app/Http/Controllers/TemplateController.php` (also two occurrences).

**Step 5: Run tests**

```bash
php artisan test --filter="meta_description_max_300"
```
Expected: all PASS.

**Step 6: Commit**

```bash
git add app/Http/Controllers/PageController.php \
        app/Http/Controllers/TemplateController.php \
        tests/Feature/PageTest.php \
        tests/Feature/TemplateTest.php
git commit -m "fix: PageController + TemplateController — meta_description max:300 consistent with PostController"
```

---

### Task 3: Calendar year bound-check

The `month` param accepts `Y-m` format but allows absurd years like 9999. Cap at 2000–2099.

**Files:**
- Modify: `app/Http/Controllers/CalendarController.php`
- Test: `tests/Feature/CalendarTest.php`

**Step 1: Write failing test**

In `tests/Feature/CalendarTest.php`, add:

```php
public function test_data_endpoint_rejects_out_of_range_year(): void
{
    $user = $this->makeUser();

    $this->actingAs($user)
        ->getJson('/calendar/data?month=9999-01')
        ->assertStatus(422);

    $this->actingAs($user)
        ->getJson('/calendar/data?month=1999-01')
        ->assertStatus(422);
}
```

**Step 2: Run to confirm failure**

```bash
php artisan test --filter="test_data_endpoint_rejects_out_of_range_year"
```
Expected: FAIL — returns 200 instead of 422.

**Step 3: Add year bounds to CalendarController**

Find the `data()` method validation in `app/Http/Controllers/CalendarController.php`:

```php
// Before
$validated = $request->validate([
    'month' => ['nullable', 'date_format:Y-m'],
]);
```

```php
// After
$validated = $request->validate([
    'month' => [
        'nullable',
        'date_format:Y-m',
        function ($attribute, $value, $fail) {
            if ($value === null) return;
            $year = (int) explode('-', $value)[0];
            if ($year < 2000 || $year > 2099) {
                $fail('The year must be between 2000 and 2099.');
            }
        },
    ],
]);
```

**Step 4: Run tests**

```bash
php artisan test --filter="test_data_endpoint_rejects_out_of_range_year"
```
Expected: PASS.

**Step 5: Run full suite**

```bash
php artisan test --compact
```
Expected: all pass.

**Step 6: Commit**

```bash
git add app/Http/Controllers/CalendarController.php \
        tests/Feature/CalendarTest.php
git commit -m "fix: CalendarController — bound-check year to 2000–2099"
```

---

### Task 4: Null-safe post author in BlogController

`$post->author->name` throws if the post author was deleted. Use null-safe access with fallback.

**Files:**
- Modify: `app/Http/Controllers/BlogController.php`
- Test: `tests/Feature/BlogTest.php` (or create if it doesn't exist)

**Step 1: Check for existing blog test**

```bash
ls tests/Feature/BlogTest.php 2>/dev/null || echo "need to create"
```

**Step 2: Write failing test**

In `tests/Feature/BlogTest.php`, add (create the file if needed with the standard header):

```php
public function test_single_post_renders_when_author_is_deleted(): void
{
    $post = \App\Models\Post::factory()->published()->create([
        'user_id' => null,  // simulate deleted author
    ]);

    $this->get("/blog/{$post->slug}")->assertOk();
}
```

> Note: if the factory doesn't allow `user_id => null` directly you may need to use `DB::table('posts')->where('id', $post->id)->update(['user_id' => null])` after creating the post.

**Step 3: Run to confirm failure**

```bash
php artisan test --filter="test_single_post_renders_when_author_is_deleted"
```
Expected: FAIL or ERROR — tries to call `->name` on null.

**Step 4: Fix BlogController**

Open `app/Http/Controllers/BlogController.php`. There are three occurrences of `$post->author->name` (lines 88, 115, 320) and at least one of `$post->author->avatar_url`. Replace all unsafe accesses:

```php
// Before (line ~88)
'author' => ['name' => $post->author->name, 'avatar_url' => $post->author->avatar_url],
// After
'author' => ['name' => $post->author?->name ?? 'Deleted User', 'avatar_url' => $post->author?->avatar_url],

// Before (line ~115)
'name' => $post->author->name,
// After
'name' => $post->author?->name ?? 'Deleted User',

// Before (line ~320)
'name' => $post->author->name,
// After
'name' => $post->author?->name ?? 'Deleted User',
```

Search for any remaining `->author->` patterns to ensure none are missed:
```bash
grep -n "->author->" app/Http/Controllers/BlogController.php
```
Expected: no output.

**Step 5: Run tests**

```bash
php artisan test --filter="test_single_post_renders_when_author_is_deleted"
```
Expected: PASS.

**Step 6: Run full suite**

```bash
php artisan test --compact
```
Expected: all pass.

**Step 7: Commit**

```bash
git add app/Http/Controllers/BlogController.php tests/Feature/BlogTest.php
git commit -m "fix: BlogController — null-safe author access with 'Deleted User' fallback"
```

---

### Task 5: Scheduled status frontend guard

Submitting with `status = 'scheduled'` but no `published_at` silently misbehaves. Add a client-side check that shows an error notification and aborts.

**Files:**
- Modify: `resources/js/Pages/Posts/Create.vue`
- Modify: `resources/js/Pages/Posts/Edit.vue`

**Step 1: Add guard to Create.vue `submit()`**

Find the `submit()` function in `resources/js/Pages/Posts/Create.vue`:

```js
// Before
function submit() {
  form.post(route("posts.store"), {
    onError: (errors) => notify('Please fix the following:', 'error', { items: Object.values(errors) }),
  });
}
```

```js
// After
function submit() {
  if (form.status === 'scheduled' && !form.published_at) {
    notify('A scheduled date is required when status is "Scheduled".', 'error')
    return
  }
  form.post(route("posts.store"), {
    onError: (errors) => notify('Please fix the following:', 'error', { items: Object.values(errors) }),
  });
}
```

**Step 2: Add guard to Edit.vue `submit()`**

Find the `submit()` function in `resources/js/Pages/Posts/Edit.vue` and apply the same guard at the top of the function body:

```js
function submit() {
  if (form.status === 'scheduled' && !form.published_at) {
    notify('A scheduled date is required when status is "Scheduled".', 'error')
    return
  }
  // … rest of existing submit logic …
}
```

**Step 3: Commit**

```bash
git add resources/js/Pages/Posts/Create.vue \
        resources/js/Pages/Posts/Edit.vue
git commit -m "fix: Posts — frontend guard prevents scheduled submit without published_at"
```

---

### Task 6: Category & tag soft-delete warning

Currently categories and tags are deleted immediately. Add a confirm dialog (JS `window.confirm`) when the target has attached posts, showing the count.

**Files:**
- Modify: `resources/js/Pages/Categories/Index.vue`
- Modify: `resources/js/Pages/Tags/Index.vue`
- Modify: `resources/js/Pages/Categories/Form.vue` (if it has a delete button)
- Test: `tests/Feature/CategoryTest.php`
- Test: `tests/Feature/TagTest.php`

**Step 1: Verify posts_count is already exposed**

The controllers already pass `posts_count` in the index response (confirmed). The Vue pages receive it via `categories`/`tags` props.

**Step 2: Add confirm guard to Categories/Index.vue**

Find the delete handler (look for `delete`, `destroy`, or a form `@submit` or link for delete). It typically looks like a form posting to the delete route. Add a guard:

If delete is triggered by a method (e.g. `deleteCategory(category)`):

```js
function deleteCategory(category) {
  if (
    category.posts_count > 0 &&
    !window.confirm(
      `"${category.name}" is used by ${category.posts_count} post${category.posts_count !== 1 ? 's' : ''}. Delete anyway? Posts will not be deleted.`
    )
  ) return

  router.delete(route('categories.destroy', category.id))
}
```

If delete is handled inline as an Inertia link with `method="delete"`, convert it to a button that calls a `deleteCategory` method instead.

**Step 3: Add the same guard to Tags/Index.vue**

```js
function deleteTag(tag) {
  if (
    tag.posts_count > 0 &&
    !window.confirm(
      `"${tag.name}" is used by ${tag.posts_count} post${tag.posts_count !== 1 ? 's' : ''}. Delete anyway? Posts will not be deleted.`
    )
  ) return

  router.delete(route('tags.destroy', tag.id))
}
```

Ensure `router` is imported: `import { router } from '@inertiajs/vue3'`

**Step 4: Write backend tests for the confirmation path**

The backend still deletes regardless — the guard is purely frontend. Add a test confirming that a category *with* posts can still be deleted via direct HTTP request (the controller has no block):

In `tests/Feature/CategoryTest.php`, add:

```php
public function test_category_with_posts_can_still_be_deleted_via_http(): void
{
    $user     = $this->makeUser();
    $category = \App\Models\Category::factory()->create();
    $post     = \App\Models\Post::factory()->create();
    $post->categories()->attach($category);

    $this->actingAs($user)
        ->delete("/categories/{$category->id}")
        ->assertRedirect('/categories');

    $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    // Post still exists
    $this->assertDatabaseHas('posts', ['id' => $post->id]);
}
```

Add an analogous test in `tests/Feature/TagTest.php`.

**Step 5: Run tests**

```bash
php artisan test --filter="category_with_posts_can_still_be_deleted|tag_with_posts_can_still_be_deleted"
```
Expected: PASS (backend already works; this just confirms it).

**Step 6: Commit**

```bash
git add resources/js/Pages/Categories/Index.vue \
        resources/js/Pages/Tags/Index.vue \
        tests/Feature/CategoryTest.php \
        tests/Feature/TagTest.php
git commit -m "fix: category + tag delete — confirm dialog when posts are attached"
```

---

### Task 7: Loop block AND/OR global filter logic

Add a `filter_logic` field (`'and'` | `'or'`) to the loop block data. The LoopSettings UI shows a toggle above the filter list. `QueryBuilder` uses `orWhere` grouping when logic is `'or'`.

**Files:**
- Modify: `resources/js/components/BlockEditor/blocks/LoopSettings.vue`
- Modify: `app/Services/QueryBuilder.php`
- Test: none (no PHPUnit test file for QueryBuilder yet; create one)

**Step 1: Write failing QueryBuilder test**

Create `tests/Unit/QueryBuilderTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Post;
use App\Services\QueryBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QueryBuilderTest extends TestCase
{
    use RefreshDatabase;

    private QueryBuilder $qb;

    protected function setUp(): void
    {
        parent::setUp();
        $this->qb = new QueryBuilder();
    }

    public function test_filter_logic_or_returns_union_of_matches(): void
    {
        // Two categories, one matching each filter
        Category::factory()->create(['name' => 'Alpha', 'slug' => 'alpha']);
        Category::factory()->create(['name' => 'Beta',  'slug' => 'beta']);
        Category::factory()->create(['name' => 'Gamma', 'slug' => 'gamma']);

        $result = $this->qb->resolve([
            'source'       => 'categories',
            'filter_logic' => 'or',
            'filters'      => [
                ['field' => 'slug', 'op' => '=', 'value' => 'alpha'],
                ['field' => 'slug', 'op' => '=', 'value' => 'beta'],
            ],
        ]);

        $this->assertCount(2, $result['items']);
        $slugs = array_column($result['items'], 'slug');
        $this->assertContains('alpha', $slugs);
        $this->assertContains('beta', $slugs);
        $this->assertNotContains('gamma', $slugs);
    }

    public function test_filter_logic_and_requires_all_conditions(): void
    {
        // AND: both conditions must match the same row — effectively returns 0 here
        Category::factory()->create(['name' => 'Alpha', 'slug' => 'alpha']);
        Category::factory()->create(['name' => 'Beta',  'slug' => 'beta']);

        $result = $this->qb->resolve([
            'source'       => 'categories',
            'filter_logic' => 'and',
            'filters'      => [
                ['field' => 'slug', 'op' => '=', 'value' => 'alpha'],
                ['field' => 'slug', 'op' => '=', 'value' => 'beta'],
            ],
        ]);

        $this->assertCount(0, $result['items']);
    }
}
```

**Step 2: Run tests to confirm failure**

```bash
php artisan test tests/Unit/QueryBuilderTest.php
```
Expected: FAIL on `test_filter_logic_or_returns_union_of_matches`.

**Step 3: Update `QueryBuilder.php` to support `filter_logic`**

In `resolve()`, pass `filter_logic` down:

```php
public function resolve(array $data, array $urlParams = []): array
{
    $source       = $data['source']       ?? 'posts';
    $filters      = $this->applyUrlParams($data['filters'] ?? [], $urlParams);
    $sort         = $data['sort']         ?? ['field' => 'created_at', 'direction' => 'desc'];
    $limit        = min((int) ($data['limit']  ?? 12), 100);
    $offset       = (int) ($data['offset'] ?? 0);
    $filterLogic  = ($data['filter_logic'] ?? 'and') === 'or' ? 'or' : 'and';

    return match ($source) {
        'posts'      => $this->resolvePosts($filters, $sort, $limit, $offset, $filterLogic),
        'categories' => $this->resolveCategories($filters, $sort, $limit, $offset, $filterLogic),
        'tags'       => $this->resolveTags($filters, $sort, $limit, $offset, $filterLogic),
        'pages'      => $this->resolvePages($filters, $sort, $limit, $offset, $filterLogic),
        default      => ['items' => [], 'total' => 0],
    };
}
```

Add `string $filterLogic = 'and'` parameter to all four private resolve methods.

Replace the individual `foreach ($filters as $filter)` blocks with a combined approach. For AND (current behaviour), each filter is `->where(...)` as before. For OR, wrap all filters in a single `->where(fn($q) => ...)` using `orWhere` calls:

```php
// Replace the foreach in each resolve method with:
$this->applyFilters($query, $filters, $source, $filterLogic);
```

Add a new private method:

```php
private function applyFilters($query, array $filters, string $source, string $logic): void
{
    if (empty($filters)) return;

    if ($logic === 'or') {
        $query->where(function ($q) use ($filters, $source) {
            foreach ($filters as $filter) {
                $q->orWhere(function ($inner) use ($filter, $source) {
                    $this->applyFilter($inner, $filter, $source);
                });
            }
        });
    } else {
        foreach ($filters as $filter) {
            $this->applyFilter($query, $filter, $source);
        }
    }
}
```

Remove the old `foreach` loops from all four resolve methods (they are now replaced by the single `$this->applyFilters(...)` call).

**Step 4: Run tests**

```bash
php artisan test tests/Unit/QueryBuilderTest.php
```
Expected: both PASS.

**Step 5: Add `filter_logic` toggle to `LoopSettings.vue`**

In the `<template>`, find the Filters section header (`<div class="flex items-center justify-between mb-2">`). Add a toggle after the label and before the "+ Add filter" button:

```html
<!-- Filter logic toggle — only show when there are 2+ filters -->
<div v-if="filters.length >= 2" class="flex items-center gap-1 text-xs text-muted-foreground">
  <button
    type="button"
    class="px-1.5 py-0.5 rounded border text-[10px] font-medium transition-colors"
    :class="filterLogic === 'and'
      ? 'border-primary text-primary bg-primary/10'
      : 'border-border text-muted-foreground hover:border-primary'"
    @click="emitData({ filter_logic: 'and' })"
  >AND</button>
  <button
    type="button"
    class="px-1.5 py-0.5 rounded border text-[10px] font-medium transition-colors"
    :class="filterLogic === 'or'
      ? 'border-primary text-primary bg-primary/10'
      : 'border-border text-muted-foreground hover:border-primary'"
    @click="emitData({ filter_logic: 'or' })"
  >OR</button>
</div>
```

In the `<script setup>`, add the `filterLogic` computed:

```js
const filterLogic = computed(() => props.block.data?.filter_logic ?? 'and')
```

**Step 6: Run full test suite**

```bash
php artisan test --compact
```
Expected: all pass.

**Step 7: Commit**

```bash
git add app/Services/QueryBuilder.php \
        tests/Unit/QueryBuilderTest.php \
        resources/js/components/BlockEditor/blocks/LoopSettings.vue
git commit -m "feat: loop block — AND/OR global filter logic toggle + QueryBuilder support"
```

---

## Summary

| # | Task | Files touched |
|---|------|---------------|
| 1 | Nord info token + calendar + StatusBadge colours | `app.scss`, `StatusBadge.vue`, `Calendar/Index.vue` |
| 2 | `meta_description max:300` on Page + Template | `PageController.php`, `TemplateController.php`, tests |
| 3 | Calendar year bound 2000–2099 | `CalendarController.php`, `CalendarTest.php` |
| 4 | Null-safe post author in BlogController | `BlogController.php`, `BlogTest.php` |
| 5 | Scheduled status frontend guard | `Posts/Create.vue`, `Posts/Edit.vue` |
| 6 | Category/tag soft-delete confirm | `Categories/Index.vue`, `Tags/Index.vue`, tests |
| 7 | Loop AND/OR filter logic | `QueryBuilder.php`, `LoopSettings.vue`, `QueryBuilderTest.php` |
