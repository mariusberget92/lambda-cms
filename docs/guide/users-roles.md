# Users & Roles

## Roles

Lambda CMS has two built-in roles:

### Administrator

Full access to everything:
- All posts, pages, categories, and tags (including other users' content)
- Template and partial management
- Media library (all files)
- Comment moderation
- User management (create, edit, ban, delete)
- Settings, webhooks, import/export

At least one administrator must always exist. The last admin cannot be deleted or demoted.

### User

Scoped access:
- Own posts (create, edit, delete)
- Categories and tags (create, edit, delete)
- Own media uploads
- Profile settings

## Inviting Users

Admins can invite new users at **Users → New User**. An email is sent with an auto-generated temporary password. The user is prompted to change it on first login.

## Banning Users

A banned user cannot log in. Bans can include a reason and an optional expiry date. When the expiry passes, the ban lifts automatically on the next login attempt.

To ban: **Users → (user) → Ban**. To lift: **Users → (user) → Unban**.

## Profile

Every user can update their name, email, password, and avatar at `/profile`.

Online status is tracked via `last_seen_at` — users active in the last 5 minutes show a green presence dot in the user list.

## Email Verification

New accounts require email verification before accessing the dashboard. The verification link expires after 60 minutes. Admins can resend the verification email from the user list.

## Two-Factor Authentication

Each user can independently enable TOTP-based two-factor authentication from their Profile page. When enabled, signing in requires a 6-digit code from an authenticator app after the password step.

See the [Two-Factor Authentication guide](./two-factor-authentication) for setup instructions, recovery codes, and troubleshooting.
