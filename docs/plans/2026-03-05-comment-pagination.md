# Comment Pagination & Settings Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add a "Load more" paginated comment endpoint to the public blog, and a Comments settings panel (enable/disable + per-page count) in the admin.

**Architecture:** A new public JSON endpoint `GET /blog/{post:slug}/comments?page=N` handles subsequent comment fetches. `BlogController::show()` serves only the first page plus metadata as Inertia props. The `SettingsController` gains a `comments` group. Two new setting rows (`comments.enabled`, `comments.per_page`) are added to `SettingsSeeder`.

**Tech Stack:** Laravel 12, Inertia 2, Vue 3, Tailwind CSS 4. Tests use PHPUnit via `php artisan test --filter=`.

---

## Critical context

- `Setting::set(key, value)` throws `InvalidArgumentException` if the key doesn't exist in the DB. New keys must be seeded in `database/seeders/SettingsSeeder.php` **and** inserted via a migration or a dedicated seeder call before `Setting::set()` can be called.
- `Setting::get(key, fallback)` returns the typed value: `'boolean'` type uses `filter_var($value, FILTER_VALIDATE_BOOLEAN)`, `'integer'` casts to `int`. Store booleans as `'1'`/`'0'`.
- Tests that touch settings must seed the rows themselves (see `SettingsTest::seedSettings()` for the exact pattern — it does `Setting::create($row)` inside a helper, not via the seeder class).
- All tests use `RefreshDatabase`, `$this->markAsInstalled()`, and `$this->seedRolesAndPermissions()` in `setUp()`.
- The `installed` middleware group wraps all public and auth routes.
- `CommentController::store()` currently has no `comments.enabled` guard — Task 1 adds it.

---

## Task 1: Seed comments settings + guard store()

**Files:**
- Modify: `database/seeders/SettingsSeeder.php`
- Modify: `app/Http/Controllers/CommentController.php`
- Modify: `tests/Feature/CommentTest.php`

### Step 1: Write failing tests

Add to `tests/Feature/CommentTest.php` inside the class, after the existing tests.

First add `use App\Models\Setting;` to the imports at the top.

Then add a `seedCommentSettings()` helper and three new tests:

```php
private function seedCommentSettings(bool $enabled = true, int $perPage = 10): void
{
    Setting::create(['group' => 'comments', 'key' => 'comments.enabled',  'value' => $enabled ? '1' : '0', 'type' => 'boolean']);
    Setting::create(['group' => 'comments', 'key' => 'comments.per_page', 'value' => (string) $perPage,     'type' => 'integer']);
}

public function test_comments_store_rejected_when_comments_disabled(): void
{
    $this->seedCommentSettings(enabled: false);
    $post = Post::factory()->published()->create();

    $this->post(route('comments.store', $post->slug), [
        'author_name' => 'Alice',
        'body'        => 'Hello!',
    ])->assertForbidden();
}

public function test_settings_comments_group_saves_correctly(): void
{
    $this->seedCommentSettings();
    $admin = $this->makeAdmin();

    $this->actingAs($admin)->put(route('settings.update', 'comments'), [
        'comments.enabled'  => '1',
        'comments.per_page' => 20,
    ])->assertRedirect();

    $this->assertDatabaseHas('settings', ['key' => 'comments.enabled',  'value' => '1']);
    $this->assertDatabaseHas('settings', ['key' => 'comments.per_page', 'value' => '20']);
}

public function test_settings_comments_validates_per_page_range(): void
{
    $this->seedCommentSettings();
    $admin = $this->makeAdmin();

    $this->actingAs($admin)->put(route('settings.update', 'comments'), [
        'comments.enabled'  => '1',
        'comments.per_page' => 999,
    ])->assertSessionHasErrors('comments.per_page');

    $this->actingAs($admin)->put(route('settings.update', 'comments'), [
        'comments.enabled'  => '1',
        'comments.per_page' => 2,
    ])->assertSessionHasErrors('comments.per_page');
}
```

### Step 2: Run tests to verify they fail

```bash
cd "C:/Users/mariu/Herd/lambda-cms"
php artisan test --filter="test_comments_store_rejected_when_comments_disabled|test_settings_comments_group_saves_correctly|test_settings_comments_validates_per_page_range" 2>&1
```

Expected: failures — `test_comments_store_rejected_when_comments_disabled` gets 302 not 403; settings tests get 404 (group not handled).

### Step 3: Add comment rows to SettingsSeeder

In `database/seeders/SettingsSeeder.php`, add to the `$defaults` array after the `// Mail` block:

```php
// Comments
['group' => 'comments', 'key' => 'comments.enabled',  'value' => '1',  'type' => 'boolean'],
['group' => 'comments', 'key' => 'comments.per_page', 'value' => '10', 'type' => 'integer'],
```

### Step 4: Guard CommentController::store()

In `app/Http/Controllers/CommentController.php`, add `use App\Models\Setting;` to imports, then add this check as the very first lines inside `store()`, before the honeypot check:

```php
if (! Setting::get('comments.enabled', true)) {
    abort(403, 'Comments are disabled.');
}
```

### Step 5: Add comments group to SettingsController::update()

In `app/Http/Controllers/SettingsController.php`, add a `'comments'` case to the `match` in `update()`:

```php
'comments' => $request->validate([
    'comments\\.enabled'  => ['required', 'in:0,1'],
    'comments\\.per_page' => ['required', 'integer', 'min:5', 'max:100'],
]),
```

### Step 6: Run tests to verify they pass

```bash
php artisan test --filter="test_comments_store_rejected_when_comments_disabled|test_settings_comments_group_saves_correctly|test_settings_comments_validates_per_page_range" 2>&1
```

Expected: 3 passing.

### Step 7: Commit

```bash
git add database/seeders/SettingsSeeder.php app/Http/Controllers/CommentController.php app/Http/Controllers/SettingsController.php tests/Feature/CommentTest.php
git commit -m "feat: add comments settings (enabled/per_page), guard store() when disabled"
```

---

## Task 2: New JSON comments endpoint

**Files:**
- Modify: `app/Http/Controllers/BlogController.php`
- Modify: `routes/web.php`
- Modify: `tests/Feature/CommentTest.php`

### Step 1: Write failing tests

Add to `tests/Feature/CommentTest.php`:

```php
public function test_comments_json_endpoint_returns_paginated_comments(): void
{
    $this->seedCommentSettings(perPage: 3);
    $post = Post::factory()->published()->create();
    Comment::factory(5)->approved()->create(['post_id' => $post->id]);

    $response = $this->getJson("/blog/{$post->slug}/comments?page=1");

    $response->assertOk()
             ->assertJsonStructure(['data', 'has_more', 'total'])
             ->assertJsonCount(3, 'data')
             ->assertJsonPath('total', 5)
             ->assertJsonPath('has_more', true);
}

public function test_comments_json_endpoint_respects_page_param(): void
{
    $this->seedCommentSettings(perPage: 3);
    $post = Post::factory()->published()->create();
    Comment::factory(5)->approved()->create(['post_id' => $post->id]);

    $response = $this->getJson("/blog/{$post->slug}/comments?page=2");

    $response->assertOk()
             ->assertJsonCount(2, 'data')
             ->assertJsonPath('has_more', false);
}

public function test_comments_json_endpoint_only_returns_approved(): void
{
    $this->seedCommentSettings(perPage: 10);
    $post = Post::factory()->published()->create();
    Comment::factory(3)->approved()->create(['post_id' => $post->id]);
    Comment::factory(2)->pending()->create(['post_id' => $post->id]);

    $response = $this->getJson("/blog/{$post->slug}/comments?page=1");

    $response->assertOk()->assertJsonCount(3, 'data');
}
```

### Step 2: Run tests to verify they fail

```bash
php artisan test --filter="test_comments_json_endpoint" 2>&1
```

Expected: 404 — route doesn't exist yet.

### Step 3: Add route to routes/web.php

In `routes/web.php`, after the existing `Route::get('/blog/{slug}', ...)` line, add:

```php
Route::get('/blog/{post:slug}/comments', [BlogController::class, 'comments'])->name('blog.comments');
```

### Step 4: Add BlogController::comments() method

In `app/Http/Controllers/BlogController.php`, add `use App\Models\Setting;` to the imports, then add this method after `show()`:

```php
/**
 * Public JSON endpoint — paginated approved comments for a post.
 */
public function comments(Post $post): \Illuminate\Http\JsonResponse
{
    $perPage = (int) Setting::get('comments.per_page', 10);
    $page    = max(1, (int) request('page', 1));

    $paginator = $post->comments()
        ->approved()
        ->oldest()
        ->with('user:id,name,avatar')
        ->paginate($perPage, ['*'], 'page', $page);

    return response()->json([
        'data'     => $paginator->map(fn (Comment $c) => [
            'id'          => $c->id,
            'author_name' => $c->author_name,
            'avatar_url'  => $c->user?->avatar_url ?? null,
            'body'        => $c->body,
            'created_at'  => $c->created_at->diffForHumans(),
        ]),
        'has_more' => $paginator->hasMorePages(),
        'total'    => $paginator->total(),
    ]);
}
```

