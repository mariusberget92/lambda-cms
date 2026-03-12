# Content Calendar & Post Scheduling Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a `scheduled` post status with a datetime picker in the editor and a `/calendar` admin page with a split-view monthly calendar.

**Architecture:** Extend the `posts.status` enum with a `scheduled` value; a Laravel Artisan command registered with `->withSchedule()` in `bootstrap/app.php` bulk-publishes overdue scheduled posts every minute; a new `CalendarController` serves Inertia and JSON responses; the existing post editor grows a third radio option with a conditional `datetime-local` input.

**Tech Stack:** Laravel 11, Inertia 2, Vue 3, Tailwind CSS 4, lucide-vue-next, PHPUnit

**Spec:** `docs/plans/2026-03-11-content-calendar-scheduling-design.md`

---

## Chunk 1: Database, Model, PostController

### Task 1: Migration — add `scheduled` to posts enum

**Files:**
- Create: `database/migrations/2026_03_11_000001_add_scheduled_to_posts_status.php`

> **Context:** The original migration (`2026_02_19_000001_create_posts_table.php`) defines `$table->enum('status', ['draft', 'published'])->default('draft')`. Tests run on SQLite (which stores enums as TEXT and doesn't enforce values), so we only need the `ALTER` on MySQL. `RefreshDatabase` rebuilds the full schema on each test run from all migrations in order, so the new migration is automatically applied.

- [ ] **Step 1: Create the migration file**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE posts MODIFY COLUMN status ENUM('draft', 'scheduled', 'published') NOT NULL DEFAULT 'draft'");
        }
        // SQLite: column is already TEXT, accepts any value — no change needed.
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE posts MODIFY COLUMN status ENUM('draft', 'published') NOT NULL DEFAULT 'draft'");
        }
    }
};
```

- [ ] **Step 2: Run the migration**

```bash
php artisan migrate
```

Expected: `Migrating: 2026_03_11_000001_add_scheduled_to_posts_status` then `Migrated`.

- [ ] **Step 3: Commit**

```bash
git add database/migrations/2026_03_11_000001_add_scheduled_to_posts_status.php
git commit -m "feat: add scheduled value to posts status enum"
```

---

### Task 2: Post model — `scopeScheduled()` and `isScheduled()`

**Files:**
- Modify: `app/Models/Post.php` (after line 75, in the Scopes section)
- Test: `tests/Feature/PostTest.php`

> **Context:** Existing scopes `scopePublished()` and `scopeDraft()` sit at lines 67–75. Add `scopeScheduled()` after them. The `isPublished()` helper is at line 108; add `isScheduled()` after it. Existing tests must all still pass after these additions.

- [ ] **Step 1: Write failing tests — add to `tests/Feature/PostTest.php`**

Find the `// ── Create / Store` comment (around line 46) and add a new section above it:

```php
// ── Scheduled Scope ───────────────────────────────────────────────────────

public function test_scope_scheduled_returns_only_scheduled_posts(): void
{
    $user = $this->makeUser();
    Post::factory()->create(['user_id' => $user->id, 'status' => 'published']);
    Post::factory()->create(['user_id' => $user->id, 'status' => 'draft']);
    $scheduled = Post::factory()->create([
        'user_id'      => $user->id,
        'status'       => 'scheduled',
        'published_at' => now()->addDay(),
    ]);

    $results = Post::scheduled()->get();

    $this->assertCount(1, $results);
    $this->assertEquals($scheduled->id, $results->first()->id);
}

public function test_is_scheduled_returns_true_for_scheduled_post(): void
{
    $user = $this->makeUser();
    $post = Post::factory()->create([
        'user_id'      => $user->id,
        'status'       => 'scheduled',
        'published_at' => now()->addDay(),
    ]);

    $this->assertTrue($post->isScheduled());
}

public function test_is_scheduled_returns_false_for_draft_post(): void
{
    $user = $this->makeUser();
    $post = Post::factory()->create(['user_id' => $user->id, 'status' => 'draft']);

    $this->assertFalse($post->isScheduled());
}

public function test_scope_published_excludes_scheduled_posts(): void
{
    $user = $this->makeUser();
    Post::factory()->create(['user_id' => $user->id, 'status' => 'published']);
    Post::factory()->create([
        'user_id'      => $user->id,
        'status'       => 'scheduled',
        'published_at' => now()->addDay(),
    ]);

    $this->assertCount(1, Post::published()->get());
}
```

- [ ] **Step 2: Run the new tests to confirm they fail**

```bash
php artisan test --filter="test_scope_scheduled|test_is_scheduled|test_scope_published_excludes_scheduled"
```

Expected: FAIL — `Call to undefined method App\Models\Post::scheduled()` (or similar).

- [ ] **Step 3: Add `scopeScheduled()` and `isScheduled()` to `app/Models/Post.php`**

After `scopeDraft()` (after line 75), add:

```php
public function scopeScheduled($query)
{
    return $query->where('status', 'scheduled');
}
```

After `isPublished()` (after line 111), add:

```php
public function isScheduled(): bool
{
    return $this->status === 'scheduled';
}
```

- [ ] **Step 4: Run the tests to confirm they pass**

```bash
php artisan test --filter="test_scope_scheduled|test_is_scheduled|test_scope_published_excludes_scheduled"
```

Expected: 4 tests, 4 assertions, PASS.

- [ ] **Step 5: Run the full test suite to confirm nothing broke**

```bash
php artisan test
```

Expected: all existing tests still pass.

- [ ] **Step 6: Commit**

```bash
git add app/Models/Post.php tests/Feature/PostTest.php
git commit -m "feat: add scopeScheduled and isScheduled to Post model"
```

---

### Task 3: PostController — scheduling validation and published_at logic

**Files:**
- Modify: `app/Http/Controllers/PostController.php`
- Test: `tests/Feature/PostTest.php`

> **Context:**
> - `store()` is at lines 59–100. Currently validates `status` as `in:draft,published` and sets `published_at = now()` on publish.
> - `update()` is at lines 137–183. Same validation, with a transition check for `published_at`.
> - `edit()` is at lines 102–135. Currently serializes `published_at` as `->toDateString()` — this strips the time, breaking pre-fill of the `datetime-local` input. Must change to `->format('Y-m-d\TH:i')`.
> - Need to add `use Illuminate\Validation\Rule;` import.
>
> **published_at rules:**
> - `status = scheduled` → `published_at` is required, must be a date after now (user-supplied)
> - `status = published`, transitioning from non-published → set `published_at = now()`
> - `status = published`, already published → preserve existing `published_at` (do NOT use the incoming request value)
> - `status = draft` → set `published_at = null`

- [ ] **Step 1: Write failing tests — add to `tests/Feature/PostTest.php`**

Add a new `// ── Scheduling ───` section after the existing store/update tests:

