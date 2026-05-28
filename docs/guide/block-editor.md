# Block Editor

The block editor is the core of Lambda CMS. Every post, page, and template is built from a tree of blocks — stored as JSON, rendered as Vue components on the frontend.

## The Canvas

The editor canvas is a full-screen drag-and-drop workspace. Blocks can be:

- **Dragged** to reorder within a container or moved across containers
- **Nested** — Container and Section blocks accept any other block as children
- **Collapsed** in the Layers panel to manage deep trees

Add a block using the **+** button at the bottom of any container, or via the block type palette.

## Block Anatomy

Every block shares the same structure:

```json
{
  "id": 1,
  "type": "heading",
  "data": { "level": 1, "text": "Hello World" },
  "children": [],
  "bindings": {},
  "customClasses": "",
  "customCss": ""
}
```

| Field | Description |
|---|---|
| `id` | Unique integer within the template |
| `type` | Block type identifier (e.g. `heading`, `loop`, `container`) |
| `data` | Block-specific configuration |
| `children` | Nested blocks (Container, Section, Loop only) |
| `bindings` | Dynamic field bindings (see below) |
| `customClasses` | Tailwind or custom CSS classes on the wrapper |
| `customCss` | Inline CSS applied to the block |

## Settings Tabs

Each block has up to four settings tabs:

- **Content** — block-specific fields: text, URLs, media, toggle options
- **Style** — typography, spacing, background, border, shadow, effects
- **Advanced** — custom ID, CSS classes, inline CSS (CodeMirror editor), animation
- **Conditions** — show/hide this block based on a loop field value

## Dynamic Field Bindings

Bindings let a block's fields pull their value from the surrounding loop or post context at render time. In the **Content** tab, any text or URL field shows a binding toggle.

Examples:
- A Heading block inside a Loop bound to `loop:title` renders each post's title
- An Image block bound to `loop:featured_image_url` renders each item's image
- A Link block bound to `loop:url` makes each card link to the correct post

## The Loop Block

The Loop block is the most powerful block in the system. It fetches data server-side from a configured source, then renders its child blocks once per item.

**Sources:** `posts`, `categories`, `tags`, `pages`

**Configuration:**

| Option | Description |
|---|---|
| Source | What to fetch |
| Filters | Field / operator / value rules (supports URL params) |
| Sort | Field and direction |
| Limit | Max items per page |
| Columns | Grid column count |
| Page param | URL query param used for pagination |

Child blocks receive the current item via Vue `provide/inject`, making all binding fields available automatically.

## Autosave & Revisions

The editor autosaves every 10 seconds of inactivity. A draft snapshot is stored in the database.

Revisions are saved on every explicit **Save**. Up to 25 revisions are kept per document. Click **Revisions** in the editor bar to browse and restore any previous version.
