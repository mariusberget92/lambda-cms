# Users

Lambda CMS uses role-based access control powered by Spatie Permission. There are two built-in roles.

## Roles

| Role | Access |
|---|---|
| **Administrator** | Full access to all admin features including users, settings, pages, templates, webhooks, and navigation. |
| **User** | Can create and manage their own posts, upload media, and manage categories and tags. Cannot access settings, users, pages, templates, or webhooks. |

## Inviting users

Administrators can invite new users from **Users → Invite**. Enter the invitee's name and email address. Lambda CMS will:

1. Create the account with an auto-generated password.
2. Send an invitation email with the temporary credentials.
3. The new user can log in and change their password from their profile.

## Managing users

The user list shows all accounts with their role, last seen timestamp, and banned status.

### Editing a user

Click any user to open the edit form. Administrators can change:

- **Name** and **email**
- **Role** (administrator or user)
- **Password** — enter a new value to reset; leave blank to keep the current password
- **Avatar** — select from the media library

### Banning a user

To prevent a user from logging in without deleting their account, use the **Ban** action. Enter a reason (stored and shown in the admin UI). Banned users receive a message explaining they are banned when they attempt to log in.

Unban a user at any time from the same panel.

::: warning Administrator protection
The initial administrator account created during installation cannot be deleted or demoted.
:::

## Your profile

Every user can edit their own name, email, password, and avatar from **Profile** (accessible from the top navigation).

## Last seen

The user list shows when each user last visited the admin panel. This timestamp updates on every authenticated page load.
