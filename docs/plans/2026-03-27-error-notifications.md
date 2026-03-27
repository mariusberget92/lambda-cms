# Error Notifications Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Replace all inline `<p v-if="form.errors.x">` error messages with a grouped error card in the top-right notification system.

**Architecture:** Extend `useNotifications` to accept an `items: string[]` option. `NotificationItem.vue` renders a bullet list when items are present. Every form's submit callback gains an `onError` handler that calls `notify()` with the errors list. Inline error `<p>` elements are removed; red `border-destructive` on inputs stays.

**Tech Stack:** Vue 3 `<script setup>`, `@inertiajs/vue3` useForm, existing `useNotifications` composable.

---

### Task 1: Extend `useNotifications.js` with `items` support

**Files:**
- Modify: `resources/js/composables/useNotifications.js`

**Step 1: Add `items` to notify options and notification object**

Replace line 26–33 with:
```js
const id       = Date.now() + (++counter)
const duration = 'duration' in options ? options.duration : readingDuration(message)
const actions  = options.actions ?? []
const items    = options.items ?? []

// Enforce max 5 — remove oldest if needed
if (notifications.value.length >= 5) {
  notifications.value.shift()
}

notifications.value.push({ id, type, message, duration, actions, items })
return id
```

**Step 2: Verify the file looks correct**

Run: `cat -n resources/js/composables/useNotifications.js`
Expected: `items` extracted from options, pushed into notification object.

**Step 3: Commit**

```bash
git add resources/js/composables/useNotifications.js
git commit -m "feat: useNotifications — add items[] support for grouped error lists"
```

---

### Task 2: Update `NotificationItem.vue` to render items list

**Files:**
- Modify: `resources/js/components/NotificationItem.vue`

**Step 1: Add `items` prop**

In `defineProps`, add after `actions`:
```js
items: { type: Array, default: () => [] },
```

**Step 2: Render items list in template**

After the `<span class="text-sm leading-snug">{{ message }}</span>` line (line 48), add:
```html
<ul v-if="items.length" class="mt-1.5 space-y-0.5 list-disc list-inside">
  <li v-for="(item, i) in items" :key="i" class="text-xs text-muted-foreground leading-snug">
    {{ item }}
  </li>
</ul>
```

Note: the `<ul>` must be inside the `<div class="flex items-start gap-2 p-3 pr-8">` wrapper so it indents under the icon. Place it after the `<span>` but still inside the flex div.

**Step 3: Verify visually**

Open any page with the notification system in the browser and trigger a test notification in the console:
```js
// In browser console on any admin page:
// (the composable is not directly accessible in console,
//  so just check the template renders correctly by reading the file)
```

**Step 4: Commit**

```bash
git add resources/js/components/NotificationItem.vue
git commit -m "feat: NotificationItem — render bullet list when items[] present"
```

---

### Task 3: Update Auth pages

**Files:**
- Modify: `resources/js/Pages/Auth/Login.vue`
- Modify: `resources/js/Pages/Auth/ForgotPassword.vue`
- Modify: `resources/js/Pages/Auth/ResetPassword.vue`

**Step 1: Import useNotifications in each file**

Add to `<script setup>`:
```js
import { useNotifications } from '@/composables/useNotifications.js'
const { notify } = useNotifications()
```

**Step 2: Login.vue — add onError, remove inline errors**

Find the `form.post(route('auth.login'), {...})` call. Add/update the options object:
```js
form.post(route('auth.login'), {
  // existing options...
  onError: (errors) => notify('Please fix the following:', 'error', { items: Object.values(errors) }),
})
```

Remove:
```html
<p v-if="form.errors.email" class="mt-1 text-xs text-destructive">{{ form.errors.email }}</p>
<p v-if="form.errors.password" class="mt-1 text-xs text-destructive">{{ form.errors.password }}</p>
```

Keep `:class="{ 'border-destructive': form.errors.email }"` on inputs.

**Step 3: ForgotPassword.vue — same pattern**

```js
form.post(route('password.email'), {
  onError: (errors) => notify('Please fix the following:', 'error', { items: Object.values(errors) }),
})
```
Remove `<p v-if="form.errors.email">` line.

**Step 4: ResetPassword.vue — same pattern**

```js
form.post(route('password.update'), {
  // existing options...
  onError: (errors) => notify('Please fix the following:', 'error', { items: Object.values(errors) }),
})
```
Remove `<p v-if="form.errors.email">` and `<p v-if="form.errors.password">` lines.

**Step 5: Commit**

```bash
git add resources/js/Pages/Auth/Login.vue resources/js/Pages/Auth/ForgotPassword.vue resources/js/Pages/Auth/ResetPassword.vue
git commit -m "feat: auth pages — show validation errors in notification toast"
```

---

### Task 4: Update Users, Categories, Tags forms

**Files:**
- Modify: `resources/js/Pages/Users/Form.vue`
- Modify: `resources/js/Pages/Categories/Form.vue`
- Modify: `resources/js/Pages/Tags/Form.vue`

**Step 1: Each file — import and add onError**

Same pattern for all three. In `Users/Form.vue`, both `form.post(...)` and `form.put(...)` calls need `onError`:
```js
import { useNotifications } from '@/composables/useNotifications.js'
const { notify } = useNotifications()

// in submit():
form.post(route('users.store'), {
  onError: (errors) => notify('Please fix the following:', 'error', { items: Object.values(errors) }),
})
// and the put() call:
form.put(route('users.update', props.user.id), {
  onError: (errors) => notify('Please fix the following:', 'error', { items: Object.values(errors) }),
})
```

Apply the same pattern to `Categories/Form.vue` (errors: name, description) and `Tags/Form.vue` (error: name).

