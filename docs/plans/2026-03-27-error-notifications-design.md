# Error Notifications Design

**Date:** 2026-03-27
**Status:** Approved

## Problem

Form validation errors are currently displayed as inline `<p>` elements beneath each input field. The project has a fully-built top-right notification/toast system (`useNotifications` composable + `Notifications.vue`) that should be the single channel for user feedback.

## Solution

Extend the existing notification system to support a list of items inside a single notification card. Remove all inline error text from forms. Keep the red `border-destructive` on inputs as a positional hint.

## Architecture

### `useNotifications.js`
- `notify(message, type, options?)` already accepts an options object
- Add `items?: string[]` to the options shape
- Notification objects in state gain an `items` field

### `NotificationItem.vue`
- When `notification.items` is present and non-empty, render a `<ul>` below the message
- Each item is a `<li>` styled consistently with the existing toast design
- No changes to auto-dismiss, progress bar, or action button behaviour

### Visual structure (error with list)
```
┌─────────────────────────────────────┐
│ ● Please fix the following:     [X] │
│   • Title is required               │
│   • Status is required              │
│   • Published date is invalid       │
│ [progress bar]                      │
└─────────────────────────────────────┘
```

### Form call site pattern
Each form's submit call gains an `onError` callback:

```js
form.post(route('posts.store'), {
  onError: (errors) => {
    notify('Please fix the following:', 'error', {
      items: Object.values(errors),
    })
  },
})
```

Remove all `<p v-if="form.errors.x">` elements. Keep `:class="{ 'border-destructive': form.errors.x }"` on inputs.

## Scope of Changes

| File | Change |
|------|--------|
| `resources/js/composables/useNotifications.js` | Add `items` to options + notification object |
| `resources/js/components/NotificationItem.vue` | Render items list when present |
| `resources/js/Pages/Posts/Edit.vue` | onError + remove inline errors |
| `resources/js/Pages/Pages/Edit.vue` | onError + remove inline errors |
| `resources/js/Pages/Users/Form.vue` | onError + remove inline errors |
| `resources/js/Pages/Profile/Edit.vue` | onError + remove inline errors |
| `resources/js/Pages/Auth/Login.vue` | onError + remove inline errors |
| `resources/js/Pages/Auth/ResetPassword.vue` | onError + remove inline errors |
| `resources/js/Pages/Auth/ForgotPassword.vue` | onError + remove inline errors |
| `resources/js/Pages/Settings/Index.vue` | onError + remove inline errors |
| Any other forms with inline `form.errors` | Same pattern |

## Non-Goals

- No changes to flash message handling (already works via Notifications.vue watcher)
- No changes to the notification positioning, animation, or auto-dismiss logic
- Tags page and other non-form pages untouched
