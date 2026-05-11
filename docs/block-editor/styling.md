# Styling

Every block in Lambda CMS has an independent **Style** tab in the settings panel. Styles are applied as inline CSS on the block's root element, so they are scoped to that block and don't affect anything else on the page.

## Typography

| Property | Options |
|---|---|
| Font family | System fonts and any web font available in the theme |
| Font size | px, rem, em, or viewport units |
| Font weight | 100–900 |
| Line height | Unitless multiplier or explicit value |
| Letter spacing | em or px |
| Text align | left, center, right, justify |
| Text decoration | none, underline, line-through |
| Text transform | none, uppercase, lowercase, capitalize |
| Color | Color picker or hex input |

## Colors and backgrounds

| Property | Options |
|---|---|
| Text color | Color picker |
| Background color | Color picker |
| Background image | Media Library or external URL |
| Background size | cover, contain, auto, or custom |
| Background position | Preset positions or custom x/y |
| Background repeat | no-repeat, repeat, repeat-x, repeat-y |

## Spacing

| Property | Description |
|---|---|
| Padding | Top, right, bottom, left (shorthand or individual) |
| Margin | Top, right, bottom, left |

All spacing values accept any valid CSS unit (px, rem, em, %, vh).

## Borders

| Property | Options |
|---|---|
| Border width | Per-side or uniform |
| Border style | solid, dashed, dotted, none |
| Border color | Color picker |
| Border radius | Uniform or per-corner, any CSS unit |

## Shadows

| Property | Options |
|---|---|
| Box shadow | Offset X/Y, blur, spread, color, inset toggle |
| Text shadow | Offset X/Y, blur, color |

## Width and height

| Property | Description |
|---|---|
| Width | CSS width value (auto, px, %, etc.) |
| Min width | Minimum width |
| Max width | Maximum width |
| Height | CSS height value |
| Min height | Minimum height |
| Max height | Maximum height |

## Overflow

Controls overflow behavior (visible, hidden, scroll, auto) for both axes.

## Responsive styles

::: info Coming soon
Per-breakpoint style overrides are planned for a future release. Currently, responsive layout is achieved by nesting blocks inside Containers with appropriate flex/grid settings.
:::

## Conditional visibility

In the **Advanced** tab, each block can be conditionally shown or hidden based on field bindings. For example: show a "No posts found" message only when the parent loop returns zero results.

| Condition | Description |
|---|---|
| Field equals | Show/hide when a bound field equals a specific value |
| Field is empty | Show/hide when a bound field has no value |
| Field is not empty | Show/hide when a bound field has a value |
