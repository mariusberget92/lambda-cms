# Featured Post Flag — Design

**Date:** 2026-04-09
**Status:** Approved

## Goal

Allow editors to mark posts as "featured" via a checkbox in the post editor. The flag must be saved by the controller and is already filterable in block editor loop blocks.

## What Already Exists (no changes needed)

- `posts.featured` DB column — present
- `Post::$fillable` — includes `featured`
- `QueryBuilder::FILTERABLE['posts']` — includes `'featured'`
- `QueryBuilder::resolvePosts()` output — includes `'featured' => (bool) $post->featured`
- `loopSources.js SOURCE_FIELDS.posts` — includes `{ value: 'featured', label: 'Is Featured' }`

The loop block filter UI already shows "Is Featured" as a filterable field for posts. No block editor work needed.

## What Needs to Be Built

### 1. PostController — store() and update()

Add `'featured' => ['boolean']` to the validation rules in both methods. Include `featured` in the data passed to `Post::create()` and `$post->update()`.

### 2. Post Editor — Create.vue and Edit.vue

Add a "Featured post" checkbox toggle to the publishing sidebar, below the existing "Enable comments" toggle. Uses the same checkbox/label pattern already in the form. Bound to `form.featured` (boolean, default false).

## Files to Modify

| File | Change |
|------|--------|
| `app/Http/Controllers/PostController.php` | Add `featured` to validation + save in `store()` and `update()` |
| `resources/js/Pages/Posts/Create.vue` | Add featured checkbox to sidebar |
| `resources/js/Pages/Posts/Edit.vue` | Add featured checkbox to sidebar |
