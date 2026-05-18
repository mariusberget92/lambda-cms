# Custom Roles & Permissions Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Replace the hardcoded administrator/user two-role system with a fully dynamic roles and permissions model using Spatie Permission. Admins can create custom roles, assign any combination of the 42 seeded permissions, and assign those roles to users.

**Architecture:** Spatie Permission is already installed. The seeder is expanded to 42 permissions and 5 roles. A new `RoleController` handles CRUD for roles. All `role:administrator` route middleware groups are replaced with granular `permission:X` middleware. Controllers gain `abort_if(!can(...))` guards for write operations. `HandleInertiaRequests` shares the user's full permission list. `AppLayout.vue` gains a `can()` helper that gates every sidebar link. Two new Inertia pages (`Roles/Index` and `Roles/Form`) provide the management UI.

**Tech Stack:** Laravel 12, Inertia 2, Vue 3, Tailwind 4, Spatie Permission 7.2

---

### Task 1: Expand the seeder — 42 permissions, 5 roles

**Files:**
- Modify: `database/seeders/DatabaseSeeder.php`

**Step 1: Replace the seeder body**

Replace the entire `run()` method with:

```php
public function run(): void
{
    $permissions = [
        // Posts
        'view posts', 'create posts', 'edit own posts', 'edit any post',
        'delete own posts', 'delete any post', 'publish posts',
        // Pages
        'view pages', 'create pages', 'edit pages', 'delete pages',
        // Templates
        'view templates', 'create templates', 'edit templates', 'delete templates',
        // Categories
        'view categories', 'create categories', 'edit categories', 'delete categories',
        // Tags
        'view tags', 'create tags', 'edit tags', 'delete tags',
        // Media
        'view media', 'upload media', 'edit own media', 'edit any media',
        'delete own media', 'delete any media',
        // Comments
        'view comments', 'moderate comments', 'reply to comments', 'delete comments',
        // Users
        'view users', 'create users', 'edit users', 'delete users', 'ban users',
        // System
        'manage roles', 'manage settings', 'manage navigation', 'manage webhooks',
    ];

    foreach ($permissions as $perm) {
        Permission::firstOrCreate(['name' => $perm]);
    }

    // administrator — all permissions
    $admin = Role::firstOrCreate(['name' => 'administrator']);
    $admin->syncPermissions(Permission::all());

    // editor — all content + media + comments + navigation
    $editor = Role::firstOrCreate(['name' => 'editor']);
    $editor->syncPermissions([
        'view posts', 'create posts', 'edit own posts', 'edit any post',
        'delete own posts', 'delete any post', 'publish posts',
        'view pages', 'create pages', 'edit pages', 'delete pages',
        'view templates', 'create templates', 'edit templates', 'delete templates',
        'view categories', 'create categories', 'edit categories', 'delete categories',
        'view tags', 'create tags', 'edit tags', 'delete tags',
        'view media', 'upload media', 'edit own media', 'edit any media',
        'delete own media', 'delete any media',
        'view comments', 'moderate comments', 'reply to comments', 'delete comments',
        'manage navigation',
    ]);

    // author — own posts (incl. publish) + view taxonomies + own media
    $author = Role::firstOrCreate(['name' => 'author']);
    $author->syncPermissions([
        'view posts', 'create posts', 'edit own posts', 'delete own posts', 'publish posts',
        'view categories', 'view tags',
        'upload media', 'edit own media', 'delete own media',
    ]);

    // contributor — draft only (no publish) + view taxonomies + own media
    $contributor = Role::firstOrCreate(['name' => 'contributor']);
    $contributor->syncPermissions([
        'view posts', 'create posts', 'edit own posts', 'delete own posts',
        'view categories', 'view tags',
        'upload media', 'edit own media', 'delete own media',
    ]);

    // user — backwards-compatibility: author-level + full taxonomy CRUD
    $user = Role::firstOrCreate(['name' => 'user']);
    $user->syncPermissions([
        'view posts', 'create posts', 'edit own posts', 'delete own posts', 'publish posts',
        'view categories', 'create categories', 'edit categories', 'delete categories',
        'view tags', 'create tags', 'edit tags', 'delete tags',
        'upload media', 'edit own media', 'delete own media',
    ]);
}
```

**Step 2: Re-run migrations and seeder**

