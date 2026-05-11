# Dynamic Blocks

Dynamic blocks fetch and display content from the database at render time. They are the foundation of blog indexes, archive pages, and sidebar widgets.

## Loop Posts

Renders a collection of posts. This block is the backbone of blog index pages and is used in the default blog-index and archive system templates.

### Settings

| Setting | Description |
|---|---|
| Source | The data source to loop over (`posts`, `category_posts`, `tag_posts`, `search_results`) |
| Limit | Maximum number of posts to show per page |
| Pagination | Enable/disable paginated navigation |
| Order by | `published_at`, `title`, `comment_count` |
| Order direction | Ascending or descending |
| Filter by category | Restrict to one or more categories |
| Filter by tag | Restrict to one or more tags |
| Filter by status | `published` only (default) or include scheduled |

### Child blocks

The Loop Posts block defines a **template row** — the markup for one post card — using child blocks. Add [Post-specific blocks](/block-editor/post-blocks) inside Loop Posts to build the card layout. Each post-specific block automatically binds to the current iteration's post data.

### Dynamic bindings

Any block inside a loop can use [dynamic bindings](/block-editor/dynamic-bindings) to pull in post field values (title, excerpt, slug, featured image, published date, author, etc.).

## Loop Categories

Renders a list of categories, each with its name, post count, description, and color. Similar nesting model to Loop Posts — define a template row using child blocks.

| Setting | Description |
|---|---|
| Limit | Max categories to show |
| Order by | `name`, `post_count` |
| Order direction | Ascending or descending |

## Loop Tags

Renders a list of tags. Same structure as Loop Categories.

| Setting | Description |
|---|---|
| Limit | Max tags to show |
| Order by | `name`, `post_count` |

## Post List

A simpler, non-templated block that renders a plain list of post titles linking to their full post. Useful for sidebars and "Recent Posts" widgets.

| Setting | Description |
|---|---|
| Limit | Number of posts to show (default 5) |
| Order by | `published_at` (newest first) or `title` |
| Show date | Toggle published date next to each title |
| Show excerpt | Toggle excerpt below each title |

## Archive Loop

Used in archive templates (category and tag pages). Automatically resolves the current archive context (which category or tag the visitor is viewing) and loops over the matching posts. No manual filter configuration needed — context is injected by the [Template Resolver](/templates/template-resolver).
