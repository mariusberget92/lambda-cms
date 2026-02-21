# Comment System Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add a flat comment system to the public blog. Guests (name + email required) and authenticated users (body only) can post comments. Guest comments are held as `pending` and require admin approval before appearing. Auth-user comments are `approved` immediately. Admins moderate via a dedicated dashboard page.

**Architecture:** Inertia-native. Standard form POSTs — no axios, no SPA fetch pattern. All comments returned to the frontend strip `author_email`. Plain-text body stored and rendered with `{{ }}` (no v-html, no XSS risk). Rate limiting on guest submissions (3 per IP per minute) registered via `RateLimiter::for()` inside `AppServiceProvider::boot()`.

**Tech Stack:** Laravel 12, Inertia 2, Vue 3, Tailwind CSS 4, shadcn-vue / reka-ui / lucide-vue-next, Spatie Permissions, SQLite, PHPUnit

---

## Reference files (read these before each task)

- `app/Http/Controllers/PostController.php` — ownership/admin abort(403) pattern
- `app/Models/Post.php` — model structure, scopes, relationships
- `tests/Feature/PostTest.php` — test structure, setUp pattern, makeUser/makeAdmin helpers
- `resources/js/Pages/Posts/Index.vue` — dashboard table pattern (filter tabs, delete modal)
- `resources/js/Layouts/AppLayout.vue` — SidebarLink pattern for nav
- `resources/js/Pages/Blog/Show.vue` — Blog show structure (where CommentSection is inserted)
- `routes/web.php` — route group structure and middleware conventions
- `bootstrap/app.php` — middleware alias registration location

---

## Task 1: Migration + Model

**Files to create/modify:**
- Create: `database/migrations/2026_02_21_000003_create_comments_table.php`
- Create: `app/Models/Comment.php`
- Create: `database/factories/CommentFactory.php`
- Modify: `app/Models/Post.php` — add `comments()` hasMany

### Step 1: Write the test first

Write `tests/Feature/CommentTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->markAsInstalled();
        $this->seedRolesAndPermissions();
    }

    private function makeUser(): User
    {
        return User::factory()->create()->assignRole('user');
    }

    private function makeAdmin(): User
    {
        return User::factory()->create()->assignRole('administrator');
    }

    // ── Task 1: Model structure ───────────────────────────────────────────────

    public function test_comment_belongs_to_post(): void
    {
        $post    = Post::factory()->published()->create();
        $comment = Comment::factory()->create(['post_id' => $post->id]);

        $this->assertTrue($comment->post->is($post));
    }

    public function test_post_has_many_comments(): void
    {
        $post = Post::factory()->published()->create();
        Comment::factory(3)->create(['post_id' => $post->id]);

        $this->assertCount(3, $post->comments);
    }
}
```

### Step 2: Run the test — confirm it fails

```bash
php artisan test tests/Feature/CommentTest.php
```

Expected: FAILED — `Comment` class not found.

### Step 3: Write the migration

Create `database/migrations/2026_02_21_000003_create_comments_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('author_name');
            $table->string('author_email')->nullable();
            $table->text('body');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
```

### Step 4: Write the Comment model

Create `app/Models/Comment.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comment extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_id',
        'user_id',
        'author_name',
        'author_email',
        'body',
        'status',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
```

### Step 5: Write the CommentFactory

Create `database/factories/CommentFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommentFactory extends Factory
{
    protected $model = Comment::class;

    public function definition(): array
    {
        return [
            'post_id'      => Post::factory()->published(),
            'user_id'      => null,
            'author_name'  => $this->faker->name(),
            'author_email' => $this->faker->safeEmail(),
            'body'         => $this->faker->paragraph(),
            'status'       => 'pending',
        ];
    }

    public function approved(): static
    {
        return $this->state(['status' => 'approved']);
    }

    public function forUser(User $user): static
    {
        return $this->state([
            'user_id'      => $user->id,
            'author_name'  => $user->name,
            'author_email' => null,
            'status'       => 'approved',
        ]);
    }
}
```

### Step 6: Add `comments()` to Post model

In `app/Models/Post.php`, add the import and method:

```php
use Illuminate\Database\Eloquent\Relations\HasMany;

public function comments(): HasMany
{
    return $this->hasMany(Comment::class);
}
```

### Step 7: Run migration

```bash
php artisan migrate
```

### Step 8: Run the test — confirm it passes

```bash
php artisan test tests/Feature/CommentTest.php
```

Expected: 2 tests pass.

### Step 9: Commit

```bash
git add database/migrations/2026_02_21_000003_create_comments_table.php app/Models/Comment.php database/factories/CommentFactory.php app/Models/Post.php tests/Feature/CommentTest.php
git commit -m "$(cat <<'EOF'
feat: add comments table, Comment model, CommentFactory, Post hasMany comments

Co-Authored-By: Claude Sonnet 4.6 <noreply@anthropic.com>
EOF
)"
```

---

## Task 2: CommentController@store (with rate limiting)

