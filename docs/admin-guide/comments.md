# Comments

Lambda CMS includes a built-in comment system with spam protection, rate limiting, nested replies, and email notifications.

## Comment workflow

New comments submitted by visitors start with **Pending** status and are not shown publicly until approved. Administrators and users with the appropriate role can approve, reject, or delete comments from the admin panel.

### Statuses

| Status | Public visibility |
|---|---|
| Pending | Hidden |
| Approved | Visible |
| Rejected | Hidden |

## Moderation

Go to **Comments** in the admin sidebar to see all comments across all posts. You can filter by status (pending, approved, rejected) and by post.

### Bulk actions

Select multiple comments using the checkboxes and apply bulk **Approve**, **Reject**, or **Delete** actions to handle large volumes quickly.

## Spam protection

Lambda CMS uses two layers of spam protection:

- **Honeypot field** — a hidden form field that bots often fill in. Any comment submission that includes a value in this field is silently rejected.
- **Rate limiting** — visitors are limited to a configurable number of comment submissions per time window, preventing comment flooding.

## Nested replies

Visitors can reply to existing comments, creating threads up to any depth. Replies are displayed inline under their parent comment.

## Email notifications

When a new comment is submitted, Lambda CMS can send two types of email notifications:

- **Admin notification** — an email to the site admin when any new comment arrives.
- **Reply notification** — an email to the original commenter when someone replies to their comment.

Email notifications require mail to be configured in **Settings → Mail**.

## Per-post control

Comments can be enabled or disabled on a per-post basis using the toggle in the post editor sidebar. The global toggle in **Settings → Comments** overrides individual post settings — when comments are globally disabled, no comment forms are shown anywhere on the site.

## Settings

| Setting | Location | Description |
|---|---|---|
| Comments enabled | Settings → Comments | Global master toggle |
| Comments per page | Settings → Comments | Pagination size (5–100) |
