# Media Library Improvements — Design

**Date:** 2026-03-28

## Overview

Seven improvements to bring the media library up to par: fix bulk selection UX, add "Used in" references, add a lightbox, add file type badges, improve upload guidance, add a description character count, toast on copy URL, and a mobile-friendly detail panel.

---

## 1. Grid Selection (Checkbox on Hover)

**Problem:** A single click on a grid tile both opens the detail panel and selects the item — two conflicting intents in one gesture.

**Solution:**
- Each thumbnail gets an absolutely-positioned checkbox (`top-2 left-2`) visible on hover or when already selected.
- Clicking the **checkbox** toggles selection (`e.stopPropagation()` prevents bubbling to the tile click handler).
- Clicking **anywhere else** on the tile opens the detail panel — no change to existing behaviour.
- A **"Select all" / "Deselect all"** button appears in the toolbar. When any item is selected the label becomes "Deselect all".
- The existing bulk-action bar (approve/delete) surfaces as normal once items are checked.
- `selected` and `activeItem` refs are already separate in the code — this is a template-only change plus the stopPropagation guard.

---

## 2. "Used In" References

**Problem:** Users have no way to know which posts use a media file before deleting it.

**Backend:**
- New route: `GET /media/{media}/usage` → `MediaController@usage`
- Returns `[{ id, title, slug }]` of posts where `featured_image_id = media->id`
- Scope: featured images only (inline TipTap image references deferred — would require full-text search)

**Frontend — Detail Panel:**
- When the detail panel opens, fire a `fetch` to `/media/{id}/usage`
- While loading: skeleton placeholder in the "Used in" section
- When loaded:
  - If empty: "Not used anywhere" in muted text
  - If used: small list of post title links, each opening the post edit page in a new tab
- Store in a `usedIn` ref; reset to `null` when panel closes

**Frontend — Delete Modal:**
- Before showing the delete confirmation, check `usedIn`
- If `usedIn.length > 0`: modal body includes *"This file is used as the featured image in: [Post A], [Post B]. Deleting it will remove the featured image from those posts."*
- Delete button remains available (DB `nullOnDelete` already handles the cascade)

---

## 3. Lightbox

**Component:** `resources/js/Pages/Media/MediaLightbox.vue` (new)

**Trigger:** Clicking the preview image in the detail panel (images only). Preview image gets `cursor-zoom-in`.

**Structure:**
- Fixed fullscreen overlay: `bg-black/90 z-50`
- Image centered: `max-h-screen max-w-[90vw] object-contain`
- Filename + position counter at bottom: `3 / 24`
- Close: ✕ button (top-right) + `Escape` key
- Navigation: left/right arrow buttons + keyboard `←` / `→`
- Navigates through the **current filtered image set only** (non-image files skipped)
- Receives `images` array (filtered from current media page) and `startIndex`

---

## 4. File Type Badges

- Small pill label in the **bottom-right** corner of non-image grid tiles
- Derived from `mime_type` (e.g. `application/pdf` → `PDF`, `video/mp4` → `MP4`, `audio/mpeg` → `MP3`, `application/msword` → `DOC`)
- Images: no badge (type is visually obvious from the thumbnail)
- Fallback: show file extension from `original_filename` if mime mapping unknown

---

## 5. Upload Guidance

- A single line of helper text below the drop zone:
  *"Accepted: JPG, PNG, GIF, WebP, SVG, PDF, MP4, MP3 · Max {n} MB"*
- `{n}` passed as a page prop from `MediaController@index` (already stored in `Settings`)
- Text styled as `text-xs text-muted-foreground`

---

## 6. Description Character Count

- Right-aligned `{{ descriptionDraft.length }} / 2000` below the description textarea
- Styled `text-xs text-muted-foreground`
- Turns `text-destructive` when ≥ 1900 characters

---

## 7. Toast on Copy URL

- Add `notify('URL copied', 'success')` in the existing copy handler alongside the icon swap
- No other changes to the copy flow

---

## 8. Mobile Detail Panel (Bottom Sheet)

- On `< md` breakpoint the detail panel is hidden by default and does not render in the sidebar
- When an item is tapped on mobile, a bottom sheet slides up (`translate-y-0` transition)
- Bottom sheet: `fixed bottom-0 inset-x-0 z-40`, rounded top corners, max-height `85vh`, scrollable
- Drag handle at top centre (visual only)
- Close button in top-right corner; also closes on backdrop tap
- On `md+` breakpoint: existing right sidebar behaviour unchanged

---

## Files Changed

### New files
- `resources/js/Pages/Media/MediaLightbox.vue`

### Modified files
- `resources/js/Pages/Media/Index.vue` — all frontend changes
- `app/Http/Controllers/MediaController.php` — `usage()` method + `maxUploadMb` prop in `index()`
- `routes/web.php` — new `GET /media/{media}/usage` route

---

## Out of Scope

- Inline TipTap image reference tracking ("used in" for body images)
- Keyboard navigation through the grid (arrow keys)
- List view toggle
- Duplicate file detection
- S3 / cloud storage
