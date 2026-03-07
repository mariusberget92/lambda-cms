# SEO Meta Keywords — Design

**Date:** 2026-03-07
**Status:** Approved

## Overview

Add `meta_keywords` support to the CMS — a nullable per-post field plus a global site default in settings. Keywords are rendered as a `<meta name="keywords">` tag in the document `<head>` via the existing `SeoHead` component.

## Approach

Option A (simple text field) was chosen. A comma-separated string stored on the post and in settings, consistent with the existing `meta_title`/`meta_description` pattern.

## Database

- **Migration:** Add nullable `meta_keywords` string column to `posts` table.
- **Settings seeder:** Add `seo.default_keywords` row (group: `seo`, type: `string`, value: `''`).

## Backend

### Post model
- Add `meta_keywords` to `$fillable`.

### PostController
- Add `meta_keywords` to validation in `store()` and `update()`: nullable, string, max:255.
- Passes through automatically as part of the post object to `create()` and `edit()`.

### SettingsController
- Add `seo.default_keywords` to the SEO group validation and save logic in `update()`.

### BlogController
- Add `keywords` key to the `$seo` array in both `index()` and `show()`.
- Fallback chain: `post->meta_keywords → Setting::get('seo.default_keywords', '')`.

## Frontend

### SeoHead.vue
Add one conditionally rendered meta tag:
```html
<meta name="keywords" :content="seo.keywords" v-if="seo.keywords" />
```

### Settings/Index.vue
Add a text input to the existing SEO panel:
- Label: "Default keywords"
- Placeholder: "e.g. laravel, cms, blog"
- Bound to `seoForm.seo_default_keywords`

### Posts/Create.vue & Posts/Edit.vue
Add a text input to the SEO sidebar panel, below `meta_description`:
- Label: "Keywords"
- Placeholder: "Leave blank to use site defaults"
- `maxlength="255"` with character counter
- Bound to `form.meta_keywords`

## Fallback Chain

```
post.meta_keywords → seo.default_keywords → (omit tag)
```

The `<meta name="keywords">` tag is only rendered when the resolved value is non-empty.

## Out of Scope

- Tag-to-keyword auto-derivation (deliberate — keeps SEO and taxonomy decoupled)
- Token/chip UI (unnecessary complexity for this audience)
- Keywords on the blog index page (global default only applies to post show pages)
