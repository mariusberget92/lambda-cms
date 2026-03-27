# TagInput Component Design

**Date:** 2026-03-27
**Status:** Approved

## Problem

The current tags panel in Posts/Create and Posts/Edit renders all tags as a flat checkbox list. It doesn't scale, offers no search, and provides no way to create new tags inline without leaving the post editor.

## Solution

A `TagInput.vue` component: a text input with live dropdown filtering of existing tags and a "+ Create" row for new ones. Selected tags (existing and new) appear as dismissible pills above the input. New tags are held in memory and created by the post controller on save — no extra API calls.

## API

```vue
<TagInput
  :tags="tags"
  v-model="form.tag_ids"
  v-model:new-tag-names="form.new_tag_names"
/>
```

**Props:**
- `tags` — `Array` of `{ id, name }` (all existing tags, passed from server)
- `modelValue` — `Array<Number>` selected tag IDs
- `newTagNames` — `Array<String>` unsaved new tag names

## Visual Structure

```
┌─────────────────────────────────────────┐
│ [vue ×]  [laravel ×]  [+ my-tag ×]     │  ← pills
│  Search or add tags...                  │  ← input
└─────────────────────────────────────────┘
          ▼ dropdown while typing "vu"
  ┌───────────────────────────────────────┐
  │  vue-router                           │
  │  vuex                                 │
  │  + Create "vu"                        │  ← only when no exact match
  └───────────────────────────────────────┘
```

- Wrapper: `rounded-md border border-border bg-background px-2 py-1.5 focus-within:ring-2 focus-within:ring-ring`
- Pills row + input: `flex flex-wrap gap-1 items-center`
- Existing tag pill: `inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full bg-primary/10 text-primary border border-primary/20`
- New tag pill: `inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full bg-accent/20 text-foreground border border-border` (with `+` prefix)
- × button on pill: `hover:text-destructive transition-colors`
- Dropdown: `absolute z-50 left-0 top-full mt-1 w-full max-h-48 overflow-y-auto rounded-md border border-border bg-card shadow-lg`
- Dropdown item: `px-3 py-1.5 text-sm cursor-pointer hover:bg-accent/20`
- "+ Create" row: same style but with `+` prefix and slightly muted until hovered

## Behaviour

- Typing filters existing tags by name (case-insensitive, substring match)
- Already-selected tags (by ID or name) are hidden from the dropdown
- Enter key:
  - If query matches exactly one existing tag → select it
  - Otherwise → add query to `new_tag_names` as a new pill
- Click dropdown item → select existing tag
- Click "+ Create" row → add to `new_tag_names`
- × on pill → remove from `tag_ids` or `new_tag_names`
- Backspace on empty input → remove last pill
- Escape → close dropdown
- Click outside → close dropdown (`onClickOutside`)
- Input is cleared after each selection

## Backend Changes

### `PostController::store()` and `update()`

After validating, before syncing tags:

```php
$newIds = collect($request->new_tag_names ?? [])
    ->filter()
    ->map(fn($name) => Tag::firstOrCreate(
        ['slug' => Tag::generateSlug($name)],
        ['name' => $name]
    )->id)
    ->toArray();

$tagIds = array_merge($request->tag_ids ?? [], $newIds);
$post->tags()->sync($tagIds);
```

### Validation

Add to both `store` and `update` rules:
```php
'new_tag_names'   => ['nullable', 'array'],
'new_tag_names.*' => ['string', 'max:50'],
```

## Scope of Changes

| File | Change |
|------|--------|
| `resources/js/Components/TagInput.vue` | CREATE |
| `resources/js/Pages/Posts/Create.vue` | Replace tags section, add `new_tag_names` to form |
| `resources/js/Pages/Posts/Edit.vue` | Replace tags section, add `new_tag_names` to form |
| `app/Http/Controllers/PostController.php` | Handle `new_tag_names` in `store` + `update` |

## Non-Goals

- No server-side search (all tags loaded with page, client-side filter is sufficient)
- No tag colour customisation
- No drag-to-reorder pills
- No tag usage count shown in dropdown
