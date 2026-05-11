# Posts

Posts are the primary content type in Lambda CMS. They appear in your blog feed, are categorized with categories and tags, and support comments, scheduling, and revision history.

## Creating a post

Navigate to **Posts → New Post**. Fill in:

- **Title** — required. The slug is auto-generated from the title and can be edited manually.
- **Excerpt** — optional short summary shown in post listings.
- **Featured image** — select from the Media Library or upload a new file.
- **Categories** and **Tags** — assign one or more of each.
- **Author** — defaults to the logged-in user; administrators can reassign.

## Editors

Each post uses one of two editors, chosen per-post:

### Rich text editor (Tiptap)

A WYSIWYG editor supporting bold, italic, underline, headings (H1–H6), ordered and unordered lists, blockquotes, links, and images. Suitable for straightforward articles.

### Block editor

A drag-and-drop canvas with 30+ block types. Enables custom layouts, dynamic content loops, and per-block styling. See the [Block Editor](/block-editor/overview) section for full details.

Toggle between editors using the **Switch editor** button on the post edit page. Switching editors clears existing content, so choose before you start writing.

## Status and publishing

| Status | Description |
|---|---|
| Draft | Not publicly visible. Default for new posts. |
| Scheduled | Will be published automatically at the chosen date and time. |
| Published | Publicly visible immediately. |

Change the status from the sidebar on the edit page.

### Scheduling

Set **Published at** to a future date and time, then save with status **Scheduled**. The `publish:scheduled-posts` Artisan command (run by the scheduler every minute) will publish the post automatically when the time arrives.

## SEO fields

Each post has its own SEO panel:

- **Meta title** — overrides the default `<title>` tag.
- **Meta description** — overrides the default meta description.
- **Meta keywords** — overrides the default keywords.

## Preview

Use the **Preview** button to open a shareable preview URL (`/preview/posts/{token}`) that works even for draft posts. The token is unique to the post and can be regenerated.

## Autosave and revisions

Lambda CMS autosaves your work every 30 seconds. The autosave is scoped to the logged-in user, so multiple users can edit different posts simultaneously without conflicts.

When you save a post, a **revision** is created automatically. Up to 25 revisions are kept per post. Open the **Revisions** panel in the sidebar to browse and restore any previous version.

## Comments

Enable or disable comments per-post from the sidebar toggle. The global comments setting in **Settings → Comments** takes precedence — if comments are disabled globally, the per-post toggle has no effect.

## Deleting posts

Posts can be deleted from the post list or the edit page. Deletion is permanent — there is no trash/recycle bin. All associated comments and revisions are deleted along with the post.