```php
// ── Scheduling ────────────────────────────────────────────────────────────

public function test_can_create_scheduled_post_with_future_date(): void
{
    $user     = $this->makeUser();
    $future   = now()->addDay()->format('Y-m-d\TH:i');

    $this->actingAs($user)->post('/posts', [
        'title'        => 'Scheduled Post',
        'status'       => 'scheduled',
        'published_at' => $future,
    ])->assertRedirect('/posts');

    $post = Post::where('title', 'Scheduled Post')->first();
    $this->assertEquals('scheduled', $post->status);
    $this->assertNotNull($post->published_at);
}

public function test_cannot_schedule_post_without_published_at(): void
{
    $user = $this->makeUser();

    $this->actingAs($user)->post('/posts', [
        'title'  => 'Bad Schedule',
        'status' => 'scheduled',
    ])->assertSessionHasErrors('published_at');
}

public function test_cannot_schedule_post_with_past_published_at(): void
{
    $user = $this->makeUser();

    $this->actingAs($user)->post('/posts', [
        'title'        => 'Past Schedule',
        'status'       => 'scheduled',
        'published_at' => now()->subHour()->format('Y-m-d\TH:i'),
    ])->assertSessionHasErrors('published_at');
}

public function test_saving_as_draft_clears_published_at(): void
{
    $user = $this->makeUser();
    $post = Post::factory()->create([
        'user_id'      => $user->id,
        'status'       => 'scheduled',
        'published_at' => now()->addDay(),
    ]);

    $this->actingAs($user)->put("/posts/{$post->id}", [
        'title'  => $post->title,
        'status' => 'draft',
    ])->assertRedirect('/posts');

    $this->assertNull($post->fresh()->published_at);
    $this->assertEquals('draft', $post->fresh()->status);
}

public function test_can_reschedule_a_published_post(): void
{
    $user = $this->makeUser();
    $post = Post::factory()->create([
        'user_id'      => $user->id,
        'status'       => 'published',
        'published_at' => now()->subDay(),
    ]);

    $future = now()->addDay()->format('Y-m-d\TH:i');

    $this->actingAs($user)->put("/posts/{$post->id}", [
        'title'        => $post->title,
        'status'       => 'scheduled',
        'published_at' => $future,
    ])->assertRedirect('/posts');

    $this->assertEquals('scheduled', $post->fresh()->status);
    $this->assertNotNull($post->fresh()->published_at);
}

public function test_republishing_preserves_original_published_at(): void
{
    $user      = $this->makeUser();
    $original  = now()->subDays(5);
    $post      = Post::factory()->create([
        'user_id'      => $user->id,
        'status'       => 'published',
        'published_at' => $original,
    ]);

    $this->actingAs($user)->put("/posts/{$post->id}", [
        'title'        => $post->title,
        'status'       => 'published',
        'published_at' => now()->format('Y-m-d\TH:i'), // incoming value must be ignored
    ])->assertRedirect('/posts');

    $this->assertEquals(
        $original->toDateTimeString(),
        $post->fresh()->published_at->toDateTimeString()
    );
}
```

- [ ] **Step 2: Run the new tests to confirm they fail**

```bash
php artisan test --filter="test_can_create_scheduled|test_cannot_schedule|test_saving_as_draft_clears|test_can_reschedule|test_republishing_preserves"
```

Expected: FAIL — validation rejects `status = scheduled` with "The selected status is invalid."

- [ ] **Step 3: Update `app/Http/Controllers/PostController.php`**

**3a. Add the `Rule` import** (after line 10, `use Illuminate\Support\Carbon;`):

```php
use Illuminate\Validation\Rule;
```

**3b. In `store()`, replace the validation block (lines 61–75):**

```php
$validated = $request->validate([
    'title'       => ['required', 'string', 'max:255'],
    'excerpt'     => ['nullable', 'string', 'max:500'],
    'body'        => ['nullable', 'string'],
    'status'      => ['required', 'in:draft,scheduled,published'],
    'published_at' => [
        Rule::when($request->input('status') === 'scheduled', ['required', 'date', 'after:now']),
        Rule::when($request->input('status') !== 'scheduled', ['nullable']),
    ],
    'category_ids'   => ['nullable', 'array'],
    'category_ids.*' => ['exists:categories,id'],
    'tag_ids'          => ['nullable', 'array'],
    'tag_ids.*'        => ['exists:tags,id'],
    'featured_image_id' => ['nullable', 'exists:media,id'],
    'comments_enabled' => ['nullable', 'boolean'],
    'meta_title'       => ['nullable', 'string', 'max:100'],
    'meta_description' => ['nullable', 'string', 'max:300'],
    'meta_keywords'    => ['nullable', 'string', 'max:255'],
]);
```

**3c. In `store()`, replace the published_at block (lines 89–91):**

```php
if ($validated['status'] === 'published') {
    $validated['published_at'] = Carbon::now();
} elseif ($validated['status'] === 'draft') {
    $validated['published_at'] = null;
}
// status === 'scheduled': published_at comes from the validated request as-is
```

**3d. In `update()`, replace the validation block (lines 143–157):**

```php
$validated = $request->validate([
    'title'       => ['required', 'string', 'max:255'],
    'excerpt'     => ['nullable', 'string', 'max:500'],
    'body'        => ['nullable', 'string'],
    'status'      => ['required', 'in:draft,scheduled,published'],
    'published_at' => [
        Rule::when($request->input('status') === 'scheduled', ['required', 'date', 'after:now']),
        Rule::when($request->input('status') !== 'scheduled', ['nullable']),
    ],
    'category_ids'   => ['nullable', 'array'],
    'category_ids.*' => ['exists:categories,id'],
    'tag_ids'           => ['nullable', 'array'],
    'tag_ids.*'         => ['exists:tags,id'],
    'featured_image_id' => ['nullable', 'exists:media,id'],
    'comments_enabled'  => ['nullable', 'boolean'],
    'meta_title'       => ['nullable', 'string', 'max:100'],
    'meta_description' => ['nullable', 'string', 'max:300'],
    'meta_keywords'    => ['nullable', 'string', 'max:255'],
]);
```

**3e. In `update()`, replace the published_at block (lines 170–174):**

```php
if ($validated['status'] === 'scheduled') {
    // published_at comes from the validated request as-is (future timestamp)
} elseif ($validated['status'] === 'published' && $post->status !== 'published') {
    $validated['published_at'] = Carbon::now();
} elseif ($validated['status'] === 'published' && $post->status === 'published') {
    unset($validated['published_at']); // preserve existing; do not overwrite
} elseif ($validated['status'] === 'draft') {
    $validated['published_at'] = null;
}
```

**3f. In `edit()`, change the `published_at` serialization (line 118):**

```php
// Old:
'published_at' => $post->published_at?->toDateString(),

// New:
'published_at' => $post->published_at?->format('Y-m-d\TH:i'),
```

**3g. In `index()`, update `published_at` serialization (line 36) to include time for scheduled posts:**

