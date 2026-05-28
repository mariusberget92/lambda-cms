# Site Blocks

Site blocks are used in `header`, `footer`, and `blog-index` templates.

## Nav Header

The sticky navigation bar. Included in the Default Header template.

| Field | Default | Description |
|---|---|---|
| Logo text | Site name | Brand label next to the λ mark |
| Show search | `true` | Toggles the search pill and Cmd+K overlay |
| Sticky | `true` | Fixes the header to the top of the viewport on scroll |

The search overlay opens on click or `Cmd/Ctrl+K`, fetches results live via the API, and closes on `Esc` or outside click.

Nav links come from the **Navigation** admin (`/navigation`), not from block data — manage them there.

---

## Site Footer

The page footer. Included in the Default Footer template.

| Field | Description |
|---|---|
| Tagline | Optional tagline below the brand |
| Copyright | Copyright line (defaults to current year + site name) |
| Show RSS | Toggle RSS / Sitemap links in the bottom row |
| Columns | Array of `{ heading, links[] }` for footer link groups |

---

## Masthead

A full-width dark hero section. Used at the top of the Default Blog Index template.

| Field | Description |
|---|---|
| Eyebrow | Small mono label above the title |
| Title | Large display title. Wrap a word in `\|\|double pipes\|\|` to render it as an inline monospace accent span |
| Subtitle | Subheading below the title |
| Stats | Array of `{ label, value }` stat cards |
| Accent word | Small accented label in the top-right corner |

**Title syntax:**

```
Share your ideas ||with the world||
```

Renders "Share your ideas" in display font, then `<with the world />` as a monospace accent element.

---

## Band

A tag cloud component. Two variants:

| Variant | Description |
|---|---|
| `band` | Full-width dark panel with grid pattern overlay. Used as a standalone section. |
| `widget` | Light card matching the sidebar style. Used in sidebar containers. |

| Field | Description |
|---|---|
| Label | Heading above the tags |
| Limit | Max number of tags to show |
| Show count | Toggle post count badge per tag |

Tags are fetched live via the API — no server-side data required.
