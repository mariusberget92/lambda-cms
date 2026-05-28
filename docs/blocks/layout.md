# Layout Blocks

## Section

A full-width page section. Wraps an inner constrained container. Use sections to create distinct horizontal bands on a page.

| Field | Default | Description |
|---|---|---|
| Padding Y | `10` | Vertical padding (Tailwind scale or CSS string) |
| Padding X | `0` | Horizontal padding |
| Full width | `false` | When true, inner content spans the full viewport width |
| Inner max width | `2xl` | Max width of the inner container (`sm` → `2xl` → `full`) |
| Min height | `auto` | `auto`, `screen` (100vh), or `1/2` (50vh) |

---

## Container

A flexible layout wrapper. Supports `flex`, `grid`, and `inline-flex` display modes.

### Flex mode

| Field | Description |
|---|---|
| Direction | `row` or `column` |
| Wrap | Whether items wrap to new lines |
| Justify | `start`, `center`, `end`, `between`, `around`, `evenly` |
| Align | `start`, `center`, `end`, `stretch` |
| Gap | CSS gap value |

### Grid mode

| Field | Description |
|---|---|
| Columns | Number of columns |
| Gap | CSS gap value |

### Per-block custom CSS

Use `customCss` on a Container to set `flex: 3;min-width:0` for proportional flex columns in a row layout — a common pattern for main + sidebar splits.

---

## Navigation

Renders a list of links. Three styles:

| Style | Appearance |
|---|---|
| `horizontal` | Inline row of text links |
| `vertical` | Stacked column of text links |
| `pills` | Pill-shaped bordered links with accent hover |

Links are configured in the Content tab. Each link has a label, URL, and optional "open in new tab" toggle.
