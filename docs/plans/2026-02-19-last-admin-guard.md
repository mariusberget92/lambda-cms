# Last-Administrator Guard Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Prevent the system from ever having zero administrators by blocking both deletion and role-demotion of the last administrator — enforced on the backend and surfaced clearly in the UI.

**Architecture:** `UserController` gains a private `adminCount()` helper. Both `update()` and `destroy()` call it and redirect with an error flash if the action would leave zero admins. The controller passes `adminCount` to the `Index` and `Form` (edit) pages. The frontend disables the delete button and locks the role select to `administrator` when the user being acted on is the only admin.

**Tech Stack:** Laravel 12, Inertia 2, Vue 3, Spatie laravel-permission v7

---

### Task 1: Backend — guard in `UserController`

**Files:**
- Modify: `app/Http/Controllers/UserController.php`

**Step 1: Add a private `adminCount()` helper method**

Add this private method anywhere in the class:

```php
private function adminCount(): int
{
    return \Spatie\Permission\Models\Role::where('name', 'administrator')
        ->first()
        ?->users()
        ->count() ?? 0;
}
```

**Step 2: Guard `update()` — block role demotion of last admin**

Inside `update()`, after `$validated` is set and **before** `$user->update(...)`, add:

```php
if (
    $user->hasRole('administrator') &&
    $validated['role'] !== 'administrator' &&
    $this->adminCount() <= 1
) {
    return redirect()
        ->route('users.index')
        ->with('error', 'There must always be at least one administrator.');
}
```

**Step 3: Guard `destroy()` — block deletion of last admin**

Inside `destroy()`, after the self-deletion check and **before** `$user->delete()`, add:

```php
if ($user->hasRole('administrator') && $this->adminCount() <= 1) {
    return redirect()
        ->route('users.index')
        ->with('error', 'There must always be at least one administrator.');
}
```

**Step 4: Pass `adminCount` from `index()`**

Change the `Inertia::render` call in `index()` from:

```php
return Inertia::render('Users/Index', [
    'users' => $users,
]);
```

to:

```php
return Inertia::render('Users/Index', [
    'users'      => $users,
    'adminCount' => $this->adminCount(),
]);
```

**Step 5: Pass `adminCount` from `edit()`**

Change the `Inertia::render` call in `edit()` from:

```php
return Inertia::render('Users/Form', [
    'user'  => [...],
    'roles' => Role::orderBy('name')->pluck('name'),
]);
```

to:

```php
return Inertia::render('Users/Form', [
    'user'       => [
        'id'    => $user->id,
        'name'  => $user->name,
        'email' => $user->email,
        'role'  => $user->getRoleNames()->first(),
    ],
    'roles'      => Role::orderBy('name')->pluck('name'),
    'adminCount' => $this->adminCount(),
]);
```

**Step 6: Manual smoke test (backend)**

- In tinker, confirm `adminCount()` logic: there is 1 admin.
- Via browser: try editing admin@lambda.test → change role to User → should redirect to users.index with red error flash.
- Try deleting admin@lambda.test via a second admin account → should succeed when there are 2 admins.

---

### Task 2: Frontend — `Users/Index.vue` disable delete for last admin

**Files:**
- Modify: `resources/js/Pages/Users/Index.vue`

**Step 1: Accept the new `adminCount` prop**

In `<script setup>`, update `defineProps`:

```js
const props = defineProps({
  users:      { type: Object, required: true },
  adminCount: { type: Number, default: 0 },
});
```

**Step 2: Add a `isLastAdmin(user)` helper**

Add this computed helper in `<script setup>`:

```js
function isLastAdmin(user) {
  return user.role === 'administrator' && props.adminCount <= 1;
}
```

**Step 3: Update the delete button**

Replace the existing delete `<button>` (the one with `v-if="user.id !== currentUserId"`) with this:

```html
<button
  v-if="user.id !== currentUserId"
  type="button"
  @click="!isLastAdmin(user) && confirmDelete(user)"
  :disabled="isLastAdmin(user)"
  :title="isLastAdmin(user) ? 'Cannot delete the only administrator' : 'Delete'"
  class="rounded-md p-1.5 text-muted-foreground transition-colors"
  :class="isLastAdmin(user)
    ? 'opacity-30 cursor-not-allowed'
    : 'hover:bg-destructive/10 hover:text-destructive'"
>
  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
  </svg>
</button>
```

**Step 4: Verify in browser**

- With only 1 admin: the delete button on the admin row should appear faded/disabled. Hovering shows the tooltip "Cannot delete the only administrator".
- With 2+ admins: both delete buttons work normally.

---

### Task 3: Frontend — `Users/Form.vue` lock role select for last admin

**Files:**
- Modify: `resources/js/Pages/Users/Form.vue`

**Step 1: Accept the new `adminCount` prop**

In `<script setup>`, update `defineProps`:

```js
const props = defineProps({
  user:       { type: Object, default: null },
  roles:      { type: Array,  default: () => [] },
  adminCount: { type: Number, default: 0 },
});
```

**Step 2: Add a `isLastAdmin` computed**

```js
const isLastAdmin = computed(
  () => props.user?.role === 'administrator' && props.adminCount <= 1
);
```

**Step 3: Disable the role `<select>` when editing the last admin**

Update the role `<select>` element:

```html
<select
  id="role"
  v-model="form.role"
  :disabled="isLastAdmin"
  class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring disabled:opacity-50 disabled:cursor-not-allowed"
  :class="{ 'border-destructive': form.errors.role }"
>
  <option value="">— Select a role —</option>
  <option v-for="r in roles" :key="r" :value="r">
    {{ r === 'administrator' ? 'Administrator' : 'User' }}
  </option>
</select>
```

**Step 4: Show a helper note when locked**

Below the existing role description `<p>`, add:

```html
<p v-if="isLastAdmin" class="text-xs text-amber-600 flex items-center gap-1">
  <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
  </svg>
  Role cannot be changed — this is the only administrator.
</p>
```

**Step 5: Verify in browser**

- Edit the sole admin → role select should be greyed out and non-interactive; amber warning note shown.
- Invite a second admin, then edit the first → role select should now be enabled.

---

### Task 4: Commit

```bash
git add app/Http/Controllers/UserController.php \
        resources/js/Pages/Users/Index.vue \
        resources/js/Pages/Users/Form.vue
git commit -m "feat: prevent deletion or demotion of the last administrator"
```
