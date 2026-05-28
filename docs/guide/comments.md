# Comments

## Public Submission

Readers submit comments via a form at the bottom of each post. Comments are **pending by default** — they are not visible until approved by an administrator.

Protections built in:
- **Honeypot field** — catches basic bots
- **Rate limiting** — prevents comment flooding
- **Per-post toggle** — comments can be disabled per post

## Moderation

Admins manage all comments at `/comments`. Actions available:

- **Approve** — makes the comment publicly visible
- **Reject** — hides the comment (not deleted, can be re-approved)
- **Delete** — permanently removes the comment
- **Bulk actions** — apply approve/reject/delete to a selection

The admin header shows a badge with the pending comment count.

## Replies

Administrators can reply to comments directly from the moderation panel. The reply is sent as an email to the original commenter and posted as an approved nested reply.

## Disabling Comments

**Per post:** Toggle **Enable comments** in the post editor sidebar.

**Site-wide:** Go to **Settings → Comments** and turn off **Comments enabled**. This hides the comment form and existing comments on all posts.
