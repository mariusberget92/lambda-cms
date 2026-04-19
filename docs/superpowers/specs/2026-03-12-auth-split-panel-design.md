# Auth Split-Panel Design

**Date:** 2026-03-12
**Status:** Approved
**Scope:** `AuthLayout.vue`, new `useParticleCanvas` composable

---

## Overview

Replace the current centered-card `AuthLayout.vue` with a full-viewport split-panel layout. The left half displays an animated particle simulation on a fixed dark background. The right half contains the slot content (auth form). All three auth pages — Login, ForgotPassword, ResetPassword — inherit the new layout automatically with zero changes to their own files.

---

## Layout Structure

`AuthLayout.vue` becomes a full-viewport two-column flex container (`flex h-screen`).

### Left Panel

- Visibility: `hidden md:flex` — hidden below the `md` breakpoint (768 px), shown as a flex column at `md` and above
- Width: `w-1/2`
- Background: `#2e3440` (Nord dark, fixed — does not change with light/dark toggle)
- Contains a single `<canvas>` element that fills the panel entirely (`w-full h-full`)
- `aria-hidden="true"` — purely decorative

### Right Panel

- Width: `w-full md:w-1/2`
- Background: `bg-background` (Tailwind semantic class) — respects the existing light/dark theme toggle
- Outer layout: `flex flex-col items-center justify-center p-8`
- The existing `max-w-sm` wrapper from the current `AuthLayout.vue` is **replaced** with an inner content wrapper: `<div class="w-full max-w-sm">` containing `<slot />`. This preserves the narrow, card-like form appearance at all viewport sizes while removing the old layout's outer centering structure.
- Auth page inputs use `w-full` and will fill the `max-w-sm` container cleanly.

### Dark Mode Toggle

Remains at `fixed top-4 right-4 z-10` — the explicit `z-10` ensures it sits above both panels regardless of any stacking contexts introduced by the canvas or its parent element.

---

## Particle Simulation — `useParticleCanvas`

**File:** `resources/js/composables/useParticleCanvas.js`

### Interface

```js
const { init, cleanup } = useParticleCanvas(canvasRef)
// canvasRef — a Vue ref<HTMLCanvasElement> bound to the <canvas> element via ref="canvasRef" in the template
// init()    — call in onMounted; synchronously sizes the canvas, scatters particles, starts the RAF loop.
//             If canvasRef.value is null, returns immediately (no-op).
// cleanup() — call in onUnmounted; cancels requestAnimationFrame and disconnects ResizeObserver
```

### Particle Configuration

| Property | Value |
|---|---|
| Count | 70 |
| Radius | 2 px |
| Speed | ~0.5 px/frame (constant magnitude, random direction) |
| Edge behaviour | Toroidal wrap (exit one edge, re-enter opposite) |
| Fill colour | `rgba(216, 222, 233, 0.85)` |

### Connection Lines

| Property | Value |
|---|---|
| Distance threshold | 120 px |
| Stroke colour | `rgba(216, 222, 233, 0.3)` |
| Opacity interpolation | Linear: `1.0` at 0 px → `0` at 120 px, multiplied by base opacity `0.3` |

### Canvas Background

Cleared each frame to `#2e3440`. Fixed — does not respond to the application theme toggle.

### Sizing

`init()` sets `canvas.width` and `canvas.height` synchronously from `canvas.parentElement.clientWidth` / `clientHeight` before scattering particles or starting the animation loop.

A `ResizeObserver` is then attached to `canvas.parentElement` to handle subsequent resizes. On each resize callback:
1. `canvas.width` and `canvas.height` are updated to the parent's `clientWidth` / `clientHeight`
2. Existing particle positions are **preserved** (not re-randomised)
3. The next animation frame renders at the new dimensions

The `ResizeObserver` is disconnected in `cleanup()`.

### Animation Loop

Pure `requestAnimationFrame` loop:
1. Clear canvas to background colour
2. Update each particle position by its velocity; apply toroidal wrap
3. For each unique pair of particles within 120 px: draw a line with interpolated opacity
4. Draw each particle as a filled circle

The composable holds the RAF handle internally and cancels it in `cleanup()`.

### Initial Particle Positions

Particles are initialised with:
- `x`: random in `[0, canvas.width]`
- `y`: random in `[0, canvas.height]`
- `vx`, `vy`: random direction, magnitude `0.5`

Initialisation happens once in `init()` after the canvas has been sized to its parent.

---

## Files Changed

| File | Change |
|---|---|
| `resources/js/Layouts/AuthLayout.vue` | Replace centered-card layout with split-panel; mount particle canvas |
| `resources/js/composables/useParticleCanvas.js` | New file — particle simulation composable |

No changes to `Login.vue`, `ForgotPassword.vue`, or `ResetPassword.vue`.
No new backend files, routes, controllers, or tests.

---

## Non-Goals

- Mouse interaction (repulsion, attraction, click effects)
- Theme-aware canvas colours
- Canvas visible on mobile (hidden below `md`)
- New auth routes or backend changes
