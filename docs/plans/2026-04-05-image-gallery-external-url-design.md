# Image & Gallery Blocks — External URL Support Design

**Date:** 2026-04-05

## Goal

Allow the image block and gallery block to use external/custom URLs as an alternative to images from the media library.

## Approach

Mode toggle (Library | URL) — two pills above the image control. The active source is derived from block data; switching modes clears `media_id` but preserves any typed URL until a library pick replaces it.

## Data Shape

No schema changes. Both blocks already store `url` and `media_id`.

- **Library mode:** `media_id` is set, `url` is the CDN/storage URL from the media record.
- **URL mode:** `media_id` is `null`, `url` is whatever the user typed.

Switching Library → URL: sets `media_id: null`, keeps existing `url`.
Switching URL → Library: no-op until user actually picks from library (which then sets both).

## Image Block (`ImageSettings.vue`)

Toggle is derived (not stored): `media_id != null` → Library tab active; else → URL tab active.

- **Library mode:** unchanged — thumbnail preview + "Select / Change image" button + MediaPicker.
- **URL mode:** plain `<input type="text">` for the URL. Alt text and caption fields remain below in both modes.

## Gallery Block (`GallerySettings.vue`)

Thumbnail grid is unchanged. The add area below it replaces the single "+ Add image" button with an always-visible inline add-form:

- Two-pill toggle at the top: `Library | URL`
- **Library mode:** "Add from library" button → MediaPicker → on select, appends `{ media_id, url, alt }` to items.
- **URL mode:** URL text input + alt text input + "Add" button → on click, appends `{ media_id: null, url, alt }` to items and clears the inputs.

`addMode` is a local `ref('library')` — not persisted in block data. Resets to `'library'` each time a component is mounted.
