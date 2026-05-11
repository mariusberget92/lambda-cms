# Layout Blocks

Layout blocks are structural containers. They hold other blocks and control how those blocks are arranged on the page.

## Section

The top-level layout wrapper. A Section maps to a `<section>` HTML element and spans the full width of the page.

**Use sections to:**
- Group related content
- Apply a background color or image to a full-width band
- Control vertical padding/margin for a content region

**Settings:**

| Setting | Description |
|---|---|
| Background color | Solid color fill |
| Background image | Image from Media Library or URL, with position and size controls |
| Background overlay | Semi-transparent color layer over a background image |
| Min height | Minimum height of the section (e.g. `100vh` for full-screen) |
| Padding | Top, right, bottom, left padding |

Sections can contain any number of **Container** or content blocks.

## Container

A Container is a constrained-width box inside a Section. It controls how its child blocks are laid out using either **Flexbox** or **CSS Grid**.

### Flex layout

| Setting | Description |
|---|---|
| Direction | Row or Column |
| Wrap | Whether children wrap to the next line |
| Justify content | `flex-start`, `center`, `flex-end`, `space-between`, `space-around` |
| Align items | `flex-start`, `center`, `flex-end`, `stretch` |
| Gap | Space between children |

### Grid layout

| Setting | Description |
|---|---|
| Columns | Number of grid columns (1–12) or a custom `grid-template-columns` value |
| Rows | Optional row template |
| Gap | Column and row gap |

### Max width

Set a max-width on the container to constrain content within a section (e.g. `1200px` for a centred content column).

### Nesting

Containers can nest other Containers to any depth, enabling complex multi-column and grid layouts. Use the [Layers panel](/block-editor/overview#layers-panel) to navigate deep nesting.
