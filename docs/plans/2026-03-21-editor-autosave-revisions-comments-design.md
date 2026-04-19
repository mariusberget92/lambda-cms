# Editor Quality, Autosave, Revisions & Comments — Design

**Date:** 2026-03-21
**Status:** Approved

---

## Overview

Four feature areas in one batch:

1. **Tiptap toolbar fix** — replace raw SVGs with lucide-vue-next icons
2. **Autosave** — server-side periodic draft saving for posts and pages
3. **Revisions** — immutable snapshots on every manual save, last 25 kept, for posts and pages
4. **Comments moderation UI** — polish + inline admin reply with email notification

---

## Feature 1: Tiptap Toolbar Icon Fix

**Problem:** All 16 toolbar buttons use raw inline `<svg>` elements, violating the project rule (lucide-vue-next only) and causing rendering failures.

**Solution:** Replace every raw SVG with the matching lucide-vue-next component.

| Button | Lucide component |
|---|---|
| Bold | `Bold` |
| Italic | `Italic` |
| Underline | `Underline` |
| Strikethrough | `Strikethrough` |
| H2 | `Heading2` |
| H3 | `Heading3` |
| Bullet list | `List` |
| Ordered list | `ListOrdered` |
| Blockquote | `Quote` |
| Code | `Code` |
| Align left | `AlignLeft` |
| Align center | `AlignCenter` |
| Align right | `AlignRight` |
| Undo | `Undo2` |
| Redo | `Redo2` |
| Insert image | `ImageIcon` |

**File:** `resources/js/Components/TiptapEditor.vue`
**Commit:** `fix: replace raw SVG icons in Tiptap toolbar with lucide-vue-next`

---

## Feature 2: Autosave (Posts + Pages)

### Database

New table: `autosaves`

```
id               bigint PK
autosaveable_type  string  (App\Models\Post | App\Models\Page)
autosaveable_id    bigint
user_id            bigint FK → users
payload            json    (full form state)
updated_at         timestamp
```

Unique constraint on `(autosaveable_type, autosaveable_id, user_id)` — one autosave row per user per record, overwritten on each save.

### Backend

- `AutosaveController` with two actions:
  - `store(Request $request, $type, $id)` — upsert the autosave row, return `{ saved_at: timestamp }`
- Routes (auth-gated):
  - `POST /posts/{post}/autosave` → `AutosaveController@storePost`
  - `POST /pages/{page}/autosave` → `AutosaveController@storePage`
- Pass `autosave` prop from `PostController@edit` and `PageController@edit` — the most recent autosave row for the current user+record, or `null`.

### Frontend

**Trigger:** Deep `watch` on the Inertia form object, debounced 10 seconds. Only fires when the form is dirty (has been modified since last save). Calls the autosave endpoint via `axios.post(...)` (not Inertia router — no page reload).

**Status indicator:** Small `"Draft saved at HH:MM"` text near the submit buttons. Shows saving spinner while in-flight, error text if the request fails.

**Recovery banner:** If `autosave` prop is present on page load and its `updated_at` is newer than the record's `updated_at`, show a dismissible banner at the top of the form:
> *"You have unsaved changes from X minutes ago —* ***Restore*** *or* ***Dismiss***"*

- **Restore** — merges autosave payload into form fields, removes banner, deletes autosave row via `DELETE /posts/{post}/autosave`
- **Dismiss** — deletes autosave row, removes banner

**Files:**
- `database/migrations/..._create_autosaves_table.php`
- `app/Models/Autosave.php`
- `app/Http/Controllers/AutosaveController.php`
- `routes/web.php` (add routes)
- `app/Http/Controllers/PostController.php` (pass autosave prop in edit)
- `app/Http/Controllers/PageController.php` (pass autosave prop in edit)
- `resources/js/Pages/Posts/Edit.vue` (watch + banner)
- `resources/js/Pages/Pages/Edit.vue` (watch + banner)

**Commit:** `feat: server-side autosave for posts and pages`

---

