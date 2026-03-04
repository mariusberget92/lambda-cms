# Design: Nord Semantic Color Tokens (Hardcoded Color Cleanup)

**Date:** 2026-03-04
**Branch:** new worktree from master

## Problem

29 hardcoded Tailwind color classes across 9 Vue files break dark mode because they render the same hue regardless of the `.dark` class. The Nord CSS token system in `app.scss` already provides `--color-status-*` tokens but is missing role and indicator tokens.

## Solution

Extend `app.scss` with dedicated role and indicator tokens, then replace all hardcoded color classes with semantic utility classes.

## New Tokens (app.scss)

Add to `:root`, `.dark`, and `@theme inline`:

### Role badges
| Token | Light value | Dark value |
|-------|-------------|------------|
| `--color-role-admin-bg` | `color-mix(in srgb, #5e81ac 15%, transparent)` | `color-mix(in srgb, #88c0d0 15%, transparent)` |
| `--color-role-admin-fg` | `#3b5e8a` | `#88c0d0` |
| `--color-role-user-bg` | `color-mix(in srgb, #4c566a 12%, transparent)` | `color-mix(in srgb, #4c566a 25%, transparent)` |
| `--color-role-user-fg` | `#4c566a` | `#9ca3af` |

### Presence / status dots
| Token | Light value | Dark value |
|-------|-------------|------------|
| `--color-online-dot` | `#a3be8c` | `#a3be8c` |

### @theme inline aliases
```
--color-role-admin-bg: var(--color-role-admin-bg)  → bg-role-admin-bg
--color-role-admin-fg: var(--color-role-admin-fg)  → text-role-admin-fg
--color-role-user-bg:  var(--color-role-user-bg)   → bg-role-user-bg
--color-role-user-fg:  var(--color-role-user-fg)   → text-role-user-fg
--color-online-dot:    var(--color-online-dot)      → bg-online-dot
```

## Replacement Map

| Semantic purpose | Old classes | New classes |
|------------------|-------------|-------------|
| Success flash banner | `bg-green-50 border-green-200 text-green-700` | `bg-status-success-bg border-status-success-border text-status-success-fg` |
| Success icon bg | `bg-green-100 text-green-600` | `bg-status-success-bg text-status-success-fg` |
| Published status dot | `bg-green-500` | `bg-status-success-fg` |
| Verified checkmark | `text-green-600` | `text-status-success-fg` |
| Online presence dot | `bg-green-500 ring-card` | `bg-online-dot ring-card` |
| Warning flash / text | `bg-amber-100 text-amber-600/700` | `bg-status-warning-bg text-status-warning-fg` |
| Draft status dot | `bg-amber-500` | `bg-status-warning-fg` |
| Warning text inline | `text-amber-600`, `text-amber-700` | `text-status-warning-fg` |
| Error flash banner | `bg-red-50 border-red-200 text-red-700` | `bg-status-error-bg border-status-error-border text-status-error-fg` |
| Administrator badge | `bg-indigo-100 text-indigo-700` | `bg-role-admin-bg text-role-admin-fg` |
| User badge | `bg-slate-100 text-slate-600` | `bg-role-user-bg text-role-user-fg` |
| Modal overlays | `bg-black/40`, `bg-black/80` | **unchanged** — intentional darkening overlay |

## Files Affected

- `resources/scss/app.scss` — add 5 new tokens to `:root`, `.dark`, `@theme inline`
- `resources/js/Pages/Dashboard/Index.vue`
- `resources/js/Pages/Posts/Index.vue`
- `resources/js/Pages/Categories/Index.vue`
- `resources/js/Pages/Tags/Index.vue`
- `resources/js/Pages/Users/Index.vue`
- `resources/js/Pages/Users/Form.vue`
- `resources/js/Pages/Profile/Index.vue`
- `resources/js/Pages/Media/Index.vue`
- `resources/js/Pages/Settings/Index.vue`

## Testing

- Visual: toggle dark mode and verify all banners, badges, and dots adapt correctly
- No new backend tests needed — purely frontend/CSS
- Run `npm run build` to confirm no compilation errors
