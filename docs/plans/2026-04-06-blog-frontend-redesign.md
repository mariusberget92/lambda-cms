# Blog Frontend Redesign — Modern Editorial

**Date:** 2026-04-06
**Status:** Approved

## Goal

Make the public blog frontend visually appealing without replacing the Nord-based default theme. All color changes must use existing CSS variables so users can retheme by changing a single token.

## Aesthetic Direction

Mix of editorial/magazine (Substack, Medium — bold typography, generous whitespace) and modern tech blog (Vercel, Linear — subtle gradients, glass effects, micro-animations). More color, more personality, still a clean default.

---

## Section 1: Header

- Strengthen the existing `backdrop-blur` with `bg-gradient-to-b from-card to-card/80`
- Add a more prominent bottom shadow so the header feels "lifted" when scrolling
- No structural changes — links and nav items stay as-is

## Section 2: Hero Strip

Biggest visual change. Replace the current whisper-thin `bg-primary/5` strip with a full dark gradient panel:

- Background: `bg-gradient-to-br from-[#2e3440] to-[#3b4252]` (Nord dark tones)
- A radial glow blob in `primary` blue sits in the background for depth
- Site name: large white type (`text-4xl font-bold`)
- Tagline: muted foreground below
- Left-border accent stays, gets thicker and slightly glows
- Bottom edge fades into the page background (gradient fade) — no hard cut

## Section 3: Post Cards (PostCard.vue)

**Hover animation:**
- `-translate-y-1` lift + `shadow-lg` shadow on hover
- Left border flashes from transparent to `primary` on hover
- All transitions `duration-300 ease-out`

**Featured image:**
- Taller: `h-56` (was `h-48`)
- Gradient overlay at bottom: `transparent → card` so title blends in
- Image scale on hover: `scale-110` (was `scale-105`)

**Typography:**
- Title: `text-2xl font-bold` (was `text-xl font-semibold`)

**"Read more" link:**
- Becomes a pill button: `border border-primary text-primary rounded-full px-3 py-1 text-xs hover:bg-primary hover:text-white transition-colors`

**No-image cards:**
- Subtle gradient background: `bg-gradient-to-br from-primary/5 to-accent/5`

## Section 4: Sidebar (BlogSidebar.vue)

- Section headers: `bg-primary/8` strip behind label (gradient pill feel)
- Category rows: `hover:bg-primary/5 rounded-md px-2 -mx-2` wash on hover
- Tag cloud pills: `hover:bg-primary hover:text-white hover:border-primary` filled state

## Section 5: Pagination

- Active page button: gradient `bg-gradient-to-r from-primary to-accent` instead of flat `bg-primary`
- Inactive buttons: subtle `hover:bg-muted` fill

## Section 6: Typography & Spacing

- Excerpt text: `leading-relaxed`
- Meta row dates/labels: slightly warmer `text-muted-foreground` (no token change needed, already correct)

---

## Files to Modify

| File | Changes |
|------|---------|
| `resources/js/Layouts/BlogLayout.vue` | Header shadow, hero gradient, hero typography, bottom fade |
| `resources/js/Components/PostCard.vue` | Card hover, image height/overlay, title size, Read more pill, no-image gradient |
| `resources/js/Components/BlogSidebar.vue` | Header pills, category hover, tag hover fill |
| `resources/js/Pages/Blog/Index.vue` | Pagination gradient active state |
| `resources/js/Pages/Blog/Archive.vue` | Pagination gradient active state |
| `resources/js/Pages/Blog/Search.vue` | Pagination gradient active state |

## Constraints

- All colors via existing CSS variables (`--primary`, `--accent`, `--card`, etc.) — no hardcoded hex except in the hero gradient which deliberately uses Nord dark tones
- No new dependencies
- No changes to admin UI
- No changes to PHP/Laravel layer
