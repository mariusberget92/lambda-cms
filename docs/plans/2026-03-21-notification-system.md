# Notification System Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Replace all scattered inline flash banners and autosave status indicators with a centralized toast notification system that slides in from the top-right corner with a reading-speed-based auto-dismiss timer.

**Architecture:** A module-level singleton composable (`useNotifications`) holds a reactive `notifications` array. A `Notifications.vue` host component is mounted in `AppLayout` and `AuthLayout`; it watches `$page.props.flash.status` and `flash.error` and calls `notify()` automatically. A `NotificationItem.vue` renders each card with CSS-transition progress bar and optional action buttons.

**Tech Stack:** Vue 3 (Composition API, `<script setup>`), `lucide-vue-next`, Tailwind CSS 4, Inertia `usePage()`

---

## Project context

- `@/` maps to `resources/js/`
- Layouts: `AppLayout.vue` (all admin pages), `AuthLayout.vue` (login/forgot-password), `BlogLayout.vue` (public — leave untouched)
- CSS status tokens already exist in `app.scss`: `--color-status-success-fg`, `--color-status-error-fg`, `--color-status-warning-fg`
- Project rule: **never use raw `<svg>` elements** — always `lucide-vue-next` components
- Run tests with: `php artisan test`

---

## Task 1: `useNotifications` composable

**Files:**
- Create: `resources/js/composables/useNotifications.js`

The composable must be a **module-level singleton** — the `notifications` array is declared outside the function so all components share the same instance.

**Step 1: Create the file**

```js
// resources/js/composables/useNotifications.js
import { ref } from 'vue'

// Module-level singleton — shared across all component instances
const notifications = ref([])
let counter = 0

function readingDuration(message) {
  const words = message.trim().split(/\s+/).length
  return Math.max(3000, words * 350) // ~170 WPM, 3 s floor
}

export function useNotifications() {
  function notify(message, type = 'success', options = {}) {
    const id = Date.now() + (++counter)
    const duration = 'duration' in options ? options.duration : readingDuration(message)
    const actions  = options.actions ?? []

    // Enforce max 5 — remove oldest if needed
    if (notifications.value.length >= 5) {
      notifications.value.shift()
    }

    notifications.value.push({ id, type, message, duration, actions })
    return id
  }

  function dismiss(id) {
    const idx = notifications.value.findIndex(n => n.id === id)
    if (idx !== -1) notifications.value.splice(idx, 1)
  }

  return { notifications, notify, dismiss }
}
```

**Step 2: Manual verification**

Open browser devtools console on any admin page and run:
```js
// After importing — just verify the shape is correct by checking the composable exports
// (no automated test runner for Vue composables in this project)
```

No automated test needed here — the composable is exercised fully in Tasks 2–7.

**Step 3: Commit**

```bash
git add resources/js/composables/useNotifications.js
git commit -m "feat: add useNotifications composable (singleton)"
```

---

## Task 2: `NotificationItem.vue` component

**Files:**
- Create: `resources/js/Components/NotificationItem.vue`

**Step 1: Create the file**

