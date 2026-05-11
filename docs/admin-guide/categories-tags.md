# Categories & Tags

Categories and tags are two independent taxonomies for organizing posts. Both are managed from the admin sidebar.

## Categories

Categories represent broad topic groups. Each post can belong to multiple categories.

### Fields

| Field | Description |
|---|---|
| Name | Display name shown in listings and the blog UI |
| Slug | URL segment — auto-generated, editable |
| Description | Optional text shown on the category archive page |
| Color | Hex color used as a visual label in the admin UI |

### Category archive

Published categories have an archive page at `/blog/category/{slug}` that lists all published posts in that category. The archive uses the **Default Archive** system template by default, or a custom archive template if one is assigned.

### Post count

The category list shows the number of published posts assigned to each category. The count is updated automatically when posts are published, unpublished, or deleted.

## Tags

Tags represent specific topics within a post. They are lighter-weight than categories and a post can have many tags.

### Fields

| Field | Description |
|---|---|
| Name | Display name |
| Slug | URL segment — auto-generated, editable |

### Tag archive

Each tag has an archive page at `/blog/tag/{slug}`. Like categories, it uses the **Default Archive** template unless a custom one is configured.

## Deleting categories and tags

Deleting a category or tag detaches it from all posts — the posts themselves are not deleted. If the deleted taxonomy was the only category/tag on a post, that post will simply have none.
