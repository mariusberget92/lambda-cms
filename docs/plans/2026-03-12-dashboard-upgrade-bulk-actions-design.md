# Dashboard Upgrade & Bulk Post Actions — Design Spec

**Date:** 2026-03-12
**Status:** Approved
**Stack:** Laravel 12, Inertia 2, Vue 3, Tailwind CSS 4

---

## Overview

Two related improvements to the CMS admin interface:

1. **Dashboard upgrade** — add a Scheduled stat card, an "Upcoming scheduled posts" panel, and a "Recent posts" panel.
2. **Bulk actions on posts** — add checkboxes to the posts index with a sticky toolbar for publishing, drafting, and deleting multiple posts at once.

---

## Feature 1: Dashboard Upgrade

### Backend — `DashboardController`

Add three new data points to `DashboardController::index()`:

- `stats.scheduled` — `Post::scheduled()->count()`
- `upcoming_scheduled` — up to 5 scheduled posts where `published_at > now()`, ordered `published_at ASC`, eager-loading `author:id,name`. Each item shape:
  ```
  { id, title, published_at (ISO 8601 string), author_name }
  ```
- `recent_posts` — last 5 posts by `updated_at DESC` (any status), eager-loading `author:id,name`. Each item shape:
  ```
  { id, title, status, published_at (ISO 8601 string|null), updated_at (ISO 8601 string), author_name }
  ```

### Frontend — `Dashboard/Index.vue`

**Stats grid:**

Expand from 4 to 5 cards. Add a **Scheduled** card between Published and Drafts:
- Icon background: `bg-indigo-50`; icon colour: `text-indigo-700`
- Uses a calendar-clock or clock icon
- Grid class: `sm:grid-cols-2 lg:grid-cols-5`

Card order: Total Posts → Published → Scheduled → Drafts → Pending Comments.

**Two new panels below the stats grid (side by side on lg screens, stacked on mobile):**

Layout: `grid grid-cols-1 lg:grid-cols-2 gap-4`.

**"Upcoming scheduled posts" panel:**
- Lists up to 5 rows. Each row: post title (links to `/posts/{id}/edit`), scheduled datetime formatted as `D MMM YYYY, HH:mm`, author name.
- Empty state: "No posts scheduled."

**"Recent posts" panel:**
- Lists up to 5 rows. Each row: post title (links to `/posts/{id}/edit`), status badge (green = published, indigo = scheduled, amber = draft — reusing the same colour tokens as the posts index), relative time (e.g. "2h ago" computed from `updated_at`).
- Empty state: "No posts yet."

**Quick actions** remain as a third panel below, or inline within the Recent Posts panel header — implementer's discretion based on visual balance.

### Testing — `tests/Feature/DashboardTest.php`

New test file:

- `test_dashboard_requires_authentication`
- `test_dashboard_includes_scheduled_count`
- `test_dashboard_upcoming_scheduled_posts_ordered_by_publish_date`
- `test_dashboard_upcoming_scheduled_posts_limited_to_five`
- `test_dashboard_upcoming_scheduled_excludes_past_scheduled`
- `test_dashboard_recent_posts_ordered_by_updated_at`
- `test_dashboard_recent_posts_limited_to_five`
- `test_dashboard_empty_upcoming_and_recent_when_no_posts`

---

## Feature 2: Bulk Post Actions

### Backend — `PostController::bulk()`

**Route:** `POST /posts/bulk` → `PostController@bulk`, named `posts.bulk`, inside the existing `auth + verified` middleware group, added alongside the other post routes.

**Validation:**
```
action  — required, in: ['publish', 'draft', 'delete']
ids     — required array, min:1
ids.*   — integer, exists:posts,id
```

**Authorization:** Resolve the posts from `ids`, then for each post verify the authenticated user is the owner (`user_id === auth()->id()`) OR has the `administrator` role. If any post fails authorization, return a 403. Only proceed with the subset of posts the user is authorized to act on is **not** acceptable — fail fast and return 403 so the user knows something is wrong.

**Action logic:**
- `publish` → `status = 'published'`; set `published_at = Carbon::now()` only if `published_at` is currently null (preserves existing publish timestamp).
- `draft` → `status = 'draft'`, `published_at = null`.
- `delete` → `Post::destroy($ids)`.

**Response:** Inertia redirect back with flash `status` message:
- "3 posts published."
- "2 posts drafted."
- "4 posts deleted."

### Frontend — `Posts/Index.vue`

**Checkbox column:**
- New leftmost column in the table with a `<th>` containing a "select all on current page" checkbox and `<td>` containing a per-row checkbox.
- `selectedIds` — reactive `ref([])` of post IDs (integers).
- Checking/unchecking a row adds/removes its ID. Select-all header checkbox sets `selectedIds` to all IDs on the current page (or clears if all selected).
- When navigating pages (filters, pagination), `selectedIds` resets to `[]`.

**Sticky bulk toolbar:**
- Conditionally rendered when `selectedIds.length > 0`.
- Fixed to the bottom of the viewport, full width, with a backdrop and shadow to lift it above content.
- Contents: "X selected" label, then three action buttons:
  - **Publish** — primary style
  - **Draft** — secondary/border style
  - **Delete** — destructive style
  - **×** button (far right) to clear selection without taking action
- Transition: slides up from off-screen when appearing, slides back down when dismissed.

**Bulk publish / draft flow:**
- Clicking Publish or Draft immediately submits `router.post(route('posts.bulk'), { action, ids: selectedIds.value })`.
- On success (Inertia redirect): `selectedIds.value = []`, page refreshes with flash message.

**Bulk delete flow:**
- Clicking Delete opens a confirmation modal (same style as the existing single-post delete modal).
- Modal text: "Delete {N} posts? This cannot be undone."
- Cancel closes modal, no action taken.
- Confirm submits `router.post(route('posts.bulk'), { action: 'delete', ids: selectedIds.value })`.
- On success: `selectedIds.value = []`, modal closes, page refreshes with flash message.

### Testing — `tests/Feature/PostTest.php`

Add to the existing `PostTest.php`:

- `test_user_can_bulk_publish_own_posts`
- `test_user_can_bulk_draft_own_posts`
- `test_user_can_bulk_delete_own_posts`
- `test_admin_can_bulk_action_any_posts`
- `test_user_cannot_bulk_action_other_users_posts` — expects 403
- `test_bulk_action_requires_valid_action` — expects 422
- `test_bulk_action_requires_at_least_one_id` — expects 422
- `test_bulk_publish_sets_published_at_when_null`
- `test_bulk_publish_preserves_existing_published_at`
- `test_bulk_draft_clears_published_at`
- `test_bulk_action_returns_flash_message`

---

## What Is Explicitly Out of Scope

- Bulk category/tag assignment
- Bulk scheduling (setting a future date for multiple posts)
- Cross-page selection (selecting posts across pagination pages)
- Activity log / audit trail
- Dashboard charts or analytics

---

## File Change Summary

| File | Change |
|------|--------|
| `app/Http/Controllers/DashboardController.php` | Add `scheduled`, `upcoming_scheduled`, `recent_posts` props |
| `resources/js/Pages/Dashboard/Index.vue` | 5th stat card, two new panels |
| `tests/Feature/DashboardTest.php` | New test file |
| `app/Http/Controllers/PostController.php` | Add `bulk()` method |
| `routes/web.php` | Add `POST /posts/bulk` route |
| `resources/js/Pages/Posts/Index.vue` | Checkboxes, sticky toolbar, bulk delete modal |
| `tests/Feature/PostTest.php` | 11 new bulk action tests |