```php
// Old:
'published_at' => $post->published_at?->toDateString(),

// New:
'published_at' => $post->status === 'scheduled'
    ? $post->published_at?->format('Y-m-d H:i')
    : $post->published_at?->toDateString(),
```

- [ ] **Step 4: Run the scheduling tests**

```bash
php artisan test --filter="test_can_create_scheduled|test_cannot_schedule|test_saving_as_draft_clears|test_can_reschedule|test_republishing_preserves"
```

Expected: 6 tests PASS.

- [ ] **Step 5: Run the full test suite**

```bash
php artisan test
```

Expected: all tests pass.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/PostController.php tests/Feature/PostTest.php
git commit -m "feat: add scheduling support to PostController (validation, published_at logic, edit serialization)"
```

---

## Chunk 2: Console Command and Calendar Backend

### Task 4: PublishScheduledPostsCommand

**Files:**
- Create: `app/Console/Commands/PublishScheduledPostsCommand.php`
- Test: `tests/Feature/PostTest.php`

> **Context:** This command does a single bulk query-builder UPDATE — it does NOT fire Eloquent model events. This is intentional and documented in the spec. The test uses `$this->artisan('posts:publish-scheduled')` which auto-discovers commands in `app/Console/Commands/`.
>
> **Dependency:** `Post::scheduled()` scope must exist (added in Task 2). Do not start this task until Task 2 is committed.

- [ ] **Step 1: Write failing tests — add to `tests/Feature/PostTest.php`**

Add a `// ── Publish Scheduled Command ───` section:

```php
// ── Publish Scheduled Command ─────────────────────────────────────────────

public function test_command_publishes_overdue_scheduled_posts(): void
{
    $user = $this->makeUser();
    $post = Post::factory()->create([
        'user_id'      => $user->id,
        'status'       => 'scheduled',
        'published_at' => now()->subMinute(),
    ]);

    $this->artisan('posts:publish-scheduled');

    $this->assertEquals('published', $post->fresh()->status);
}

public function test_command_does_not_publish_future_scheduled_posts(): void
{
    $user = $this->makeUser();
    $post = Post::factory()->create([
        'user_id'      => $user->id,
        'status'       => 'scheduled',
        'published_at' => now()->addHour(),
    ]);

    $this->artisan('posts:publish-scheduled');

    $this->assertEquals('scheduled', $post->fresh()->status);
}

public function test_command_does_not_affect_draft_or_published_posts(): void
{
    $user    = $this->makeUser();
    $draft   = Post::factory()->create(['user_id' => $user->id, 'status' => 'draft']);
    $published = Post::factory()->create([
        'user_id'      => $user->id,
        'status'       => 'published',
        'published_at' => now()->subDay(),
    ]);

    $this->artisan('posts:publish-scheduled');

    $this->assertEquals('draft',     $draft->fresh()->status);
    $this->assertEquals('published', $published->fresh()->status);
}

public function test_command_preserves_original_published_at_after_publishing(): void
{
    $user   = $this->makeUser();
    $target = now()->subMinutes(5);
    $post   = Post::factory()->create([
        'user_id'      => $user->id,
        'status'       => 'scheduled',
        'published_at' => $target,
    ]);

    $this->artisan('posts:publish-scheduled');

    $this->assertEquals(
        $target->toDateTimeString(),
        $post->fresh()->published_at->toDateTimeString()
    );
}
```

- [ ] **Step 2: Run the new tests to confirm they fail**

```bash
php artisan test --filter="test_command_"
```

Expected: FAIL — "Command 'posts:publish-scheduled' is not defined."

- [ ] **Step 3: Create `app/Console/Commands/PublishScheduledPostsCommand.php`**

```php
<?php

namespace App\Console\Commands;

use App\Models\Post;
use Illuminate\Console\Command;

class PublishScheduledPostsCommand extends Command
{
    protected $signature   = 'posts:publish-scheduled';
    protected $description = 'Publish scheduled posts whose publish date has passed.';

    public function handle(): void
    {
        Post::scheduled()
            ->where('published_at', '<=', now())
            ->update(['status' => 'published']);
    }
}
```

- [ ] **Step 4: Run the command tests**

```bash
php artisan test --filter="test_command_"
```

Expected: 4 tests PASS.

- [ ] **Step 5: Run the full test suite**

```bash
php artisan test
```

Expected: all tests pass.

---

### Task 5: Register the scheduler in bootstrap/app.php

**Files:**
- Modify: `bootstrap/app.php`

> **Context:** Laravel 11 has no `Kernel.php`. Scheduling is configured via `->withSchedule()` on the `Application` builder in `bootstrap/app.php`. The command runs every minute with `withoutOverlapping()` to prevent double-execution.

- [ ] **Step 1: Update `bootstrap/app.php`**

At the top of the file, add the import after the existing `use` statements:

```php
use Illuminate\Console\Scheduling\Schedule;
```

The current file ends with `})->create();` chained directly. Change that closing `})->create();` to insert `->withSchedule(...)` before `->create()`.

**Find this exact closing sequence** (the last 4 lines of `bootstrap/app.php`):

```php
        });
    })->create();
```

**Replace with:**

```php
        });
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('posts:publish-scheduled')->everyMinute()->withoutOverlapping();
    })
    ->create();
```

The final lines of `bootstrap/app.php` should now look like:

```php
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (UnauthorizedException $e, \Illuminate\Http\Request $request) {
            return redirect()->route('dashboard')
                ->with('error', 'You do not have permission to access that page.');
        });
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('posts:publish-scheduled')->everyMinute()->withoutOverlapping();
    })
    ->create();
```

- [ ] **Step 2: Verify the schedule is registered**

```bash
php artisan schedule:list
```

Expected: Shows `posts:publish-scheduled` with "Every minute" and "Without overlapping".

- [ ] **Step 3: Commit command and scheduler together**

> **Note:** The tests added to `tests/Feature/PostTest.php` in Task 4 Step 1 have not been committed yet (Task 4 has no commit step). Stage `PostTest.php` here alongside the command file, or verify it is already staged from Task 4 — either way, do **not** add it a second time if it is already staged.

```bash
git add app/Console/Commands/PublishScheduledPostsCommand.php bootstrap/app.php tests/Feature/PostTest.php
git commit -m "feat: add PublishScheduledPostsCommand and register everyMinute scheduler"
```

---

### Task 6: CalendarController and routes

**Files:**
- Create: `app/Http/Controllers/CalendarController.php`
- Create: `tests/Feature/CalendarTest.php`
- Modify: `routes/web.php`

