# Interactive Blocks

Interactive blocks add UI elements that visitors can interact with or navigate through.

## Link

A styled call-to-action button or text link.

| Setting | Description |
|---|---|
| Label | The link text |
| URL | Destination URL (absolute or relative) |
| Open in new tab | Adds `target="_blank" rel="noopener"` |
| Style | Button or inline text link |

Button styling (background, border, radius, padding) is controlled in the **Style** tab.

## Navigation

Renders the site's navigation menu as configured in **Admin → Navigation**.

| Setting | Description |
|---|---|
| Layout | Horizontal (row) or vertical (column) |
| Active link highlight | Highlight the link matching the current URL |
| Logo | Optional logo image displayed before the nav links |

The Navigation block is typically placed inside a **Section** at the top of header templates or partial templates that are included in other templates.

## Search

A search input form. Submitting the form navigates to `/?search={query}` (or a configurable route), which renders the **Default Search Results** template.

| Setting | Description |
|---|---|
| Placeholder | Input placeholder text |
| Button label | Search button text |
| Button visible | Toggle the submit button (pressing Enter still submits) |

## Pagination

Renders next/previous pagination links. Used automatically by Loop Posts when **Pagination** is enabled — you do not need to add this block manually in most cases. It can be placed explicitly for custom pagination placement.
