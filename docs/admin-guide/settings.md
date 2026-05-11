# Settings

The Settings panel (**Admin → Settings**) lets you configure Lambda CMS at runtime without editing files. Settings are stored in the database and take precedence over `.env` defaults.

## Site

| Setting | Description |
|---|---|
| Site name | Used in the browser tab, emails, and the RSS feed title |
| Site URL | Base URL for canonical links and sitemap entries |
| Timezone | PHP timezone string (e.g. `Europe/Oslo`, `America/New_York`) used for scheduled posts and date display |
| Date format | PHP date format string (e.g. `F j, Y`) for date output throughout the admin UI |

## SEO

| Setting | Description |
|---|---|
| Title separator | Character between page title and site name in the `<title>` tag (e.g. `·`, `—`, `\|`) |
| Default meta description | Fallback `<meta name="description">` for pages without a custom description |
| Default OG image URL | Fallback Open Graph image URL |
| Default keywords | Fallback `<meta name="keywords">` content |

## Mail

| Setting | Description |
|---|---|
| Mailer | `smtp`, `mailgun`, or `log` |
| Host | SMTP server hostname |
| Port | SMTP port (typically `587` for TLS, `465` for SSL) |
| Username | SMTP authentication username |
| Password | SMTP authentication password |
| Encryption | `tls` or `ssl` |
| From address | Sender email address |
| From name | Sender display name |

Use **Send test email** to deliver a test message to your own address and confirm mail is working.

## Media

| Setting | Description |
|---|---|
| Max upload size | Maximum file size for uploads in megabytes |
| Image resize width | Images wider than this value (px) are resized on upload |

## Comments

| Setting | Description |
|---|---|
| Comments enabled | Master on/off toggle for the comment system site-wide |
| Comments per page | Number of comments shown per page in threads (5–100) |

## Calendar

The **Calendar** page (**Admin → Calendar**) provides a month-view editorial calendar showing all posts by their `published_at` date. Click any post to jump to its edit page. Scheduled and published posts are both shown; drafts are not.