```vue
<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { CircleCheck, CircleX, TriangleAlert, Info, X } from 'lucide-vue-next'
import { useNotifications } from '@/composables/useNotifications.js'

const props = defineProps({
  notification: { type: Object, required: true },
})

const { dismiss } = useNotifications()

// Icon + color maps (keyed by type)
const ICONS = {
  success: CircleCheck,
  error:   CircleX,
  warning: TriangleAlert,
  info:    Info,
}

const ACCENT_COLORS = {
  success: 'var(--color-status-success-fg)',
  error:   'var(--color-status-error-fg)',
  warning: 'var(--color-status-warning-fg)',
  info:    'hsl(var(--primary))',
}

const icon        = ICONS[props.notification.type]  ?? Info
const accentColor = ACCENT_COLORS[props.notification.type] ?? ACCENT_COLORS.info

// Progress bar — CSS transition approach
// barWidth starts at 100%, transitions to 0% over `duration` ms
const barWidth      = ref('100%')
const barTransition = ref('none')
let dismissTimer    = null

onMounted(() => {
  if (props.notification.duration === null) return

  // One rAF delay so the browser paints the bar at 100% before transitioning
  requestAnimationFrame(() => {
    barTransition.value = `width ${props.notification.duration}ms linear`
    barWidth.value      = '0%'
    dismissTimer = setTimeout(
      () => dismiss(props.notification.id),
      props.notification.duration
    )
  })
})

onBeforeUnmount(() => {
  if (dismissTimer) clearTimeout(dismissTimer)
})

function handleAction(handler) {
  handler()
  dismiss(props.notification.id)
}
</script>

<template>
  <div
    class="relative w-80 rounded-md border border-l-4 bg-background shadow-md overflow-hidden"
    :style="{ borderLeftColor: accentColor }"
  >
    <div class="px-4 py-3">
      <!-- Icon + message + close button -->
      <div class="flex items-start gap-3">
        <component
          :is="icon"
          class="w-4 h-4 mt-0.5 shrink-0"
          :style="{ color: accentColor }"
        />
        <p class="flex-1 text-sm leading-snug">{{ notification.message }}</p>
        <button
          type="button"
          @click="dismiss(notification.id)"
          class="text-muted-foreground hover:text-foreground transition-colors"
          aria-label="Dismiss"
        >
          <X class="w-3.5 h-3.5" />
        </button>
      </div>

      <!-- Action buttons (optional — used for autosave restore) -->
      <div v-if="notification.actions.length" class="mt-2 flex gap-2 pl-7">
        <button
          v-for="(action, i) in notification.actions"
          :key="action.label"
          type="button"
          @click="handleAction(action.handler)"
          :class="i === 0
            ? 'rounded-md bg-primary px-3 py-1 text-xs font-medium text-primary-foreground hover:bg-[var(--primary-hover)] transition-colors'
            : 'rounded-md border px-3 py-1 text-xs font-medium hover:bg-accent transition-colors'"
        >
          {{ action.label }}
        </button>
      </div>
    </div>

    <!-- Progress bar (hidden for persistent notifications) -->
    <div
      v-if="notification.duration !== null"
      class="absolute bottom-0 left-0 h-0.5"
      :style="{
        width:      barWidth,
        transition: barTransition,
        backgroundColor: accentColor,
      }"
    />
  </div>
</template>
```

**Step 2: Commit**

```bash
git add resources/js/Components/NotificationItem.vue
git commit -m "feat: NotificationItem component with progress bar and action buttons"
```

---

## Task 3: `Notifications.vue` host component

**Files:**
- Create: `resources/js/Components/Notifications.vue`

This component:
1. Renders a fixed top-right `<TransitionGroup>` stack of `NotificationItem`s
2. Watches `$page.props.flash.status` and `flash.error` and fires `notify()` when they have a value

**Step 1: Create the file**

```vue
<script setup>
import { watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useNotifications } from '@/composables/useNotifications.js'
import NotificationItem from '@/Components/NotificationItem.vue'

const { notifications, notify } = useNotifications()
const page = usePage()

// Watch flash.status — shown on every Inertia redirect that sets it
watch(
  () => page.props.flash?.status,
  (val) => { if (val) notify(val, 'success') },
  { immediate: true }
)

// Watch flash.error — e.g. Dashboard shows a server-side error banner
watch(
  () => page.props.flash?.error,
  (val) => { if (val) notify(val, 'error') },
  { immediate: true }
)
</script>

<template>
  <Teleport to="body">
    <div class="fixed top-4 right-4 z-50 flex flex-col gap-2 pointer-events-none">
      <TransitionGroup name="notification" tag="div" class="flex flex-col gap-2">
        <div
          v-for="n in notifications"
          :key="n.id"
          class="pointer-events-auto"
        >
          <NotificationItem :notification="n" />
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<style scoped>
.notification-enter-active {
  transition: transform 250ms ease-out, opacity 250ms ease-out;
}
.notification-leave-active {
  transition: transform 200ms ease-in, opacity 200ms ease-in;
  position: absolute;
}
.notification-enter-from,
.notification-leave-to {
  transform: translateX(110%);
  opacity: 0;
}
</style>
```