**Step 2: Remove all inline error `<p>` elements in each file**

Remove every `<p v-if="form.errors.x" class="mt-1 text-xs text-destructive">` line.
Keep `:class="{ 'border-destructive': form.errors.x }"` on inputs.

**Step 3: Commit**

```bash
git add resources/js/Pages/Users/Form.vue resources/js/Pages/Categories/Form.vue resources/js/Pages/Tags/Form.vue
git commit -m "feat: users/categories/tags forms — validation errors via notification toast"
```

---

### Task 5: Update Posts and Pages forms

**Files:**
- Modify: `resources/js/Pages/Posts/Create.vue`
- Modify: `resources/js/Pages/Posts/Edit.vue`
- Modify: `resources/js/Pages/Pages/Create.vue`
- Modify: `resources/js/Pages/Pages/Edit.vue`

**Step 1: Same pattern — import, add onError to each submit call**

For `Posts/Edit.vue` the submit is `form.put(route('posts.update', props.post.id), {...})`. Add:
```js
onError: (errors) => notify('Please fix the following:', 'error', { items: Object.values(errors) }),
```

Same for `Posts/Create.vue`, `Pages/Create.vue`, `Pages/Edit.vue`.

**Step 2: Remove inline error `<p>` elements in all four files**

Error fields in Posts: `title`, `excerpt`, `body`, `published_at`.
Error fields in Pages: `title`, `slug`.

**Step 3: Commit**

```bash
git add resources/js/Pages/Posts/Create.vue resources/js/Pages/Posts/Edit.vue resources/js/Pages/Pages/Create.vue resources/js/Pages/Pages/Edit.vue
git commit -m "feat: posts/pages forms — validation errors via notification toast"
```

---

### Task 6: Update remaining admin forms

**Files:**
- Modify: `resources/js/Pages/Navigation/Index.vue`
- Modify: `resources/js/Pages/Templates/Create.vue`
- Modify: `resources/js/Pages/Templates/Edit.vue`
- Modify: `resources/js/Pages/Profile/Edit.vue` (check for inline errors)
- Modify: `resources/js/Pages/Settings/Index.vue` (check for inline errors)

**Step 1: Same import + onError pattern in each**

Apply the same `onError` + `Object.values(errors)` pattern.
Remove all inline error `<p>` elements.

**Step 2: Profile/Edit.vue — note multiple forms**

Profile has separate forms (info, password, avatar). Each `form.post/put` call needs its own `onError`. Use the same `notify` instance (import once at the top of `<script setup>`).

**Step 3: Settings/Index.vue — check for inline errors**

Settings may use a different submit pattern. Read the file first and apply the same approach.

**Step 4: Commit**

```bash
git add resources/js/Pages/Navigation/Index.vue resources/js/Pages/Templates/Create.vue resources/js/Pages/Templates/Edit.vue resources/js/Pages/Profile/Edit.vue resources/js/Pages/Settings/Index.vue
git commit -m "feat: navigation/templates/profile/settings — validation errors via notification toast"
```

---

### Task 7: Update Blog comment form

**Files:**
- Modify: `resources/js/Pages/Blog/Show.vue`

**Note:** `Blog/Show.vue` is a public page. Check which layout it uses. If it does NOT use `AppLayout.vue`, the `<Notifications />` component may not be rendered. Read the file's `<script setup>` for layout assignment.

**Step 1: Read the file and check layout**

```bash
head -20 resources/js/Pages/Blog/Show.vue
```

**Step 2a: If AppLayout is used** — apply the same `onError` pattern and remove inline errors.

**Step 2b: If a different/no layout is used** — keep inline errors for the comment form only (do not convert it). Note this as a future task.

**Step 3: Commit if changed**

```bash
git add resources/js/Pages/Blog/Show.vue
git commit -m "feat: blog comment form — validation errors via notification toast"
```

---

### Task 8: Handle Install pages

**Files:**
- `resources/js/Pages/Install/Database.vue`
- `resources/js/Pages/Install/Site.vue`
- `resources/js/Pages/Install/Admin.vue`
- `resources/js/Pages/Install/Mail.vue`

**Note:** Install pages use a dedicated wizard layout, not `AppLayout.vue`. The `<Notifications />` component is only rendered inside AppLayout.

**Step 1: Check the Install layout**

```bash
grep -n "layout\|Notifications\|AppLayout" resources/js/Pages/Install/Database.vue
grep -n "Notifications" resources/js/Layouts/InstallLayout.vue 2>/dev/null || echo "no install layout found"
```

**Step 2a: If `<Notifications />` is NOT in the install layout** — add it to whatever layout Install pages use, then apply the `onError` pattern.

**Step 2b: If there is no shared install layout** — add `<Notifications />` directly to each Install page (inside a `<Teleport to="body">` if needed, or just import `Notifications.vue` component directly).

**Step 3: Apply onError + remove inline errors** in all four install pages once notifications are available.

**Step 4: Commit**

```bash
git add resources/js/Pages/Install/
git commit -m "feat: install pages — validation errors via notification toast"
```

---

### Task 9: Build and verify

**Step 1: Run production build**

```bash
npm run build
```
Expected: no errors, build succeeds.

**Step 2: Manual smoke test checklist**
- [ ] Submit Login form with blank fields → error toast appears with bullet list
- [ ] Submit Posts/Edit with blank title → error toast appears, red border on title input
- [ ] Submit Users/Create with duplicate email → error toast appears
- [ ] No inline error `<p>` text visible anywhere on any form

**Step 3: Final commit if any cleanup needed**

```bash
git add -A
git commit -m "feat: error notifications — all forms migrated to toast system"
```
