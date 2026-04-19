# Comment Pagination & Settings Design

**Date:** 2026-03-05
**Status:** Approved

## Summary

Add paginated "Load more" comment loading to the public blog post page, backed by a dedicated JSON endpoint. Add a Comments settings panel in the admin that controls: comments enabled/disabled site-wide, and the number of comments loaded per batch.

## Decisions Made

- **Pagination strategy:** Offset/page-based (`?page=N&per_page=X`) — simpler than cursor-based, fits Laravel's built-in `paginate()`, acceptable edge-case risk for a blog CMS.
- **Load trigger:** Explicit "Load more" button (not auto-scroll) — simpler, more accessible, better UX fit for a blog.
- **Disabled state:** Show existing approved comments + a "Comments are closed." notice where the form was. Do NOT hide all comments.
- **Data transport:** Dedicated JSON endpoint (not Inertia partial reload) for subsequent page fetches.

## Architecture

A new public JSON route `GET /blog/{post:slug}/comments?page=N` handled by `BlogController::comments()`. The existing `BlogController::show()` serves the first page of comments as an Inertia prop alongside metadata. The `SettingsController` gains a `comments` group following the existing `site/locale/media/mail` pattern.

## Data Flow

### BlogController::show() changes
Reads two settings at render time:
- `Setting::get('comments.per_page', 10)` — page size
- `Setting::get('comments.enabled', true)` — global toggle

Adds to Inertia response:
- `comments` — first page array (replaces current all-comments array)
- `commentsTotal` — total approved comment count for the post
- `commentsHasMore` — bool, true if more pages exist
- `commentsPerPage` — int, passed through so Vue knows page size for subsequent fetches
- `commentsEnabled` — bool

### New BlogController::comments() endpoint
- Route: `GET /blog/{post:slug}/comments` (public, no auth)
- Returns JSON: `{ data: [...], has_more: bool, total: int }`
- Reads `comments.per_page` from settings
- Only returns approved comments, ordered oldest-first
- Accepts `?page=N` query param

### SettingsController::update('comments')
Validates and saves:
- `comments.enabled` — boolean, stored as `'1'`/`'0'`
- `comments.per_page` — integer, min 5, max 100

### CommentController::store() guard
Checks `Setting::get('comments.enabled', true)` before processing. Returns 403 with JSON error if disabled. Guards against direct POST even when the form is hidden client-side.

## Frontend Behaviour

### Blog/Show.vue
- Local `ref([...initialComments])` array seeded from Inertia prop on mount
- Comment count heading uses `commentsTotal` (true total, not local array length)
- "Load more" button shown when `hasMore` ref is true
- Click → `fetch('/blog/{slug}/comments?page=N')`, append `data` to local array, update `hasMore`
- Loading spinner replaces button during fetch
- On fetch failure: inline dismissible error below list — *"Failed to load more comments."* with a retry button
- When `commentsEnabled` is false: comments list still renders, form replaced with *"Comments are closed."* notice

### Settings/Index.vue
New "Comments" panel added after Mail panel:
- Toggle (checkbox) for `comments.enabled` — label: "Enable comments"
- Number input for `comments.per_page` — label: "Comments per page", min 5, max 100
- Follows existing `useForm` + `PUT /settings/comments` pattern
- Flash success message on save (reuses existing flash infrastructure)

## Error Handling

| Scenario | Behaviour |
|---|---|
| `fetch()` network/server error | Inline error + retry button below comment list |
| POST to disabled comments | 403 JSON from server, Inertia flashes error |
| `per_page` out of range (< 5 or > 100) | Server validation rejects, form shows error |
| Setting changed mid-session | Next "Load more" uses new page size — minor overlap/skip acceptable |

## Routes

```
GET  /blog/{post:slug}/comments          BlogController@comments   (public JSON)
PUT  /settings/comments                  SettingsController@update (admin)
```

The `POST /blog/{post:slug}/comments` store route is unchanged.

## Tests

All added to `tests/Feature/CommentTest.php`:

1. `test_comments_json_endpoint_returns_paginated_comments`
   - Seeds N approved comments, fetches page 1, asserts correct JSON shape and count
2. `test_comments_json_endpoint_respects_page_param`
   - Seeds enough comments to span 2 pages, asserts page 2 returns correct offset
3. `test_comments_store_rejected_when_comments_disabled`
   - Sets `comments.enabled = '0'`, POSTs a comment, asserts 403
4. `test_settings_comments_group_saves_correctly`
   - Admin PUTs `comments` group with valid data, asserts `Setting::get` returns saved values
5. `test_settings_comments_validates_per_page_range`
   - PUTs values outside 5–100 range, asserts validation fails

Existing `test_guest_can_submit_comment` — no change needed; default for `comments.enabled` is truthy so it passes without explicit seeding.