**Files to create/modify:**
- Create: `app/Http/Controllers/CommentController.php`
- Modify: `routes/web.php` — add `POST /blog/{post}/comments`
- Modify: `app/Providers/AppServiceProvider.php` — register rate limiter

### Step 1: Add test cases to CommentTest.php

Append to `tests/Feature/CommentTest.php` (inside the class, after the existing tests):

```php
// ── Task 2: store ─────────────────────────────────────────────────────────────

public function test_guest_can_submit_comment_status_is_pending(): void
{
    $post = Post::factory()->published()->create();

    $this->post(route('comments.store', $post), [
        'author_name'  => 'Alice',
        'author_email' => 'alice@example.com',
        'body'         => 'Great post!',
    ])->assertRedirect();

    $this->assertDatabaseHas('comments', [
        'post_id'     => $post->id,
        'author_name' => 'Alice',
        'body'        => 'Great post!',
        'status'      => 'pending',
        'user_id'     => null,
    ]);
}

public function test_auth_user_can_submit_comment_status_is_approved(): void
{
    $user = $this->makeUser();
    $post = Post::factory()->published()->create();

    $this->actingAs($user)->post(route('comments.store', $post), [
        'body' => 'Nice article.',
    ])->assertRedirect();

    $this->assertDatabaseHas('comments', [
        'post_id'     => $post->id,
        'user_id'     => $user->id,
        'author_name' => $user->name,
        'body'        => 'Nice article.',
        'status'      => 'approved',
    ]);
}

public function test_guest_comment_does_not_appear_in_blog_show(): void
{
    $post = Post::factory()->published()->create(['slug' => 'test-post']);
    Comment::factory()->create([
        'post_id' => $post->id,
        'status'  => 'pending',
        'body'    => 'Pending comment body',
    ]);

    $this->get(route('blog.show', $post->slug))->assertInertia(
        fn ($page) => $page->where('comments', [])
    );
}

public function test_approved_comment_appears_in_blog_show(): void
{
    $post = Post::factory()->published()->create(['slug' => 'approved-post']);
    Comment::factory()->approved()->create([
        'post_id'     => $post->id,
        'author_name' => 'Bob',
        'body'        => 'Approved comment body',
    ]);

    $this->get(route('blog.show', $post->slug))->assertInertia(
        fn ($page) => $page
            ->has('comments', 1)
            ->where('comments.0.body', 'Approved comment body')
            ->where('comments.0.author_name', 'Bob')
            ->missing('comments.0.author_email')
    );
}

public function test_rate_limit_blocks_excessive_guest_submissions(): void
{
    $post = Post::factory()->published()->create();

    for ($i = 0; $i < 3; $i++) {
        $this->post(route('comments.store', $post), [
            'author_name'  => 'Spammer',
            'author_email' => 'spam@example.com',
            'body'         => 'Comment ' . $i,
        ])->assertRedirect();
    }

    $this->post(route('comments.store', $post), [
        'author_name'  => 'Spammer',
        'author_email' => 'spam@example.com',
        'body'         => 'Too many',
    ])->assertStatus(429);
}

public function test_store_validates_body_required(): void
{
    $post = Post::factory()->published()->create();

    $this->post(route('comments.store', $post), [
        'author_name'  => 'Alice',
        'author_email' => 'alice@example.com',
    ])->assertSessionHasErrors('body');
}

public function test_store_validates_author_name_required_for_guest(): void
{
    $post = Post::factory()->published()->create();

    $this->post(route('comments.store', $post), [
        'author_email' => 'alice@example.com',
        'body'         => 'Hello',
    ])->assertSessionHasErrors('author_name');
}

public function test_store_validates_author_email_required_for_guest(): void
{
    $post = Post::factory()->published()->create();

    $this->post(route('comments.store', $post), [
        'author_name' => 'Alice',
        'body'        => 'Hello',
    ])->assertSessionHasErrors('author_email');
}

public function test_auth_user_does_not_need_name_or_email_fields(): void
{
    $user = $this->makeUser();
    $post = Post::factory()->published()->create();

    $this->actingAs($user)->post(route('comments.store', $post), [
        'body' => 'Just the body.',
    ])->assertSessionHasNoErrors();
}

public function test_author_email_is_not_returned_in_blog_show(): void
{
    $post = Post::factory()->published()->create(['slug' => 'email-test-post']);
    Comment::factory()->approved()->create([
        'post_id'      => $post->id,
        'author_email' => 'secret@example.com',
    ]);

    $this->get(route('blog.show', $post->slug))->assertInertia(
        fn ($page) => $page->missing('comments.0.author_email')
    );
}
```

### Step 2: Run tests — confirm they fail

```bash
php artisan test tests/Feature/CommentTest.php --filter="test_guest_can_submit|test_auth_user_can_submit|test_rate_limit|test_store_validates|test_approved_comment|test_guest_comment_does_not|test_author_email"
```

Expected: FAILED — route `comments.store` not found.

### Step 3: Register the rate limiter

In `app/Providers/AppServiceProvider.php`, update `boot()`:

