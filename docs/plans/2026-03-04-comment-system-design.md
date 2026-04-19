# Design: Comment System

**Date:** 2026-03-04
**Branch:** feature/comments (worktree at `.worktrees/feature-comments`) — already has migration, model, factory, and 2 model tests

## Overview

Full end-to-end comment system: public submission on blog posts, admin moderation panel, pending count on dashboard, comment count on posts index, and queued email notification to admin on new submission.

## Existing Foundation

- Migration: `comments` table — `post_id`, `user_id` (nullable), `author_name`, `author_email` (nullable), `body`, `status` (pending/approved/rejected, default pending)
- `Comment` model — `approved`/`pending` scopes, `belongsTo(Post)`, `belongsTo(User)`
- `Post::hasMany(Comment)` — already wired
- `CommentFactory` — exists
- 2 model tests — pass

## Public Side

### Submission (`Blog/Show.vue`)

Below the post body, two sections:

**Approved comments list:**
- Each comment shows: avatar initial circle (using `author_name`), name, relative timestamp (`created_at->diffForHumans()`), body
- Only `status = approved` comments shown, ordered oldest-first
- If `user_id` is set and the user has a CMS avatar, show it instead of the initial

**Submission form** (`POST /posts/{post:slug}/comments`):
- Fields: `author_name` (required), `author_email` (optional), `body` (textarea, required)
- If visitor is authenticated CMS user: pre-fill name/email from `auth.user`, hide those fields
- Honeypot: hidden `website` field — if non-empty server-side, accept silently but discard (return success response, do not persist)
- Rate limiting: `throttle:1,1` (1 submission per minute per IP) on the route
- On success: Inertia redirect back with flash "Your comment has been submitted and is awaiting moderation."

### Public BlogController update
- `show()` eager-loads `comments()->approved()->oldest()->get()` on the post
- Passes `comments` collection and authenticated user's name/email to the page props

## Admin Side

### CommentController (admin actions)

| Method | Route | Action |
|--------|-------|--------|
| `index` | `GET /comments` | Paginated list, filterable by status via `?filter=pending\|approved\|rejected\|all` |
| `approve` | `PATCH /comments/{comment}/approve` | Set status = approved |
| `reject` | `PATCH /comments/{comment}/reject` | Set status = rejected |
| `destroy` | `DELETE /comments/{comment}` | Hard delete |
| `bulk` | `POST /comments/bulk` | `action` (approve/reject/delete) + `ids[]` array |

All admin routes behind `['auth', 'verified', 'role:administrator']`.

### `Comments/Index.vue`

- Filter tabs: **Pending** | **Approved** | **Rejected** | **All** — clicking updates `?filter=` query param via Inertia visit
- Table columns: Post title (link opens blog post in new tab), Author + email, Body excerpt (80 chars), Submitted, Status badge, Actions
- Row actions: Approve (✓), Reject (✗), Delete (trash, with inline confirm)
- Bulk: select-all checkbox, bulk Approve / Reject / Delete buttons appear when ≥1 row selected
- Sidebar link: "Comments" with speech-bubble icon, admin-only, shows pending count badge if > 0

### Dashboard widget

Add a fourth stat card to `Dashboard/Index.vue`: "Pending comments" count, amber colour (using `--color-status-warning-*`), links to `/comments?filter=pending`. `DashboardController` passes `pendingCommentsCount`.

### Posts index comment count

Add a comment count column to `Posts/Index.vue` table. `PostController::index()` adds `withCount('comments')`. Display as plain number with a small speech-bubble icon.

## Email Notification

- Mailable: `NewCommentNotification` — sent to `Setting::get('mail.from_address')` (the admin inbox)
- Subject: `New comment on "{post title}"`
- Body: author name, email, post title (link), comment body excerpt
- Dispatched via `dispatch(new SendNewCommentNotification($comment))` — uses `ShouldQueue`; with default `sync` driver it sends inline, with a real queue it's async
- Uses the runtime mail config applied by `BootstrapSettings` middleware (settings-driven SMTP)

## Spam Protection

- Honeypot field `website` — hidden via CSS (`display:none` in the Vue template), checked server-side; if filled → silently succeed without persisting
- Rate limit: `RateLimiter::for('comments', ...)` — 1 per minute per IP, returns 429 on breach

## Testing

- **Unit:** `CommentFactory` states (pending/approved/rejected), honeypot discard logic
- **Feature:** guest can submit comment, authenticated user pre-fill, rate limit enforced, admin can approve/reject/delete, bulk actions, non-admin cannot access `/comments`, notification dispatched on submit
- **No browser/E2E tests** — Inertia feature tests only

## Files Created / Modified

### New
- `app/Http/Controllers/CommentController.php`
- `app/Mail/NewCommentNotification.php`
- `app/Jobs/SendNewCommentNotification.php`
- `resources/views/mail/new-comment.blade.php`
- `resources/js/Pages/Comments/Index.vue`
- `tests/Feature/CommentTest.php` (extend existing 2-test file)

### Modified
- `app/Http/Controllers/BlogController.php` — eager-load approved comments on `show()`
- `app/Http/Controllers/DashboardController.php` — add `pendingCommentsCount`
- `app/Http/Controllers/PostController.php` — add `withCount('comments')` to index
- `resources/js/Pages/Blog/Show.vue` — add comments list + submission form
- `resources/js/Pages/Dashboard/Index.vue` — add pending comments stat card
- `resources/js/Pages/Posts/Index.vue` — add comment count column
- `resources/js/Layouts/AppLayout.vue` — add Comments sidebar link with badge
- `routes/web.php` — add comment routes
