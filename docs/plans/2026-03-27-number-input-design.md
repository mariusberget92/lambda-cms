# NumberInput Component Design

**Date:** 2026-03-27
**Status:** Approved

## Problem

Native `<input type="number">` renders browser-default spinner buttons (▲▼) that are visually inconsistent with the Nord design system — wrong colors, wrong size, wrong style in both light and dark modes.

## Solution

A `NumberInput.vue` component wrapping a native `<input type="number">` (spinners hidden via CSS) with custom ▲▼ chevron buttons positioned inside the right edge of the input. Drop-in replacement with the same `v-model` API.

## API

```vue
<NumberInput v-model="form.port" :min="1" :max="65535" />
<NumberInput v-model="form.limit" :min="1" :max="100" :step="1" />
```

**Props:**
- `modelValue` — `Number | String` (required)
- `min` — `Number` (optional)
- `max` — `Number` (optional)
- `step` — `Number` (default: 1)
- `disabled` — `Boolean` (default: false)
- `class` — passed through for width overrides (e.g. `w-20`)

## Visual Structure

```
┌─────────────────────┬────┐
│  8080               │ ▲  │  ← ChevronUp (lucide w-3 h-3)
│                     ├────┤
│                     │ ▼  │  ← ChevronDown (lucide w-3 h-3)
└─────────────────────┴────┘
```

- Wrapper: `relative inline-flex w-full`
- Native input: `w-full rounded-md border border-border bg-background pl-3 pr-7 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring [appearance:textfield]`
- Button column: `absolute right-0 inset-y-0 flex flex-col border-l border-border rounded-r-md overflow-hidden`
- Each button: `flex-1 flex items-center justify-center px-1 text-muted-foreground hover:bg-accent/20 hover:text-foreground transition-colors disabled:opacity-40 disabled:cursor-not-allowed`
- Divider between buttons: `border-b border-border` on the top button

## Behaviour

- ▲ button calls `inputRef.stepUp()` then emits `inputRef.value`
- ▼ button calls `inputRef.stepDown()` then emits `inputRef.value`
- `min`/`max`/`step` enforcement is handled automatically by the native input's `stepUp()`/`stepDown()`
- Native input `@change` and `@input` both emit `update:modelValue` with the numeric value
- Dark mode: fully automatic via Nord CSS tokens

## Scope of Changes

| File | Inputs to replace |
|------|-------------------|
| `resources/js/Components/NumberInput.vue` | CREATE |
| `resources/js/Components/DateTimePicker.vue` | hours, minutes (2) |
| `resources/js/Pages/Install/Mail.vue` | port (1) |
| `resources/js/Pages/Install/Database.vue` | port (1) |
| `resources/js/Pages/Settings/Index.vue` | mail port, max_upload_mb, resize_max_width, comments per_page (4) |
| `resources/js/Components/BlockEditor/blocks/LoopSettings.vue` | limit, offset (2) |
| `resources/js/Components/BlockEditor/blocks/ContainerSettings.vue` | columns (1) |
| `resources/js/Components/BlockEditor/blocks/ComponentSettings.vue` | limit, offset (2) |

**Total: 13 inputs replaced across 7 files.**

## Non-Goals

- No locale-aware number formatting
- No mouse-wheel scroll support
- No long-press repeat on buttons
