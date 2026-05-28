# Posts & Pages

## Posts

Posts are the primary content type. They support two editors:

- **Rich text** (Tiptap) — WYSIWYG with formatting toolbar and HTML source toggle
- **Block editor** — full drag-and-drop canvas (toggle per post)

### Status

| Status | Behaviour |
|---|---|
| `draft` | Not publicly visible |
| `scheduled` | Published automatically when `published_at` is reached |
| `published` | Live on the blog |

Scheduled posts require the Laravel scheduler to be running. See [Deployment](/guide/deployment).

### Featured Image

Upload or select a media library image. Displayed as a hero image on the single post page and as a thumbnail in the post card.

### SEO Fields

Each post has its own `meta_title`, `meta_description`, `meta_keywords`, and OG image. When left blank, the global defaults from Settings are used.

### Draft Preview

Every post gets a shareable preview URL:

```
/preview/posts/{token}
```

This URL works without login, making it easy to share drafts for review. Tokens are generated on post creation and never change unless manually regenerated.

### Bulk Actions

From the Posts list, select multiple posts and apply: **Publish**, **Draft**, or **Delete**. Permission checks apply — users can only bulk-action their own posts.

## Pages

Pages are static content managed by administrators. They use the block editor exclusively (no Tiptap).

Published pages are served at `/{slug}` — a catch-all route that fires after all named routes. This means any custom slug that doesn't conflict with `/blog`, `/search`, `/feed`, etc. will work.

### Preview

Same as posts: `/preview/pages/{token}`.

## Revisions

Both posts and pages maintain up to 25 revisions. Every save creates a new revision snapshot. Click **Revisions** in the editor bar to browse and one-click restore any previous version.

## Autosave

The editor autosaves to a temporary draft every 10 seconds of inactivity. If you navigate away without saving, you'll be prompted to restore the draft on your next visit.
