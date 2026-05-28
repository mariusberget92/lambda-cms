# Block Reference

Lambda CMS ships with 30+ block types organised into five groups. Every block is available in both the post/page editor and the template editor.

## Groups

| Group | Blocks |
|---|---|
| [Content](/blocks/content) | Paragraph, Heading, Quote, Code, Divider, Spacer, HTML |
| [Media](/blocks/content#media) | Image, Gallery, Video, Embed |
| [Layout](/blocks/layout) | Section, Container, Navigation |
| [Data](/blocks/data) | Loop, Pagination, Post Card, Post Title, Post Body, Post Featured Image, Post Meta, Post Author, Post Taxonomy, Post Comments, Archive Title, Post List |
| [Site](/blocks/site) | Nav Header, Site Footer, Masthead, Band |
| [Interactive](/blocks/interactive) | Button, CTA, Link, Accordion, Tabs, Table, Search, Filter Link, Active Filter, Icon List, Template |

## Common Fields

All blocks share these optional fields available in the **Advanced** tab:

| Field | Description |
|---|---|
| Block label | Custom name shown in the Layers panel |
| Custom ID | HTML `id` attribute on the wrapper element |
| Custom classes | Space-separated CSS or Tailwind classes |
| Custom CSS | Scoped CSS rules (CodeMirror editor) |
| Animation | Entrance animation class |

## Conditions

Any block can be conditionally hidden based on a loop field value. Set a **field**, **operator**, and **value** in the **Conditions** tab. Supported operators: `equals`, `not equals`, `contains`, `starts with`, `ends with`, `is empty`, `is not empty`.