> **Context:**
> - `index()` returns an Inertia response with current-month data.
> - `data(Request $request)` returns JSON for AJAX month-navigation.
> - Access control: administrators see everything; regular users see all published posts plus their own drafts/scheduled.
> - `grouped` = posts with `published_at` in the requested month, keyed by date string (`Y-m-d`), value = array of post summaries.
> - `unscheduled_drafts` = draft posts with `published_at = null` (always; not scoped to a month).
> - Shape of each post summary: `{ id, title, slug, status, published_at (ISO with time), author_name }`.
> - **Cross-chunk note:** `test_authenticated_user_can_access_calendar()` calls `GET /calendar` which triggers `index()` → `Inertia::render('Calendar/Index', ...)`. The `Calendar/Index.vue` component is not created until Task 10 (Chunk 3). During Chunk 2, this specific test will fail with a "component not found" Inertia error. This is expected — run it last or run only the `data` endpoint tests until Chunk 3 is complete. The commit step (Step 7) should only be done after all CalendarTest cases pass, which requires Task 10 first.

- [ ] **Step 1: Create `tests/Feature/CalendarTest.php`**

```php
<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CalendarTest extends TestCase
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

    // ── Access ────────────────────────────────────────────────────────────────

    public function test_guest_is_redirected_from_calendar(): void
    {
        $this->get('/calendar')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_calendar(): void
    {
        $this->actingAs($this->makeUser())->get('/calendar')->assertOk();
    }

    // ── Data endpoint ─────────────────────────────────────────────────────────

    public function test_data_endpoint_returns_grouped_and_unscheduled_drafts_keys(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->getJson('/calendar/data?month=2026-03')
            ->assertOk()
            ->assertJsonStructure(['grouped', 'unscheduled_drafts']);
    }

    public function test_data_endpoint_defaults_to_current_month(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->getJson('/calendar/data')
            ->assertOk()
            ->assertJsonStructure(['grouped', 'unscheduled_drafts']);
    }

    public function test_data_endpoint_rejects_invalid_month_format(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->getJson('/calendar/data?month=not-a-month')
            ->assertStatus(422);
    }

    // ── Admin sees all posts ──────────────────────────────────────────────────

    public function test_admin_grouped_contains_all_statuses(): void
    {
        $admin     = $this->makeAdmin();
        $otherUser = $this->makeUser();

        $month = '2026-03';
        $date  = Carbon::createFromFormat('Y-m', $month)->startOfMonth()->addDays(5);

        Post::factory()->create([
            'user_id'      => $otherUser->id,
            'status'       => 'published',
            'published_at' => $date,
        ]);
        Post::factory()->create([
            'user_id'      => $otherUser->id,
            'status'       => 'scheduled',
            'published_at' => $date->copy()->addDay(),
        ]);

        $response = $this->actingAs($admin)
            ->getJson("/calendar/data?month={$month}")
            ->assertOk();

        $grouped = $response->json('grouped');
        $allPosts = collect($grouped)->flatten(1);
        $this->assertCount(2, $allPosts);
    }

    public function test_unscheduled_drafts_is_empty_when_none_exist(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->getJson('/calendar/data?month=2026-03')
            ->assertOk()
            ->assertJson(['unscheduled_drafts' => []]);
    }

    public function test_admin_unscheduled_drafts_includes_all_users_drafts(): void
    {
        $admin     = $this->makeAdmin();
        $otherUser = $this->makeUser();

        Post::factory()->create([
            'user_id'      => $otherUser->id,
            'status'       => 'draft',
            'published_at' => null,
        ]);

        $response = $this->actingAs($admin)
            ->getJson('/calendar/data?month=2026-03')
            ->assertOk();

        $this->assertCount(1, $response->json('unscheduled_drafts'));
    }

    // ── Regular user scoping ──────────────────────────────────────────────────

    public function test_regular_user_grouped_excludes_other_users_drafts_and_scheduled(): void
    {
        $user      = $this->makeUser();
        $otherUser = $this->makeUser();

        $month = '2026-03';
        $date  = Carbon::createFromFormat('Y-m', $month)->startOfMonth()->addDays(3);

        // Own published post — should appear
        Post::factory()->create([
            'user_id'      => $user->id,
            'status'       => 'published',
            'published_at' => $date,
        ]);
        // Other user's draft with published_at — should be excluded
        Post::factory()->create([
            'user_id'      => $otherUser->id,
            'status'       => 'draft',
            'published_at' => $date->copy()->addDay(),
        ]);
        // Other user's scheduled post — should be excluded
        Post::factory()->create([
            'user_id'      => $otherUser->id,
            'status'       => 'scheduled',
            'published_at' => $date->copy()->addDays(2),
        ]);

        $response = $this->actingAs($user)
            ->getJson("/calendar/data?month={$month}")
            ->assertOk();

        $grouped = $response->json('grouped');
        $allPosts = collect($grouped)->flatten(1);
        $this->assertCount(1, $allPosts);
        $this->assertEquals('published', $allPosts->first()['status']);
    }

    public function test_regular_user_unscheduled_drafts_excludes_other_users_drafts(): void
    {
        $user      = $this->makeUser();
        $otherUser = $this->makeUser();

        Post::factory()->create([
            'user_id'      => $user->id,
            'status'       => 'draft',
            'published_at' => null,
        ]);
        Post::factory()->create([
            'user_id'      => $otherUser->id,
            'status'       => 'draft',
            'published_at' => null,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/calendar/data?month=2026-03')
            ->assertOk();

        $this->assertCount(1, $response->json('unscheduled_drafts'));
    }
}
```

- [ ] **Step 2: Run the CalendarTest to confirm all tests fail**

```bash
php artisan test tests/Feature/CalendarTest.php
```