Also add `use App\Models\Comment;` to the imports if not already present.

### Step 5: Run tests to verify they pass

```bash
php artisan test --filter="test_comments_json_endpoint" 2>&1
```

Expected: 3 passing.

### Step 6: Commit

```bash
git add app/Http/Controllers/BlogController.php routes/web.php tests/Feature/CommentTest.php
git commit -m "feat: add public JSON comments endpoint with offset pagination"
```

---

## Task 3: Update BlogController::show() to send first-page props

**Files:**
- Modify: `app/Http/Controllers/BlogController.php`

No new tests needed — the JSON endpoint tests cover the pagination logic. The Inertia prop change is covered by the Vue update in Task 4.

### Step 1: Update show() in BlogController

Replace the `'comments'` and `'authUser'` lines in `show()`'s Inertia response with:

```php
'comments'         => $firstPage->map(fn ($c) => [
    'id'          => $c->id,
    'author_name' => $c->author_name,
    'avatar_url'  => $c->user?->avatar_url ?? null,
    'body'        => $c->body,
    'created_at'  => $c->created_at->diffForHumans(),
]),
'commentsTotal'    => $total,
'commentsHasMore'  => $firstPage->count() < $total,
'commentsPerPage'  => $perPage,
'commentsEnabled'  => (bool) Setting::get('comments.enabled', true),
'authUser'         => auth()->check() ? [
    'name'  => auth()->user()->name,
    'email' => auth()->user()->email,
] : null,
```

And update the top of `show()` to build the first page manually (before the Inertia render call, after finding `$post`):

```php
$perPage   = (int) Setting::get('comments.per_page', 10);
$total     = $post->comments()->approved()->count();
$firstPage = $post->comments()
    ->approved()
    ->oldest()
    ->with('user:id,name,avatar')
    ->limit($perPage)
    ->get();
```

Also remove the `'comments' => fn ($q) => $q->approved()->oldest()` and `'comments.user:id,name,avatar'` eager loads from the `with()` call on the post query — they are no longer needed since we query comments separately.

### Step 2: Run full test suite to verify nothing broke

```bash
php artisan test --filter=CommentTest 2>&1
```

Expected: all comment tests still pass.

### Step 3: Commit

```bash
git add app/Http/Controllers/BlogController.php
git commit -m "feat: serve first page of comments + metadata from BlogController::show()"
```

---

## Task 4: Update Blog/Show.vue — Load more + disabled state

**Files:**
- Modify: `resources/js/Pages/Blog/Show.vue`

### Step 1: Update script setup

Replace the entire `<script setup>` block with:

```js
<script setup>
import { ref, computed } from 'vue'
import { Link, useForm, usePage } from '@inertiajs/vue3'
import BlogLayout from '@/Layouts/BlogLayout.vue'
import BlogSidebar from '@/Components/BlogSidebar.vue'

defineOptions({ layout: BlogLayout })

const props = defineProps({
  post:             Object,
  sidebar:          Object,
  comments:         { type: Array,   default: () => [] },
  commentsTotal:    { type: Number,  default: 0 },
  commentsHasMore:  { type: Boolean, default: false },
  commentsPerPage:  { type: Number,  default: 10 },
  commentsEnabled:  { type: Boolean, default: true },
  authUser:         { type: Object,  default: null },
})

function formatDate(date) {
  if (!date) return ''
  return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })
}

const page = usePage()

// ── Comment list state ────────────────────────────────────────────────────────
const commentList  = ref([...props.comments])
const hasMore      = ref(props.commentsHasMore)
const currentPage  = ref(1)
const loadingMore  = ref(false)
const loadError    = ref(false)

async function loadMore() {
  loadingMore.value = true
  loadError.value   = false
  try {
    const nextPage = currentPage.value + 1
    const res      = await fetch(`/blog/${props.post.slug}/comments?page=${nextPage}`)
    if (!res.ok) throw new Error('Server error')
    const json = await res.json()
    commentList.value.push(...json.data)
    hasMore.value     = json.has_more
    currentPage.value = nextPage
  } catch {
    loadError.value = true
  } finally {
    loadingMore.value = false
  }
}

// ── Submission form ───────────────────────────────────────────────────────────
const form = useForm({
  author_name:  props.authUser?.name  ?? '',
  author_email: props.authUser?.email ?? '',
  body:         '',
  website:      '', // honeypot
})

function submitComment() {
  form.post(route('comments.store', props.post.slug), {
    preserveScroll: true,
    onSuccess: () => {
      form.reset('body')
    },
  })
}
</script>
```

