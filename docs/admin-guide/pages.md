# Pages

Pages are standalone content items separate from the blog feed. Use them for static content like About, Contact, or Privacy Policy.

## Differences from posts

| Feature | Posts | Pages |
|---|---|---|
| Appears in blog feed | Yes | No |
| Categories & Tags | Yes | No |
| Editor | Rich text or Block editor | Block editor only |
| Comments | Configurable | No |
| Author | Yes | No |
| Routing | `/blog/{slug}` | `/{slug}` |

## Creating a page

Navigate to **Pages → New Page**. Fill in:

- **Title** — required. The slug is auto-generated and editable.
- **Status** — Draft or Published.

The page editor always uses the **block editor** — rich text is not available for pages. This is intentional: pages typically require custom layouts that benefit from the block system.

## Routing

Published pages are accessible at `/{slug}`. The page catch-all route resolves slugs after all other named routes, so a page named `about` lives at `/about`.

::: warning Reserved slugs
Do not create pages with slugs that conflict with built-in routes: `blog`, `feed`, `sitemap.xml`, `api`, `login`, `dashboard`, or `install`.
:::

## Preview

Like posts, pages have a **Preview** URL (`/preview/pages/{token}`) that works for drafts. Regenerate the token at any time from the sidebar.

## Autosave and revisions

Pages support the same autosave and revision system as posts — 30-second autosave intervals, up to 25 revisions per page, full restore capability.

## Navigation

To add a page to the navigation menu, go to **Navigation** and create a nav item linked to the page. Only published pages appear as options when using the `Page` nav item type.
