# Block Editor Overview

The block editor is Lambda CMS's visual page builder. It replaces (or supplements) the rich-text editor for pages and, optionally, for posts. It lets you compose layouts from a library of 30+ typed blocks, each independently styled.

## Opening the editor

- **Pages** always use the block editor.
- **Posts** can use either the rich-text editor or the block editor. Switch using the **Switch editor** toggle on the edit page. Switching clears existing content.
- **Templates** always use the block editor.

## Canvas

The canvas is the central editing area. Blocks are added by clicking the **+** button that appears between blocks or in empty containers. Each block on the canvas can be:

- **Selected** — click a block to reveal its settings panel in the right sidebar.
- **Moved** — drag the handle (⠿) to reorder or re-nest blocks.
- **Duplicated** — via the block toolbar.
- **Deleted** — via the block toolbar.

Blocks can be nested to any depth inside **Section** and **Container** layout blocks.

## Layers panel

The layers panel (left sidebar) shows a tree view of all blocks on the canvas. It mirrors the nested structure and is useful for:

- Navigating deeply nested layouts.
- Selecting blocks that are hard to click on the canvas.
- Getting a structural overview of the page.

Click any item in the layers panel to select that block and open its settings.

## Settings panel

When a block is selected, the right sidebar shows its settings. Settings are divided into tabs:

- **Content** — block-specific inputs (text, image, link, etc.)
- **Style** — typography, colors, backgrounds, spacing, borders, shadows
- **Advanced** — conditional visibility rules

## Autosave and revisions

The block editor autosaves every 30 seconds. Changes are also saved to a revision history (up to 25 revisions) each time you explicitly save. Use the **Revisions** panel to browse and restore previous versions.

## Block categories

Blocks are organized into five categories:

| Category | Examples |
|---|---|
| [Content](/block-editor/content-blocks) | Paragraph, Heading, Image, Video, Gallery, Quote, Code, Table |
| [Layout](/block-editor/layout-blocks) | Section, Container |
| [Interactive](/block-editor/interactive-blocks) | Link, Navigation, Search |
| [Dynamic](/block-editor/dynamic-blocks) | Loop Posts, Loop Categories, Loop Tags, Post List |
| [Post-specific](/block-editor/post-blocks) | Post Title, Post Body, Featured Image, Post Meta, Author, Taxonomy, Comments |
