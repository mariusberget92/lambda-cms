# Notification System — Design

**Date:** 2026-03-21
**Status:** Approved

---

## Overview

Replace all scattered inline flash banners and autosave status indicators with a single, centralized toast notification system. Notifications slide in from the top-right corner with a progress bar that shrinks over a reading-speed-based duration.

---

## Scope

Replaces:
- Inline `flash.status` success banners on individual pages
- Inline `flash.error` banners on individual pages
- Autosave status indicator ("Draft saved at HH:MM", saving spinner, error text) in Posts/Edit.vue and Pages/Edit.vue
- "Restore autosave" dismissible banner in Posts/Edit.vue and Pages/Edit.vue
- Unused `FlashMessage.vue` component (deleted)

---

## Architecture

**Approach:** Composable + provide/inject. No new dependencies.

**Files:**
- `resources/js/composables/useNotifications.js` — reactive state + `notify()` / `dismiss()` functions
- `resources/js/Components/Notifications.vue` — `<TransitionGroup>` host, mounted in AppLayout, watches flash props
- `resources/js/Components/NotificationItem.vue` — single notification card with animation and progress bar

---

## Notification Shape

```js
{
  id: number,           // Date.now() + counter
  type: 'success' | 'error' | 'warning' | 'info',
  message: string,
  duration: number | null,  // ms; null = persistent (no auto-dismiss)
  actions: [{ label: string, handler: Function }]  // optional
}
```

---

## Duration Formula

```js
function readingDuration(message) {
  const words = message.trim().split(/\s+/).length
  return Math.max(3000, words * 350)  // ~170 WPM, 3 s floor
}
```

Persistent notifications (`duration: null`) have no progress bar and remain until the user clicks ✕ or an action button.

Up to **5** notifications visible simultaneously. When a 6th arrives, the oldest is dismissed.

---

## Visual Design

| Property | Value |
|---|---|
| Position | `fixed top-4 right-4 z-50` |
| Width | `w-80` |
| Stack direction | Downward, `gap-2` |
| Card | `rounded-md border shadow-md bg-background` |
| Type accent | 4 px left border in type color |
| Progress bar | 2 px strip pinned to card bottom, shrinks left-to-right |
| Max visible | 5 (oldest auto-dismissed on overflow) |

**Type colors** (reuse existing CSS tokens):
| Type | Border / bar color | Icon (lucide-vue-next) |
|---|---|---|
| success | `--color-status-success-fg` | `CircleCheck` |
| error | `--color-status-error-fg` | `CircleX` |
| warning | `--color-status-warning-fg` | `TriangleAlert` |
| info | `hsl(var(--primary))` | `Info` |

---

## Animation

**Enter:** slide in from right — `translateX(110%) → translateX(0)`, `opacity 0 → 1`, 250 ms ease-out.

**Leave:** slide out to right — `translateX(0) → translateX(110%)`, `opacity 1 → 0`, 200 ms ease-in.

**Progress bar:** CSS `transition: width linear` over `duration` ms, triggered one tick after mount so the browser paints the bar at 100% width first.

---

## Integration Points

### AppLayout

- Mounts `<Notifications />` once (no props needed — it reads flash internally).
- `Notifications.vue` watches `$page.props.flash.status` and `flash.error` with `watchEffect`, calls `notify()` when they change.

### Posts/Edit.vue and Pages/Edit.vue — Autosave

Replace current inline status with `notify()` calls:

```js
// Saving (no notification — too noisy)
// On success:
notify(`Draft saved at ${savedAt}`, 'info')
// On error:
notify('Autosave failed — check your connection', 'error')
```

### Posts/Edit.vue and Pages/Edit.vue — Restore banner

Replace current dismissible banner with a persistent notification:

```js
notify(
  `You have unsaved changes from ${minutesAgo} minutes ago`,
  'info',
  {
    duration: null,
    actions: [
      { label: 'Restore', handler: restoreAutosave },
      { label: 'Dismiss', handler: dismissAutosave },
    ],
  }
)
```

---

## useNotifications.js API

```js
const { notify, dismiss, notifications } = useNotifications()

// notify(message, type?, options?)
notify('Post created successfully.')                  // success, auto-duration
notify('Something went wrong.', 'error')
notify('Unsaved changes…', 'info', { duration: null, actions: [...] })

// dismiss(id)
dismiss(42)
```

Default type is `'success'`.

---

## Deleted File

- `resources/js/Components/FlashMessage.vue` — remove (unused, superseded)
