# Page Builder — Full-Screen Design

**Date:** 2026-04-04
**Status:** Approved

---

## Overview

The Pages Create and Edit views should open in a dedicated full-screen page builder that takes over the entire viewport — no sidebar, no topbar from AppLayout. The block editor fills all available space, and page metadata is accessible via a thin dark top bar built into the builder itself.

---

## Layout

### `PageBuilderLayout.vue`

A new layout component that replaces `AppLayout` on the Pages Create and Edit pages.

- `fixed inset-0 z-50 flex flex-col` — takes over the entire viewport
- Dark background matching the block editor (`bg-[#1e1e2e]` / `data-theme="dark"`)
- Renders: `<PageBuilderBar>` (44px) + default slot (fills remaining height)
- Inertia `<Head>` still works as normal

### Block Editor sizing

The `BlockEditor` component currently uses:
```
min-height: 500px; max-height: calc(100vh - 220px)
```
In fullscreen mode this becomes `height: calc(100vh - 44px)` — flush to the viewport edges, no border, no rounded corners.

---

## Top Bar (`PageBuilderBar.vue`)

**Height:** 44px
**Theme:** Always dark (`data-theme="dark"`, `bg-[#181825]`, `border-b border-white/10`)

### Layout (left → right)

```
[ ← ]  [ Page title (inline editable) ]  ············  [ Draft | Published ]  [ SEO ▾ ]  [ Revisions ▾ ]  [ Save ]
```

| Slot | Element | Detail |
|------|---------|--------|
| Far left | Back arrow button | Navigates to `pages.index` |
| Left-center | Title input | Plain `<input>`, no visible border until focused; `bg-transparent`, light text |
| Right | Status pills | Two toggles: Draft / Published — pill style, active pill has primary accent bg |
| Right | SEO button | Opens a popover below the bar with meta_title, meta_description, meta_keywords |
| Right | Revisions button | Opens a popover listing revisions with Restore actions |
| Far right | Save button | Primary accent, `Saving…` state while processing |

### SEO Popover

Anchored to the SEO button, opens downward. Contains the three SEO fields as a compact inline form (no extra save — fields are part of the main form, saved on Save click).

### Revisions Popover

Anchored to the Revisions button. Lists revisions with user name, date, and Restore button — same logic as current implementation.

---

## Pages/Create.vue and Pages/Edit.vue

- Swap `AppLayout` for `PageBuilderLayout`
- Remove the existing meta card (title input, slug row, status, SEO expand, revisions expand)
- Pass title, slug, status, SEO fields, and revisions state down to `PageBuilderBar` via props/events
- The `BlockEditor` component is used unchanged inside the layout slot

---

## Data Flow

```
Pages/Edit.vue (useForm, autosave, revisions logic)
  └── PageBuilderLayout
        ├── PageBuilderBar
        │     emits: update:title, update:slug, update:status,
        │             update:metaTitle, update:metaDescription, update:metaKeywords,
        │             save, restoreRevision
        └── BlockEditor (unchanged)
```

All form state stays in the page component (`Edit.vue` / `Create.vue`). The bar receives values as props and emits changes upward — no logic lives in the bar itself.

---

## Files to Create / Modify

| File | Action |
|------|--------|
| `resources/js/Layouts/PageBuilderLayout.vue` | Create |
| `resources/js/Components/PageBuilderBar.vue` | Create |
| `resources/js/Pages/Pages/Edit.vue` | Modify — swap layout, remove meta card, wire bar |
| `resources/js/Pages/Pages/Create.vue` | Modify — same as Edit |
