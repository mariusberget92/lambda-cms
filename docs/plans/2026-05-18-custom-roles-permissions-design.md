# Custom Roles & Permissions — Design

**Date:** 2026-05-18
**Stack:** Laravel 12 + Inertia 2 + Vue 3 + Spatie Permission 7.2

---

## Overview

Replaces the hardcoded two-role system (administrator / user) with a fully dynamic roles-and-permissions model. Admins can create custom roles, assign granular permissions per role, and assign those roles to users — enabling fine-grained team structures (editors, authors, contributors, moderators, etc.).

The `administrator` role is system-protected: it cannot be edited or deleted. All 42 permissions are seeded upfront; custom roles pick and choose from them.

---

## Permission Catalogue

42 permissions grouped into 9 sections:

### Posts
| Permission | Meaning |
|---|---|
| `view posts` | List and open any post in the admin |
| `create posts` | Create new draft posts |
| `edit own posts` | Edit posts the user created |
| `edit any post` | Edit posts regardless of author |
| `delete own posts` | Delete posts the user created |
| `delete any post` | Delete posts regardless of author |
| `publish posts` | Set status to published or scheduled |

### Pages
| Permission | Meaning |
|---|---|
| `view pages` | List and open any page |
| `create pages` | Create new pages |
| `edit pages` | Edit any page |
| `delete pages` | Delete any page |

### Templates
| Permission | Meaning |
|---|---|
| `view templates` | List and open any template |
| `create templates` | Create new templates |
| `edit templates` | Edit any template |
| `delete templates` | Delete any template |

### Categories
| Permission | Meaning |
|---|---|
| `view categories` | List categories |
| `create categories` | Create new categories |
| `edit categories` | Edit any category |
| `delete categories` | Delete any category |

### Tags
| Permission | Meaning |
|---|---|
| `view tags` | List tags |
| `create tags` | Create new tags |
| `edit tags` | Edit any tag |
| `delete tags` | Delete any tag |

### Media
| Permission | Meaning |
|---|---|
| `view media` | See all media (without it, user sees own uploads only) |
| `upload media` | Upload new files |
| `edit own media` | Edit alt/description on own uploads |
| `edit any media` | Edit alt/description on anyone's uploads |
| `delete own media` | Delete own uploads |
| `delete any media` | Delete anyone's uploads |

### Comments
| Permission | Meaning |
|---|---|
| `view comments` | Access the comments management page |
| `moderate comments` | Approve or reject comments |
| `reply to comments` | Post admin replies |
| `delete comments` | Delete any comment |

### Users
| Permission | Meaning |
|---|---|
| `view users` | Access the users management page |
| `create users` | Invite new users |
| `edit users` | Edit user name, email, role |
| `delete users` | Delete user accounts |
| `ban users` | Ban and unban users |

### System
| Permission | Meaning |
|---|---|
| `manage roles` | Create, edit, delete roles |
| `manage settings` | Access and update site settings |
| `manage navigation` | Edit navigation menus |
| `manage webhooks` | Create, edit, delete webhooks |

---

## Seeded Roles

Five roles are seeded by default. Only `administrator` is system-protected.

| Role | Permissions | Notes |
|---|---|---|
| `administrator` | All 42 | System role — cannot be edited or deleted |
| `editor` | 34 | All content + media + comments + navigation; no users/roles/settings/webhooks |
| `author` | 11 | Own posts (incl. publish) + view taxonomies + own media |
| `contributor` | 10 | Own posts (draft only, no publish) + view taxonomies + own media |
| `user` | 17 | Author-level + full taxonomy CRUD — kept for backwards compatibility |

---

## Data & Models

Uses Spatie Permission v7 — `roles`, `permissions`, and pivot tables are provided by the package.

No additional migrations required beyond what Spatie installs.

### Role model usage

```php
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

$role = Role::create(['name' => 'editor']);
$role->givePermissionTo(['edit any post', 'view pages']);

$user->assignRole('editor');
$user->can('edit any post'); // true
```

---

## Routes

```
GET    /roles              → RoleController@index
GET    /roles/create       → RoleController@create
POST   /roles              → RoleController@store
GET    /roles/{role}/edit  → RoleController@edit
PUT    /roles/{role}       → RoleController@update
DELETE /roles/{role}       → RoleController@destroy
```

All routes are guarded by `['auth', 'verified', 'permission:manage roles']`.

### Route middleware changes

All previously `role:administrator`-gated sections now use per-section permission middleware:

| Section | Middleware |
|---|---|
| Pages | `permission:view pages` |
| Templates | `permission:view templates` |
| Users | `permission:view users` |
| Roles | `permission:manage roles` |
| Comments | `permission:view comments` |
| Settings | `permission:manage settings` |
| Navigation | `permission:manage navigation` |
| Webhooks | `permission:manage webhooks` |

---

## Controller Authorization

Controller methods check the minimum required permission for each action, independent of role:

```php
// store/create endpoints
abort_if(! $request->user()->can('create posts'), 403);

// update endpoints
abort_if(! $request->user()->can('edit pages'), 403);

// destroy endpoints
abort_if(! request()->user()->can('delete categories'), 403);

// owner-or-any pattern (media)
if ($media->user_id !== $request->user()->id && ! $request->user()->can('delete any media')) {
    abort(403);
}

// publish gate (posts)
if (in_array($status, ['published', 'scheduled']) && ! $user->can('publish posts')) {
    abort(403);
}
```

---

## Inertia Shared Data

`HandleInertiaRequests` shares the authenticated user's full permission list on every page load:

```php
"auth" => [
    "user" => [
        "id"             => ...,
        "name"           => ...,
        "email"          => ...,
        "avatar_url"     => ...,
        "role"           => $user->getRoleNames()->first(),
        "email_verified" => $user->hasVerifiedEmail(),
        "permissions"    => $user->getAllPermissions()->pluck('name')->values(),
    ]
]
```

---

## UI

### AppLayout — sidebar

A `can()` helper is defined once in `AppLayout.vue`:

```js
const can = (permission) => user.value.permissions?.includes(permission) ?? false
```

Each sidebar section is conditionally rendered:

```html
<SidebarLink v-if="can('view pages')" :href="route('pages.index')" label="Pages" />
<SidebarLink v-if="can('manage roles')" :href="route('roles.index')" label="Roles" />
```

### Roles/Index

- Table: role name, permission count, user count, system badge
- Permissions preview: first 12 as chips + "+X more" overflow
- Edit/Delete actions disabled for `administrator`
- Delete disabled if the role has users assigned

### Roles/Form (create & edit)

- Name field (disabled for system roles)
- Permissions grouped by section, each with a "Select all" toggle per group
- Global "Select all / Deselect all" toggle
- System role shows a read-only notice instead of the form

### Users/Form

- Role dropdown now populates from the `roles` array passed by the controller
- Links to `/roles` for permission management reference

---

## Guards Summary

| Action | Blocked when |
|---|---|
| Edit `administrator` role | Always (system role) |
| Delete `administrator` role | Always (system role) |
| Delete role with users | Role has `users_count > 0` |
| Publish post | User lacks `publish posts` permission |
| Edit other's post | User lacks `edit any post` permission |
| Ban user | User lacks `ban users` permission |
| Access settings | User lacks `manage settings` permission |