```php
<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        RateLimiter::for('guest-comments', function (Request $request) {
            return $request->user()
                ? Limit::none()
                : Limit::perMinute(3)->by($request->ip());
        });
    }
}
```

### Step 4: Create CommentController

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
     * Dashboard moderation index.
     */
    public function index(Request $request): Response
    {
        $comments = Comment::with('post:id,title,slug')
            ->when(
                $request->input('status'),
                fn ($q, $status) => $q->where('status', $status)
            )
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Comment $comment) => [
                'id'          => $comment->id,
                'body'        => $comment->body,
                'author_name' => $comment->author_name,
                'status'      => $comment->status,
                'created_at'  => $comment->created_at->toDateString(),
                'post'        => [
                    'id'    => $comment->post->id,
                    'title' => $comment->post->title,
                    'slug'  => $comment->post->slug,
                ],
            ]);

        return Inertia::render('Comments/Index', [
            'comments' => $comments,
            'filters'  => $request->only('status'),
        ]);
    }

    /**
     * Store a new comment (public: guests + auth users).
     */
    public function store(Request $request, Post $post): RedirectResponse
    {
        $isGuest = ! $request->user();

        $validated = $request->validate([
            'body'         => ['required', 'string', 'max:2000'],
            'author_name'  => $isGuest ? ['required', 'string', 'max:100'] : ['nullable'],
            'author_email' => $isGuest ? ['required', 'email', 'max:255'] : ['nullable'],
        ]);

        if ($isGuest) {
            Comment::create([
                'post_id'      => $post->id,
                'user_id'      => null,
                'author_name'  => $validated['author_name'],
                'author_email' => $validated['author_email'],
                'body'         => $validated['body'],
                'status'       => 'pending',
            ]);

            return back()->with('status', 'Your comment is awaiting approval.');
        }

        $user = $request->user();

        Comment::create([
            'post_id'      => $post->id,
            'user_id'      => $user->id,
            'author_name'  => $user->name,
            'author_email' => null,
            'body'         => $validated['body'],
            'status'       => 'approved',
        ]);

        return back()->with('status', 'Comment posted.');
    }

    /**
     * Delete a comment (owner or administrator).
     */
    public function destroy(Request $request, Comment $comment): RedirectResponse
    {
        if ($comment->user_id !== $request->user()->id && ! $request->user()->hasRole('administrator')) {
            abort(403);
        }

        $comment->delete();

        return back()->with('status', 'Comment deleted.');
    }

    /**
     * Approve a pending comment (administrator only — enforced at route level).
     */
    public function approve(Comment $comment): RedirectResponse
    {
        $comment->update(['status' => 'approved']);

        return back()->with('status', 'Comment approved.');
    }

    /**
     * Reject a comment (administrator only — enforced at route level).
     */
    public function reject(Comment $comment): RedirectResponse
    {
        $comment->update(['status' => 'rejected']);

        return back()->with('status', 'Comment rejected.');
    }
}
```

### Step 5: Add routes to web.php

Add `use App\Http\Controllers\CommentController;` to imports.

Inside the `Route::middleware('installed')` outermost group, after the blog show route, add the public comment store route:

```php
Route::post('/blog/{post}/comments', [CommentController::class, 'store'])
    ->middleware('throttle:guest-comments')
    ->name('comments.store');
```

Inside `Route::middleware(['auth', 'verified'])` group, add:

```php
Route::get('/comments', [CommentController::class, 'index'])->name('comments.index');
Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');
```

Inside `Route::middleware(['auth', 'verified', 'role:administrator'])` group, add:

```php
Route::patch('/comments/{comment}/approve', [CommentController::class, 'approve'])->name('comments.approve');
Route::patch('/comments/{comment}/reject',  [CommentController::class, 'reject'])->name('comments.reject');
```

### Step 6: Run tests — confirm they pass

```bash
php artisan test tests/Feature/CommentTest.php
```

Expected: all tests written so far pass.

### Step 7: Commit

```bash
git add app/Http/Controllers/CommentController.php app/Providers/AppServiceProvider.php routes/web.php tests/Feature/CommentTest.php
git commit -m "$(cat <<'EOF'
feat: add CommentController with store, destroy, approve, reject and rate limiting

Co-Authored-By: Claude Sonnet 4.6 <noreply@anthropic.com>
EOF
)"
```

---

## Task 3: Dashboard Comments/Index.vue

**Files to create:**
- Create: `resources/js/Pages/Comments/Index.vue`

### Step 1: Add dashboard index tests to CommentTest.php

Append to `tests/Feature/CommentTest.php`:

```php
// ── Task 3: dashboard index ───────────────────────────────────────────────────

public function test_dashboard_comments_index_requires_auth(): void
{
    $this->get(route('comments.index'))->assertRedirect(route('login'));
}

