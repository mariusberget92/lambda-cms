# Content Blocks

Content blocks render static or media content. They are the building blocks of articles and landing pages.

## Paragraph

A block of body text. Supports inline formatting: bold, italic, underline, links. Use the **Content** tab to enter text. Typography and color are controlled in the **Style** tab.

## Heading

An HTML heading element (H1–H6). Set the heading level in the **Content** tab. Use one H1 per page for SEO.

## Image

Renders a single image. Settings:

| Setting | Description |
|---|---|
| Image | Select from Media Library or enter an external URL |
| Alt text | Overrides the media library alt text for this instance |
| Caption | Optional caption rendered below the image |
| Link | Optional URL — wraps the image in an `<a>` tag |
| Lazy load | Adds `loading="lazy"` to the `<img>` tag |

## Video

Embeds a video. Supports:

- **Upload** — a video file from the Media Library
- **External URL** — YouTube, Vimeo, or any direct `.mp4` URL

Settings include autoplay, muted, loop, and controls toggles.

## Gallery

A grid of images from the Media Library. Settings:

| Setting | Description |
|---|---|
| Images | Multi-select from the Media Library |
| Columns | Number of columns in the grid (1–6) |
| Gap | Space between images |
| Aspect ratio | Crop ratio applied uniformly to all images |

## Quote

A styled `<blockquote>`. Fields: quote text and optional attribution (author name and source).

## Code

A syntax-highlighted code block powered by CodeMirror 6. Settings:

| Setting | Description |
|---|---|
| Language | Syntax highlighting language (CSS, JS, PHP, etc.) |
| Content | The code to display |
| Show line numbers | Toggle line number gutter |
| Theme | One Dark (default) or other available themes |

## Table

A structured HTML table. Use the **Content** tab to add rows and columns. Toggle header row and striped rows in settings.

## Divider

A horizontal rule (`<hr>`). Style the color, thickness, and margin in the **Style** tab.

## Spacer

An invisible block that adds vertical whitespace. Set the height in the **Style** tab.
