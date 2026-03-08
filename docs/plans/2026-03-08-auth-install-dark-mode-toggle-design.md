# Dark Mode Toggle — Auth & Installer Pages Design

**Date:** 2026-03-08
**Status:** Approved

## Problem

The dark mode toggle only exists inside `AppLayout.vue`'s topbar. Auth pages (`/login`, `/forgot-password`, `/reset-password`) use `layout: null` and installer pages use `InstallLayout.vue` — neither renders the toggle. Additionally, `initTheme()` is only called in `AppLayout.vue`'s `onMounted`, so the saved localStorage preference is never applied when a user lands directly on any of these pages.

## Goal

Every page except the public blog frontend has a working dark mode toggle and correctly applies the user's saved theme preference on first load.

## Solution — Option A

Three targeted changes:

### 1. `app.js` — Global `initTheme()` call

Call `initTheme()` once before the Vue app mounts so the saved preference is applied on every page load regardless of layout.

### 2. New `AuthLayout.vue`

A minimal centered-card layout matching the current inline structure of the auth pages, with a fixed-position dark mode toggle button in the top-right corner (`fixed top-4 right-4`).

- Uses the same `Sun`/`Moon` icons from `lucide-vue-next` as `AppLayout.vue`
- Uses `useTheme` composable (`isDark`, `toggleTheme`, `initTheme`)
- `Login.vue`, `ForgotPassword.vue`, and `ResetPassword.vue` switch from `layout: null` to `layout: AuthLayout`
- The centered card markup currently inline in each page moves into the layout's `<slot>`

### 3. `InstallLayout.vue` — Add toggle

Add the same fixed-position toggle button to the existing installer layout. The step-progress bar and card structure are untouched.

## Files Changed

| File | Change |
|------|--------|
| `resources/js/app.js` | Add `initTheme()` call before `createApp` mount |
| `resources/js/Layouts/AuthLayout.vue` | **Create** — centered card + fixed toggle |
| `resources/js/Layouts/InstallLayout.vue` | Add fixed toggle button |
| `resources/js/Pages/Auth/Login.vue` | Switch to `AuthLayout`, remove inline wrapper |
| `resources/js/Pages/Auth/ForgotPassword.vue` | Switch to `AuthLayout`, remove inline wrapper |
| `resources/js/Pages/Auth/ResetPassword.vue` | Switch to `AuthLayout`, remove inline wrapper |

## Toggle Placement

`fixed top-4 right-4` — floating in the top-right corner of the viewport. Appropriate for pages with no topbar. `AppLayout.vue` is unchanged (toggle lives in its topbar).

## Testing

Manual verification only (pure frontend change):
- Load `/login` directly → saved theme preference applied on load
- Toggle button appears top-right, switches between light/dark
- Theme persists across navigation to `/forgot-password`
- Installer pages show toggle and apply saved preference
- Dashboard (`AppLayout`) unaffected
