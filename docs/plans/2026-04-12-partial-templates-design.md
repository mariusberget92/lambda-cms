# Partial Templates (Reusable Block Snippets) — Design

**Date:** 2026-04-12
**Status:** Approved

---

## Goal

Allow users to create named reusable block structures ("partials") and embed them anywhere in the block editor via a new `template` block type. Changes to a partial automatically reflect everywhere it is used. Key use case: define a "Card" partial once and use it as the repeating child inside a Loop block.

---

## Section 1 — Partials Management

Partials reuse the existing `templates` table and infrastructure. A new type value `partial` is added.

**Backend:**
- `TemplateController` validation: add `partial` to the `in:` rule for `type` (store + update)
- No migration needed — `type` is a plain string column

**Frontend (`Templates/Index.vue`):**
- Add a dedicated "Partials" section below the existing template type groups
- "New Partial" button routes to `templates.create?type=partial`
- Partials use the exact same block editor as page-level templates (no separate pages)

**Recursion prevention:**
- Partials must not contain a `template` block (infinite loop risk)
- Implementation: the block editor canvas/palette injects `provide('isPartial', true)` when editing a partial
- `BlockTypePanel` hides the `template` block type when `inject('isPartial')` is `true`
- No server-side validation needed (the palette simply doesn't offer it)

---

## Section 2 — Shared Props Delivery

All published partials are injected into every Inertia page response.

**`HandleInertiaRequests::share()`** — add:
```php
'partials' => fn () => Template::published()
    ->where('type', 'partial')
    ->get(['id', 'title', 'blocks'])
    ->toArray(),
```

- Lazy closure — queried only when the response is built, not on every middleware tick
- Available everywhere as `usePage().props.partials` — array of `{id, title, blocks[]}`
- Live reference: editing + re-publishing a partial is immediately reflected on the next page load
- No caching at this stage

---

## Section 3 — The `template` Block Type

### `TemplateBlock.vue` (public renderer)

- Reads `block.data.template_id`
- Finds the matching entry in `usePage().props.partials`
- Renders via `<BlockRenderer :blocks="partial.blocks" />`
- Loop context flows through automatically: because `TemplateBlock` sits inside `LoopItemProvider` when used in a loop, all `loop:fieldName` bindings in the partial resolve against the current loop item
- If no match (partial deleted/unpublished): renders a neutral placeholder div

### `TemplateSettings.vue` (block editor settings panel)

- Single `<select>` listing partials from `usePage().props.partials`
- "Edit partial →" link opens `/templates/{id}/edit` in a new tab
- Shows "No partials yet — create one in Templates" when list is empty

### Registration

| File | Change |
|------|--------|
| `BlockRenderer.vue` | Import `TemplateBlock`; add `template: TemplateBlock` to `BLOCK_MAP` |
| `BlockTypePanel.vue` | Add to `ALL_TYPES` (Interactive group); `DEFAULT_DATA`: `{ template_id: null }`; hide when `inject('isPartial', false)` is `true` |
| `BlockLayers.vue` | Add `template: 'Template'` to `LABELS`; add `template: TemplateSettings` to `COMPONENT_MAP`; import `TemplateSettings` |

---

## File Change Summary

| File | Action |
|------|--------|
| `app/Http/Controllers/TemplateController.php` | Add `partial` to type validation (store + update) |
| `app/Http/Middleware/HandleInertiaRequests.php` | Add `partials` lazy shared prop |
| `resources/js/Pages/Templates/Index.vue` | Add Partials section + New Partial button |
| `resources/js/Pages/Templates/Create.vue` | Show correct title for partial type |
| `resources/js/Pages/Templates/Edit.vue` | Inject `isPartial` provide when type is partial; pass to block editor |
| `resources/js/Components/Blocks/TemplateBlock.vue` | **New** — lookup + render partial blocks |
| `resources/js/Components/BlockEditor/blocks/TemplateSettings.vue` | **New** — partial selector settings panel |
| `resources/js/Components/BlockRenderer.vue` | Register `template` block type |
| `resources/js/Components/BlockEditor/BlockTypePanel.vue` | Register type; hide when `isPartial` |
| `resources/js/Components/BlockEditor/BlockLayers.vue` | Register label + settings component |
