# Data Blocks

Data blocks are context-aware — they read from the surrounding Loop or the current post context to display dynamic content.

## Loop

The most powerful block. Fetches items from a source, applies filters, and renders child blocks once per item.

| Field | Description |
|---|---|
| Source | `posts`, `categories`, `tags`, or `pages` |
| Filters | Array of `{ field, op, value, urlParam }` rules |
| Filter logic | `and` / `or` |
| Sort | `{ field, direction }` |
| Limit | Max items to fetch |
| Columns | Grid columns for the rendered items |
| Gap | Gap between items (`sm`, `md`, `lg`, or CSS value) |
| Page param | URL query param used for pagination (e.g. `page`) |

### Filter operators

`=`, `!=`, `contains`, `starts_with`, `ends_with`

### URL param filters

Set `urlParam` on a filter to read the value from the current URL query string at render time. This powers the category/tag filtering on the blog index.

---

## Pagination

Renders page navigation for the nearest parent Loop block that uses a `pageParam`.

| Field | Description |
|---|---|
| Page param | Must match the Loop's `pageParam` |
| Style | `numbered` or `prev-next` |
| Alignment | `left`, `center`, `right` |
| Button style | `filled`, `outline`, `ghost` |

---

## Template

Embeds a saved template (partial) inline. The embedded template re-inherits the surrounding loop context, so all bindings inside it work correctly.

| Field | Description |
|---|---|
| Template | Select a partial template by name |

---

## Post Card

A pre-styled terminal-themed card for post listings. Used inside the Post Card partial template. Reads from `loopItem` context — must be inside a Loop block or the Post Card partial.

Displays: filepath, callsign, issue number, category glyph, post title, excerpt, author, date, reading time.

---

## Post Title

Renders the current post's `<h1>` title with a back-to-blog link. Used in single-post templates.

---

## Post Body

Renders the current post's body content. Used in single-post templates. Applies prose styling with all colors driven by design tokens.

---

## Post Featured Image

Renders the current post's featured image. Two variants:

| Variant | Description |
|---|---|
| `hero` | Full-bleed with gradient fade, cinematic aspect ratio |
| `default` | Standard responsive image with border-radius |

---

## Post Meta

Renders author name/avatar, publish date, and estimated reading time. Shown as a flat row with a bottom border (designed to sit inside the unified post card shell).

| Field | Description |
|---|---|
| Show date | Toggle publish date |
| Show author | Toggle author line |
| Show read time | Toggle reading time estimate |

---

## Post Taxonomy

Renders the post's categories and tags as linked chips. Shown below Post Meta with a bottom border separator.

| Field | Description |
|---|---|
| Show categories | Toggle category chips |
| Show tags | Toggle tag chips |

---

## Post Author

Renders the post author's avatar and name. Standalone block — lighter-weight than Post Meta.

---

## Post Comments

Renders the approved comment thread with a load-more paginator. Only appears when comments are enabled for the post.

---

## Post List

A grid of post cards fetched from `block.data.resolved.posts`. Simpler than Loop + Post Card — use when you need a quick post grid without template-level customisation.

---

## Archive Title

Renders the archive page heading — shows the category or tag name with a dark terminal card treatment.

Used in `archive` templates. Reads from `archiveContext` (injected by the archive controller).