### Step 2: Update the comments section in the template

Replace the entire `<!-- Comments section -->` div with:

```html
<!-- Comments section -->
<div class="mt-12 pt-8 border-t">
  <h2 class="text-xl font-bold mb-6">
    {{ commentsTotal ? commentsTotal + ' Comment' + (commentsTotal !== 1 ? 's' : '') : 'Comments' }}
  </h2>

  <!-- Flash: submitted confirmation -->
  <Transition name="fade">
    <div
      v-if="page.props.flash?.status"
      class="mb-6 flex items-center gap-2 rounded-md bg-status-success-bg border border-status-success-border px-4 py-3 text-sm text-status-success-fg"
    >
      <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
      {{ page.props.flash.status }}
    </div>
  </Transition>

  <!-- Comment list -->
  <div v-if="commentList.length" class="space-y-6 mb-6">
    <div
      v-for="comment in commentList"
      :key="comment.id"
      class="flex gap-3"
    >
      <div class="w-9 h-9 rounded-full bg-primary/20 flex items-center justify-center text-sm font-semibold text-primary shrink-0">
        <img v-if="comment.avatar_url" :src="comment.avatar_url" :alt="comment.author_name" class="w-full h-full rounded-full object-cover" />
        <span v-else>{{ comment.author_name.charAt(0).toUpperCase() }}</span>
      </div>
      <div class="flex-1 min-w-0">
        <div class="flex items-baseline gap-2 mb-1">
          <span class="text-sm font-semibold">{{ comment.author_name }}</span>
          <span class="text-xs text-muted-foreground">{{ comment.created_at }}</span>
        </div>
        <p class="text-sm text-foreground/90 whitespace-pre-line">{{ comment.body }}</p>
      </div>
    </div>
  </div>
  <p v-else class="text-sm text-muted-foreground mb-6">No comments yet. Be the first!</p>

  <!-- Load more -->
  <div v-if="hasMore || loadError" class="mb-10 text-center">
    <p v-if="loadError" class="text-sm text-destructive mb-2">Failed to load more comments.</p>
    <button
      type="button"
      :disabled="loadingMore"
      @click="loadMore"
      class="inline-flex items-center gap-2 rounded-md border px-4 py-2 text-sm font-medium hover:bg-accent transition-colors disabled:opacity-50"
    >
      <svg v-if="loadingMore" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
      </svg>
      {{ loadingMore ? 'Loading...' : (loadError ? 'Retry' : 'Load more comments') }}
    </button>
  </div>
  <div v-else class="mb-10"></div>

  <!-- Submission form OR disabled notice -->
  <div v-if="commentsEnabled" class="rounded-lg border bg-card p-6">
    <h3 class="text-base font-semibold mb-4">Leave a comment</h3>
    <form @submit.prevent="submitComment" class="space-y-4">
      <!-- Honeypot (hidden) -->
      <input v-model="form.website" type="text" name="website" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true" />

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium mb-1.5">Name <span class="text-destructive">*</span></label>
          <input
            v-model="form.author_name"
            type="text"
            class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
            :class="{ 'border-destructive': form.errors.author_name }"
            placeholder="Your name"
            required
          />
          <p v-if="form.errors.author_name" class="mt-1 text-xs text-destructive">{{ form.errors.author_name }}</p>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1.5">Email <span class="text-muted-foreground text-xs font-normal">(optional, not shown)</span></label>
          <input
            v-model="form.author_email"
            type="email"
            class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
            :class="{ 'border-destructive': form.errors.author_email }"
            placeholder="you@example.com"
          />
          <p v-if="form.errors.author_email" class="mt-1 text-xs text-destructive">{{ form.errors.author_email }}</p>
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium mb-1.5">Comment <span class="text-destructive">*</span></label>
        <textarea
          v-model="form.body"
          rows="4"
          class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring resize-y"
          :class="{ 'border-destructive': form.errors.body }"
          placeholder="Share your thoughts..."
          required
        />
        <p v-if="form.errors.body" class="mt-1 text-xs text-destructive">{{ form.errors.body }}</p>
      </div>

      <div class="flex items-center justify-between">
        <p class="text-xs text-muted-foreground">Comments are moderated before appearing.</p>
        <button
          type="submit"
          :disabled="form.processing"
          class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 disabled:opacity-60"
        >
          <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
          </svg>
          Submit comment
        </button>
      </div>
    </form>
  </div>

  <!-- Comments disabled notice -->
  <div v-else class="rounded-lg border bg-muted/30 px-6 py-5 text-sm text-muted-foreground flex items-center gap-2">
    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
    </svg>
    Comments are closed.
  </div>
</div>
```

