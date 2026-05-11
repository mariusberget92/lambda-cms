# Post Blocks

Post blocks output data from a specific post. They are designed to be used inside [Loop Posts](/block-editor/dynamic-blocks#loop-posts) or in **single-post** templates, where the current post context is automatically available.

## Post Title

Renders the post's title. Behaves like a [Heading block](/block-editor/content-blocks#heading) — configure the heading level (H1–H6) in the settings.

## Post Body

Renders the full post body. For rich-text posts, this outputs the Tiptap HTML. For block-editor posts, this renders the embedded block tree.

## Featured Image

Renders the post's featured image. Settings mirror the [Image block](/block-editor/content-blocks#image) (alt text override, lazy load, link). Falls back gracefully if no featured image is set.

## Post Meta

A composite block showing post metadata. Toggle each field individually:

- Published date (formatted using the site date format setting)
- Author name
- Reading time estimate
- Category list (linked)
- Tag list (linked)
- Comment count

## Post Excerpt

Renders the post's excerpt field. If the excerpt is empty, falls back to an auto-generated excerpt from the first N characters of the post body.

## Author

Renders the post author's information. Fields:

| Field | Description |
|---|---|
| Avatar | Author's profile picture |
| Name | Author's display name |
| Bio | If available on the user model |

## Post Taxonomy

Renders the post's categories or tags as a list of linked chips/badges. Configure which taxonomy to display (categories or tags) and the visual style.

## Post Comments

Renders the comment thread for the current post, including the comment submission form. Only visible when comments are enabled globally and for the specific post.

Settings:

| Setting | Description |
|---|---|
| Show comment form | Toggle the submission form |
| Comments per page | Override the global setting for this block instance |

## Archive Header

Renders the title and description of the current archive context (category or tag). Intended for use in archive templates. Outputs the category/tag name as a heading and the description as body text.

## Search Results Header

Renders the current search query string and result count. Intended for use in search-results templates.
