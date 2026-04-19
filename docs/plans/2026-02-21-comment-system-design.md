# Comment System Design

**Date:** 2026-02-21
**Status:** Approved
**Approach:** Inertia-native (Approach A)

---

## Overview

Add a flat comment system to the public blog. Guest users (name + email) and authenticated users can post comments. Guest comments require admin approval; authenticated user comments are auto-approved. Admins moderate via a dashboard Comments page.

---

## Database Schema

Table: `comments`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `post_id` | FK → posts | cascade delete |
| `user_id` | FK → users, nullable | null = guest comment |
| `author_name` | string | stored for guests; snapshotted for auth users |
| `author_email` | string | guests only; never exposed publicly |
| `body` | text | plain text, no HTML |
| `status` | enum: `pending`, `approved`, `rejected` | auth users default `approved`, guests default `pending` |
| `created_at` / `updated_at` | timestamps | |

- Auth users: `user_id` set, `author_name` snapshotted from user at post time
- `author_email` stored for guests, never returned to frontend

---

## Routes & Controllers

### CommentController

| Method | Route | Middleware | Description |
|---|---|---|---|
| `POST` | `/blog/{post}/comments` | public | Submit a comment (guest or auth) |
| `DELETE` | `/comments/{comment}` | `auth` | Delete own comment or as admin |
| `PATCH` | `/comments/{comment}/approve` | `auth, role:administrator` | Approve a pending comment |
| `PATCH` | `/comments/{comment}/reject` | `auth, role:administrator` | Reject a comment |

### Dashboard

| Method | Route | Middleware | Description |
|---|---|---|---|
| `GET` | `/comments` | `auth, verified` | List all comments, filterable by status |

### Rate Limiting

Guest comment submissions: 3 per IP per minute via Laravel `RateLimiter`.

---

## Data Flow

### `BlogController@show` additions

Returns with the post:
- `comments` — approved comments only: `[id, body, author_name, created_at, is_owner]`
- `pending_count` — integer, non-zero only for admins

### Frontend Components

| Component | Path | Purpose |
|---|---|---|
| `CommentSection.vue` | `resources/js/Components/` | Container: comment list + form |
| `CommentForm.vue` | `resources/js/Components/` | Guest fields (name/email/body) or auth fields (body only) |
| `CommentItem.vue` | `resources/js/Components/` | Single comment with delete button for owner/admin |

### `Blog/Show.vue`

`<CommentSection>` added below the tags section. Receives `comments`, `postId`, `auth.user` as props.

### `Comments/Index.vue` (dashboard)

Same table pattern as `Posts/Index.vue`:
- Filter tabs: All / Pending / Approved / Rejected
- Columns: Post title, Author, Excerpt, Date, Status, Actions (Approve / Reject / Delete)
- Navigation link added to `AppLayout.vue` sidebar

### Form Behaviour

- **Guest:** name (required) + email (required) + body (required, max 2000 chars) → status: `pending`
- **Auth user:** body only; name/email auto-filled server-side → status: `approved`
- On success (auth): Inertia router visit refreshes page, comment appears immediately
- On success (guest): flash message "Your comment is awaiting approval"

---

## Validation

| Field | Rules |
|---|---|
| `body` | required, string, max:2000 |
| `author_name` | required if guest, max:100 |
| `author_email` | required if guest, email, max:255 |

Auth users: name/email taken from `$request->user()` — not submitted by form.

---

## Authorization

- **Delete:** `$comment->user_id === $request->user()->id \|\| $request->user()->hasRole('administrator')` — same pattern as Post delete
- **Approve/Reject:** `role:administrator` middleware on route level
- **Guests:** cannot delete their own comments (no account to verify ownership)

---

## Security

- Comment `body` stored as plain text, displayed with `{{ }}` text interpolation — **no `v-html`**, no XSS risk
- `author_email` excluded from all JSON returned to frontend
- Rate limiting on guest submissions (3/min per IP)
- No HTML allowed in comment body (plain text only)

---

## Testing

Feature tests in `tests/Feature/CommentTest.php`:

- Guest can submit comment (status = pending)
- Auth user can submit comment (status = approved)
- Guest comment does not appear until approved
- Rate limit blocks excessive guest submissions
- Auth user can delete own comment
- Admin can delete any comment
- User cannot delete another user's comment (403)
- Admin can approve pending comment
- Non-admin cannot approve comment (403)
- Admin can reject comment
- Dashboard index lists all comments
- Dashboard index filters by status
