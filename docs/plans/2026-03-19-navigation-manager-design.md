# Navigation Manager — Design

**Date:** 2026-03-19
**Status:** Approved

## Goal

Add a managed navigation system to Lambda CMS so administrators can control which links appear in the public blog header. Supports both published custom pages and arbitrary custom links (e.g. external URLs or internal routes like the blog home).

## Stack context

Laravel 12 · Inertia 2 · Vue 3 · Tailwind CSS 4 · vue-draggable-plus (already installed)

---

## Database

New `nav_items` table:

| column | type | notes |
|--------|------|-------|
| `id` | bigint PK | |
| `type` | enum(`page`, `custom`) | |
| `label` | string(255) | display text in nav |
| `url` | string(255) nullable | used for `custom` items only |
| `page_id` | FK → pages nullable | null for custom; cascade on delete |
| `sort_order` | integer default 0 | ascending order in nav |
| `timestamps` | | |

---

## Backend

### NavItem model (`app/Models/NavItem.php`)

- `$fillable`: type, label, url, page_id, sort_order
- `belongsTo(Page::class)` relationship
- `resolvedUrl` accessor: returns `url` for custom items; `"/{page->slug}"` for page items

### NavigationController (`app/Http/Controllers/NavigationController.php`)

Admin-only (middleware: `auth`, `verified`, `role:administrator`).

| method | route | action |
|--------|-------|--------|
| GET | `/navigation` | Render `Navigation/Index.vue` with nav items + published pages list |
| POST | `/navigation` | Create item; assign `sort_order = max + 1` |
| POST | `/navigation/reorder` | Accept `[{id, sort_order}]`, bulk-update |
| DELETE | `/navigation/{item}` | Delete item |

**Validation — store:**
- `type`: required, in:page,custom
- `label`: required, string, max:255
- `url`: required_if:type,custom, nullable, string, max:255
- `page_id`: required_if:type,page, nullable, exists:pages,id

### HandleInertiaRequests shared prop

Add `navItems` to `share()`:

```php
'navItems' => NavItem::with('page')
    ->orderBy('sort_order')
    ->get()
    ->filter(fn ($item) =>
        $item->type === 'custom' ||
        ($item->page && $item->page->status === 'published')
    )
    ->map(fn ($item) => [
        'label' => $item->label,
        'url'   => $item->resolvedUrl,
    ])
    ->values(),
```

---

## Admin UI

### `resources/js/Pages/Navigation/Index.vue`

Single-page manager. Two-column card layout:

**Left — current nav items (drag-to-reorder):**
- VueDraggable list, fires `POST /navigation/reorder` on drop
- Each row: drag handle · label · type badge (Page / Custom) · resolved URL (muted) · optional "draft" warning badge if linked page is draft · delete button

**Right — add item form:**
- Type toggle: `Page` / `Custom link`
- If Page: `<select>` of published pages; label auto-fills from page title (editable)
- If Custom: label text input + URL text input
- Add button → `POST /navigation`, appends to list

**Empty state:** "No nav items yet. Add your first item →"

### AppLayout.vue sidebar

New admin-only `SidebarLink` for Navigation, placed between Pages and Categories in the Content section. Uses a menu/list icon.

---

## Public display

### `BlogLayout.vue`

Read `navItems` from `usePage().props`. Render between site name and Dashboard/Sign-in:

```vue
<Link v-for="item in navItems" :key="item.url"
  :href="item.url"
  class="text-sm text-muted-foreground hover:text-foreground transition-colors"
>{{ item.label }}</Link>
```

External links (URL starts with `http`) open in `target="_blank" rel="noopener"` using a plain `<a>` tag instead of Inertia `<Link>`.

If `navItems` is empty the header is unchanged.

---

## Testing

### `NavItemModelTest`

- Factory creates item
- `resolvedUrl` returns correct URL for both types
- Cascade: deleting a Page cascades to its NavItem

### `NavigationTest`

- Admin can view navigation manager
- Admin can add a page nav item
- Admin can add a custom nav item
- Admin can reorder items
- Admin can delete an item
- Non-admin gets 403 on all endpoints
- Validation rejects missing label
- Validation rejects page type without page_id
- Validation rejects custom type without url
- `navItems` shared prop excludes draft-page items
- `navItems` shared prop includes custom items regardless
- Cascade: draft page item absent from shared prop; custom item still present

---

## File list

**New:**
- `database/migrations/YYYY_MM_DD_000001_create_nav_items_table.php`
- `app/Models/NavItem.php`
- `database/factories/NavItemFactory.php`
- `app/Http/Controllers/NavigationController.php`
- `resources/js/Pages/Navigation/Index.vue`
- `tests/Feature/NavItemModelTest.php`
- `tests/Feature/NavigationTest.php`

**Modified:**
- `routes/web.php` — add navigation routes
- `app/Http/Middleware/HandleInertiaRequests.php` — share `navItems`
- `resources/js/Layouts/AppLayout.vue` — sidebar link
- `resources/js/Layouts/BlogLayout.vue` — render nav links
