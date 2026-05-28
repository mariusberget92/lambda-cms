# Content & Media Blocks

## Paragraph

Renders rich HTML content. Supports dynamic field binding for the `content` field (useful inside loops).

| Field | Description |
|---|---|
| Content | HTML string (supports full Tiptap output) |
| Icon | Optional prefix/suffix icon (Iconify) |

---

## Heading

Renders an `<h1>`–`<h6>` tag.

| Field | Description |
|---|---|
| Level | 1–6 |
| Text | Heading text (bindable) |
| Icon | Optional prefix/suffix icon |

---

## Quote

Renders a `<blockquote>` with optional attribution. Styled with a left accent border using `var(--accent)`.

| Field | Description |
|---|---|
| Text | Quote body (bindable) |
| Attribution | Optional author line (bindable) |

---

## Code

Renders a syntax-highlighted code block with a language label header.

| Field | Description |
|---|---|
| Language | Display label (e.g. `javascript`, `php`) |
| Code | The code content |

---

## Divider

A horizontal separator. Three styles:

| Style | Renders |
|---|---|
| `line` | Full-width `<hr>` |
| `dots` | Three centred dots |
| `space` | Blank vertical spacer |

---

## Spacer

An explicit vertical gap. Configure height as a CSS value (e.g. `2rem`, `48px`) or using responsive Tailwind integer values.

---

## HTML

Renders raw HTML. **Admin-only** — not available to regular users. Use for embedding scripts, iframes, or custom markup that doesn't fit a standard block.

---

## Image

| Field | Description |
|---|---|
| URL | Media library image or external URL (bindable) |
| Alt text | `alt` attribute (bindable) |
| Caption | Optional `<figcaption>` |
| Max height | CSS max-height value |
| Aspect ratio | CSS aspect-ratio value |

---

## Gallery

A responsive image grid. Automatically uses 1, 2, or 3 columns based on item count. Configure items in the Content tab — each item has a media ID, URL, and alt text.

---

## Video

Paste a YouTube or Vimeo URL. The block auto-extracts the embed URL and renders a responsive `<iframe>`.

| Field | Description |
|---|---|
| URL | YouTube or Vimeo URL (bindable) |
| Caption | Optional caption |

---

## Embed

Generic iframe embed for any URL.

| Field | Description |
|---|---|
| URL | Embed source URL |
| Aspect ratio | CSS aspect-ratio (default `16/9`) |
| Max width | Optional max width in pixels |
| Caption | Optional caption |