**Step 2: Commit**

```bash
git add resources/js/Components/Notifications.vue
git commit -m "feat: Notifications host component with flash watcher and slide animation"
```

---

## Task 4: Mount in AppLayout + AuthLayout, delete FlashMessage.vue

**Files:**
- Modify: `resources/js/Layouts/AppLayout.vue`
- Modify: `resources/js/Layouts/AuthLayout.vue`
- Delete: `resources/js/Components/FlashMessage.vue`

**Step 1: Update AppLayout**

In `resources/js/Layouts/AppLayout.vue`:

1. Add import in `<script setup>`:
```js
import Notifications from '@/Components/Notifications.vue'
```

2. Add `<Notifications />` as the last child of the root `<div class="flex h-screen ...">`:
```html
  </div><!-- closes flex h-screen -->
  <Notifications />
</template>
```

The full end of the template should look like:
```html
    <!-- Main area -->
    <div class="flex flex-col flex-1 overflow-hidden">
      ...
    </div>
  </div>
  <Notifications />
</template>
```

**Step 2: Update AuthLayout**

In `resources/js/Layouts/AuthLayout.vue`:

1. Add import in `<script setup>`:
```js
import Notifications from '@/Components/Notifications.vue'
```

2. Add `<Notifications />` just before the closing `</template>`:
```html
    <!-- Dark mode toggle ... -->
    <button ...>...</button>

    <Notifications />
  </div>
</template>
```

**Step 3: Delete FlashMessage.vue**

```bash
rm resources/js/Components/FlashMessage.vue
```

**Step 4: Verify the app loads**

```bash
npm run build 2>&1 | tail -5
```

Expected: no errors. If `FlashMessage` is imported anywhere, fix those imports (search: `grep -r "FlashMessage" resources/js`).

**Step 5: Run tests**

```bash
php artisan test
```

Expected: all passing (no backend changes).

**Step 6: Commit**

```bash
git add resources/js/Layouts/AppLayout.vue resources/js/Layouts/AuthLayout.vue
git rm resources/js/Components/FlashMessage.vue
git commit -m "feat: mount Notifications in AppLayout and AuthLayout, remove FlashMessage"
```

---

## Task 5: Remove inline flash banners from admin + auth pages

**Files to modify** (remove the inline flash banner HTML from each):

**Admin pages (AppLayout):**
- `resources/js/Pages/Categories/Index.vue`
- `resources/js/Pages/Comments/Index.vue`
- `resources/js/Pages/Dashboard/Index.vue`
- `resources/js/Pages/Navigation/Index.vue`
- `resources/js/Pages/Pages/Index.vue`
- `resources/js/Pages/Posts/Index.vue`
- `resources/js/Pages/Profile/Index.vue`
- `resources/js/Pages/Settings/Index.vue`
- `resources/js/Pages/Tags/Index.vue`
- `resources/js/Pages/Users/Index.vue`

**Auth pages (AuthLayout):**
- `resources/js/Pages/Auth/Login.vue`
- `resources/js/Pages/Auth/ForgotPassword.vue`

**Do NOT touch:**
- `resources/js/Pages/Blog/Show.vue` — uses `BlogLayout` (public page, Notifications not mounted there)

---

**What to remove from each page:**

For each file, read it first, then delete the flash banner block. The pattern varies slightly per file but is always one of these two shapes:

**Shape A — success banner** (most pages):
```html
<Transition name="fade">
  <div v-if="$page.props.flash?.status" class="...">
    ...
    {{ $page.props.flash.status }}
  </div>
</Transition>
```

**Shape B — error banner** (Dashboard/Index.vue):
```html
<div v-if="$page.props.flash?.error" class="...">
  ...
  {{ $page.props.flash.error }}
</div>
```

**Shape C — Auth pages** (Login.vue, ForgotPassword.vue) — inline without Transition wrapper:
```html
<div v-if="$page.props.flash?.status" class="text-sm text-green-600 ...">
  {{ $page.props.flash.status }}
</div>
```