Expected: FAIL — 404 for `/calendar` and `/calendar/data` (routes don't exist yet).

- [ ] **Step 3: Create `app/Http/Controllers/CalendarController.php`**

```php
<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class CalendarController extends Controller
{
    public function index(Request $request): Response
    {
        $month = Carbon::now()->format('Y-m');
        $data  = $this->buildMonthData($request, $month);

        return Inertia::render('Calendar/Index', [
            'month'              => $month,
            'grouped'            => $data['grouped'],
            'unscheduled_drafts' => $data['unscheduled_drafts'],
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        $month = $validated['month'] ?? Carbon::now()->format('Y-m');
        $data  = $this->buildMonthData($request, $month);

        return response()->json([
            'grouped'            => $data['grouped'],
            'unscheduled_drafts' => $data['unscheduled_drafts'],
        ]);
    }

    private function buildMonthData(Request $request, string $month): array
    {
        $user    = $request->user();
        $isAdmin = $user->hasRole('administrator');
        $start   = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end     = $start->copy()->endOfMonth();

        // Posts with a published_at falling in this month
        $postsQuery = Post::with('author:id,name')
            ->whereNotNull('published_at')
            ->whereBetween('published_at', [$start, $end]);

        if (! $isAdmin) {
            $postsQuery->where(function ($q) use ($user) {
                $q->where('status', 'published')
                  ->orWhere('user_id', $user->id);
            });
        }

        $grouped = $postsQuery->get()
            ->map(fn ($post) => [
                'id'           => $post->id,
                'title'        => $post->title,
                'slug'         => $post->slug,
                'status'       => $post->status,
                'published_at' => $post->published_at->toIso8601String(),
                'author_name'  => $post->author?->name ?? 'Unknown',
            ])
            ->groupBy(fn ($post) => Carbon::parse($post['published_at'])->toDateString())
            ->map->values()
            ->toArray();

        // Unscheduled drafts (draft + no published_at)
        $draftsQuery = Post::with('author:id,name')
            ->where('status', 'draft')
            ->whereNull('published_at');

        if (! $isAdmin) {
            $draftsQuery->where('user_id', $user->id);
        }

        $unscheduledDrafts = $draftsQuery->get()
            ->map(fn ($post) => [
                'id'           => $post->id,
                'title'        => $post->title,
                'slug'         => $post->slug,
                'status'       => $post->status,
                'published_at' => null,
                'author_name'  => $post->author?->name ?? 'Unknown',
            ])
            ->values()
            ->toArray();

        return [
            'grouped'            => $grouped,
            'unscheduled_drafts' => $unscheduledDrafts,
        ];
    }
}
```

- [ ] **Step 4: Add the calendar routes to `routes/web.php`**

Inside the `auth` + `verified` middleware group (after the existing `Route::resource('tags', ...)` line), add:

```php
Route::get('/calendar',      [CalendarController::class, 'index'])->name('calendar');
Route::get('/calendar/data', [CalendarController::class, 'data'])->name('calendar.data');
```

Also add the import at the top of `routes/web.php`:

```php
use App\Http\Controllers\CalendarController;
```

- [ ] **Step 5: Run only the data endpoint CalendarTest cases**

> Run only the JSON endpoint tests now — `test_authenticated_user_can_access_calendar` requires `Calendar/Index.vue` (Task 10) to exist first.

```bash
php artisan test --filter="test_data_endpoint|test_admin_|test_regular_user_|test_guest_is_redirected|test_unscheduled_drafts_is_empty"
```

Expected: those tests PASS. `test_authenticated_user_can_access_calendar` will fail until Task 10.

- [ ] **Step 6: Defer full CalendarTest run and commit until after Task 10**

> Do not commit yet. Leave the CalendarController, routes, and CalendarTest staged. After Task 10 (`Calendar/Index.vue` is created), run the full CalendarTest suite, then run `php artisan test`, then commit all three files together:

```bash
git add app/Http/Controllers/CalendarController.php routes/web.php tests/Feature/CalendarTest.php
git commit -m "feat: add CalendarController with index and data endpoints"
```

---

## Chunk 3: Frontend

### Task 7: Posts/Index.vue — Scheduled badge and filter

**Files:**
- Modify: `resources/js/Pages/Posts/Index.vue`
- Test: `tests/Feature/PostTest.php`

> **Context:**
> - The status filter `<select>` (around line 49–57) currently only has `published` and `draft` options.
> - The status badge (around lines 98–106) only handles published/draft.
> - The `PostController::index()` now sends `status === 'scheduled'` posts with `published_at` including time (done in Task 3 step 3g).

- [ ] **Step 1: Write a failing test — add to `tests/Feature/PostTest.php`**

```php
// ── Index scheduled badge ─────────────────────────────────────────────────

public function test_index_includes_scheduled_post_with_datetime(): void
{
    $user   = $this->makeUser();
    $future = now()->addDay();
    Post::factory()->create([
        'user_id'      => $user->id,
        'status'       => 'scheduled',
        'published_at' => $future,
    ]);

    $response = $this->actingAs($user)->get('/posts')->assertOk();

    $posts = $response->original->getData()['page']['props']['posts']['data'];
    $scheduledPost = collect($posts)->firstWhere('status', 'scheduled');

    $this->assertNotNull($scheduledPost);
    $this->assertStringContainsString(
        $future->format('Y-m-d'),
        $scheduledPost['published_at']
    );
}
```

- [ ] **Step 2: Run the test**

```bash
php artisan test --filter="test_index_includes_scheduled_post"
```

> **Expected result:** This test exercises purely backend behaviour that was already implemented in Task 3 (the `PostController::index()` serialisation change and the validation change). If Tasks 1–3 are complete, this test should **PASS immediately** with no additional code needed here. If it fails, verify the Task 3 PostController changes are in place before continuing.

Expected: PASS.

- [ ] **Step 3: Update `resources/js/Pages/Posts/Index.vue` — add `scheduled` to filter**

Find the status filter `<select>` (around line 49) and add the scheduled option:

```html
<!-- Find this block: -->
<option value="">All statuses</option>
<option value="published">Published</option>
<option value="draft">Draft</option>

<!-- Replace with: -->
<option value="">All statuses</option>
<option value="published">Published</option>
<option value="scheduled">Scheduled</option>
<option value="draft">Draft</option>
```

- [ ] **Step 4: Update the status badge (around lines 97–106) to handle scheduled**

```html
<!-- Find this block: -->
<span
  class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium"
  :class="post.status === 'published'
    ? 'bg-status-success-bg text-status-success-fg'
    : 'bg-status-warning-bg text-status-warning-fg'"
>
  <span class="w-1.5 h-1.5 rounded-full" :class="post.status === 'published' ? 'bg-status-success-fg' : 'bg-status-warning-fg'"></span>
  {{ post.status === 'published' ? 'Published' : 'Draft' }}
</span>

<!-- Replace with: -->
<span
  class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium"
  :class="{
    'bg-status-success-bg text-status-success-fg': post.status === 'published',
    'bg-indigo-50 text-indigo-700':                 post.status === 'scheduled',
    'bg-status-warning-bg text-status-warning-fg': post.status === 'draft',
  }"
>
  <span
    class="w-1.5 h-1.5 rounded-full"
    :class="{
      'bg-status-success-fg': post.status === 'published',
      'bg-indigo-500':         post.status === 'scheduled',
      'bg-status-warning-fg': post.status === 'draft',
    }"
  ></span>
  <template v-if="post.status === 'published'">Published</template>
  <template v-else-if="post.status === 'scheduled'">Scheduled · {{ post.published_at }}</template>
  <template v-else>Draft</template>
</span>
```

- [ ] **Step 5: Run the full test suite**

```bash
php artisan test
```

Expected: all tests pass.

- [ ] **Step 6: Commit**

```bash
git add resources/js/Pages/Posts/Index.vue tests/Feature/PostTest.php
git commit -m "feat: add Scheduled badge and filter to posts index"
```

---

### Task 8: Posts/Create.vue and Edit.vue — Scheduled status UI

**Files:**
- Modify: `resources/js/Pages/Posts/Create.vue`
- Modify: `resources/js/Pages/Posts/Edit.vue`

> **Context:**
> - The Status panel in both files is at lines ~81–99. It has Draft and Published radio buttons.
> - We add a Scheduled radio option between them, plus a `datetime-local` input that appears only when Scheduled is selected.
> - In `Create.vue`: add `published_at: ''` to the form state. Clear it when switching away from Scheduled.
> - In `Edit.vue`: initialise `published_at` from `props.post.published_at` (now ISO datetime from the server due to Task 3 edit() change).
> - Add a computed `daysUntilPublish` helper displayed below the datetime input.
> - When switching to Draft, clear `form.published_at`.

- [ ] **Step 1: Update the `<script setup>` of `resources/js/Pages/Posts/Create.vue`**

**1a. Add `computed` to the import:**

```js
// Old:
import { ref } from 'vue'

// New:
import { ref, computed } from 'vue'
```

**1b. Add `published_at` to the form:**

```js
// In useForm({...}), after 'status: "draft",', add:
published_at: '',
```

**1c. Add the `daysUntilPublish` computed and `onStatusChange` watcher** after `const form = useForm({...})`:

```js
const daysUntilPublish = computed(() => {
  if (!form.published_at) return null
  const diff = Math.ceil((new Date(form.published_at) - Date.now()) / 86400000)
  return diff > 0 ? diff : null
})

function onStatusChange(newStatus) {
  if (newStatus === 'draft') {
    form.published_at = ''
  }
}
```

- [ ] **Step 2: Replace the Status panel in `resources/js/Pages/Posts/Create.vue`**

Find the Status panel (lines ~81–99):

```html
<!-- Status -->
<div class="rounded-lg border bg-card p-4">
  <h3 class="text-sm font-medium mb-3">Status</h3>
  <div class="space-y-2">
    <label class="flex items-center gap-3 cursor-pointer">
      <input type="radio" v-model="form.status" value="draft" class="accent-primary" />
      <div>
        <span class="text-sm font-medium">Draft</span>
        <p class="text-xs text-muted-foreground">Only visible to you</p>
      </div>
    </label>
    <label class="flex items-center gap-3 cursor-pointer">
      <input type="radio" v-model="form.status" value="published" class="accent-primary" />
      <div>
        <span class="text-sm font-medium">Published</span>
        <p class="text-xs text-muted-foreground">Visible to everyone</p>
      </div>
    </label>
  </div>
</div>
```

Replace with:

```html
<!-- Status -->
<div class="rounded-lg border bg-card p-4">
  <h3 class="text-sm font-medium mb-3">Status</h3>
  <div class="space-y-2">
    <label class="flex items-center gap-3 cursor-pointer">
      <input type="radio" v-model="form.status" value="draft" class="accent-primary" @change="onStatusChange('draft')" />
      <div>
        <span class="text-sm font-medium">Draft</span>
        <p class="text-xs text-muted-foreground">Only visible to you</p>
      </div>
    </label>
    <label class="flex items-center gap-3 cursor-pointer">
      <input type="radio" v-model="form.status" value="scheduled" class="accent-primary" />
      <div>
        <span class="text-sm font-medium">Scheduled</span>
        <p class="text-xs text-muted-foreground">Auto-publishes at a set time</p>
      </div>
    </label>
    <label class="flex items-center gap-3 cursor-pointer">
      <input type="radio" v-model="form.status" value="published" class="accent-primary" @change="onStatusChange('published')" />
      <div>
        <span class="text-sm font-medium">Published</span>
        <p class="text-xs text-muted-foreground">Visible to everyone</p>
      </div>
    </label>
  </div>

  <!-- Datetime picker — only when Scheduled -->
  <div v-show="form.status === 'scheduled'" class="mt-3 pt-3 border-t border-border">
    <label class="text-xs font-medium text-muted-foreground mb-1 block">Publish on</label>
    <input
      type="datetime-local"
      v-model="form.published_at"
      class="w-full rounded-md border bg-background px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
    />
    <p v-if="daysUntilPublish" class="text-xs text-indigo-600 mt-1">
      ⏱ publishes in {{ daysUntilPublish }} day{{ daysUntilPublish === 1 ? '' : 's' }}
    </p>
    <p v-if="form.errors.published_at" class="text-xs text-destructive mt-1">
      {{ form.errors.published_at }}
    </p>
  </div>
</div>
```

- [ ] **Step 3: Apply the changes to `resources/js/Pages/Posts/Edit.vue`**

**3a. Update the import** (same change as Create.vue):

```js
// Old:
import { ref } from 'vue'

// New:
import { ref, computed } from 'vue'
```

**3b. Add `published_at` to the form** — initialised from the server prop:

```js
// In useForm({...}), after 'status: props.post.status,', add:
published_at: props.post.published_at ?? '',
```

**3c. Add `daysUntilPublish` and `onStatusChange`** after `const form = useForm({...})`:

```js
const daysUntilPublish = computed(() => {
  if (!form.published_at) return null
  const diff = Math.ceil((new Date(form.published_at) - Date.now()) / 86400000)
  return diff > 0 ? diff : null
})

function onStatusChange(newStatus) {
  if (newStatus === 'draft') {
    form.published_at = ''
  }
}
```

**3d. Replace the Status panel** — find the same block in Edit.vue (lines ~81–99) and replace with:

```html
<!-- Status -->
<div class="rounded-lg border bg-card p-4">
  <h3 class="text-sm font-medium mb-3">Status</h3>
  <div class="space-y-2">
    <label class="flex items-center gap-3 cursor-pointer">
      <input type="radio" v-model="form.status" value="draft" class="accent-primary" @change="onStatusChange('draft')" />
      <div>
        <span class="text-sm font-medium">Draft</span>
        <p class="text-xs text-muted-foreground">Only visible to you</p>
      </div>
    </label>
    <label class="flex items-center gap-3 cursor-pointer">
      <input type="radio" v-model="form.status" value="scheduled" class="accent-primary" />
      <div>
        <span class="text-sm font-medium">Scheduled</span>
        <p class="text-xs text-muted-foreground">Auto-publishes at a set time</p>
      </div>
    </label>
    <label class="flex items-center gap-3 cursor-pointer">
      <input type="radio" v-model="form.status" value="published" class="accent-primary" />
      <div>
        <span class="text-sm font-medium">Published</span>
        <p class="text-xs text-muted-foreground">Visible to everyone</p>
      </div>
    </label>
  </div>

  <!-- Datetime picker — only when Scheduled -->
  <div v-show="form.status === 'scheduled'" class="mt-3 pt-3 border-t border-border">
    <label class="text-xs font-medium text-muted-foreground mb-1 block">Publish on</label>
    <input
      type="datetime-local"
      v-model="form.published_at"
      class="w-full rounded-md border bg-background px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
    />
    <p v-if="daysUntilPublish" class="text-xs text-indigo-600 mt-1">
      ⏱ publishes in {{ daysUntilPublish }} day{{ daysUntilPublish === 1 ? '' : 's' }}
    </p>
    <p v-if="form.errors.published_at" class="text-xs text-destructive mt-1">
      {{ form.errors.published_at }}
    </p>
  </div>
</div>
```

**3e. Update the Details section** — Edit.vue has a "Details" panel at the bottom (around lines 253–264) that currently displays `post.published_at` as a raw string. After our Task 3 backend change, this value is now a `Y-m-d\TH:i` ISO string. Update the display to be readable:

Find:
```html
<div v-if="post.published_at" class="flex justify-between text-muted-foreground">
  <span>Published</span>
  <span>{{ post.published_at }}</span>
</div>
```

Replace with:
```html
<div v-if="post.published_at" class="flex justify-between text-muted-foreground">
  <span>{{ post.status === 'scheduled' ? 'Scheduled' : 'Published' }}</span>
  <span>{{ post.published_at?.replace('T', ' ') }}</span>
</div>
```

- [ ] **Step 4: Build to check for errors**

```bash
npm run build
```

Expected: clean build with no errors.

- [ ] **Step 5: Commit**

```bash
git add resources/js/Pages/Posts/Create.vue resources/js/Pages/Posts/Edit.vue
git commit -m "feat: add Scheduled radio and datetime picker to post editor"
```

---

### Task 9: AppLayout.vue — Calendar navigation link

**Files:**
- Modify: `resources/js/Layouts/AppLayout.vue`

> **Context:** The sidebar navigation is in the template between the `<!-- Navigation -->` comment and the `<!-- User / logout at bottom -->` section. Links are `<SidebarLink>` components with `href`, `active`, and a named `#icon` slot. The Calendar link goes in the "Content" section between Posts and Categories (line ~38). Uses the `Calendar` icon from `lucide-vue-next`.

- [ ] **Step 1: Add Calendar icon import to `resources/js/Layouts/AppLayout.vue`**

Find the lucide import line:

```js
import { Sun, Moon } from "lucide-vue-next";
```

Replace with:

```js
import { Sun, Moon, Calendar } from "lucide-vue-next";
```

- [ ] **Step 2: Add the Calendar SidebarLink in the template**

Find the Posts SidebarLink closing tag (around line 37) followed by the Categories SidebarLink. Insert the Calendar link between them:

```html
<!-- After the </SidebarLink> closing tag of Posts, before Categories: -->
<SidebarLink :href="route('calendar')" :active="currentRoute === 'calendar'">
  <template #icon>
    <Calendar class="w-4 h-4" />
  </template>
  Calendar
</SidebarLink>
```

> **Note:** `:active="currentRoute === 'calendar'"` uses strict equality (not `startsWith`) because the route is named exactly `'calendar'` with no sub-routes. This is intentionally different from the `startsWith('posts.')` pattern used for resource controllers.

- [ ] **Step 3: Build to check for errors**

```bash
npm run build
```

Expected: clean build.

- [ ] **Step 4: Commit**

```bash
git add resources/js/Layouts/AppLayout.vue
git commit -m "feat: add Calendar link to sidebar navigation"
```

---

### Task 10: Calendar/Index.vue — New calendar page

**Files:**
- Create: `resources/js/Pages/Calendar/Index.vue`

> **Context:** Split layout with left mini-calendar and right detail panel. Uses Inertia props (`month`, `grouped`, `unscheduled_drafts`). Month navigation uses `fetch()` to call `/calendar/data?month=YYYY-MM` without full page reload. Colour coding: blue = scheduled, green = published, amber = draft.

- [ ] **Step 1: Create `resources/js/Pages/Calendar/Index.vue`**

```vue
<template>
  <AppLayout title="Calendar">
    <Head title="Calendar" />

    <div class="grid grid-cols-1 lg:grid-cols-[300px_1fr] gap-6">

      <!-- Left: Mini calendar -->
      <div class="rounded-lg border bg-card p-4">
        <!-- Month navigation -->
        <div class="flex items-center justify-between mb-4">
          <button
            @click="prevMonth"
            class="p-1 rounded hover:bg-accent transition-colors text-muted-foreground hover:text-foreground"
            aria-label="Previous month"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
          </button>
          <span class="text-sm font-semibold">{{ monthLabel }}</span>
          <button
            @click="nextMonth"
            class="p-1 rounded hover:bg-accent transition-colors text-muted-foreground hover:text-foreground"
            aria-label="Next month"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
          </button>
        </div>

        <!-- Day-of-week headers (Mon–Sun). Use index as key to avoid duplicate-key warnings. -->
        <div class="grid grid-cols-7 mb-1">
          <div
            v-for="(dow, i) in ['M','T','W','T','F','S','S']"
            :key="i"
            class="text-center text-[10px] font-medium text-muted-foreground py-1"
          >{{ dow }}</div>
        </div>

        <!-- Calendar grid -->
        <div class="grid grid-cols-7 gap-0.5">
          <!-- Empty cells before month start -->
          <div v-for="n in paddingDays" :key="'pad-' + n" />

          <!-- Day cells -->
          <button
            v-for="cell in dayCells"
            :key="cell.dateStr"
            @click="selectDay(cell.dateStr)"
            class="relative aspect-square flex flex-col items-center justify-center rounded text-xs transition-colors"
            :class="{
              'bg-primary text-primary-foreground font-semibold': selectedDay === cell.dateStr,
              'hover:bg-accent': selectedDay !== cell.dateStr,
            }"
          >
            {{ cell.day }}
            <!-- Colour dot -->
            <span
              v-if="cell.dotColor"
              class="absolute bottom-0.5 w-1 h-1 rounded-full"
              :class="cell.dotColor"
            />
          </button>
        </div>

        <!-- Legend -->
        <div class="flex gap-3 mt-4 justify-center">
          <span class="flex items-center gap-1 text-[10px] text-muted-foreground">
            <span class="w-2 h-2 rounded-full bg-green-500 inline-block"></span>Published
          </span>
          <span class="flex items-center gap-1 text-[10px] text-muted-foreground">
            <span class="w-2 h-2 rounded-full bg-blue-500 inline-block"></span>Scheduled
          </span>
          <span class="flex items-center gap-1 text-[10px] text-muted-foreground">
            <span class="w-2 h-2 rounded-full bg-amber-500 inline-block"></span>Draft
          </span>
        </div>
      </div>

      <!-- Right: Detail panel -->
      <div class="space-y-6">

        <!-- Selected day posts -->
        <div class="rounded-lg border bg-card p-4">
          <h2 class="text-sm font-semibold mb-3">
            {{ selectedDay ? 'Posts for ' + formattedSelectedDay : 'Select a day' }}
          </h2>

          <div v-if="!selectedDay" class="text-sm text-muted-foreground">
            Click a day on the calendar to see its posts.
          </div>

          <div v-else-if="selectedDayPosts.length === 0" class="text-sm text-muted-foreground">
            No posts on this day.
          </div>

          <ul v-else class="space-y-2">
            <li
              v-for="post in selectedDayPosts"
              :key="post.id"
            >
              <a
                :href="route('posts.edit', post.id)"
                class="flex items-center gap-3 rounded-md p-2 hover:bg-accent transition-colors group"
              >
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium truncate group-hover:text-foreground">{{ post.title }}</p>
                  <p class="text-xs text-muted-foreground">
                    {{ formatTime(post.published_at) }} · {{ post.author_name }}
                  </p>
                </div>
                <span
                  class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-medium"
                  :class="{
                    'bg-status-success-bg text-status-success-fg': post.status === 'published',
                    'bg-indigo-50 text-indigo-700':                 post.status === 'scheduled',
                    'bg-status-warning-bg text-status-warning-fg': post.status === 'draft',
                  }"
                >{{ post.status }}</span>
              </a>
            </li>
          </ul>
        </div>

        <!-- Unscheduled drafts -->
        <div class="rounded-lg border bg-card p-4">
          <h2 class="text-sm font-semibold mb-3">Unscheduled drafts</h2>

          <div v-if="unscheduledDrafts.length === 0" class="text-sm text-muted-foreground">
            No unscheduled drafts.
          </div>

          <ul v-else class="space-y-2">
            <li
              v-for="post in unscheduledDrafts"
              :key="post.id"
            >
              <a
                :href="route('posts.edit', post.id)"
                class="flex items-center gap-3 rounded-md p-2 hover:bg-accent transition-colors group"
              >
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium truncate group-hover:text-foreground">{{ post.title }}</p>
                  <p class="text-xs text-muted-foreground">{{ post.author_name }}</p>
                </div>
                <span class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-medium bg-status-warning-bg text-status-warning-fg">
                  draft
                </span>
              </a>
            </li>
          </ul>
        </div>

      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  month:              { type: String, required: true },
  grouped:            { type: Object, default: () => ({}) },
  unscheduled_drafts: { type: Array,  default: () => [] },
})

// ── Reactive state ────────────────────────────────────────────────────────

const currentMonth      = ref(props.month)
const grouped           = ref(props.grouped)
const unscheduledDrafts = ref(props.unscheduled_drafts)
const selectedDay       = ref(null)

// ── Month navigation ──────────────────────────────────────────────────────

async function navigateToMonth(monthStr) {
  try {
    const url = route('calendar.data') + '?month=' + monthStr
    const res  = await fetch(url, {
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    })
    if (!res.ok) throw new Error(`HTTP ${res.status}`)
    const data = await res.json()

    currentMonth.value      = monthStr
    grouped.value           = data.grouped
    unscheduledDrafts.value = data.unscheduled_drafts
    selectedDay.value       = null
  } catch (err) {
    console.error('Failed to load calendar data:', err)
  }
}

function prevMonth() {
  const [y, m] = currentMonth.value.split('-').map(Number)
  const d = new Date(y, m - 2, 1)
  navigateToMonth(`${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`)
}

function nextMonth() {
  const [y, m] = currentMonth.value.split('-').map(Number)
  const d = new Date(y, m, 1)
  navigateToMonth(`${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`)
}

// ── Calendar grid ─────────────────────────────────────────────────────────

const paddingDays = computed(() => {
  const [y, m] = currentMonth.value.split('-').map(Number)
  const firstDow = new Date(y, m - 1, 1).getDay() // 0 = Sunday
  return (firstDow + 6) % 7 // convert to Monday-first (Mon=0)
})

const dayCells = computed(() => {
  const [y, m] = currentMonth.value.split('-').map(Number)
  const daysInMonth = new Date(y, m, 0).getDate()
  const cells = []

  for (let d = 1; d <= daysInMonth; d++) {
    const dateStr  = `${currentMonth.value}-${String(d).padStart(2, '0')}`
    const posts    = grouped.value[dateStr] || []
    cells.push({ day: d, dateStr, dotColor: dotColorForPosts(posts) })
  }
  return cells
})

function dotColorForPosts(posts) {
  if (!posts || posts.length === 0) return null
  if (posts.some(p => p.status === 'scheduled'))  return 'bg-blue-500'
  if (posts.some(p => p.status === 'published'))  return 'bg-green-500'
  return 'bg-amber-500'
}

// ── Day selection ─────────────────────────────────────────────────────────

function selectDay(dateStr) {
  selectedDay.value = dateStr
}

const selectedDayPosts = computed(() => {
  if (!selectedDay.value) return []
  return grouped.value[selectedDay.value] || []
})

const formattedSelectedDay = computed(() => {
  if (!selectedDay.value) return ''
  return new Date(selectedDay.value + 'T00:00:00').toLocaleDateString('en-US', {
    weekday: 'long', month: 'long', day: 'numeric',
  })
})

// ── Helpers ───────────────────────────────────────────────────────────────

const monthLabel = computed(() => {
  const [y, m] = currentMonth.value.split('-').map(Number)
  return new Date(y, m - 1, 1).toLocaleString('default', { month: 'long', year: 'numeric' })
})

function formatTime(isoString) {
  if (!isoString) return ''
  return new Date(isoString).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })
}
</script>
```

- [ ] **Step 2: Build to check for errors**

```bash
npm run build
```

Expected: clean build, no errors.

- [ ] **Step 3: Run the full CalendarTest suite** (now that `Calendar/Index.vue` exists)

```bash
php artisan test tests/Feature/CalendarTest.php
```

Expected: all CalendarTest cases PASS (including `test_authenticated_user_can_access_calendar`).

- [ ] **Step 4: Run the full test suite**

```bash
php artisan test
```

Expected: all tests pass.

- [ ] **Step 5: Commit Calendar/Index.vue and the deferred CalendarController files**

```bash
git add resources/js/Pages/Calendar/Index.vue app/Http/Controllers/CalendarController.php routes/web.php tests/Feature/CalendarTest.php
git commit -m "feat: add Calendar/Index.vue split-view calendar page and CalendarController"
```

---

## Summary

| Task | File(s) | Type |
|------|---------|------|
| 1 | `database/migrations/…_add_scheduled_to_posts_status.php` | New |
| 2 | `app/Models/Post.php`, `tests/Feature/PostTest.php` | Modify |
| 3 | `app/Http/Controllers/PostController.php`, `tests/Feature/PostTest.php` | Modify |
| 4 | `app/Console/Commands/PublishScheduledPostsCommand.php`, `tests/Feature/PostTest.php` | New + Modify |
| 5 | `bootstrap/app.php` | Modify |
| 6 | `app/Http/Controllers/CalendarController.php`, `routes/web.php`, `tests/Feature/CalendarTest.php` | New + Modify |
| 7 | `resources/js/Pages/Posts/Index.vue`, `tests/Feature/PostTest.php` | Modify |
| 8 | `resources/js/Pages/Posts/Create.vue`, `resources/js/Pages/Posts/Edit.vue` | Modify |
| 9 | `resources/js/Layouts/AppLayout.vue` | Modify |
| 10 | `resources/js/Pages/Calendar/Index.vue` | New |
