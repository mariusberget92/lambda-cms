# Per-Post Comments Toggle — Design

**Date:** 2026-03-06
**Status:** Approved

## Goal

Allow authors to disable comments on individual posts. The global `comments.enabled` setting remains the master switch — if it is off, no post can receive comments regardless of its own flag.

## Approach

Boolean column `comments_enabled` on the `posts` table (default `true`). Simple, readable, consistent with the existing model structure.

## Behaviour Rules

| Global setting | Post flag | Comments open? |
|---|---|---|
| OFF | ON | ❌ No |
| OFF | OFF | ❌ No |
| ON | ON | ✅ Yes |
| ON | OFF | ❌ No |

Existing posts default to `true` (opt-out model). New posts also default to `true`.

## Database & Model

- New migration: `add_comments_enabled_to_posts_table` — adds `$table->boolean('comments_enabled')->default(true)`.
- `Post::$fillable` gains `'comments_enabled'`.
- `Post::$casts` gains `'comments_enabled' => 'boolean'`.
- New helper `Post::commentsOpen(): bool` — returns `false` when the global `comments.enabled` setting is off **or** when `$this->comments_enabled` is `false`. All consumers use this single method.

## Backend

- `PostController::store()` and `update()` accept `comments_enabled` (validated as `nullable|boolean`; defaults to `true` when absent).
- `CommentController::store()` replaces its raw `Setting::get('comments.enabled')` check with `$post->commentsOpen()`.
- `BlogController::comments()` adds a `$post->commentsOpen()` guard (returns 403 when closed).
- `BlogController::show()` replaces `Setting::get('comments.enabled')` with `$post->commentsOpen()` for the `commentsEnabled` Inertia prop.

## Frontend

- `Posts/Create.vue` and `Posts/Edit.vue`: add an **"Allow comments"** checkbox (default checked) in the sidebar meta panel, alongside Status and Published At.
- `Blog/Show.vue`: no changes — already consumes `commentsEnabled` prop and shows the "Comments are closed" notice correctly.

## Testing

- Migration rolls forward and back cleanly.
- `PostTest`: store/update with `comments_enabled = false` persists correctly; defaults to `true` when omitted.
- `CommentTest`: submitting a comment to a post with `comments_enabled = false` returns 403 even when the global setting is on.
- `BlogTest` (or `CommentTest`): JSON endpoint returns 403 for a post with comments disabled.
