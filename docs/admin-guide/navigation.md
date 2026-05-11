# Navigation

The Navigation panel lets you build the site's main menu. Nav items are rendered wherever your template includes a **Navigation block**.

## Nav item types

| Type | Description |
|---|---|
| **Page** | Links to a published Lambda CMS page. The URL is resolved automatically from the page's slug. |
| **Custom** | A free-form label and URL — use for external links or any route not backed by a page. |

## Managing nav items

Go to **Navigation** in the admin sidebar. From here you can:

- **Add** a new nav item — choose the type, enter a label, and pick a page or enter a custom URL.
- **Reorder** items — drag and drop rows to change the menu order. The order is saved immediately.
- **Edit** an existing item — click the item to change its label, type, or destination.
- **Delete** an item — removes it from the menu immediately. The linked page is not affected.

## Published pages only

When the nav item type is **Page**, only currently **published** pages appear as options in the dropdown. Drafts are excluded to prevent dead links in the public menu.

## Rendering in templates

Add a **Navigation** block to any template to render the menu. The block outputs the nav items in their defined order. See [Interactive Blocks](/block-editor/interactive-blocks) for styling and configuration options.