For each page: read the file, find the banner block, delete it. Also remove any `fade` transition CSS in `<style>` blocks if they're only used for the flash banner.

Also check if `FlashMessage` is imported anywhere (it should not be, since it was never wired into AppLayout — but double-check):
```bash
grep -r "FlashMessage" resources/js/Pages
```
Expected: no results.

**After editing all files:**

```bash
npm run build 2>&1 | tail -5
php artisan test
```

**Commit:**

```bash
git add resources/js/Pages/
git commit -m "refactor: remove inline flash banners (now handled by Notifications component)"
```

---

## Task 6: Wire autosave in `Posts/Edit.vue` to `notify()`

**Files:**
- Modify: `resources/js/Pages/Posts/Edit.vue`

Replace the `autosaveStatus`/`autosaveSavedAt`/`showRestoreBanner` refs and their template HTML with calls to the notification system.

**Step 1: Read the current file**

Read `resources/js/Pages/Posts/Edit.vue` to see the exact current state.

**Step 2: Update `<script setup>`**

**Add import:**
```js
import { useNotifications } from '@/composables/useNotifications.js'
```

**Add after `useForm(...)` call:**
```js
const { notify } = useNotifications()
```

**Remove these three lines entirely:**
```js
const autosaveStatus  = ref(null) // null | 'saving' | 'saved' | 'error'
const autosaveSavedAt = ref(null)

const showRestoreBanner = ref(
  props.autosave !== null &&
  props.post.updated_at !== null &&
  new Date(props.autosave.updated_at) > new Date(props.post.updated_at)
)
```

**Replace `doAutosave()` with:**
```js
async function doAutosave() {
  try {
    const res = await axios.post(route('posts.autosave', props.post.id), {
      payload: form.data(),
    })
    notify(`Draft saved at ${res.data.saved_at}`, 'info')
  } catch {
    notify('Autosave failed — check your connection', 'error')
  }
}
```

**Add restore banner notification (just after the `notify` const):**
```js
// Show persistent restore banner if autosave is newer than last save
if (
  props.autosave !== null &&
  props.post.updated_at !== null &&
  new Date(props.autosave.updated_at) > new Date(props.post.updated_at)
) {
  notify('You have unsaved changes from a previous session.', 'info', {
    duration: null,
    actions: [
      { label: 'Restore', handler: restoreAutosave },
      { label: 'Dismiss', handler: dismissAutosave },
    ],
  })
}
```

**Important:** This `notify()` call references `restoreAutosave` and `dismissAutosave` — make sure it appears *after* those functions are defined in the file. Hoist the `notify` call to just before the `onBeforeUnmount` line, or move it after both functions. The simplest approach: move this block to the end of the `<script setup>`, after all function definitions.

**Update `restoreAutosave()`** — remove the `showRestoreBanner.value = false` line (the notification system dismisses it via the action handler):
```js
async function restoreAutosave() {
  const payload = props.autosave.payload
  Object.keys(payload).forEach(key => {
    if (key in form) form[key] = payload[key]
  })
  await axios.delete(route('posts.autosave.destroy', props.post.id))
}
```

**Update `dismissAutosave()`** — same, remove the ref assignment:
```js
async function dismissAutosave() {
  await axios.delete(route('posts.autosave.destroy', props.post.id))
}
```

**Step 3: Update the template**

**Remove** the autosave recovery banner `<div>` at the top of the form:
```html
<!-- Autosave recovery banner -->
<div
  v-if="showRestoreBanner"
  class="mb-4 flex items-center gap-3 rounded-md border border-amber-300 ..."
>
  ...
</div>
```

**Remove** the three autosave status `<span>` elements next to the action buttons:
```html
<span v-if="autosaveStatus === 'saving'" class="...">Saving draft…</span>
<span v-else-if="autosaveStatus === 'saved'" class="...">Draft saved at {{ autosaveSavedAt }}</span>
<span v-else-if="autosaveStatus === 'error'" class="...">Autosave failed</span>
```

**Step 4: Check `ref` imports**

