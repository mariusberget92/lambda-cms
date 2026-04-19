# Comment System Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Full end-to-end comment system — public submission on blog posts, admin moderation panel with bulk actions, pending count on dashboard, comment count on posts index, and queued email notification to admin.

**Architecture:** `CommentController` handles both public `store` and all admin CRUD actions. A `SendNewCommentNotification` job dispatches a `NewCommentNotification` mailable on every new submission. Spam is caught by a honeypot field and rate limiting. All comments start as `pending`; approved comments are shown publicly.

**Tech Stack:** Laravel 11, Inertia.js, Vue 3, Tailwind CSS 4, Spatie Laravel-Permission (`role:administrator`), Laravel Mail + Jobs

**Worktree:** `.worktrees/feature-comments` on branch `feature/comments`

**Existing foundation (already committed on this branch):**
- Migration: `database/migrations/2026_02_21_000003_create_comments_table.php` — `post_id`, `user_id` (nullable), `author_name`, `author_email` (nullable), `body`, `status` (pending/approved/rejected, default pending), timestamps
- `app/Models/Comment.php` — `approved`/`pending` scopes, `belongsTo(Post)`, `belongsTo(User)`
- `app/Models/Post.php` — `hasMany(Comment)` already added
- `database/factories/CommentFactory.php` — exists
- `tests/Feature/CommentTest.php` — 2 passing model tests exist (extend, don't replace)

---

### Task 1: Rate limiter + CommentController stub + routes

**Files:**
- Modify: `bootstrap/app.php` — register comment rate limiter
- Create: `app/Http/Controllers/CommentController.php`
- Modify: `routes/web.php`

**Step 1: Register the comment rate limiter**

In `bootstrap/app.php`, find the `withRouting` or `withMiddleware` call. Add a `RateLimiter` definition in the `boot` method (or wherever `RateLimiter::for` is defined — check if an `AppServiceProvider` exists).

In `app/Providers/AppServiceProvider.php` (or create if absent), inside `boot()`:

```php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

RateLimiter::for('comments', function ($request) {
    return Limit::perMinute(1)->by($request->ip());
});
```

**Step 2: Create CommentController with stub methods**

Create `app/Http/Controllers/CommentController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CommentController extends Controller
{
    /**
     * Admin moderation index — paginated, filterable by status.
     */
    public function index(Request $request): Response
    {
        $filter = $request->input('filter', 'pending');

        $comments = Comment::with(['post:id,title,slug', 'user:id,name'])
            ->when($filter !== 'all', fn ($q) => $q->where('status', $filter))
            ->latest()
            ->paginate(25)
            ->withQueryString()
            ->through(fn (Comment $c) => [
                'id'          => $c->id,
                'author_name' => $c->author_name,
                'author_email'=> $c->author_email,
                'body'        => $c->body,
                'body_excerpt'=> \Illuminate\Support\Str::limit($c->body, 80),
                'status'      => $c->status,
                'created_at'  => $c->created_at->diffForHumans(),
                'post'        => [
                    'title' => $c->post->title,
                    'slug'  => $c->post->slug,
                ],
            ]);

        return Inertia::render('Comments/Index', [
            'comments'       => $comments,
            'filter'         => $filter,
            'pendingCount'   => Comment::pending()->count(),
        ]);
    }

    /**
     * Public — store a new comment (pending).
     */
    public function store(Request $request, Post $post): RedirectResponse
    {
        // Honeypot — silently discard if filled
        if ($request->filled('website')) {
            return back()->with('status', 'Your comment has been submitted and is awaiting moderation.');
        }

        $validated = $request->validate([
            'author_name'  => ['required', 'string', 'max:100'],
            'author_email' => ['nullable', 'email', 'max:255'],
            'body'         => ['required', 'string', 'max:5000'],
        ]);

        $comment = $post->comments()->create([
            'user_id'      => $request->user()?->id,
            'author_name'  => $validated['author_name'],
            'author_email' => $validated['author_email'] ?? null,
            'body'         => $validated['body'],
            'status'       => 'pending',
        ]);

        dispatch(new \App\Jobs\SendNewCommentNotification($comment));

        return back()->with('status', 'Your comment has been submitted and is awaiting moderation.');
    }

    /**
     * Admin — approve a comment.
     */
    public function approve(Comment $comment): RedirectResponse
    {
        $comment->update(['status' => 'approved']);
        return back()->with('status', 'Comment approved.');
    }

    /**
     * Admin — reject a comment.
     */
    public function reject(Comment $comment): RedirectResponse
    {
        $comment->update(['status' => 'rejected']);
        return back()->with('status', 'Comment rejected.');
    }

    /**
     * Admin — hard delete a comment.
     */
    public function destroy(Comment $comment): RedirectResponse
    {
        $comment->delete();
        return back()->with('status', 'Comment deleted.');
    }

    /**
     * Admin — bulk approve / reject / delete.
     */
    public function bulk(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:approve,reject,delete'],
            'ids'    => ['required', 'array', 'min:1'],
            'ids.*'  => ['integer', 'exists:comments,id'],
        ]);

        $comments = Comment::whereIn('id', $validated['ids']);

        match ($validated['action']) {
            'approve' => $comments->update(['status' => 'approved']),
            'reject'  => $comments->update(['status' => 'rejected']),
            'delete'  => $comments->delete(),
        };

        return back()->with('status', ucfirst($validated['action']) . 'd ' . count($validated['ids']) . ' comment(s).');
    }
}
```

**Step 3: Add routes to `routes/web.php`**

Add the public comment store route in the `installed` group alongside `/blog/{slug}`:

```php
// Comment submission (public, rate-limited)
Route::post('/blog/{post:slug}/comments', [CommentController::class, 'store'])
    ->middleware('throttle:comments')
    ->name('comments.store');
```

Add admin comment routes inside the `['auth', 'verified', 'role:administrator']` group:

```php
Route::get('/comments',                      [CommentController::class, 'index'])->name('comments.index');
Route::patch('/comments/{comment}/approve',  [CommentController::class, 'approve'])->name('comments.approve');
Route::patch('/comments/{comment}/reject',   [CommentController::class, 'reject'])->name('comments.reject');
Route::delete('/comments/{comment}',         [CommentController::class, 'destroy'])->name('comments.destroy');
Route::post('/comments/bulk',                [CommentController::class, 'bulk'])->name('comments.bulk');
```

Also add the `use` import at the top of `routes/web.php`:
```php
use App\Http\Controllers\CommentController;
```

**Step 4: Run tests to confirm nothing broken yet**

```bash
php artisan test
```

Expected: all existing tests pass.

**Step 5: Commit**

```bash
git add app/Http/Controllers/CommentController.php routes/web.php app/Providers/AppServiceProvider.php
git commit -m "feat: add CommentController with all actions and comment routes"
```

---

### Task 2: TDD — Feature tests for CommentController (write tests first)

**Files:**
- Modify: `tests/Feature/CommentTest.php` (extend the existing 2 model tests)

**Step 1: Add the following tests to the existing `CommentTest` class**

Open `tests/Feature/CommentTest.php` and add these test methods after the existing 2. The class already has `setUp`, `makeUser()`, `makeAdmin()` helpers.

```php
// ── Public store ──────────────────────────────────────────────────────────

public function test_guest_can_submit_comment(): void
{
    $post = Post::factory()->published()->create();

    $response = $this->post(route('comments.store', $post->slug), [
        'author_name' => 'Alice',
        'author_email'=> 'alice@example.com',
        'body'        => 'Great post!',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('comments', [
        'post_id'     => $post->id,
        'author_name' => 'Alice',
        'status'      => 'pending',
    ]);
}

public function test_honeypot_silently_discards_comment(): void
{
    $post = Post::factory()->published()->create();

    $this->post(route('comments.store', $post->slug), [
        'author_name' => 'Bot',
        'body'        => 'Spam',
        'website'     => 'http://spam.example.com', // honeypot filled
    ]);

    $this->assertDatabaseMissing('comments', ['author_name' => 'Bot']);
}

public function test_comment_requires_name_and_body(): void
{
    $post = Post::factory()->published()->create();

    $this->post(route('comments.store', $post->slug), [])
        ->assertSessionHasErrors(['author_name', 'body']);
}

public function test_authenticated_user_comment_stores_user_id(): void
{
    $user = $this->makeUser();
    $post = Post::factory()->published()->create();

    $this->actingAs($user)->post(route('comments.store', $post->slug), [
        'author_name' => $user->name,
        'body'        => 'Logged in comment',
    ]);

    $this->assertDatabaseHas('comments', [
        'post_id' => $post->id,
        'user_id' => $user->id,
    ]);
}

// ── Admin actions ─────────────────────────────────────────────────────────

public function test_non_admin_cannot_access_comments_index(): void
{
    $user = $this->makeUser();
    $this->actingAs($user)->get(route('comments.index'))->assertForbidden();
}

public function test_admin_can_view_comments_index(): void
{
    $admin = $this->makeAdmin();
    $post  = Post::factory()->published()->create();
    Comment::factory()->create(['post_id' => $post->id, 'status' => 'pending']);

    $this->actingAs($admin)->get(route('comments.index'))->assertOk();
}

public function test_admin_can_approve_comment(): void
{
    $admin   = $this->makeAdmin();
    $post    = Post::factory()->published()->create();
    $comment = Comment::factory()->create(['post_id' => $post->id, 'status' => 'pending']);

    $this->actingAs($admin)->patch(route('comments.approve', $comment))->assertRedirect();
    $this->assertDatabaseHas('comments', ['id' => $comment->id, 'status' => 'approved']);
}

public function test_admin_can_reject_comment(): void
{
    $admin   = $this->makeAdmin();
    $post    = Post::factory()->published()->create();
    $comment = Comment::factory()->create(['post_id' => $post->id, 'status' => 'pending']);

    $this->actingAs($admin)->patch(route('comments.reject', $comment))->assertRedirect();
    $this->assertDatabaseHas('comments', ['id' => $comment->id, 'status' => 'rejected']);
}

public function test_admin_can_delete_comment(): void
{
    $admin   = $this->makeAdmin();
    $post    = Post::factory()->published()->create();
    $comment = Comment::factory()->create(['post_id' => $post->id]);

    $this->actingAs($admin)->delete(route('comments.destroy', $comment))->assertRedirect();
    $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
}

public function test_admin_can_bulk_approve(): void
{
    $admin = $this->makeAdmin();
    $post  = Post::factory()->published()->create();
    $ids   = Comment::factory(3)->create(['post_id' => $post->id, 'status' => 'pending'])->pluck('id')->toArray();

    $this->actingAs($admin)->post(route('comments.bulk'), ['action' => 'approve', 'ids' => $ids])->assertRedirect();
    $this->assertEquals(3, Comment::whereIn('id', $ids)->where('status', 'approved')->count());
}

public function test_notification_dispatched_on_comment_store(): void
{
    \Illuminate\Support\Facades\Queue::fake();

    $post = Post::factory()->published()->create();

    $this->post(route('comments.store', $post->slug), [
        'author_name' => 'Alice',
        'body'        => 'Test notification',
    ]);

    \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\SendNewCommentNotification::class);
}
```

**Step 2: Run tests — they must fail**

```bash
php artisan test --filter=CommentTest
```

Expected: failures due to missing `SendNewCommentNotification` job and `CommentFactory` states. Note exact error messages.

**Step 3: Commit the failing tests**

```bash
git add tests/Feature/CommentTest.php
git commit -m "test: add CommentController feature tests (TDD — failing)"
```

---

### Task 3: CommentFactory states + SendNewCommentNotification job + mailable

**Files:**
- Modify: `database/factories/CommentFactory.php`
- Create: `app/Jobs/SendNewCommentNotification.php`
- Create: `app/Mail/NewCommentNotification.php`
- Create: `resources/views/mail/new-comment.blade.php`

**Step 1: Add factory states to CommentFactory**

Open `database/factories/CommentFactory.php` and add these states:

```php
public function pending(): static
{
    return $this->state(fn (array $attributes) => ['status' => 'pending']);
}

public function approved(): static
{
    return $this->state(fn (array $attributes) => ['status' => 'approved']);
}

public function rejected(): static
{
    return $this->state(fn (array $attributes) => ['status' => 'rejected']);
}
```

Also make sure the factory definition includes `post_id` — check if it's already there. If not, add:

```php
public function definition(): array
{
    return [
        'post_id'      => \App\Models\Post::factory(),
        'user_id'      => null,
        'author_name'  => fake()->name(),
        'author_email' => fake()->safeEmail(),
        'body'         => fake()->paragraph(),
        'status'       => 'pending',
    ];
}
```

**Step 2: Create the job**

Create `app/Jobs/SendNewCommentNotification.php`:

```php
<?php

namespace App\Jobs;

use App\Mail\NewCommentNotification;
use App\Models\Comment;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendNewCommentNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly Comment $comment) {}

    public function handle(): void
    {
        try {
            $adminEmail = Setting::get('mail.from_address', config('mail.from.address'));

            if (empty($adminEmail)) {
                Log::warning('SendNewCommentNotification: no admin email configured, skipping.');
                return;
            }

            Mail::to($adminEmail)->send(new NewCommentNotification($this->comment));
        } catch (\Throwable $e) {
            Log::error('SendNewCommentNotification failed', ['exception' => $e->getMessage()]);
        }
    }
}
```

**Step 3: Create the mailable**

Create `app/Mail/NewCommentNotification.php`:

```php
<?php

namespace App\Mail;

use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewCommentNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Comment $comment) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New comment on "' . $this->comment->post->title . '"',
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'mail.new-comment',
        );
    }
}
```

**Step 4: Create the plain-text mail view**

Create `resources/views/mail/new-comment.blade.php`:

```
New comment awaiting moderation on Lambda CMS.

Post: {{ $comment->post->title }}
URL: {{ url('/blog/' . $comment->post->slug) }}

Author: {{ $comment->author_name }} ({{ $comment->author_email ?? 'no email' }})

Comment:
{{ $comment->body }}

---
To moderate this comment, visit:
{{ url('/comments') }}
```

**Step 5: Run the tests — they should now pass**

```bash
php artisan test --filter=CommentTest
```

Expected: all 12 tests green.

**Step 6: Commit**

```bash
git add database/factories/CommentFactory.php app/Jobs/SendNewCommentNotification.php app/Mail/NewCommentNotification.php resources/views/mail/new-comment.blade.php
git commit -m "feat: add CommentFactory states, SendNewCommentNotification job and mailable"
```

---

### Task 4: Update BlogController::show() and PostController::index()

**Files:**
- Modify: `app/Http/Controllers/BlogController.php`
- Modify: `app/Http/Controllers/PostController.php`

**Step 1: Update BlogController::show()**

In `BlogController::show()`, add eager-loading for approved comments and pass them as props.

Replace:
```php
$post = Post::published()
    ->with(['author:id,name,avatar', 'categories:id,name,slug', 'tags:id,name,slug', 'featuredImage:id,path,disk,alt'])
    ->where('slug', $slug)
    ->firstOrFail();
```

With:
```php
$post = Post::published()
    ->with([
        'author:id,name,avatar',
        'categories:id,name,slug',
        'tags:id,name,slug',
        'featuredImage:id,path,disk,alt',
        'comments' => fn ($q) => $q->approved()->oldest(),
        'comments.user:id,name,avatar',
    ])
    ->where('slug', $slug)
    ->firstOrFail();
```

Then in the `Inertia::render('Blog/Show', [...])` call, add after `'sidebar' => $this->sidebarData()`:

```php
'comments' => $post->comments->map(fn ($c) => [
    'id'          => $c->id,
    'author_name' => $c->author_name,
    'avatar_url'  => $c->user?->avatar_url ?? null,
    'body'        => $c->body,
    'created_at'  => $c->created_at->diffForHumans(),
]),
'authUser' => auth()->check() ? [
    'name'  => auth()->user()->name,
    'email' => auth()->user()->email,
] : null,
```

**Step 2: Update PostController::index()**

In `PostController::index()`, add `withCount('comments')` to the query:

Replace:
```php
$posts = Post::with('author:id,name', 'categories:id,name', 'tags:id,name')
```

With:
```php
$posts = Post::with('author:id,name', 'categories:id,name', 'tags:id,name')
    ->withCount('comments')
```

And in the `->through()` callback, add to the returned array:
```php
'comments_count' => $post->comments_count,
```

**Step 3: Run all tests**

```bash
php artisan test
```

Expected: all tests pass.

**Step 4: Commit**

```bash
git add app/Http/Controllers/BlogController.php app/Http/Controllers/PostController.php
git commit -m "feat: eager-load approved comments on blog show, add comment count to posts index"
```

---

### Task 5: Update DashboardController + Dashboard/Index.vue

**Files:**
- Modify: `app/Http/Controllers/DashboardController.php`
- Modify: `resources/js/Pages/Dashboard/Index.vue`

**Step 1: Add pendingCommentsCount to DashboardController**

Add `use App\Models\Comment;` to the imports.

In `index()`, extend the `Inertia::render` call:

```php
return Inertia::render("Dashboard/Index", [
    "stats" => [
        "total"                => Post::count(),
        "published"            => Post::published()->count(),
        "drafts"               => Post::draft()->count(),
        "pendingCommentsCount" => Comment::pending()->count(),
    ],
]);
```

**Step 2: Add pending comments stat card to Dashboard/Index.vue**

The stats grid is `sm:grid-cols-3`. Change it to `sm:grid-cols-2 lg:grid-cols-4` to accommodate 4 cards.

After the existing Drafts card (`</div>` at line ~61), add:

```html
<a :href="route('comments.index') + '?filter=pending'" class="rounded-lg border bg-card p-5 hover:bg-accent/30 transition-colors">
  <div class="flex items-center justify-between mb-3">
    <p class="text-sm font-medium text-muted-foreground">Pending Comments</p>
    <div class="w-8 h-8 rounded-md bg-status-warning-bg flex items-center justify-center">
      <svg class="w-4 h-4 text-status-warning-fg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
      </svg>
    </div>
  </div>
  <p class="text-3xl font-bold">{{ stats.pendingCommentsCount }}</p>
</a>
```

Also update `defineProps` to include the new field:
```js
defineProps({
  stats: {
    type: Object,
    default: () => ({ total: 0, published: 0, drafts: 0, pendingCommentsCount: 0 }),
  },
});
```

**Step 3: Build**

```bash
npm run build
```

**Step 4: Commit**

```bash
git add app/Http/Controllers/DashboardController.php resources/js/Pages/Dashboard/Index.vue
git commit -m "feat: add pending comments count to dashboard stats"
```

---

### Task 6: Update Posts/Index.vue with comment count column

**Files:**
- Modify: `resources/js/Pages/Posts/Index.vue`

**Step 1: Add Comments column header**

In the `<thead>`, after the Date `<th>` (before the empty actions `<th>`), add:

```html
<th class="text-left font-medium px-4 py-3 hidden lg:table-cell">Comments</th>
```

**Step 2: Add comment count cell to each row**

After the Date `<td>`, add:

```html
<td class="px-4 py-3 hidden lg:table-cell text-muted-foreground text-xs">
  <span class="inline-flex items-center gap-1">
    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
    </svg>
    {{ post.comments_count ?? 0 }}
  </span>
</td>
```

Also update the empty state `colspan` from `6` to `7`.

**Step 3: Build**

```bash
npm run build
```

**Step 4: Commit**

```bash
git add resources/js/Pages/Posts/Index.vue
git commit -m "feat: show comment count on posts index table"
```

---

### Task 7: Update AppLayout.vue — Comments sidebar link

**Files:**
- Modify: `resources/js/Layouts/AppLayout.vue`

**Step 1: Pass pendingCommentsCount via HandleInertiaRequests**

Open `app/Http/Middleware/HandleInertiaRequests.php`. In the `share()` method, add `pendingCommentsCount` to the shared props (only for admin users to avoid unnecessary DB queries for regular users):

```php
use App\Models\Comment;

// Inside share() return array:
'pendingCommentsCount' => fn () => auth()->check() && auth()->user()->hasRole('administrator')
    ? Comment::pending()->count()
    : 0,
```

**Step 2: Add Comments sidebar link to AppLayout.vue**

In `AppLayout.vue`, find the "Account" nav section. After the Users `<SidebarLink>` block, add a Comments link (also admin-only):

```html
<SidebarLink
  v-if="user.role === 'administrator'"
  :href="route('comments.index')"
  :active="currentRoute?.startsWith('comments.')"
>
  <template #icon>
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
    </svg>
  </template>
  <span class="flex items-center justify-between w-full">
    Comments
    <span
      v-if="$page.props.pendingCommentsCount > 0"
      class="ml-auto inline-flex items-center justify-center w-5 h-5 rounded-full bg-status-warning-bg text-status-warning-fg text-xs font-semibold"
    >{{ $page.props.pendingCommentsCount }}</span>
  </span>
</SidebarLink>
```

**Step 3: Build**

```bash
npm run build
```

**Step 4: Run all tests**

```bash
php artisan test
```

Expected: all tests pass.

**Step 5: Commit**

```bash
git add app/Http/Middleware/HandleInertiaRequests.php resources/js/Layouts/AppLayout.vue
git commit -m "feat: add Comments sidebar link with pending count badge"
```

---

### Task 8: Create Comments/Index.vue — admin moderation page

**Files:**
- Create: `resources/js/Pages/Comments/Index.vue`

**Step 1: Create the page**

Create `resources/js/Pages/Comments/Index.vue` with this structure:

```vue
<template>
  <AppLayout title="Comments">
    <Head title="Comments" />

    <!-- Page header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-lg font-semibold">Comments</h2>
        <p class="text-sm text-muted-foreground mt-0.5">Moderate reader comments across your posts.</p>
      </div>
    </div>

    <!-- Flash -->
    <Transition name="fade">
      <div
        v-if="$page.props.flash?.status"
        class="flex items-center gap-2 rounded-md bg-status-success-bg border border-status-success-border px-4 py-3 text-sm text-status-success-fg mb-4"
      >
        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        {{ $page.props.flash.status }}
      </div>
    </Transition>

    <!-- Filter tabs -->
    <div class="flex gap-1 mb-4 border-b">
      <button
        v-for="tab in tabs"
        :key="tab.value"
        @click="setFilter(tab.value)"
        class="px-4 py-2 text-sm font-medium border-b-2 transition-colors"
        :class="filter === tab.value
          ? 'border-primary text-foreground'
          : 'border-transparent text-muted-foreground hover:text-foreground'"
      >
        {{ tab.label }}
        <span v-if="tab.value === 'pending' && pendingCount > 0" class="ml-1 inline-flex items-center justify-center w-4 h-4 rounded-full bg-status-warning-bg text-status-warning-fg text-xs">{{ pendingCount }}</span>
      </button>
    </div>

    <!-- Bulk actions bar -->
    <Transition name="fade">
      <div v-if="selected.length > 0" class="flex items-center gap-3 mb-4 px-4 py-2.5 rounded-md bg-muted/50 border">
        <span class="text-sm text-muted-foreground">{{ selected.length }} selected</span>
        <button @click="bulkAction('approve')" class="text-sm font-medium text-status-success-fg hover:underline">Approve</button>
        <button @click="bulkAction('reject')"  class="text-sm font-medium text-status-warning-fg hover:underline">Reject</button>
        <button @click="bulkAction('delete')"  class="text-sm font-medium text-destructive hover:underline">Delete</button>
      </div>
    </Transition>

    <!-- Table -->
    <div class="rounded-lg border bg-card overflow-hidden">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b bg-muted/40">
            <th class="px-4 py-3 w-8">
              <input type="checkbox" :checked="allSelected" @change="toggleAll" class="rounded" />
            </th>
            <th class="text-left px-4 py-3 font-medium text-muted-foreground">Post</th>
            <th class="text-left px-4 py-3 font-medium text-muted-foreground">Author</th>
            <th class="text-left px-4 py-3 font-medium text-muted-foreground hidden md:table-cell">Comment</th>
            <th class="text-left px-4 py-3 font-medium text-muted-foreground hidden lg:table-cell">Date</th>
            <th class="text-left px-4 py-3 font-medium text-muted-foreground">Status</th>
            <th class="px-4 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y">
          <tr v-if="comments.data.length === 0">
            <td colspan="7" class="px-4 py-10 text-center text-sm text-muted-foreground">No comments found.</td>
          </tr>
          <tr v-for="comment in comments.data" :key="comment.id" class="hover:bg-muted/20 transition-colors">
            <td class="px-4 py-3">
              <input type="checkbox" :value="comment.id" v-model="selected" class="rounded" />
            </td>
            <td class="px-4 py-3 max-w-[160px]">
              <a
                :href="`/blog/${comment.post.slug}`"
                target="_blank"
                class="text-sm font-medium hover:underline line-clamp-2"
              >{{ comment.post.title }}</a>
            </td>
            <td class="px-4 py-3">
              <p class="font-medium text-sm">{{ comment.author_name }}</p>
              <p v-if="comment.author_email" class="text-xs text-muted-foreground">{{ comment.author_email }}</p>
            </td>
            <td class="px-4 py-3 hidden md:table-cell text-muted-foreground text-xs max-w-[240px]">
              <span class="line-clamp-2">{{ comment.body_excerpt }}</span>
            </td>
            <td class="px-4 py-3 hidden lg:table-cell text-muted-foreground text-xs">{{ comment.created_at }}</td>
            <td class="px-4 py-3">
              <span
                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                :class="{
                  'bg-status-warning-bg text-status-warning-fg': comment.status === 'pending',
                  'bg-status-success-bg text-status-success-fg': comment.status === 'approved',
                  'bg-status-error-bg text-status-error-fg':     comment.status === 'rejected',
                }"
              >{{ comment.status }}</span>
            </td>
            <td class="px-4 py-3">
              <div class="flex items-center justify-end gap-1">
                <!-- Approve -->
                <button
                  v-if="comment.status !== 'approved'"
                  @click="approve(comment)"
                  title="Approve"
                  class="rounded-md p-1.5 text-muted-foreground hover:bg-status-success-bg hover:text-status-success-fg transition-colors"
                >
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                  </svg>
                </button>
                <!-- Reject -->
                <button
                  v-if="comment.status !== 'rejected'"
                  @click="reject(comment)"
                  title="Reject"
                  class="rounded-md p-1.5 text-muted-foreground hover:bg-status-warning-bg hover:text-status-warning-fg transition-colors"
                >
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                  </svg>
                </button>
                <!-- Delete -->
                <button
                  @click="confirmDelete(comment)"
                  title="Delete"
                  class="rounded-md p-1.5 text-muted-foreground hover:bg-destructive/10 hover:text-destructive transition-colors"
                >
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                  </svg>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="comments.last_page > 1" class="flex justify-end gap-1 mt-4">
      <a
        v-for="link in comments.links"
        :key="link.label"
        :href="link.url ?? '#'"
        class="px-3 py-1.5 rounded-md text-sm border transition-colors"
        :class="link.active
          ? 'bg-primary text-primary-foreground border-primary'
          : link.url
            ? 'hover:bg-accent text-foreground border-border'
            : 'text-muted-foreground border-border cursor-default pointer-events-none'"
      >{{ decodeHtmlEntities(link.label) }}</a>
    </div>

    <!-- Delete confirm modal -->
    <Transition name="fade">
      <div v-if="deleteTarget" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm">
        <div class="bg-card rounded-lg border shadow-lg max-w-sm w-full p-6 space-y-4">
          <h3 class="text-sm font-semibold">Delete comment?</h3>
          <p class="text-sm text-muted-foreground">By <strong>{{ deleteTarget.author_name }}</strong> — this cannot be undone.</p>
          <div class="flex justify-end gap-3">
            <button @click="deleteTarget = null" class="rounded-md border px-4 py-2 text-sm font-medium hover:bg-accent transition-colors">Cancel</button>
            <button @click="deleteComment" class="rounded-md bg-destructive px-4 py-2 text-sm font-medium text-destructive-foreground hover:bg-destructive/90 transition-colors">Delete</button>
          </div>
        </div>
      </div>
    </Transition>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  comments:     { type: Object, required: true },
  filter:       { type: String, default: 'pending' },
  pendingCount: { type: Number, default: 0 },
})

const tabs = [
  { label: 'Pending',  value: 'pending' },
  { label: 'Approved', value: 'approved' },
  { label: 'Rejected', value: 'rejected' },
  { label: 'All',      value: 'all' },
]

const selected    = ref([])
const deleteTarget = ref(null)

const allSelected = computed(() =>
  props.comments.data.length > 0 &&
  props.comments.data.every(c => selected.value.includes(c.id))
)

function toggleAll() {
  if (allSelected.value) {
    selected.value = []
  } else {
    selected.value = props.comments.data.map(c => c.id)
  }
}

function setFilter(value) {
  router.get(route('comments.index'), { filter: value }, { preserveState: false })
}

function approve(comment) {
  router.patch(route('comments.approve', comment.id), {}, { preserveScroll: true })
}

function reject(comment) {
  router.patch(route('comments.reject', comment.id), {}, { preserveScroll: true })
}

function confirmDelete(comment) {
  deleteTarget.value = comment
}

function deleteComment() {
  if (!deleteTarget.value) return
  router.delete(route('comments.destroy', deleteTarget.value.id), {
    preserveScroll: true,
    onFinish: () => { deleteTarget.value = null },
  })
}

function bulkAction(action) {
  router.post(route('comments.bulk'), { action, ids: selected.value }, {
    preserveScroll: true,
    onSuccess: () => { selected.value = [] },
  })
}

function decodeHtmlEntities(str) {
  const txt = document.createElement('textarea')
  txt.innerHTML = str
  return txt.value
}
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
```

**Step 2: Build**

```bash
npm run build
```

Expected: clean build.

**Step 3: Commit**

```bash
git add resources/js/Pages/Comments/Index.vue
git commit -m "feat: add Comments/Index.vue admin moderation page with bulk actions"
```

---

### Task 9: Update Blog/Show.vue — comments section + submission form

**Files:**
- Modify: `resources/js/Pages/Blog/Show.vue`

**Step 1: Add comments section below the post body and tags**

In `Blog/Show.vue`, after the closing `</div>` of the tags section (line ~86), and before the closing `</div>` of the main `lg:col-span-2` column, add:

```html
<!-- Comments section -->
<div class="mt-10 pt-8 border-t">
  <h2 class="text-lg font-semibold mb-6">
    {{ comments.length === 1 ? '1 Comment' : `${comments.length} Comments` }}
  </h2>

  <!-- Approved comments list -->
  <div v-if="comments.length > 0" class="space-y-6 mb-10">
    <div v-for="comment in comments" :key="comment.id" class="flex gap-4">
      <!-- Avatar -->
      <div class="shrink-0">
        <img
          v-if="comment.avatar_url"
          :src="comment.avatar_url"
          :alt="comment.author_name"
          class="w-9 h-9 rounded-full object-cover"
        />
        <div v-else class="w-9 h-9 rounded-full bg-primary/20 flex items-center justify-center text-sm font-semibold text-primary">
          {{ comment.author_name.charAt(0).toUpperCase() }}
        </div>
      </div>
      <!-- Body -->
      <div class="flex-1">
        <div class="flex items-baseline gap-2 mb-1">
          <span class="text-sm font-medium">{{ comment.author_name }}</span>
          <span class="text-xs text-muted-foreground">{{ comment.created_at }}</span>
        </div>
        <p class="text-sm text-foreground/90 leading-relaxed">{{ comment.body }}</p>
      </div>
    </div>
  </div>

  <!-- Flash: comment submitted -->
  <Transition name="fade">
    <div
      v-if="$page.props.flash?.status"
      class="mb-6 flex items-center gap-2 rounded-md bg-status-success-bg border border-status-success-border px-4 py-3 text-sm text-status-success-fg"
    >
      {{ $page.props.flash.status }}
    </div>
  </Transition>

  <!-- Submission form -->
  <div class="rounded-lg border bg-card p-6">
    <h3 class="text-sm font-semibold mb-4">Leave a comment</h3>
    <form @submit.prevent="submitComment" class="space-y-4">
      <!-- Honeypot (hidden) -->
      <input type="text" name="website" v-model="form.website" class="hidden" tabindex="-1" autocomplete="off" />

      <!-- Author name — hidden if logged in -->
      <div v-if="!authUser" class="space-y-1">
        <label class="text-sm font-medium">Name <span class="text-destructive">*</span></label>
        <input
          v-model="form.author_name"
          type="text"
          class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
          :class="{ 'border-destructive': form.errors.author_name }"
          placeholder="Your name"
        />
        <p v-if="form.errors.author_name" class="text-xs text-destructive">{{ form.errors.author_name }}</p>
      </div>

      <!-- Author email — hidden if logged in -->
      <div v-if="!authUser" class="space-y-1">
        <label class="text-sm font-medium">Email <span class="text-muted-foreground text-xs">(optional)</span></label>
        <input
          v-model="form.author_email"
          type="email"
          class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
          :class="{ 'border-destructive': form.errors.author_email }"
          placeholder="your@email.com"
        />
        <p v-if="form.errors.author_email" class="text-xs text-destructive">{{ form.errors.author_email }}</p>
      </div>
      <p v-else class="text-xs text-muted-foreground">Commenting as <strong>{{ authUser.name }}</strong></p>

      <!-- Body -->
      <div class="space-y-1">
        <label class="text-sm font-medium">Comment <span class="text-destructive">*</span></label>
        <textarea
          v-model="form.body"
          rows="4"
          class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring resize-none"
          :class="{ 'border-destructive': form.errors.body }"
          placeholder="Share your thoughts..."
        ></textarea>
        <p v-if="form.errors.body" class="text-xs text-destructive">{{ form.errors.body }}</p>
      </div>

      <div class="flex justify-end">
        <button
          type="submit"
          :disabled="form.processing"
          class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 disabled:opacity-50"
        >
          {{ form.processing ? 'Submitting...' : 'Post comment' }}
        </button>
      </div>
    </form>
  </div>
</div>
```

**Step 2: Update the script setup**

Replace the existing `<script setup>` with:

```vue
<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import BlogLayout from '@/Layouts/BlogLayout.vue'
import BlogSidebar from '@/Components/BlogSidebar.vue'

defineOptions({ layout: BlogLayout })

const props = defineProps({
  post:     Object,
  sidebar:  Object,
  comments: { type: Array, default: () => [] },
  authUser: { type: Object, default: null },
})

const form = useForm({
  author_name:  props.authUser?.name  ?? '',
  author_email: props.authUser?.email ?? '',
  body:         '',
  website:      '', // honeypot
})

function submitComment() {
  form.post(route('comments.store', props.post.slug), {
    preserveScroll: true,
    onSuccess: () => form.reset('body'),
  })
}

function formatDate(date) {
  if (!date) return ''
  return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })
}
</script>
```

**Step 3: Build**

```bash
npm run build
```

**Step 4: Run all tests**

```bash
php artisan test
```

Expected: all tests pass.

**Step 5: Commit**

```bash
git add resources/js/Pages/Blog/Show.vue
git commit -m "feat: add comment list and submission form to Blog/Show.vue"
```

---

### Task 10: Final verification and PR

**Step 1: Run full test suite**

```bash
php artisan test
```

Expected: all tests pass (original 2 model tests + 11 new feature tests = 13 comment tests, plus all pre-existing tests).

**Step 2: Build**

```bash
npm run build
```

Expected: clean build.

**Step 3: Invoke finishing-a-development-branch skill**

Use `superpowers:finishing-a-development-branch` to push `feature/comments` and create a PR targeting `master`.