public function test_dashboard_comments_index_lists_all_comments(): void
{
    $admin = $this->makeAdmin();
    $post  = Post::factory()->published()->create();

    Comment::factory()->create(['post_id' => $post->id, 'status' => 'pending']);
    Comment::factory()->approved()->create(['post_id' => $post->id]);
    Comment::factory()->create(['post_id' => $post->id, 'status' => 'rejected']);

    $this->actingAs($admin)
        ->get(route('comments.index'))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('Comments/Index')
                ->has('comments.data', 3)
        );
}

public function test_dashboard_comments_index_filters_by_status(): void
{
    $admin = $this->makeAdmin();
    $post  = Post::factory()->published()->create();

    Comment::factory()->create(['post_id' => $post->id, 'status' => 'pending']);
    Comment::factory()->approved()->create(['post_id' => $post->id]);

    $this->actingAs($admin)
        ->get(route('comments.index', ['status' => 'pending']))
        ->assertInertia(
            fn ($page) => $page
                ->has('comments.data', 1)
                ->where('comments.data.0.status', 'pending')
        );
}

public function test_dashboard_comments_index_is_accessible_by_regular_user(): void
{
    $user = $this->makeUser();

    $this->actingAs($user)
        ->get(route('comments.index'))
        ->assertOk();
}
```

### Step 2: Run tests — confirm they fail

```bash
php artisan test tests/Feature/CommentTest.php --filter="test_dashboard_comments_index"
```

Expected: FAILED — Inertia component `Comments/Index` not found.

### Step 3: Create Comments/Index.vue

Create `resources/js/Pages/Comments/Index.vue`:

```vue
<template>
  <AppLayout title="Comments">
    <Head title="Comments" />

    <!-- Page header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-lg font-semibold">Comments</h2>
        <p class="text-sm text-muted-foreground mt-0.5">Moderate blog comments</p>
      </div>
      <span
        v-if="pendingCount > 0"
        class="inline-flex items-center rounded-full bg-amber-100 text-amber-700 px-2.5 py-0.5 text-xs font-medium"
      >
        {{ pendingCount }} pending
      </span>
    </div>

    <!-- Flash message -->
    <Transition name="fade">
      <div
        v-if="$page.props.flash?.status"
        class="mb-4 flex items-center gap-2 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700"
      >
        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        {{ $page.props.flash.status }}
      </div>
    </Transition>

    <!-- Status filter tabs -->
    <div class="flex items-center gap-1 mb-4 border-b">
      <button
        v-for="tab in tabs"
        :key="tab.value"
        type="button"
        class="px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors"
        :class="statusFilter === tab.value
          ? 'border-primary text-foreground'
          : 'border-transparent text-muted-foreground hover:text-foreground'"
        @click="applyFilter(tab.value)"
      >
        {{ tab.label }}
      </button>
    </div>

    <!-- Table -->
    <div class="rounded-lg border overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-muted/50 text-muted-foreground">
          <tr>
            <th class="text-left font-medium px-4 py-3">Comment</th>
            <th class="text-left font-medium px-4 py-3 hidden sm:table-cell">Post</th>
            <th class="text-left font-medium px-4 py-3 hidden md:table-cell">Status</th>
            <th class="text-left font-medium px-4 py-3 hidden lg:table-cell">Date</th>
            <th class="px-4 py-3 w-28"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-border">
          <tr v-if="comments.data.length === 0">
            <td colspan="5" class="px-4 py-12 text-center text-muted-foreground">
              No comments found.
            </td>
          </tr>
          <tr
            v-for="comment in comments.data"
            :key="comment.id"
            class="hover:bg-muted/30 transition-colors group"
          >
            <td class="px-4 py-3">
              <div class="font-medium text-xs text-muted-foreground mb-0.5">{{ comment.author_name }}</div>
              <div class="line-clamp-2 text-sm">{{ comment.body }}</div>
            </td>
            <td class="px-4 py-3 hidden sm:table-cell text-muted-foreground text-xs">
              <a :href="`/blog/${comment.post.slug}`" class="hover:text-foreground underline underline-offset-2 line-clamp-1">
                {{ comment.post.title }}
              </a>
            </td>
            <td class="px-4 py-3 hidden md:table-cell">
              <span
                class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium"
                :class="{
                  'bg-amber-100 text-amber-700': comment.status === 'pending',
                  'bg-green-100 text-green-700': comment.status === 'approved',
                  'bg-red-100 text-red-700':     comment.status === 'rejected',
                }"
              >
                <span
                  class="w-1.5 h-1.5 rounded-full"
                  :class="{
                    'bg-amber-500': comment.status === 'pending',
                    'bg-green-500': comment.status === 'approved',
                    'bg-red-500':   comment.status === 'rejected',
                  }"
                ></span>
                {{ comment.status.charAt(0).toUpperCase() + comment.status.slice(1) }}
              </span>
            </td>
            <td class="px-4 py-3 hidden lg:table-cell text-muted-foreground text-xs">
              {{ comment.created_at }}
            </td>
            <td class="px-4 py-3">
              <div
                v-if="isAdmin"
                class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity"
              >
                <!-- Approve -->
                <button
                  v-if="comment.status !== 'approved'"
                  type="button"
                  title="Approve"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-green-100 hover:text-green-700 transition-colors"
                  @click="approve(comment)"
                >
                  <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                  </svg>
                </button>
                <!-- Reject -->
                <button
                  v-if="comment.status !== 'rejected'"
                  type="button"
                  title="Reject"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-red-100 hover:text-red-700 transition-colors"
                  @click="reject(comment)"
                >
                  <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                  </svg>
                </button>
                <!-- Delete -->
                <button
                  type="button"
                  title="Delete"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-destructive/10 hover:text-destructive transition-colors"
                  @click="confirmDelete(comment)"
                >
                  <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
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
    <div v-if="comments.last_page > 1" class="flex items-center justify-between mt-4 text-sm">
      <p class="text-muted-foreground">
        Showing {{ comments.from }}–{{ comments.to }} of {{ comments.total }}
      </p>
      <div class="flex gap-1">
        <a
          v-for="link in comments.links"
          :key="link.label"
          :href="link.url ?? undefined"
          class="inline-flex items-center justify-center px-3 py-1.5 rounded-md text-sm transition-colors"
          :class="link.active
            ? 'bg-primary text-primary-foreground font-medium'
            : link.url
              ? 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'
              : 'text-muted-foreground/40 cursor-not-allowed pointer-events-none'"
        >{{ decodeHtmlEntities(link.label) }}</a>
      </div>
    </div>

    <!-- Delete confirmation modal -->
    <Transition name="fade">
      <div v-if="deleteTarget" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="deleteTarget = null" />
        <div class="relative bg-card border rounded-xl shadow-xl w-full max-w-sm p-6">
          <h3 class="font-semibold text-base mb-2">Delete comment?</h3>
          <p class="text-sm text-muted-foreground mb-5">
            By <span class="font-medium text-foreground">{{ deleteTarget.author_name }}</span>. This cannot be undone.
          </p>
          <div class="flex gap-3 justify-end">
            <button
              type="button"
              @click="deleteTarget = null"
              class="rounded-md border px-4 py-2 text-sm font-medium hover:bg-accent transition-colors"
            >
              Cancel
            </button>
            <button
              type="button"
              @click="deleteComment"
              class="rounded-md bg-destructive px-4 py-2 text-sm font-medium text-destructive-foreground hover:bg-destructive/90 transition-colors"
            >
              Delete
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  comments: Object,
  filters:  Object,
})

