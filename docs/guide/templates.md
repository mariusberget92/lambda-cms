# Template System

Templates are named collections of blocks that control how each public page type looks. Lambda CMS ships with 7 system templates that cover every page on the blog.

## Template Types

| Type | URL it renders | Can delete |
|---|---|---|
| `header` | Every public page (top) | No |
| `footer` | Every public page (bottom) | No |
| `blog-index` | `/` | No |
| `single-post` | `/blog/{slug}` | No |
| `archive` | `/blog/category/{slug}`, `/blog/tag/{slug}` | No |
| `search-results` | `/search` | No |
| `partial` | Embedded via Template block | No (system), Yes (custom) |

System templates (marked with a lock icon) cannot be deleted but can be freely edited in the block editor.

## How Templates Are Resolved

At request time, Lambda CMS looks up the **published** template of each type. If you publish a new `blog-index` template, it becomes active immediately — the previous one is automatically drafted.

Header and footer templates are shared to every page as Inertia props and rendered by `BlogLayout.vue` around the main page content.

## Creating Custom Templates

Go to **Templates → New Template** and choose a type. Custom templates work identically to system templates — the same block editor, autosave, and revision history.

For `blog-index`, `archive`, and `search-results` types, a **loop source** selector appears so the editor knows what context to provide for dynamic bindings. For `single-post`, `header`, and `footer` types, this is hidden (no loop source needed).

## Partials

A `partial` template is a reusable component embedded in other templates via the **Template block**. The system Post Card is a partial — it's embedded inside the Loop block in the blog index template.

Partials correctly re-inherit the loop context of their parent, so bindings inside a partial work exactly as they would if the blocks were written inline.

**To update the Post Card design:**
1. Go to **Templates** and find **Post Card**
2. Edit the blocks in the block editor
3. Save — the change propagates to every template using it

## Design Tokens

All block components use CSS custom properties from the blog design system. The full token set:

| Token | Purpose |
|---|---|
| `--bg` | Page background |
| `--panel` | Card / surface background |
| `--ink` | Primary text |
| `--soft` | Secondary / muted text |
| `--line` | Subtle border / separator |
| `--line-strong` | Visible border |
| `--accent` | Interactive color (buttons, links, active states) |
| `--accent-ink` | Text on accent backgrounds |
| `--code` | Dark panel background |
| `--code-ink` | Text on dark panels |
| `--blog-radius` | Border radius |
| `--gutter` | Horizontal spacing rhythm |

The `--accent` token is driven by the **Accent color** setting in the admin. Changing it updates the live blog frontend without a redeploy.
