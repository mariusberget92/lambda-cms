# Frontend Redesign — Design Document
**Date:** 2026-04-12

## Goal

Give the public-facing frontend a clean editorial / publication look using plain Tailwind utilities. Remove all NORD palette CSS-variable classes and dark mode styles from frontend files. The dashboard and auth pages are unaffected.

## Approach

**Option B — Hardcoded Tailwind utilities.** All CSS-variable-based classes (`bg-background`, `text-foreground`, `bg-card`, `text-muted-foreground`, etc.) are replaced with literal Tailwind classes across the blog files. No `dark:` variants are added. Since no CSS variables are used, the NORD theme and dark mode have zero effect on the frontend.

## Typography

- **Heading font:** *Lora* (Google Fonts), loaded via `<link>` in `BlogLayout`'s `<Head>`.
- **Utility class:** `.font-editorial { font-family: 'Lora', Georgia, serif; }` added to `app.css`.
- **Body copy:** `font-sans` (system-ui / Inter) throughout.

## Base Colour Palette

| Role | Tailwind class |
|---|---|
| Page background | `bg-white` |
| Primary text | `text-gray-900` |
| Muted / meta text | `text-gray-500` |
| Borders / rules | `border-gray-200` |
| Subtle backgrounds | `bg-gray-50` |
| Links (hover) | `hover:text-gray-900` + underline |
| Primary buttons | `bg-gray-900 text-white` |

## Component Designs

### BlogLayout
- White background (`bg-white min-h-screen flex flex-col`).
- **Header:** non-sticky, white, `border-b border-gray-200`. Site name: `font-editorial text-xl font-bold text-gray-900`. Nav links: `text-sm text-gray-500 hover:text-gray-900`. Search icon + Dashboard/Sign in link retained.
- **Hero strip removed entirely** (`bg-[#2e3440]` block deleted).
- **Content area:** `max-w-3xl mx-auto px-6 py-12`. Sidebar layout (`lg:grid-cols-3`) structure unchanged.
- **Footer:** `border-t border-gray-200 py-6 text-center text-xs text-gray-400`. Plain copyright line.
- Lora loaded via `<link rel="preconnect">` + `<link href="...fonts.googleapis.com...">` in `<Head>`.

### PostCard
- No card box, no border, no hover lift, no shadow, no accent bar, no gradient.
- Posts separated by `border-t border-gray-200` divider in the list.
- **Featured image:** full-width, `rounded-lg aspect-video object-cover`.
- **Category:** `text-xs text-gray-500 uppercase tracking-wide` — no colored pills.
- **Title:** `font-editorial text-2xl font-bold text-gray-900 leading-snug` — title is the link, plain hover underline.
- **Excerpt:** `text-base text-gray-600 leading-relaxed line-clamp-3`.
- **Meta row:** author avatar + name + date + reading time in `text-sm text-gray-500`.
- **No "Read more" button.**

### BlogSidebar
- Section headings: `text-xs font-semibold uppercase tracking-widest text-gray-400 mb-3`.
- Links: `text-sm text-gray-700 hover:text-gray-900`.
- Tag chips: `border border-gray-200 rounded-full px-2.5 py-0.5 text-xs text-gray-600 hover:bg-gray-900 hover:text-white hover:border-gray-900`.
- Search: underline-style input (`border-b border-gray-300 bg-transparent`), no box.

### Blog/Index.vue
- Post list: `space-y-12` with `border-t border-gray-200` divider between posts.
- Pagination: plain text links — `text-sm text-gray-500 hover:text-gray-900`; active page `font-semibold text-gray-900 underline`.

### Blog/Show.vue (Single Post)
- Title: `font-editorial text-4xl font-bold leading-tight text-gray-900`.
- Meta (author + date): `text-sm text-gray-500` row below title.
- Featured image: `w-full rounded-lg mb-10`.
- Body prose: `prose prose-gray max-w-none`.
- Comments: `border-t border-gray-200 mt-16 pt-10`. Form inputs underline-style. Submit: `bg-gray-900 text-white`.

### Blog/Archive.vue
- Taxonomy label: `text-xs uppercase tracking-widest text-gray-400`.
- Name: `font-editorial text-3xl font-bold text-gray-900`.
- Post count: `text-sm text-gray-500`.
- Same post list as index.

### Blog/Search.vue
- Prominent search bar at top.
- Same post list below.

## Files Changed

1. `resources/css/app.css` — add `.font-editorial` utility
2. `resources/js/Layouts/BlogLayout.vue` — new header/footer, load Lora, remove hero strip
3. `resources/js/Components/PostCard.vue` — clean editorial card
4. `resources/js/Components/BlogSidebar.vue` — clean sidebar
5. `resources/js/Pages/Blog/Index.vue` — divider list + pagination
6. `resources/js/Pages/Blog/Show.vue` — article typography + comments
7. `resources/js/Pages/Blog/Archive.vue` — taxonomy heading + post list
8. `resources/js/Pages/Blog/Search.vue` — search bar + post list

## Out of Scope

- `Blog/Page.vue` and `Blog/TemplatePage.vue` — block-renderer-driven; appearance controlled by user-built blocks.
- Dashboard, auth, and admin pages — completely unchanged.
- Dark mode toggle — not added to frontend (intentionally light-only).