If `showRestoreBanner`, `autosaveStatus`, and `autosaveSavedAt` were the only `ref` uses removed, check whether `ref` is still needed elsewhere in the file. It is — `revisionsOpen`, `revisionsLoading`, `revisions`, `showMediaPicker`, `featuredImage` all use `ref`. Keep the import.

**Step 5: Build and test**

```bash
npm run build 2>&1 | tail -5
php artisan test
```

**Step 6: Smoke test**

1. Open a post edit page
2. Change the title — wait 10 s — verify a blue "Draft saved at HH:MM" toast appears top-right
3. Navigate away and back to a post that has an autosave — verify "You have unsaved changes" toast appears with Restore/Dismiss buttons
4. Click Restore — verify form fields update and toast disappears

**Step 7: Commit**

```bash
git add resources/js/Pages/Posts/Edit.vue
git commit -m "refactor: wire Posts/Edit autosave to notification system"
```

---

## Task 7: Wire autosave in `Pages/Edit.vue` to `notify()`

**Files:**
- Modify: `resources/js/Pages/Pages/Edit.vue`

Exact same changes as Task 6, but for pages. The autosave routes are `pages.autosave` and `pages.autosave.destroy`, and the record prop is `props.page` (not `props.post`).

**Step 1: Read the current file**

Read `resources/js/Pages/Pages/Edit.vue`.

**Step 2: Update `<script setup>`**

**Add import:**
```js
import { useNotifications } from '@/composables/useNotifications.js'
```

**Add after `useForm(...)` call:**
```js
const { notify } = useNotifications()
```

**Remove:**
```js
const autosaveStatus  = ref(null)
const autosaveSavedAt = ref(null)

const showRestoreBanner = ref(
  props.autosave !== null &&
  props.page.updated_at !== null &&
  new Date(props.autosave.updated_at) > new Date(props.page.updated_at)
)
```

**Replace `doAutosave()`:**
```js
async function doAutosave() {
  try {
    const res = await axios.post(route('pages.autosave', props.page.id), {
      payload: form.data(),
    })
    notify(`Draft saved at ${res.data.saved_at}`, 'info')
  } catch {
    notify('Autosave failed — check your connection', 'error')
  }
}
```

**Add restore banner notification (after all function definitions):**
```js
if (
  props.autosave !== null &&
  props.page.updated_at !== null &&
  new Date(props.autosave.updated_at) > new Date(props.page.updated_at)
) {
  notify('You have unsaved changes from a previous session.', 'info', {
    duration: null,
    actions: [
      { label: 'Restore', handler: restoreAutosave },
      { label: 'Dismiss', handler: dismissAutosave },
    ],
  })
}
```

**Update `restoreAutosave()` and `dismissAutosave()`** — remove `showRestoreBanner.value = false` from both:
```js
async function restoreAutosave() {
  const payload = props.autosave.payload
  Object.keys(payload).forEach(key => {
    if (key in form) form[key] = payload[key]
  })
  await axios.delete(route('pages.autosave.destroy', props.page.id))
}

async function dismissAutosave() {
  await axios.delete(route('pages.autosave.destroy', props.page.id))
}
```

**Step 3: Update the template**

Remove the autosave recovery banner `<div v-if="showRestoreBanner" ...>`.

Remove the three autosave status `<span>` elements:
```html
<span v-if="autosaveStatus === 'saving'" class="...">Saving draft…</span>
<span v-else-if="autosaveStatus === 'saved'" class="...">Draft saved at {{ autosaveSavedAt }}</span>
<span v-else-if="autosaveStatus === 'error'" class="...">Autosave failed</span>
```

**Step 4: Check `ref` imports**

`ref` is still used by `revisionsOpen`, `revisionsLoading`, `revisions` — keep the import.

**Step 5: Build and test**

```bash
npm run build 2>&1 | tail -5
php artisan test
```

Expected: all 364 tests passing.

**Step 6: Commit**

```bash
git add resources/js/Pages/Pages/Edit.vue
git commit -m "refactor: wire Pages/Edit autosave to notification system"
```
