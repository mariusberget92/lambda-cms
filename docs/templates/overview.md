# Templates Overview

Templates define the visual structure of public-facing pages. They are built with the same block editor used for posts and pages, and are resolved automatically by Lambda CMS based on the type of page being rendered.

## Template types

| Type | Used for | Route examples |
|---|---|---|
| `partial` | Reusable fragments (headers, footers, sidebars) | Included by other templates |
| `blog-index` | The main blog listing page | `/` |
| `single-post` | An individual blog post | `/blog/{slug}` |
| `archive` | Category and tag archive pages | `/blog/category/{slug}`, `/blog/tag/{slug}` |
| `search-results` | Search results page | `/?search=query` |

## System templates

Five system templates ship with Lambda CMS and are seeded automatically during installation:

| Slug | Type | Description |
|---|---|---|
| `post-card` | `partial` | The default post card layout used inside loop blocks |
| `default-blog-index` | `blog-index` | Blog home page with paginated post loop |
| `default-single-post` | `single-post` | Single post view with title, body, meta, and comments |
| `default-archive` | `archive` | Category/tag archive with post loop and archive header |
| `default-search-results` | `search-results` | Search results with query header and results loop |

System templates are **protected** — they cannot be deleted, and their type and slug cannot be changed. Their blocks can be edited.

## Custom templates

You can create additional templates of any type. Multiple templates of the same type can exist simultaneously; the [Template Resolver](/templates/template-resolver) determines which one is used.

## Template editor

Templates use the full block editor with autosave and revision history. Edit a template from **Admin → Templates → [template name]**.

## Partials

Partials are templates that are not rendered directly by the router; they are **included** inside other templates. Use them for shared elements like headers, footers, and sidebars to avoid duplicating block structures across multiple templates.

::: tip
The system `post-card` partial is a good example: it defines one post card layout once, and the Loop Posts block in the blog-index template references it.
:::