const page    = usePage()
const isAdmin = computed(() => page.props.auth?.user?.roles?.includes('administrator') ?? false)

const pendingCount = computed(
  () => props.comments.data.filter(c => c.status === 'pending').length
)

const tabs = [
  { label: 'All',      value: '' },
  { label: 'Pending',  value: 'pending' },
  { label: 'Approved', value: 'approved' },
  { label: 'Rejected', value: 'rejected' },
]

const statusFilter = ref(props.filters?.status ?? '')

function applyFilter(value) {
  statusFilter.value = value
  router.get(
    route('comments.index'),
    { status: value },
    { preserveState: true, replace: true }
  )
}

function decodeHtmlEntities(str) {
  const txt = document.createElement('textarea')
  txt.innerHTML = str
  return txt.value
}

function approve(comment) {
  router.patch(route('comments.approve', comment.id))
}

function reject(comment) {
  router.patch(route('comments.reject', comment.id))
}

const deleteTarget = ref(null)

function confirmDelete(comment) {
  deleteTarget.value = comment
}

function deleteComment() {
  if (!deleteTarget.value) return
  router.delete(route('comments.destroy', deleteTarget.value.id), {
    onFinish: () => { deleteTarget.value = null },
  })
}
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
```

### Step 4: Run tests — confirm they pass

```bash
php artisan test tests/Feature/CommentTest.php --filter="test_dashboard_comments_index"
```

Expected: all pass.

### Step 5: Commit

```bash
git add resources/js/Pages/Comments/Index.vue tests/Feature/CommentTest.php
git commit -m "$(cat <<'EOF'
feat: add Comments/Index.vue dashboard page with status filter tabs and moderation actions

Co-Authored-By: Claude Sonnet 4.6 <noreply@anthropic.com>
EOF
)"
```

---

## Task 4: BlogController@show update

**Files to modify:**
- `app/Http/Controllers/BlogController.php` — eager load approved comments, return `comments` and `pending_count`

### Step 1: Add blog show comment tests to CommentTest.php

Append to `tests/Feature/CommentTest.php`:

```php
// ── Task 4: blog show comments ────────────────────────────────────────────────

public function test_blog_show_includes_pending_count_for_admin(): void
{
    $admin = $this->makeAdmin();
    $post  = Post::factory()->published()->create(['slug' => 'admin-count-post']);

    Comment::factory(2)->create(['post_id' => $post->id, 'status' => 'pending']);
    Comment::factory()->approved()->create(['post_id' => $post->id]);

    $this->actingAs($admin)
        ->get(route('blog.show', $post->slug))
        ->assertInertia(
            fn ($page) => $page->where('pending_count', 2)
        );
}

