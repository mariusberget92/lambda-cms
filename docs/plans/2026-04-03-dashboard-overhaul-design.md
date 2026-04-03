# Dashboard & Admin UI Overhaul — Design Document

**Date:** 2026-04-03
**Scope:** Full admin shell — AppLayout, Dashboard page, all list/form/settings pages
**Approach:** Token-first → 4 reusable patterns → apply everywhere

---

## 1. Token & Foundation

### Light mode changes (`resources/css/app.css` `:root`)

| Token | Old | New | Reason |
|---|---|---|---|
| `--background` | `#ffffff` | `#f7f8fa` | Cards need something to pop against |
| `--card` | `#e5e9f0` | `#ffffff` | Cards should be pure white |
| `--border` | `#d8dee9` | `#e4e7ec` | More neutral, less Nordic blue-grey tint |

### New tokens (light + dark)
```css
--shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
--shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.07), 0 2px 4px -2px rgb(0 0 0 / 0.05);
```

Dark mode tokens unchanged.

---

## 2. The 4 Component Patterns

### 2.1 PageHeader (`resources/js/Components/PageHeader.vue`)
- Props: `title` (string), `description` (string, optional)
- Slots: `#actions` (right side, optional)
- Layout: flex row, title+description on left, actions on right, `pb-6 mb-6 border-b border-border`
- Typography: title `text-xl font-semibold`, description `text-sm text-muted-foreground mt-1`

### 2.2 StatCard (`resources/js/Components/StatCard.vue`)
- Props: `label`, `value`, `color` (Nord color name: blue/green/cyan/yellow/red/purple), `href` (optional)
- White card, `shadow-sm`, `border`, `rounded-xl`, `p-5`
- Icon container: 40×40 `rounded-lg` with Nord-tinted bg and icon in matching fg color
- Value: `text-2xl font-bold mt-3`
- Label: `text-sm text-muted-foreground`
- When `href` provided, wraps in `<a>` with `hover:shadow-md transition-shadow`

### 2.3 DataTable (`resources/js/Components/DataTable.vue`)
- Slot: `#head` (th cells), `#body` (tr rows), `#empty`, `#footer`
- `<thead>`: `bg-muted/40`, `text-xs font-medium uppercase tracking-wide text-muted-foreground`
- `<tbody tr>`: `hover:bg-muted/30 transition-colors`
- Empty state: centered icon + message from `#empty` slot
- Footer: border-top + `#footer` slot for pagination

### 2.4 ContentCard (`resources/js/Components/ContentCard.vue`)
- Props: `title` (optional), `description` (optional)
- Slots: `#actions` (header right), default body, `#footer`
- White card, `shadow-sm`, `border`, `rounded-xl`
- Header (if title provided): `px-6 py-4 border-b`, title `text-sm font-semibold`
- Body: `px-6 py-5`
- Footer (if slot used): `px-6 py-4 border-t bg-muted/20`

---

## 3. AppLayout

### Sidebar (`resources/js/Layouts/AppLayout.vue`)
- Active nav item: `bg-sidebar-primary/10 text-sidebar-primary font-medium` + `border-l-2 border-sidebar-primary pl-[10px]` (compensate padding)
- Inactive hover: `hover:bg-sidebar-accent/60`
- Logo area: keep, add `shadow-sm` on the bottom border div
- User footer: tighten spacing, add `hover:bg-sidebar-accent/40 rounded-md cursor-pointer transition-colors` to the row

### Topbar
- Add `shadow-sm` class (replaces border-only separation on white bg)
- Title: `text-base font-semibold`
- Theme toggle: ensure `hover:bg-accent rounded-md` wrapping

---

## 4. Dashboard Page (`resources/js/Pages/Dashboard/Index.vue`)

- Remove welcome/greeting block
- Stat row: 5 `StatCard` components
  - Total Posts → `color="blue"`
  - Published → `color="green"`
  - Scheduled → `color="cyan"`
  - Drafts → `color="yellow"`
  - Pending Comments → `color="red"`, `href` to comments filter
- Two-column panels: `ContentCard` with header title + "View all →" link in `#actions`
- Empty states: add a small Lucide icon above the message text
- Quick actions: `ContentCard` with 4 buttons: New Post (primary), View Posts, Upload Media, New Page (admin only)

---

## 5. Other Pages

### Consistent page structure
Every page:
1. `<PageHeader :title="..." :description="...">` with `#actions` slot for primary CTA
2. Optional filter/search bar: `bg-card border rounded-lg px-4 py-3 mb-4`
3. Body content in `DataTable` (list pages) or `ContentCard` sections (form/settings pages)

### List pages to update
- Posts Index, Categories Index, Tags Index, Media Index, Users Index, Comments Index
- Swap table chrome → `DataTable` component
- Row actions (edit/delete) → right-aligned icon buttons, visible on `tr:hover`

### Form pages to update
- Posts Edit/Create, Categories Form, Profile, Settings
- Group fields into `ContentCard` sections with named headers
- Save button in card `#footer` slot

### Settings page
- Each tab body → one or more `ContentCard` sections per logical group
- No structural/data changes

---

## Out of scope
- No changes to controllers, routes, or data flow
- No new features or fields
- Block editor pages unchanged (they have their own dark-themed canvas)
- Auth pages (login, reset password) unchanged
