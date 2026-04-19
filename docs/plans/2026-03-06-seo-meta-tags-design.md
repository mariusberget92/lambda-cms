# SEO Meta Tags — Design

**Date:** 2026-03-06
**Status:** Approved

## Overview

Add two-level SEO meta tag support to the public blog: global defaults via the Settings system, with per-post nullable overrides on individual posts. Covers `<title>`, `<meta name="description">`, `<link rel="canonical">`, and Open Graph tags (`og:title`, `og:description`, `og:image`, `og:type`, `og:url`).

---

## Decisions

- **Scope:** Core + Open Graph (B). No Twitter Cards or robots noindex — YAGNI.
- **OG image:** Reuses the post's featured image. Falls back to a global default URL stored in settings. No separate per-post OG image picker.
- **Title construction:** `{meta_title or post.title} | {site.name}` — the site name suffix is always appended using a configurable separator. `meta_title` overrides only the first portion.
- **Architecture:** Shared `SeoHead.vue` component (Approach B) — single source of truth for all meta tag rendering, used by both `Blog/Index.vue` and `Blog/Show.vue`.

---

## Section 1 — Database & Model

### Migration

Add two nullable string columns to the `posts` table:

| column | type | nullable | notes |
|--------|------|----------|-------|
| `meta_title` | string | yes | overrides the title portion of `<title>` |
| `meta_description` | string (text) | yes | overrides excerpt as meta description |

### Post model

- Both fields added to `$fillable`.
- No cast required (plain strings).
- `PostFactory` adds both as `null` defaults.

### SettingsSeeder

New `seo` group — three new rows (via `insertOrIgnore`):

| key | type | default |
|-----|------|---------|
| `seo.title_separator` | string | ` \| ` |
| `seo.default_description` | string | `''` |
| `seo.default_og_image_url` | string | `''` |

No new model or migration for the global OG image — stored as a plain URL string, consistent with all other settings values.

---

## Section 2 — Backend

### BlogController::show()

Resolves all SEO values server-side and passes a single `seo` prop:

```php
$separator = Setting::get('seo.title_separator', ' | ');
$siteName  = Setting::get('site.name', config('app.name'));

$seo = [
    'title'       => ($post->meta_title ?: $post->title) . $separator . $siteName,
    'description' => $post->meta_description ?: $post->excerpt ?: Setting::get('seo.default_description', ''),
    'image'       => $post->featuredImage?->url ?: Setting::get('seo.default_og_image_url', ''),
    'canonical'   => url("/blog/{$post->slug}"),
    'type'        => 'article',
];
```

### BlogController::index()

Global defaults only (no per-post fields):

```php
$seo = [
    'title'       => Setting::get('site.name', config('app.name')),
    'description' => Setting::get('seo.default_description', ''),
    'image'       => Setting::get('seo.default_og_image_url', ''),
    'canonical'   => url('/blog'),
    'type'        => 'website',
];
```

### PostController store/update

Two new validated nullable fields added to both methods:

```php
'meta_title'       => ['nullable', 'string', 'max:100'],
'meta_description' => ['nullable', 'string', 'max:300'],
```

`PostController::edit()` passes `meta_title` and `meta_description` to the Vue component alongside existing fields.

---

## Section 3 — Frontend

### SeoHead.vue (new)

`resources/js/Components/SeoHead.vue` — single source of truth for all meta tag rendering:

```vue
<script setup>
import { Head } from '@inertiajs/vue3'
defineProps({ seo: Object })
</script>

<template>
  <Head>
    <title>{{ seo.title }}</title>
    <meta name="description"        :content="seo.description"  v-if="seo.description" />
    <link rel="canonical"           :href="seo.canonical" />
    <meta property="og:type"        :content="seo.type ?? 'website'" />
    <meta property="og:url"         :content="seo.canonical" />
    <meta property="og:title"       :content="seo.title" />
    <meta property="og:description" :content="seo.description"  v-if="seo.description" />
    <meta property="og:image"       :content="seo.image"        v-if="seo.image" />
  </Head>
</template>
```

### Blog/Show.vue and Blog/Index.vue

- Accept a new `seo` prop (Object).
- Render `<SeoHead :seo="seo" />` at the top of `<template>`.
- Remove the existing bare `<title>` / `<Head>` usage (currently none — these pages have no Head at all).

### Posts/Create.vue and Posts/Edit.vue

New **SEO** sidebar panel added below the Comments panel. Contains:

- `meta_title` text input — `max="100"`, live character counter, hint: "Leave blank to use post title"
- `meta_description` textarea — `max="300"`, live character counter, hint: "Leave blank to use excerpt"

Both fields added to `useForm` with `null` defaults (Create) or `props.post.meta_title ?? null` / `props.post.meta_description ?? null` (Edit).

### Settings/Index.vue

New **SEO** panel (same card style as existing panels), with its own `useForm` and `PUT settings.update('seo')` submission:

- `seo.title_separator` — text input
- `seo.default_description` — textarea
- `seo.default_og_image_url` — URL text input

---

## Fallback Chain Summary

| Tag | Resolution order |
|-----|-----------------|
| `<title>` | `(meta_title \|\| post.title)` + separator + `site.name` |
| `meta description` | `meta_description` → `excerpt` → `seo.default_description` |
| `og:image` | `featured_image.url` → `seo.default_og_image_url` |
| `canonical` | Always `url("/blog/{slug}")` (or `url('/blog')` on index) |