public function test_blog_show_does_not_include_pending_count_for_regular_user(): void
{
    $user = $this->makeUser();
    $post = Post::factory()->published()->create(['slug' => 'user-count-post']);

    Comment::factory(2)->create(['post_id' => $post->id, 'status' => 'pending']);

    $this->actingAs($user)
        ->get(route('blog.show', $post->slug))
        ->assertInertia(
            fn ($page) => $page->where('pending_count', 0)
        );
}
```

### Step 2: Run tests — confirm they fail

```bash
php artisan test tests/Feature/CommentTest.php --filter="test_approved_comment_appears|test_guest_comment_does_not|test_author_email_is_not|test_blog_show_includes_pending|test_blog_show_does_not_include"
```

Expected: FAILED — `comments` key not found in Inertia props.

### Step 3: Update BlogController@show

In `app/Http/Controllers/BlogController.php`, update `show()`. Add `use Illuminate\Http\Request;` to imports and change the signature from `show(string $slug)` to `show(Request $request, string $slug)`. Update the method body to eager-load comments and return the new props:

```php
public function show(Request $request, string $slug): Response
{
    $post = Post::published()
        ->with([
            'author:id,name,avatar',
            'categories:id,name,slug',
            'tags:id,name,slug',
            'featuredImage:id,path,disk,alt',
            'comments' => fn ($q) => $q->approved()->oldest(),
        ])
        ->where('slug', $slug)
        ->firstOrFail();

    $isAdmin      = $request->user()?->hasRole('administrator') ?? false;
    $pendingCount = $isAdmin ? $post->comments()->pending()->count() : 0;
    $authUserId   = $request->user()?->id;

    return Inertia::render('Blog/Show', [
        'post' => [
            'id'                 => $post->id,
            'title'              => $post->title,
            'slug'               => $post->slug,
            'excerpt'            => $post->excerpt,
            'body'               => $post->body,
            'published_at'       => $post->published_at?->toDateString(),
            'featured_image_url' => $post->featuredImage?->url,
            'featured_image_alt' => $post->featuredImage?->alt,
            'author'             => [
                'name'       => $post->author->name,
                'avatar_url' => $post->author->avatar_url,
            ],
            'categories' => $post->categories->map(fn ($c) => [
                'id'   => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
            ])->values(),
            'tags' => $post->tags->map(fn ($t) => [
                'name' => $t->name,
                'slug' => $t->slug,
            ])->values(),
        ],
        'comments' => $post->comments
            ->map(fn ($comment) => [
                'id'          => $comment->id,
                'body'        => $comment->body,
                'author_name' => $comment->author_name,
                'created_at'  => $comment->created_at->toDateString(),
                'is_owner'    => $authUserId && $comment->user_id === $authUserId,
            ])
            ->values(),
        'pending_count' => $pendingCount,
        'sidebar'       => $this->sidebarData(),
    ]);
}
```

Note: Since comments are eager-loaded with `->approved()->oldest()`, the collection already contains only approved comments — no need to filter again in the map.

### Step 4: Run tests — confirm they pass

```bash
php artisan test tests/Feature/CommentTest.php tests/Feature/BlogTest.php
```

Expected: all pass. BlogTest must also pass (no regressions).

### Step 5: Commit

```bash
git add app/Http/Controllers/BlogController.php tests/Feature/CommentTest.php
git commit -m "$(cat <<'EOF'
feat: update BlogController@show to return approved comments and pending_count

Co-Authored-By: Claude Sonnet 4.6 <noreply@anthropic.com>
EOF
)"
```

---

## Task 5: Vue frontend components + Blog/Show.vue integration

**Files to create/modify:**
- Create: `resources/js/Components/CommentItem.vue`
- Create: `resources/js/Components/CommentForm.vue`
- Create: `resources/js/Components/CommentSection.vue`
- Modify: `resources/js/Pages/Blog/Show.vue` — add `<CommentSection>` below tags

### Step 1: Create CommentItem.vue

Create `resources/js/Components/CommentItem.vue`:

```vue
<template>
  <div class="flex gap-3 py-4 border-b last:border-b-0">
    <!-- Avatar initial -->
    <div class="shrink-0 w-9 h-9 rounded-full bg-primary/10 flex items-center justify-center text-sm font-semibold text-primary">
      {{ comment.author_name.charAt(0).toUpperCase() }}
    </div>

    <div class="flex-1 min-w-0">
      <!-- Header -->
      <div class="flex items-center justify-between gap-2 mb-1">
        <div>
          <span class="text-sm font-medium">{{ comment.author_name }}</span>
          <span class="text-xs text-muted-foreground ml-2">{{ comment.created_at }}</span>
        </div>
        <!-- Delete button (owner or admin) -->
        <button
          v-if="comment.is_owner || isAdmin"
          type="button"
          class="text-muted-foreground hover:text-destructive transition-colors p-1 rounded"
          title="Delete comment"
          @click="$emit('delete', comment)"
        >
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
          </svg>
        </button>
      </div>

      <!-- Body — plain text, safe -->
      <p class="text-sm text-foreground leading-relaxed whitespace-pre-wrap">{{ comment.body }}</p>
    </div>
  </div>
