# Creating Templates

## Creating a new template

Go to **Admin → Templates → New Template**. Fill in:

| Field | Description |
|---|---|
| Name | Human-readable label shown in the admin UI |
| Slug | URL-safe identifier — auto-generated from name, editable |
| Type | `partial`, `blog-index`, `single-post`, `archive`, or `search-results` |

After saving, you are taken to the block editor for this template.

## Building the layout

Use the block editor to compose the template's layout. The block library available in templates includes all block types, including post-specific and dynamic blocks.

### Blog-index templates

A blog-index template typically contains:

1. A **Section** with a **Navigation** block at the top.
2. A **Section** with a **Loop Posts** block as the main content area.
3. Optionally a sidebar **Container** with a **Post List** or **Loop Categories** block.
4. A **Section** with footer content at the bottom.

### Single-post templates

A single-post template typically contains:

1. A header section with a **Navigation** block.
2. A content section with **Post Title**, **Post Meta**, **Post Body**, and **Post Comments** blocks.
3. An optional related posts section using a **Loop Posts** block.

### Archive templates

An archive template typically contains:

1. A header section with a **Navigation** block.
2. An **Archive Header** block showing the category/tag name and description.
3. A **Loop Posts** (or **Archive Loop**) block for the posts.

### Search-results templates

A search-results template typically contains:

1. A header section with a **Search** block.
2. A **Search Results Header** block showing the query and result count.
3. A **Loop Posts** block configured with the `search_results` source.

### Partial templates

Partials contain whatever blocks should be reused. A header partial typically contains a **Section** with a **Navigation** block (and optionally a logo). A footer partial contains site info, links, and social icons.

## Autosave and revisions

Templates autosave every 30 seconds. Each explicit save creates a revision (up to 25 kept). Restore any revision from the **Revisions** panel in the editor sidebar.

## Duplicating a template

From the template list, use the duplicate action (⊕) to copy an existing template as a starting point. The duplicated template gets a new slug and is not marked as a system template, so it can be freely edited and deleted.

## Deleting a template

Custom templates can be deleted from the template list. System templates cannot be deleted. If you delete a custom template that was the active template for a given route, the system falls back to the default system template of the same type.
