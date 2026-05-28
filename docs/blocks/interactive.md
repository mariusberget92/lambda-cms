# Interactive Blocks

## Button

A styled call-to-action button or link.

| Field | Description |
|---|---|
| Label | Button text (bindable) |
| URL | Link href (bindable) |
| Variant | `filled`, `outline`, `ghost` |
| Size | `sm`, `md`, `lg` |
| Alignment | `left`, `center`, `right` |
| Full width | Stretches to container width |
| Icon | Optional Iconify icon with position and color |
| Custom bg / text color | Override default token colors |
| Border radius | Override default `--blog-radius` |

---

## CTA

A bordered call-to-action card with headline, body text, and a button.

| Field | Description |
|---|---|
| Headline | Card title (bindable) |
| Text | Body copy (bindable) |
| Button URL | Link href (bindable) |
| Button label | Button text (bindable) |

---

## Link

A wrapper block that turns its child blocks into a clickable link. Useful for making entire card areas clickable without extra markup.

| Field | Description |
|---|---|
| Label | Link text (used when no children) |
| URL | `href` (bindable) |
| Target | `_self` or `_blank` |

---

## Accordion

A collapsible FAQ or content panel group.

| Field | Description |
|---|---|
| Default state | `first-open`, `all-open`, `all-closed` |
| Border style | `bordered` (default), `separated`, `borderless` |

Add items as child blocks. Each item has a **title** field and accepts any block as its content.

---

## Tabs

A tabbed content panel.

| Field | Description |
|---|---|
| Tab style | `underline` (default), `pills`, `buttons` |
| Alignment | `left`, `center`, `right` |

Add tab items as children. Each item has a **label** and accepts any block as its content.

---

## Table

A data table. Supports both static (manually entered) and dynamic (loop-sourced) data.

| Field | Description |
|---|---|
| Mode | `static` or `dynamic` |
| Columns | Array of `{ id, label, align, prefix, suffix }` |
| Rows | Static data rows (static mode only) |
| Striped | Alternating row background |
| Header style | Filled header row |
| Border style | `full`, `outer`, `none` |
| Responsive | `scroll` (horizontal scroll) or `stack` (label-based stacking) |

---

## Search

A search input card. Submits to `/search?q=`.

| Field | Description |
|---|---|
| Placeholder | Input placeholder text |
| Button label | Submit button label |
| Scope | `posts` (only option currently) |
| Show heading | Toggle the "Search" heading (useful when a page-level H1 already provides context) |

---

## Filter Link

A link that appends a URL param to the current page, used to filter the Loop block. Two variants:

| Variant | Description |
|---|---|
| `list` | Text link with dot prefix — used for category lists |
| `pill` | Rounded pill tag — used for tag clouds |

| Field | Description |
|---|---|
| Param name | URL query param (e.g. `category`, `tag`) |
| Label | Link text (bindable via `loop:name`) |

The active state is detected from the current URL automatically.

---

## Active Filter

Shows the currently active filter (category or tag) with a clear button. Displays a default title (e.g. "Latest Posts") when no filter is active.

| Field | Description |
|---|---|
| Default title | Text shown when no filter is active |

---

## Icon List

A list of items each with an optional Iconify icon.

| Field | Description |
|---|---|
| Direction | `vertical` or `horizontal` |
| Items | Array of `{ text, icon, iconSize, iconColor }` |
| Default icon size | Applied to all items without an individual size |
| Gap | Spacing between items |
