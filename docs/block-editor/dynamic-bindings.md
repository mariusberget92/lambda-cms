# Dynamic Bindings

Dynamic bindings connect block content to data from the current loop or post context. Instead of hard-coding a value in a block's **Content** tab, you bind the block to a data field that is populated at render time.

## How bindings work

When a block is inside a [Loop Posts](/block-editor/dynamic-blocks#loop-posts) block, each iteration provides a **post object** as the loop context. Any block inside the loop can bind one of its content fields to a field on that post object.

For example:
- A **Heading** block binds its text to `post.title` → each card renders with the correct post title.
- An **Image** block binds its `src` to `post.featured_image.url` → each card shows the correct image.
- A **Link** block binds its `href` to `post.url` → each card links to the right post.

## Binding a field

In the block's **Content** tab, look for the binding toggle (⚡) next to any text or URL field. Click it to switch from a static value to a binding selector. Choose the field from the dropdown.

## Available post fields

When inside a Loop Posts or single-post template context:

| Binding key | Value |
|---|---|
| `post.title` | Post title |
| `post.excerpt` | Post excerpt |
| `post.slug` | Post slug |
| `post.url` | Full public URL |
| `post.published_at` | Published date (formatted) |
| `post.featured_image.url` | Featured image URL |
| `post.featured_image.alt` | Featured image alt text |
| `post.author.name` | Author display name |
| `post.author.avatar` | Author avatar URL |
| `post.comment_count` | Number of approved comments |
| `post.categories` | List of category names |
| `post.tags` | List of tag names |
| `post.reading_time` | Estimated reading time in minutes |

## Available category/tag fields

When inside a Loop Categories or Loop Tags context:

| Binding key | Value |
|---|---|
| `category.name` | Category name |
| `category.slug` | Category slug |
| `category.url` | Full archive URL |
| `category.description` | Category description |
| `category.color` | Category hex color |
| `category.post_count` | Number of published posts |
| `tag.name` | Tag name |
| `tag.slug` | Tag slug |
| `tag.url` | Full archive URL |
| `tag.post_count` | Number of published posts |

## Conditional visibility with bindings

In the **Advanced** tab, use bindings to conditionally show or hide a block:

- **Show when `post.featured_image.url` is not empty** — only show the image block when the post has a featured image.
- **Show when `category.description` is not empty** — only show the description block when a category has a description.

This prevents empty elements from rendering in the DOM.

## Loop source context

The available binding keys depend on the **loop source** set on the parent Loop block. The binding selector automatically shows only fields that are relevant to the current loop source.