### Step 3: Run full test suite

```bash
php artisan test --filter=CommentTest 2>&1
```

Expected: all passing.

### Step 4: Commit

```bash
git add resources/js/Pages/Blog/Show.vue
git commit -m "feat: add load-more pagination and comments-disabled state to blog show page"
```

---

## Task 5: Add Comments settings panel to Settings/Index.vue

**Files:**
- Modify: `resources/js/Pages/Settings/Index.vue`

### Step 1: Add commentsForm to script setup

In the `<script setup>` block, add after the existing `mailForm` block:

```js
// ── Comments form ─────────────────────────────────────────────────────────────
const commentsForm = useForm({
  'comments.enabled':  props.settings['comments.enabled'] === '1' || props.settings['comments.enabled'] === true,
  'comments.per_page': Number(props.settings['comments.per_page'] ?? 10),
})

function submitComments() {
  // Convert boolean to '1'/'0' for server
  commentsForm
    .transform((data) => ({
      'comments.enabled':  data['comments.enabled'] ? '1' : '0',
      'comments.per_page': data['comments.per_page'],
    }))
    .put(route('settings.update', 'comments'), { preserveScroll: true })
}
```

### Step 2: Add Comments panel to template

Add this form block **after** the closing `</form>` of the Mail panel and **before** the Test email panel:

```html
<!-- ── Comments panel ─────────────────────────────────────────────────── -->
<form @submit.prevent="submitComments">
  <div class="rounded-lg border bg-card p-6 space-y-4">
    <div>
      <h3 class="text-sm font-semibold">Comments</h3>
      <p class="text-xs text-muted-foreground mt-0.5">Control comment visibility and loading behaviour.</p>
    </div>

    <div class="flex items-center justify-between">
      <div>
        <label for="comments_enabled" class="text-sm font-medium">Enable comments</label>
        <p class="text-xs text-muted-foreground mt-0.5">When disabled, existing comments remain visible but new submissions are blocked.</p>
      </div>
      <input
        id="comments_enabled"
        v-model="commentsForm['comments.enabled']"
        type="checkbox"
        class="w-4 h-4 rounded border-border accent-primary"
      />
    </div>

    <div class="space-y-1">
      <label for="comments_per_page" class="text-sm font-medium">Comments per page</label>
      <input
        id="comments_per_page"
        v-model.number="commentsForm['comments.per_page']"
        type="number"
        min="5"
        max="100"
        class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
        :class="{ 'border-destructive': commentsForm.errors['comments.per_page'] }"
      />
      <p class="text-xs text-muted-foreground">How many comments load initially and per "Load more" click (5–100).</p>
      <p v-if="commentsForm.errors['comments.per_page']" class="text-xs text-destructive">{{ commentsForm.errors['comments.per_page'] }}</p>
    </div>

    <div class="flex justify-end pt-1">
      <button
        type="submit"
        :disabled="commentsForm.processing"
        class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 disabled:opacity-50"
      >
        {{ commentsForm.processing ? 'Saving...' : 'Save changes' }}
      </button>
    </div>
  </div>
</form>
```

### Step 3: Run full test suite

```bash
php artisan test 2>&1
```

Check the output file if the command exits with code 1 (known shell buffering issue — look for "Tests: N passed" in the output).

Expected: all 183+ tests still passing, 0 failures.

### Step 4: Run frontend build

```bash
cd "C:/Users/mariu/Herd/lambda-cms" && npm run build 2>&1 | tail -10
```

Expected: `✓ built in Xs` with no errors (chunk size warning is pre-existing, not an error).

### Step 5: Commit

```bash
git add resources/js/Pages/Settings/Index.vue
git commit -m "feat: add Comments settings panel (enable/disable + per page)"
```

---

## Task 6: Final verification + finishing branch

### Step 1: Run full test suite one more time

```bash
php artisan test 2>&1
```

Verify all tests pass.

### Step 2: Use finishing-a-development-branch skill

Invoke `superpowers:finishing-a-development-branch` to handle merge/PR/cleanup.
