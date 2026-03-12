# Content Calendar & Post Scheduling — Design

**Date:** 2026-03-11
**Status:** Approved

---

## Goal

Add a `scheduled` post status with a date/time picker in the editor so authors can queue posts for automatic publication, and a dedicated `/calendar` admin page that shows all posts on a split-view monthly calendar.

---

## Decisions

| Question | Decision |
|---|---|
| Calendar access | Dedicated `/calendar` admin page |
| Scheduling UI in editor | Third status option "Scheduled" with datetime picker |
| Auto-publish mechanism | Laravel Task Scheduler (`everyMinute`) |
| Calendar layout | Split view: mini calendar (left) + day detail panel (right) |

---

## Data Model

### `posts.status` enum

Add `scheduled` as a third value alongside the existing `draft` and `published`.

**Migration:** `alter_posts_status_add_scheduled` — alters the enum column. No data migration required; existing rows are unaffected.

**State machine:**
```
draft      ──► scheduled  (author sets future date/time)
draft      ──► published  (author publishes immediately)
scheduled  ──► published  (scheduler fires when published_at ≤ now)
scheduled  ──► draft      (author cancels the schedule)
published  ──► draft      (author unpublishes)
```

**`published_at` semantics** — already exists; becomes the target publish timestamp when `status = scheduled`. Set to `now()` when immediately publishing. Cleared (set to `null`) when reverting to `draft`.

### `Post` model additions

- `scopeScheduled()` — `where('status', 'scheduled')`
- `isScheduled()` — boolean helper
- `scopePublished()` — unchanged (`where('status', 'published')`); public blog is unaffected

---

## Backend Components

### Migration

`database/migrations/YYYY_MM_DD_add_scheduled_to_posts_status.php`

Alters the `posts.status` enum to include `scheduled`.

### `PostController` validation changes

`app/Http/Controllers/PostController.php` — `store()` and `update()`:

- `status = scheduled`: `published_at` required, must be `after:now`
- `status = published` + no `published_at`: set to `now()` (preserves existing behaviour)
- `status = draft`: set `published_at = null`

### `PublishScheduledPostsCommand`

`app/Console/Commands/PublishScheduledPostsCommand.php`

```php
php artisan posts:publish-scheduled
```

Bulk-updates all overdue scheduled posts in a single query:

```php
Post::scheduled()
    ->where('published_at', '<=', now())
    ->update(['status' => 'published']);
```

### `Kernel.php`

`app/Console/Kernel.php` — registers the command:

```php
$schedule->command('posts:publish-scheduled')->everyMinute();
```

Cron entry (documented in README and surfaced on the dashboard for admins):
```
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

### `CalendarController`

`app/Http/Controllers/CalendarController.php`

Two actions, both behind `auth` + `verified` middleware:

**`index()`** — Inertia response. Passes the current month's posts as an array: `[{ id, title, slug, status, published_at, author_name }]`. Admins see all posts; regular users see only their own drafts/scheduled + all published posts.

**`data(Request $request)`** — JSON response. Accepts `?month=YYYY-MM`. Returns posts for that month grouped by `Y-m-d` key:
```json
{
  "2026-03-07": [{ "id": 1, "title": "...", "status": "scheduled", "published_at": "..." }],
  "2026-03-11": [{ "id": 2, "title": "...", "status": "published", "published_at": "..." }]
}
```
Also returns `unscheduled_drafts`: posts with `status = draft` and `published_at = null`, for display in the sidebar panel regardless of selected day.

---

## Frontend Components

### `Pages/Calendar/Index.vue` (new)

Split layout within `AppLayout`:

- **Left column** — compact monthly mini-calendar (7-column CSS grid). Days with posts show colour-coded dots: green (published), blue (scheduled), amber (draft). Month navigation buttons call `GET /calendar/data?month=YYYY-MM` via `fetch()` and swap data without full page reload. Selected day is highlighted.

- **Right column** — two sections:
  1. **Selected day** — heading "Posts for [day]", list of post rows with title, time, status badge, author. Clicking a row navigates to `route('posts.edit', post.id)`.
  2. **Unscheduled drafts** — always visible below, lists drafts with no `published_at`. Same click-to-edit behaviour.

- Empty states: "No posts on this day" / "No unscheduled drafts".

### `Pages/Posts/Create.vue` + `Edit.vue` (modify)

Replace the current Draft / Published radio group with a three-option radio group: **Draft**, **Scheduled**, **Published**.

When **Scheduled** is selected:
- A `<input type="datetime-local">` slides into view below (via `v-show`)
- Displays a helper line: "publishes in X days" (computed from `published_at - now`)
- Validation error shown inline if date is in the past

When **Draft** is selected: `published_at` field is hidden and cleared.
When **Published** is selected: `published_at` field is hidden; backend sets it to `now()`.

### `Pages/Posts/Index.vue` (modify)

- Add `scheduled` to the status filter dropdown
- Show a "Scheduled" badge (indigo colour, consistent with `#eef2ff` background / `#4338ca` text) in the status column with the scheduled date/time

### `AppLayout.vue` (modify)

Add a `<SidebarLink>` for Calendar between Posts and Categories in the left navigation:
```
href: route('calendar')
icon: Calendar (lucide-vue-next)
label: Calendar
```

---

## Routes

```php
// In routes/web.php, inside the auth+verified middleware group:
Route::get('/calendar',       [CalendarController::class, 'index'])->name('calendar');
Route::get('/calendar/data',  [CalendarController::class, 'data'])->name('calendar.data');
```

---

## Tests

### `PostTest.php` additions

**Scheduling:**
- Can create a post with `status = scheduled` and a future `published_at`
- Cannot schedule without a `published_at` (validation fails)
- Cannot schedule with a past `published_at` (validation fails)
- Scheduled post does not appear on the public blog
- Saving with `status = draft` clears `published_at`

**`posts:publish-scheduled` command:**
- Publishes a scheduled post whose `published_at` has passed
- Does not publish a scheduled post whose `published_at` is in the future
- Does not affect draft or already-published posts

**Posts list:**
- Index shows Scheduled status badge

### `CalendarTest.php` (new)

- Authenticated user can access `GET /calendar`
- Guest is redirected from `GET /calendar`
- `GET /calendar/data?month=2026-03` returns JSON with posts grouped by date
- Returns posts of all statuses (draft, scheduled, published) for admin
- Unscheduled drafts are returned separately
- Regular user sees only their own drafts/scheduled, not others' drafts

---

## Out of Scope

- Drag-to-reschedule on the calendar (nice-to-have, future)
- Timezone-aware scheduling per user (all times use the app timezone from settings)
- Recurring posts
