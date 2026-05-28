# Configuration

All runtime configuration is managed through **Settings** in the admin panel (`/settings`). Settings are stored in the database and take effect immediately — no redeploy required.

## Site

| Setting | Description |
|---|---|
| Site name | Used in the `<title>` tag and RSS feed |
| Site URL | Full URL used for sitemap and canonical links |

## Appearance

**Accent color** — choose from the Nord palette or enter a custom hex value. The color applies live to both the admin panel (`--primary`) and the blog frontend (`--accent`), affecting buttons, links, hover states, and active indicators.

## Locale

| Setting | Description |
|---|---|
| Timezone | Applied to all date/time display and scheduling |
| Date format | Controls how published dates are formatted in the blog |

## SEO

| Setting | Description |
|---|---|
| Title separator | Character between page title and site name, e.g. `·` or `\|` |
| Default description | Fallback `<meta name="description">` when a post has none |
| Default OG image | Fallback open graph image URL |
| Default keywords | Fallback `<meta name="keywords">` |

## Mail

Lambda CMS sends emails for password reset, email verification, comment notifications, and user invites.

| Setting | Description |
|---|---|
| Driver | `smtp`, `log` (dev), or `mailgun` |
| Host / Port | SMTP server details |
| Username / Password | SMTP credentials |
| Encryption | `tls` or `ssl` |
| From address / name | Displayed sender |

Use the **Send test email** button to verify your configuration before going live.

## Media

| Setting | Default | Description |
|---|---|---|
| Max upload size | 10 MB | Maximum file size per upload |
| Resize max width | 1920 px | Images wider than this are resized on upload |

## Comments

| Setting | Default | Description |
|---|---|---|
| Comments enabled | Yes | Global toggle — disables comments site-wide when off |
| Comments per page | 15 | Number of approved comments loaded per page |

## Navigation

Manage the public navigation bar at `/navigation`. Items can link to published pages (internal) or any custom URL. Drag to reorder.

## Custom JavaScript

Inject a script into every public-facing page — useful for analytics, chat widgets, or tracking pixels. Applied just before `</body>`.
