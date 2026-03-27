# DateTimePicker Component Design

**Date:** 2026-03-27
**Status:** Approved

## Problem

The native `<input type="datetime-local">` uses the browser's default picker UI, which is visually inconsistent with the Nord design system used throughout the CMS.

## Solution

A custom `DateTimePicker.vue` component built on reka-ui's headless `Calendar` primitive, styled entirely with Nord CSS tokens. Drops in as a direct replacement for `<input type="datetime-local">` with the same `v-model` interface.

## API

```vue
<DateTimePicker v-model="form.published_at" />
```

- `v-model` — `string` in `YYYY-MM-DDTHH:mm` format (same as `datetime-local`)
- Past dates/times are disabled internally — no prop needed
- Dark mode handled automatically via Nord CSS tokens; trigger input uses `[color-scheme:light] dark:[color-scheme:dark]`

## Component Structure

**File:** `resources/js/Components/DateTimePicker.vue`

### Trigger
- Styled like all other inputs: `rounded-md border bg-background px-3 py-1.5 text-sm`
- `CalendarDays` lucide icon on the left in `text-muted-foreground`
- Shows formatted value (`Mar 27, 2026 · 10:30 AM`) or placeholder when empty
- Clicking opens/closes the popover

### Popover Panel
- Positioned `absolute` below the trigger, `z-50`
- `bg-card border border-border rounded-lg shadow-lg dark:shadow-black/40 w-72 p-3`
- Dismissed via `onClickOutside` from `@vueuse/core` (same pattern as `SelectBox.vue`)

### Calendar Section (reka-ui `Calendar`)
- **Header:** month/year label (`font-medium`) + prev/next chevron buttons (`ChevronLeft`/`ChevronRight` from lucide)
- **Day grid:** 7-column grid, Su–Sa headers in `text-xs text-muted-foreground`
- **Day cell states:**
  - Default: `hover:bg-accent/20 rounded-md text-sm`
  - Today: `border border-primary text-primary`
  - Selected: `bg-primary text-primary-foreground rounded-md`
  - Past (before today): `text-muted-foreground/40 cursor-not-allowed` — not clickable
  - Adjacent month days: `text-muted-foreground/30`
- Past months are not navigable (prev chevron disabled when already at current month)

### Time Section
- Divider line `border-t border-border mt-2 pt-2`
- Label `text-xs text-muted-foreground` + row with:
  - Hours input: `w-10 text-center rounded border bg-background text-sm` (1–12)
  - `:` separator
  - Minutes input: `w-10 text-center rounded border bg-background text-sm` (00–59, zero-padded)
  - AM/PM toggle: two side-by-side buttons, active = `bg-primary text-primary-foreground rounded`, inactive = `text-muted-foreground`
- If the selected date is today, hours/minutes that are in the past are not selectable (clamp to current time minimum)

## Data Flow

Internal state:
- `selectedDate` — `Date | null`
- `hours` — `number` (1–12)
- `minutes` — `number` (0–59)
- `period` — `'AM' | 'PM'`

On any change, emit `update:modelValue` with combined `YYYY-MM-DDTHH:mm` string (24h conversion for the value, 12h display in UI).

On mount, parse incoming `modelValue` into internal state.

## Integration

Replace `<input type="datetime-local">` in:
- `resources/js/Pages/Posts/Edit.vue`
- `resources/js/Pages/Posts/Create.vue`

Remove the `@click="$event.target.showPicker?.()"` and `[color-scheme:light] dark:[color-scheme:dark]` classes — those were workarounds for the native input.

## Non-Goals

- No date range selection
- No time zone handling
- No seconds picker
- No inline (non-popover) mode