</template>

<script setup>
defineProps({
  comment: { type: Object,  required: true },
  isAdmin: { type: Boolean, default: false },
})

defineEmits(['delete'])
</script>
```

### Step 2: Create CommentForm.vue

Create `resources/js/Components/CommentForm.vue`:

```vue
<template>
  <form @submit.prevent="submit" class="space-y-4">
    <!-- Guest fields -->
    <template v-if="!user">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium mb-1" for="comment-name">Name</label>
          <input
            id="comment-name"
            v-model="form.author_name"
            type="text"
            required
            maxlength="100"
            placeholder="Your name"
            class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
            :class="{ 'border-destructive': form.errors.author_name }"
          />
          <p v-if="form.errors.author_name" class="mt-1 text-xs text-destructive">{{ form.errors.author_name }}</p>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1" for="comment-email">
            Email <span class="text-muted-foreground font-normal">(not published)</span>
          </label>
          <input
            id="comment-email"
            v-model="form.author_email"
            type="email"
            required
            maxlength="255"
            placeholder="you@example.com"
            class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
            :class="{ 'border-destructive': form.errors.author_email }"
          />
          <p v-if="form.errors.author_email" class="mt-1 text-xs text-destructive">{{ form.errors.author_email }}</p>
        </div>
      </div>
    </template>

    <!-- Signed-in user -->
    <template v-else>
      <p class="text-sm text-muted-foreground">
        Commenting as <span class="font-medium text-foreground">{{ user.name }}</span>
      </p>
    </template>

    <!-- Body -->
    <div>
      <label class="block text-sm font-medium mb-1" for="comment-body">Comment</label>
      <textarea
        id="comment-body"
        v-model="form.body"
        required
        rows="4"
        maxlength="2000"
        placeholder="Share your thoughts..."
        class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring resize-none"
        :class="{ 'border-destructive': form.errors.body }"
      />
      <div class="flex items-center justify-between mt-1">
        <p v-if="form.errors.body" class="text-xs text-destructive">{{ form.errors.body }}</p>
        <p class="text-xs text-muted-foreground ml-auto">{{ form.body.length }} / 2000</p>
      </div>
    </div>

    <!-- Submit -->
    <button
      type="submit"
      :disabled="form.processing"
      class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 disabled:opacity-50 disabled:cursor-not-allowed"
    >
      <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
      </svg>
      {{ user ? 'Post comment' : 'Submit comment' }}
    </button>
  </form>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
  postId: { type: Number, required: true },
  user:   { type: Object, default: null },
})

const form = useForm({
  author_name:  '',
  author_email: '',
  body:         '',
})

function submit() {
  form.post(route('comments.store', props.postId), {
    preserveScroll: true,
    onSuccess: () => form.reset('body', 'author_name', 'author_email'),
  })
}
</script>
```

### Step 3: Create CommentSection.vue

Create `resources/js/Components/CommentSection.vue`:

```vue
<template>
  <section class="mt-10 pt-8 border-t">
    <h2 class="text-xl font-semibold mb-6">
      {{ comments.length }} {{ comments.length === 1 ? 'Comment' : 'Comments' }}
    </h2>

    <!-- Pending notice for admins -->
    <div
      v-if="isAdmin && pendingCount > 0"
      class="mb-6 flex items-center gap-2 rounded-md bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-700"
    >
      <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
      <span>
        {{ pendingCount }} comment{{ pendingCount === 1 ? '' : 's' }} awaiting moderation.
        <a :href="route('comments.index')" class="underline font-medium hover:text-amber-800">Review in dashboard</a>
      </span>
    </div>

    <!-- Flash message -->
    <Transition name="fade">
      <div
        v-if="$page.props.flash?.status"
        class="mb-6 flex items-center gap-2 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700"
      >
        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        {{ $page.props.flash.status }}
      </div>
    </Transition>

    <!-- Comment list -->
    <div v-if="comments.length" class="mb-8">
      <CommentItem
        v-for="comment in comments"
        :key="comment.id"
        :comment="comment"
        :is-admin="isAdmin"
        @delete="handleDelete"
      />
    </div>
    <div v-else class="mb-8 text-sm text-muted-foreground">
      No comments yet. Be the first!
    </div>

    <!-- Delete confirmation modal -->
    <Transition name="fade">
      <div v-if="deleteTarget" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="deleteTarget = null" />
        <div class="relative bg-card border rounded-xl shadow-xl w-full max-w-sm p-6">
          <h3 class="font-semibold text-base mb-2">Delete comment?</h3>
          <p class="text-sm text-muted-foreground mb-5">This cannot be undone.</p>
          <div class="flex gap-3 justify-end">
            <button type="button" @click="deleteTarget = null"
              class="rounded-md border px-4 py-2 text-sm font-medium hover:bg-accent transition-colors">
              Cancel
            </button>
            <button type="button" @click="doDelete"
              class="rounded-md bg-destructive px-4 py-2 text-sm font-medium text-destructive-foreground hover:bg-destructive/90 transition-colors">
              Delete
            </button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Comment form -->
    <div class="rounded-lg border bg-card p-6">
      <h3 class="text-base font-semibold mb-4">Leave a comment</h3>
      <CommentForm :post-id="postId" :user="user" />
    </div>
  </section>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import CommentItem from '@/Components/CommentItem.vue'