## Feature 3: Revisions (Posts + Pages)

### Database

New table: `revisions`

```
id               bigint PK
revisable_type   string  (App\Models\Post | App\Models\Page)
revisable_id     bigint
user_id          bigint FK → users (who saved this revision)
payload          json    (full snapshot: all fields as stored in DB)
created_at       timestamp
```

No `updated_at` — revisions are immutable. No unique constraint — multiple revisions per record.

### Backend

- `RevisionController` with:
  - `index(Request $request, $type, $id)` — return last 25 revisions for record (newest first)
  - `restore(Request $request, Revision $revision)` — return the payload JSON (no DB write — frontend loads it into the form, user must manually save)
- Routes (auth-gated):
  - `GET  /posts/{post}/revisions` → `RevisionController@indexPost`
  - `GET  /pages/{page}/revisions` → `RevisionController@indexPage`
  - `GET  /revisions/{revision}/restore` → `RevisionController@restore`
- `HasRevisions` trait on `Post` and `Page` models:
  - `saveRevision(int $userId)` — creates a revision snapshot, then prunes to last 25
  - `pruneRevisions()` — deletes revisions beyond 25 (oldest first)
- Call `$post->saveRevision(auth()->id())` in `PostController@update` after successful save. Same for `PageController@update`.

### Frontend

**Revisions panel** — collapsible sidebar section on `Posts/Edit.vue` and `Pages/Edit.vue`:

- Loads revisions lazily (Axios GET on panel open)
- List items: `"Mar 21, 14:32 — John Doe"` with a "Restore" button
- On Restore click: confirmation dialog `"Restore this version? Your current changes will be replaced."`
- On confirm: load payload into form fields — no auto-submit, user sees the restored state and clicks Update to commit
- Loading/empty states

**Files:**
- `database/migrations/..._create_revisions_table.php`
- `app/Models/Revision.php`
- `app/Models/Concerns/HasRevisions.php` (trait)
- `app/Http/Controllers/RevisionController.php`
- `routes/web.php`
- `app/Http/Controllers/PostController.php` (call saveRevision in update)
- `app/Http/Controllers/PageController.php` (call saveRevision in update)
- `resources/js/Pages/Posts/Edit.vue` (revisions sidebar panel)
- `resources/js/Pages/Pages/Edit.vue` (revisions sidebar panel)

**Commit:** `feat: revision history for posts and pages (last 25)`

---

## Feature 4: Comments Moderation UI

### Polish

- Full comment body visible — long comments get a "Show more / Show less" toggle (threshold: 300 chars)
- Author avatar: initials circle, Nord-colored (cycle through Nord accent colors by first letter)
- Card layout: author + timestamp header row, full body, linked post title, status badge + action buttons in clean footer row
- Bulk action UI unchanged — already works well

### Reply Functionality

**Database change:** Add `parent_id` nullable FK to `comments` table (self-referential, `ON DELETE CASCADE`).

**Backend:**
- `POST /comments/{comment}/reply` → `CommentController@reply` (admin-only)
  - Validates `body` (required, max 2000)
  - Creates child comment: `author_name` = admin's name, `user_id` = admin's id, `status = approved`, `parent_id` = parent comment id, `post_id` = parent's `post_id`
  - If parent comment has `author_email`, dispatches `SendCommentReplyNotification` mail job
  - Returns redirect back with flash

**Frontend:**
- Reply button on each comment (not on replies — one level only)
- Clicking shows inline textarea + "Send reply" / "Cancel" buttons below the comment card
- Replies displayed indented beneath their parent comment in the list
- Reply badge on comments that already have a reply

**Files:**
- `database/migrations/..._add_parent_id_to_comments_table.php`
- `app/Http/Controllers/CommentController.php` (add reply method)
- `app/Mail/CommentReplyMail.php` (new mailable)
- `routes/web.php` (add reply route)
- `resources/js/Pages/Comments/Index.vue` (polish + reply UI)

**Commit:** `feat: comments UI polish and inline admin reply`