```bash
php artisan migrate:fresh --seed
```

Expected: 42 permissions seeded, 5 roles created with correct permission counts.

---

### Task 2: Create RoleController

**Files:**
- Create: `app/Http/Controllers/RoleController.php`

**Step 1: Create the controller**

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::withCount('users')
            ->get()
            ->map(fn ($r) => [
                'id'          => $r->id,
                'name'        => $r->name,
                'permissions' => $r->permissions->pluck('name')->values(),
                'users_count' => $r->users_count,
                'is_system'   => $r->name === 'administrator',
            ]);

        return Inertia::render('Roles/Index', ['roles' => $roles]);
    }

    public function create()
    {
        return Inertia::render('Roles/Form', [
            'groupedPermissions' => $this->groupedPermissions(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:50', 'unique:roles,name'],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role = Role::create(['name' => strtolower(trim($validated['name']))]);
        $role->syncPermissions($validated['permissions'] ?? []);

        return redirect()->route('roles.index')->with('status', 'Role created.');
    }

    public function edit(Role $role)
    {
        if ($role->name === 'administrator') {
            return redirect()->route('roles.index')
                ->with('error', 'The administrator role cannot be edited.');
        }

        return Inertia::render('Roles/Form', [
            'role' => [
                'id'          => $role->id,
                'name'        => $role->name,
                'permissions' => $role->permissions->pluck('name')->values(),
                'is_system'   => false,
            ],
            'groupedPermissions' => $this->groupedPermissions(),
        ]);
    }

    public function update(Request $request, Role $role)
    {
        if ($role->name === 'administrator') {
            return redirect()->route('roles.index')
                ->with('error', 'The administrator role cannot be edited.');
        }

        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:50', 'unique:roles,name,' . $role->id],
            'permissions'   => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role->update(['name' => strtolower(trim($validated['name']))]);
        $role->syncPermissions($validated['permissions'] ?? []);

        app()['cache']->forget('spatie.permission.cache');

        return redirect()->route('roles.index')->with('status', 'Role updated.');
    }

    public function destroy(Role $role)
    {
        if ($role->name === 'administrator') {
            return redirect()->route('roles.index')
                ->with('error', 'The administrator role cannot be deleted.');
        }

        if ($role->users()->count() > 0) {
            return redirect()->route('roles.index')
                ->with('error', 'Cannot delete a role that has users assigned to it.');
        }

        $role->delete();
        app()['cache']->forget('spatie.permission.cache');

        return redirect()->route('roles.index')->with('status', 'Role deleted.');
    }

    private function groupedPermissions(): array
    {
        return [
            'Posts'     => ['view posts', 'create posts', 'edit own posts', 'edit any post', 'delete own posts', 'delete any post', 'publish posts'],
            'Pages'     => ['view pages', 'create pages', 'edit pages', 'delete pages'],
            'Templates' => ['view templates', 'create templates', 'edit templates', 'delete templates'],
            'Categories'=> ['view categories', 'create categories', 'edit categories', 'delete categories'],
            'Tags'      => ['view tags', 'create tags', 'edit tags', 'delete tags'],
            'Media'     => ['view media', 'upload media', 'edit own media', 'edit any media', 'delete own media', 'delete any media'],
            'Comments'  => ['view comments', 'moderate comments', 'reply to comments', 'delete comments'],
            'Users'     => ['view users', 'create users', 'edit users', 'delete users', 'ban users'],
            'System'    => ['manage roles', 'manage settings', 'manage navigation', 'manage webhooks'],
        ];
    }
}
```

---

### Task 3: Register routes

**Files:**
- Modify: `routes/web.php`

**Step 1: Add the RoleController import**

```php
use App\Http\Controllers\RoleController;
```

**Step 2: Replace all `role:administrator` middleware groups**

Replace the old single `role:administrator` group with individual permission-based groups:

```php
// Pages
Route::middleware(['auth', 'verified', 'permission:view pages'])->group(function () {
    Route::resource('pages', PageController::class)->except(['show']);
    Route::post('/pages/{page}/autosave',   [AutosaveController::class, 'storePage'])->name('pages.autosave');
    Route::delete('/pages/{page}/autosave', [AutosaveController::class, 'destroyPage'])->name('pages.autosave.destroy');
    Route::get('/pages/{page}/revisions',   [RevisionController::class, 'indexPage'])->name('pages.revisions');
});

// Templates
Route::middleware(['auth', 'verified', 'permission:view templates'])->group(function () {
    Route::resource('templates', TemplateController::class)->except(['show']);
    Route::post('/templates/{template}/autosave',   [AutosaveController::class, 'storeTemplate'])->name('templates.autosave');
    Route::delete('/templates/{template}/autosave', [AutosaveController::class, 'destroyTemplate'])->name('templates.autosave.destroy');
    Route::get('/templates/{template}/revisions',   [RevisionController::class, 'indexTemplate'])->name('templates.revisions');
});

// Users
Route::middleware(['auth', 'verified', 'permission:view users'])->group(function () {
    Route::resource('users', UserController::class)->except(['show']);
    Route::post('/users/{user}/ban',   [BanController::class, 'ban'])->name('users.ban');
    Route::delete('/users/{user}/ban', [BanController::class, 'unban'])->name('users.unban');
});

// Roles
Route::middleware(['auth', 'verified', 'permission:manage roles'])->group(function () {
    Route::resource('roles', RoleController::class)->except(['show']);
});

// Comments
Route::middleware(['auth', 'verified', 'permission:view comments'])->group(function () {
    Route::get('/comments',                     [CommentController::class, 'index'])->name('comments.index');
    Route::patch('/comments/{comment}/approve', [CommentController::class, 'approve'])->name('comments.approve');
    Route::patch('/comments/{comment}/reject',  [CommentController::class, 'reject'])->name('comments.reject');
    Route::delete('/comments/{comment}',        [CommentController::class, 'destroy'])->name('comments.destroy');
    Route::post('/comments/bulk',               [CommentController::class, 'bulk'])->name('comments.bulk');
    Route::post('/comments/{comment}/reply',    [CommentController::class, 'reply'])->name('comments.reply');
});

// Settings
Route::middleware(['auth', 'verified', 'permission:manage settings'])->group(function () {
    Route::get('/settings',             [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings/{group}',     [SettingsController::class, 'update'])->name('settings.update');
    Route::post('/settings/test-email', [SettingsController::class, 'testEmail'])->name('settings.test-email');
});

// Navigation
Route::middleware(['auth', 'verified', 'permission:manage navigation'])->group(function () {
    Route::get('/navigation',              [NavigationController::class, 'index'])->name('navigation.index');
    Route::post('/navigation',             [NavigationController::class, 'store'])->name('navigation.store');
    Route::put('/navigation/{navItem}',    [NavigationController::class, 'update'])->name('navigation.update');
    Route::delete('/navigation/{navItem}', [NavigationController::class, 'destroy'])->name('navigation.destroy');
    Route::post('/navigation/reorder',     [NavigationController::class, 'reorder'])->name('navigation.reorder');
});

// Webhooks
Route::middleware(['auth', 'verified', 'permission:manage webhooks'])->group(function () {
    Route::get('/webhooks',              [WebhookController::class, 'index'])->name('webhooks.index');
    Route::post('/webhooks',             [WebhookController::class, 'store'])->name('webhooks.store');
    Route::put('/webhooks/{webhook}',    [WebhookController::class, 'update'])->name('webhooks.update');
    Route::delete('/webhooks/{webhook}', [WebhookController::class, 'destroy'])->name('webhooks.destroy');
});
```

**Step 3: Add `roles` to the catch-all exclusion regex**

In the `pages.show` catch-all route, add `roles` to the exclusion pattern:

```php
->where('slug', '^(?!login|logout|dashboard|blog|feed|sitemap\.xml|posts|categories|tags|users|roles|profile|settings|media|comments|pages|templates|calendar|password|register|verify|install|email|forgot-password|reset-password|search).*$')
```

---

### Task 4: Controller authorization guards

**Files:**
- Modify: `app/Http/Controllers/PostController.php`
- Modify: `app/Http/Controllers/PageController.php`
- Modify: `app/Http/Controllers/TemplateController.php`
- Modify: `app/Http/Controllers/CategoryController.php`
- Modify: `app/Http/Controllers/TagController.php`
- Modify: `app/Http/Controllers/MediaController.php`
- Modify: `app/Http/Controllers/AutosaveController.php`
- Modify: `app/Http/Controllers/BanController.php`
- Modify: `app/Http/Controllers/CalendarController.php`
- Modify: `app/Http/Controllers/UserController.php`

**PostController:**

In `store()`, after resolving `$status`, add:
```php
if (in_array($status, ['published', 'scheduled']) && ! $request->user()->can('publish posts')) {
    abort(403);
}
```

In `edit()`, replace `hasRole('administrator')` owner check:
```php
// Before:
if ($post->user_id !== $user->id && ! $user->hasRole('administrator')) { abort(403); }
// After:
if ($post->user_id !== $user->id && ! $user->can('edit any post')) { abort(403); }
```

In `update()`, same pattern + publish gate before validation.

In `destroy()`:
```php
if ($post->user_id !== $user->id && ! $user->can('delete any post')) { abort(403); }
```

In `bulk()`, replace `hasRole('administrator')` with `can('edit any post')`.

**PageController — store/update/destroy:**
```php
abort_if(! $request->user()->can('create pages'), 403); // store
abort_if(! $request->user()->can('edit pages'),   403); // update
abort_if(! request()->user()->can('delete pages'), 403); // destroy
```

**TemplateController — edit/update/destroy:**
```php
if ($template->user_id !== $user->id && ! $user->can('edit templates')) { abort(403); }
```

**CategoryController — store/update/destroy:**
```php
abort_if(! $request->user()->can('create categories'), 403);
abort_if(! $request->user()->can('edit categories'),   403);
abort_if(! request()->user()->can('delete categories'), 403);
```

**TagController — store/update/destroy:**
```php
abort_if(! $request->user()->can('create tags'), 403);
abort_if(! $request->user()->can('edit tags'),   403);
abort_if(! request()->user()->can('delete tags'), 403);
```

**MediaController:**

`index()` — filter to own uploads if user lacks `view media`:
```php
if (! $request->user()->can('view media')) {
    $query->where('user_id', $request->user()->id);
}
```

`update()` / `usage()`:
```php
if ($media->user_id !== $request->user()->id && ! $request->user()->can('edit any media')) {
    abort(403);
}
```

`destroy()`:
```php
if ($media->user_id !== $request->user()->id && ! $request->user()->can('delete any media')) {
    abort(403);
}
```

`bulkDestroy()` — filter to own media if user lacks `delete any media`:
```php
if (! $request->user()->can('delete any media')) {
    $query->where('user_id', $request->user()->id);
}
```

**BanController — ban():**
```php
abort_if(! $request->user()->can('ban users'), 403);
```

**CalendarController:**
```php
// Replace:
$isAdmin = $user->hasRole('administrator');
// With:
$isAdmin = $user->can('edit any post');
```

**UserController — store/update/destroy:**
```php
abort_if(! $request->user()->can('create users'), 403); // store
abort_if(! $request->user()->can('edit users'),   403); // update
abort_if(! $request->user()->can('delete users'), 403); // destroy
```

Also update the role validation rule:
```php
// Before:
'role' => ['required', 'string', Rule::in(['administrator', 'user'])],
// After:
'role' => ['required', 'string', Rule::in(Role::pluck('name')->toArray())],
```

---

### Task 5: Share permissions via Inertia

**Files:**
- Modify: `app/Http/Middleware/HandleInertiaRequests.php`

**Step 1: Update the `auth.user` shared data**

In the `share()` method, update the user array to include permissions:

```php
"auth" => [
    "user" => $request->user() ? array_merge(
        $request->user()->only("id", "name", "email", "avatar_url"),
        [
            "role"           => $request->user()->getRoleNames()->first(),
            "email_verified" => $request->user()->hasVerifiedEmail(),
            "permissions"    => $request->user()->getAllPermissions()->pluck('name')->values(),
        ]
    ) : null,
],
```

**Step 2: Update pendingCommentsCount**

Replace `hasRole('administrator')` with permission check:

```php
"pendingCommentsCount" => fn () => $request->user()?->can('view comments')
    ? Comment::pending()->count()
    : null,
```

---

### Task 6: Frontend — AppLayout sidebar

**Files:**
- Modify: `resources/js/Layouts/AppLayout.vue`

**Step 1: Add `can()` helper**

In `<script setup>`, after `const user = computed(...)`, add:

```js
const can = (permission) => user.value.permissions?.includes(permission) ?? false
```

**Step 2: Replace all `user.role === 'administrator'` checks**

Update every sidebar `v-if` to use `can()`:

```html
<!-- Posts — all authenticated users -->
<SidebarLink :href="route('posts.index')" label="Posts" ... />

<!-- Categories -->
<SidebarLink v-if="can('view categories')" :href="route('categories.index')" label="Categories" ... />

<!-- Tags -->
<SidebarLink v-if="can('view tags')" :href="route('tags.index')" label="Tags" ... />

<!-- Media -->
<SidebarLink v-if="can('upload media') || can('view media')" :href="route('media.index')" label="Media" ... />

<!-- Pages -->
<SidebarLink v-if="can('view pages')" :href="route('pages.index')" label="Pages" ... />

<!-- Templates -->
<SidebarLink v-if="can('view templates')" :href="route('templates.index')" label="Templates" ... />

<!-- Calendar -->
<SidebarLink :href="route('calendar')" label="Calendar" ... />

<!-- Comments -->
<SidebarLink v-if="can('view comments')" :href="route('comments.index')" label="Comments" ... />

<!-- Users -->
<SidebarLink v-if="can('view users')" :href="route('users.index')" label="Users" ... />

<!-- Roles -->
<SidebarLink v-if="can('manage roles')" :href="route('roles.index')" label="Roles" ... />

<!-- Settings -->
<SidebarLink v-if="can('manage settings')" :href="route('settings.index')" label="Settings" ... />

<!-- Navigation -->
<SidebarLink v-if="can('manage navigation')" :href="route('navigation.index')" label="Navigation" ... />

<!-- Webhooks -->
<SidebarLink v-if="can('manage webhooks')" :href="route('webhooks.index')" label="Webhooks" ... />
```

---

### Task 7: Create Roles/Index.vue

**Files:**
- Create: `resources/js/Pages/Roles/Index.vue`

```vue
<template>
  <AppLayout title="Roles">
    <Head title="Roles" />

    <PageHeader title="Roles" description="Manage roles and their permissions">
      <template #actions>
        <a
          :href="route('roles.create')"
          class="shrink-0 inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)]"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
          </svg>
          New role
        </a>
      </template>
    </PageHeader>

    <DataTable :empty="roles.length === 0">
      <template #empty>No roles found.</template>
      <template #headers>
        <th class="text-left">Role</th>
        <th class="text-left">Permissions</th>
        <th class="text-left">Users</th>
        <th class="w-10"></th>
      </template>
      <template #rows>
        <tr v-for="role in roles" :key="role.id" class="hover:bg-muted/30 transition-colors group">
          <td>
            <div class="flex items-center gap-2">
              <span class="font-medium capitalize">{{ role.name }}</span>
              <span v-if="role.is_system" class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-medium bg-muted text-muted-foreground border">
                system
              </span>
            </div>
          </td>
          <td>
            <div class="flex flex-wrap gap-1">
              <span
                v-for="perm in role.permissions.slice(0, 12)"
                :key="perm"
                class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-medium bg-muted text-muted-foreground"
              >{{ perm }}</span>
              <span
                v-if="role.permissions.length > 12"
                class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-medium bg-muted text-muted-foreground"
              >+{{ role.permissions.length - 12 }} more</span>
              <span v-if="role.permissions.length === 0" class="text-xs text-muted-foreground">No permissions</span>
            </div>
          </td>
          <td class="text-sm text-muted-foreground">{{ role.users_count }}</td>
          <td>
            <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
              <a
                v-if="!role.is_system"
                :href="route('roles.edit', role.id)"
                class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-accent hover:text-accent-foreground transition-colors"
                title="Edit"
              >
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
              </a>
              <button
                v-if="!role.is_system"
                type="button"
                :disabled="role.users_count > 0"
                :title="role.users_count > 0 ? 'Role has users assigned' : 'Delete'"
                @click="deleteTarget = role"
                class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-destructive/10 hover:text-destructive transition-colors disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-transparent disabled:hover:text-muted-foreground"
              >
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
              </button>
            </div>
          </td>
        </tr>
      </template>
    </DataTable>

    <!-- Delete confirmation modal -->
    <Transition name="fade">
      <div v-if="deleteTarget" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="deleteTarget = null" />
        <div class="relative bg-card border rounded-xl shadow-xl w-full max-w-sm p-6">
          <h3 class="font-semibold text-base mb-2">Delete role?</h3>
          <p class="text-sm text-muted-foreground mb-5">
            The <strong>{{ deleteTarget.name }}</strong> role will be permanently removed.
          </p>
          <div class="flex gap-3 justify-end">
            <button type="button" @click="deleteTarget = null" class="rounded-md border px-4 py-2 text-sm font-medium hover:bg-accent transition-colors">
              Cancel
            </button>
            <button type="button" @click="deleteRole" :disabled="deleting" class="rounded-md bg-destructive px-4 py-2 text-sm font-medium text-destructive-foreground hover:bg-destructive/90 disabled:opacity-50 transition-colors">
              {{ deleting ? 'Deleting...' : 'Delete' }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/PageHeader.vue'
import DataTable from '@/Components/DataTable.vue'

const props = defineProps({
  roles: { type: Array, required: true },
})

const deleteTarget = ref(null)
const deleting = ref(false)

function deleteRole() {
  if (!deleteTarget.value) return
  deleting.value = true
  router.delete(route('roles.destroy', deleteTarget.value.id), {
    onFinish: () => { deleting.value = false; deleteTarget.value = null },
  })
}
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
```

---

### Task 8: Create Roles/Form.vue

**Files:**
- Create: `resources/js/Pages/Roles/Form.vue`

```vue
<template>
  <AppLayout :title="isEditing ? 'Edit Role' : 'New Role'">
    <Head :title="isEditing ? 'Edit Role' : 'New Role'" />

    <form @submit.prevent="submit" class="max-w-2xl">
      <div class="flex items-center gap-3 mb-6">
        <a :href="route('roles.index')" class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-accent hover:text-accent-foreground transition-colors">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
          </svg>
        </a>
        <div>
          <h2 class="text-lg font-semibold">{{ isEditing ? 'Edit role' : 'New role' }}</h2>
          <p class="text-sm text-muted-foreground mt-0.5">{{ isEditing ? role.name : 'Create a custom role with specific permissions' }}</p>
        </div>
      </div>

      <div class="rounded-lg border bg-card p-6 space-y-4">
        <!-- Name -->
        <div class="space-y-1">
          <label for="name" class="text-sm font-medium">Name <span class="text-destructive">*</span></label>
          <input
            id="name"
            v-model="form.name"
            type="text"
            placeholder="e.g. moderator"
            autofocus
            class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
            :class="{ 'border-destructive': form.errors.name }"
          />
          <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
        </div>

        <!-- Permissions -->
        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <label class="text-sm font-medium">Permissions</label>
            <button type="button" @click="toggleAll" class="text-xs text-muted-foreground hover:text-foreground underline">
              {{ allSelected ? 'Deselect all' : 'Select all' }}
            </button>
          </div>

          <div v-for="(perms, group) in groupedPermissions" :key="group" class="space-y-2">
            <div class="flex items-center justify-between">
              <span class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{{ group }}</span>
              <button type="button" @click="toggleGroup(perms)" class="text-xs text-muted-foreground hover:text-foreground underline">
                {{ groupAllSelected(perms) ? 'Deselect' : 'Select all' }}
              </button>
            </div>
            <div class="grid grid-cols-2 gap-1.5">
              <label
                v-for="perm in perms"
                :key="perm"
                class="flex items-center gap-2 rounded-md border px-3 py-2 cursor-pointer hover:bg-muted/50 transition-colors"
                :class="{ 'border-primary bg-primary/5': form.permissions.includes(perm) }"
              >
                <input type="checkbox" :value="perm" v-model="form.permissions" class="rounded" />
                <span class="text-sm">{{ perm }}</span>
              </label>
            </div>
          </div>
        </div>
      </div>

      <div class="flex gap-3 mt-4 justify-end">
        <a :href="route('roles.index')" class="rounded-md border px-4 py-2 text-sm font-medium hover:bg-accent transition-colors">
          Cancel
        </a>
        <button
          type="submit"
          :disabled="form.processing"
          class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)] disabled:opacity-50"
        >
          {{ form.processing ? 'Saving...' : isEditing ? 'Save changes' : 'Create role' }}
        </button>
      </div>
    </form>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { useNotifications } from '@/composables/useNotifications.js'

const { notify } = useNotifications()

const props = defineProps({
  role:               { type: Object, default: null },
  groupedPermissions: { type: Object, required: true },
})

const isEditing = computed(() => !!props.role)

const form = useForm({
  name:        props.role?.name ?? '',
  permissions: props.role?.permissions ?? [],
})

const allPerms = computed(() => Object.values(props.groupedPermissions).flat())
const allSelected = computed(() => allPerms.value.every(p => form.permissions.includes(p)))

function groupAllSelected(perms) {
  return perms.every(p => form.permissions.includes(p))
}

function toggleGroup(perms) {
  if (groupAllSelected(perms)) {
    form.permissions = form.permissions.filter(p => !perms.includes(p))
  } else {
    perms.forEach(p => { if (!form.permissions.includes(p)) form.permissions.push(p) })
  }
}

function toggleAll() {
  if (allSelected.value) {
    form.permissions = []
  } else {
    form.permissions = [...allPerms.value]
  }
}

function submit() {
  const opts = {
    onError: (errors) => notify('Please fix the following:', 'error', { items: Object.values(errors) }),
  }
  if (isEditing.value) {
    form.put(route('roles.update', props.role.id), opts)
  } else {
    form.post(route('roles.store'), opts)
  }
}
</script>
```

---

### Task 9: Update Users/Form.vue

**Files:**
- Modify: `resources/js/Pages/Users/Form.vue`

**Step 1: Update roleOptions computed**

Replace the hardcoded label mapping in `roleOptions`:

```js
const roleOptions = computed(() => props.roles.map(r => ({
  value: r,
  label: r.charAt(0).toUpperCase() + r.slice(1),
})))
```

**Step 2: Update role helper text**

Replace the static `<p>` under the role select with a link to roles:

```html
<p class="text-xs text-muted-foreground">
  Roles and their permissions are managed in
  <a :href="route('roles.index')" class="underline hover:text-foreground">Roles</a>.
</p>
```

---

### Task 10: Build and verify

**Step 1: Build assets**

```bash
yarn build
```

Expected: no errors, all chunks compiled.

**Step 2: Verify routes**

```bash
php artisan route:list --name=roles
```

Expected: 6 routes (index, create, store, edit, update, destroy).

**Step 3: Verify seeded permissions and roles**

```bash
php artisan tinker --execute="
echo 'Permissions: ' . \Spatie\Permission\Models\Permission::count() . PHP_EOL;
\Spatie\Permission\Models\Role::with('permissions')->get()->each(function(\$r) {
    echo \$r->name . ': ' . \$r->permissions->count() . PHP_EOL;
});
"
```

Expected output:
```
Permissions: 42
administrator: 42
editor: 34
author: 11
contributor: 10
user: 17
```

**Step 4: Manual smoke tests**

- Log in as administrator → all sidebar links visible
- Log in as author → Posts, Media, Categories, Tags visible; Users/Roles/Settings not visible
- Create a new role "moderator" with only Comments permissions → save, assign to a user, log in as that user
- Confirm moderator can access `/comments` but gets 403 on `/settings`
- Try to edit/delete the `administrator` role → should redirect with error
- Try to delete a role with users → delete button disabled

---

### Task 11: Commit and push

```bash
git add \
  database/seeders/DatabaseSeeder.php \
  app/Http/Controllers/RoleController.php \
  app/Http/Controllers/PostController.php \
  app/Http/Controllers/PageController.php \
  app/Http/Controllers/TemplateController.php \
  app/Http/Controllers/CategoryController.php \
  app/Http/Controllers/TagController.php \
  app/Http/Controllers/MediaController.php \
  app/Http/Controllers/AutosaveController.php \
  app/Http/Controllers/BanController.php \
  app/Http/Controllers/CalendarController.php \
  app/Http/Controllers/UserController.php \
  app/Http/Middleware/HandleInertiaRequests.php \
  routes/web.php \
  resources/js/Layouts/AppLayout.vue \
  resources/js/Pages/Roles/Index.vue \
  resources/js/Pages/Roles/Form.vue \
  resources/js/Pages/Users/Form.vue \
  resources/js/Pages/Users/Index.vue
git commit -m "feat: custom roles & permissions system"
git push -u origin claude/research-cms-features-XG1g2
```