import CommentForm from '@/Components/CommentForm.vue'

const props = defineProps({
  comments:     { type: Array,  default: () => [] },
  postId:       { type: Number, required: true },
  pendingCount: { type: Number, default: 0 },
})

const page    = usePage()
const user    = computed(() => page.props.auth?.user ?? null)
const isAdmin = computed(() => page.props.auth?.user?.roles?.includes('administrator') ?? false)

const deleteTarget = ref(null)

function handleDelete(comment) {
  deleteTarget.value = comment
}

function doDelete() {
  if (!deleteTarget.value) return
  router.delete(route('comments.destroy', deleteTarget.value.id), {
    preserveScroll: true,
    onFinish: () => { deleteTarget.value = null },
  })
}
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
```

### Step 4: Update Blog/Show.vue

In `resources/js/Pages/Blog/Show.vue`:

1. Add import after other imports:
```js
import CommentSection from '@/Components/CommentSection.vue'
```

2. Update `defineProps` to include the new props:
```js
const props = defineProps({
  post:         Object,
  sidebar:      Object,
  comments:     { type: Array,  default: () => [] },
  pendingCount: { type: Number, default: 0 },
})
```

3. In the template, add `<CommentSection>` after the closing tag of the tags section and before the closing `</div>` of the main content column:
```html
<!-- Comments -->
<CommentSection
  :comments="comments"
  :post-id="post.id"
  :pending-count="pendingCount"
/>
```

### Step 5: Run full test suite

```bash
php artisan test
```

Expected: all tests pass. No regressions.

### Step 6: Commit

```bash
git add resources/js/Components/CommentItem.vue resources/js/Components/CommentForm.vue resources/js/Components/CommentSection.vue resources/js/Pages/Blog/Show.vue
git commit -m "$(cat <<'EOF'
feat: add CommentSection, CommentForm, CommentItem components and wire into Blog/Show

Co-Authored-By: Claude Sonnet 4.6 <noreply@anthropic.com>
EOF
)"
```

---

## Task 6: AppLayout nav link + final verification

**Files to modify:**
- `resources/js/Layouts/AppLayout.vue` — add Comments nav link in sidebar

### Step 1: Add Comments link to AppLayout.vue

In `resources/js/Layouts/AppLayout.vue`, find the Media `SidebarLink` block and add a Comments link directly after it (still inside the "Content" section):

```html
<SidebarLink :href="route('comments.index')" :active="currentRoute?.startsWith('comments.')">
  <template #icon>
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
    </svg>
  </template>
  Comments
</SidebarLink>
```

### Step 2: Run full test suite

```bash
php artisan test
```

Expected: all tests pass.

### Step 3: Commit

```bash
git add resources/js/Layouts/AppLayout.vue
git commit -m "$(cat <<'EOF'
feat: add Comments nav link to AppLayout sidebar

Co-Authored-By: Claude Sonnet 4.6 <noreply@anthropic.com>
EOF
)"
```

---

## Final manual smoke test (browser)

1. Visit `/blog/{any-published-slug}` — CommentSection appears below tags
2. Submit as guest → flash "awaiting approval", comment not visible
3. Log in as admin → visit `/comments` → comment shows as pending
4. Approve comment → revisit blog post → comment appears
5. Log in as regular user → submit comment → appears immediately
6. Delete own comment → disappears
7. Comments link appears in sidebar navigation

---

## Summary of all files

| File | Action |
|---|---|
| `database/migrations/2026_02_21_000003_create_comments_table.php` | Create |
| `app/Models/Comment.php` | Create |
| `database/factories/CommentFactory.php` | Create |
| `app/Models/Post.php` | Modify — add `comments()` HasMany |
| `app/Http/Controllers/CommentController.php` | Create |
| `app/Providers/AppServiceProvider.php` | Modify — register rate limiter |
| `routes/web.php` | Modify — add comment routes |
| `app/Http/Controllers/BlogController.php` | Modify — load comments, pending_count |
| `resources/js/Components/CommentItem.vue` | Create |
| `resources/js/Components/CommentForm.vue` | Create |
| `resources/js/Components/CommentSection.vue` | Create |
| `resources/js/Pages/Comments/Index.vue` | Create |
| `resources/js/Pages/Blog/Show.vue` | Modify — add CommentSection |
| `resources/js/Layouts/AppLayout.vue` | Modify — add Comments nav link |
| `tests/Feature/CommentTest.php` | Create |
