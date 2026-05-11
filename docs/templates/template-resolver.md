# Template Resolver

The Template Resolver (`app/Services/TemplateResolver.php`) is responsible for selecting the correct template to render for each public page request. It runs on every public route and returns the best matching template.

## Resolution order

For each request, the resolver selects the first matching template using this priority order:

1. **Custom template** — a non-system template of the correct type, if one exists.
2. **System template** — the built-in protected template for the type.

If multiple custom templates of the same type exist, the resolver picks the most recently updated one. To control which template is used for a specific route, use only one custom template of each type.

## Per-type resolution

### Blog index (`/`)

Looks for a `blog-index` template. Falls back to `default-blog-index`.

### Single post (`/blog/{slug}`)

Looks for a `single-post` template. Falls back to `default-single-post`.

The resolved post object is injected into the template context so that Post blocks and dynamic bindings have access to the post's data.

### Category archive (`/blog/category/{slug}`)

Looks for an `archive` template. Falls back to `default-archive`.

The resolved category object (including name, description, color, and slug) is injected into the template context. Archive Loop and Archive Header blocks use this context automatically.

### Tag archive (`/blog/tag/{slug}`)

Same as category archive but with the tag object injected.

### Search results (`/?search=query`)

Looks for a `search-results` template. Falls back to `default-search-results`.

The search query string is injected into the template context. The Loop Posts block configured with the `search_results` source uses this to filter posts.

### Pages (`/{slug}`)

Pages do not use the template system — they render their own block tree directly. The page's blocks are rendered as-is without wrapping in a template.

## Partial inclusion

Partials are not resolved by the router. They are included explicitly within other templates using the **Include Partial** option available on Section or Container blocks. The resolver fetches the named partial's blocks and renders them inline.

## Context injection

The resolver passes context to the block renderer so that dynamic blocks and bindings have access to:

| Context key | Available in |
|---|---|
| Current post | Single-post templates |
| Current category | Category archive templates |
| Current tag | Tag archive templates |
| Search query | Search-results templates |
| Paginator | Any template with a paginated Loop block |

Block types that consume context (Post Title, Post Body, Archive Header, etc.) read from this injected context rather than fetching data themselves.
