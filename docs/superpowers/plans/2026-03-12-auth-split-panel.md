# Auth Split-Panel Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the centered-card `AuthLayout.vue` with a full-viewport split-panel — dark particle canvas on the left, auth form on the right — inherited automatically by all three auth pages.

**Architecture:** A new `useParticleCanvas` composable encapsulates all canvas/RAF/ResizeObserver logic. `AuthLayout.vue` is rewritten to a two-column flex layout that mounts the composable. No auth page files are touched.

**Tech Stack:** Vue 3 Composition API, vanilla Canvas 2D API, `requestAnimationFrame`, `ResizeObserver`, Tailwind CSS 4, Vite.

> **Working directory for all bash commands:** the project root `C:\Users\mariu\Herd\lambda-cms` (or whichever worktree is checked out on `feature/auth-split-panel`). All relative paths are relative to this root.

---

## Chunk 1: useParticleCanvas composable + AuthLayout rewrite

### Task 1: Create `useParticleCanvas.js`

**Files:**
- Create: `resources/js/composables/useParticleCanvas.js`

> The directory `resources/js/composables/` already exists — do not create it. It contains `useTheme.js` (a pre-existing composable used by `AuthLayout.vue`; do not modify it).

> No tests for this task — the composable is pure visual/animation code with no application logic or server interaction. Manual verification is in Task 3.

- [ ] **Step 1: Create the composable file**

Create `resources/js/composables/useParticleCanvas.js` with the following content:

```js
export function useParticleCanvas(canvasRef) {
  const PARTICLE_COUNT       = 70
  const PARTICLE_RADIUS      = 2
  const PARTICLE_SPEED       = 0.5
  const CONNECTION_THRESHOLD = 120
  const BG_COLOR             = '#2e3440'
  const PARTICLE_COLOR       = 'rgba(216, 222, 233, 0.85)'
  const LINE_BASE_OPACITY    = 0.3

  let rafHandle = null
  let particles  = []
  let observer   = null

  /** Scatter N particles randomly across the given dimensions. */
  function scatter(width, height) {
    particles = Array.from({ length: PARTICLE_COUNT }, () => {
      const angle = Math.random() * Math.PI * 2
      return {
        x:  Math.random() * width,
        y:  Math.random() * height,
        vx: Math.cos(angle) * PARTICLE_SPEED,
        vy: Math.sin(angle) * PARTICLE_SPEED,
      }
    })
  }

  /** One animation frame: clear → move → lines → dots. */
  function draw() {
    const canvas = canvasRef.value
    if (!canvas) return

    const ctx    = canvas.getContext('2d')
    const width  = canvas.width
    const height = canvas.height

    // 1. Clear
    ctx.fillStyle = BG_COLOR
    ctx.fillRect(0, 0, width, height)

    // 2. Move particles (toroidal wrap)
    for (const p of particles) {
      p.x += p.vx
      p.y += p.vy
      if (p.x < 0)      p.x += width
      if (p.x > width)  p.x -= width
      if (p.y < 0)      p.y += height
      if (p.y > height) p.y -= height
    }

    // 3. Draw connection lines
    ctx.lineWidth = 1
    for (let i = 0; i < particles.length; i++) {
      for (let j = i + 1; j < particles.length; j++) {
        const dx   = particles[i].x - particles[j].x
        const dy   = particles[i].y - particles[j].y
        const dist = Math.sqrt(dx * dx + dy * dy)
        if (dist < CONNECTION_THRESHOLD) {
          const opacity = (1 - dist / CONNECTION_THRESHOLD) * LINE_BASE_OPACITY
          ctx.strokeStyle = `rgba(216, 222, 233, ${opacity.toFixed(3)})`
          ctx.beginPath()
          ctx.moveTo(particles[i].x, particles[i].y)
          ctx.lineTo(particles[j].x, particles[j].y)
          ctx.stroke()
        }
      }
    }

    // 4. Draw particle dots
    ctx.fillStyle = PARTICLE_COLOR
    for (const p of particles) {
      ctx.beginPath()
      ctx.arc(p.x, p.y, PARTICLE_RADIUS, 0, Math.PI * 2)
      ctx.fill()
    }

    rafHandle = requestAnimationFrame(draw)
  }

  /**
   * Call in onMounted.
   * Synchronously sizes the canvas, scatters particles, starts the RAF loop.
   * If canvasRef.value is null, returns immediately (no-op).
   */
  function init() {
    const canvas = canvasRef.value
    if (!canvas) return

    const parent    = canvas.parentElement
    canvas.width    = parent.clientWidth
    canvas.height   = parent.clientHeight

    scatter(canvas.width, canvas.height)
    rafHandle = requestAnimationFrame(draw)

    observer = new ResizeObserver(() => {
      canvas.width  = parent.clientWidth
      canvas.height = parent.clientHeight
      // Particles keep their positions; toroidal wrap corrects any
      // out-of-bounds state on the next animation frame automatically.
    })
    observer.observe(parent)
  }

  /**
   * Call in onUnmounted.
   * Cancels the RAF loop and disconnects the ResizeObserver.
   */
  function cleanup() {
    if (rafHandle !== null) {
      cancelAnimationFrame(rafHandle)
      rafHandle = null
    }
    if (observer) {
      observer.disconnect()
      observer = null
    }
  }

  return { init, cleanup }
}
```

- [ ] **Step 2: Commit the composable**

```bash
git add resources/js/composables/useParticleCanvas.js
git commit -m "feat: add useParticleCanvas composable with RAF particle simulation"
```

---

### Task 2: Rewrite `AuthLayout.vue`

**Files:**
- Modify: `resources/js/Layouts/AuthLayout.vue`

> No tests — layout-only visual change. Manual verification in Task 3.

- [ ] **Step 1: Replace `AuthLayout.vue` with the split-panel layout**

The current file is 25 lines. Replace it entirely with:

```vue
<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { Sun, Moon } from 'lucide-vue-next'
import { useTheme } from '@/composables/useTheme.js'
import { useParticleCanvas } from '@/composables/useParticleCanvas.js'

const { isDark, toggleTheme } = useTheme()

const canvasRef = ref(null)
const { init, cleanup } = useParticleCanvas(canvasRef)

onMounted(init)
onUnmounted(cleanup)
</script>

<template>
  <div class="flex h-screen overflow-hidden">

    <!-- Left panel: particle canvas (desktop only) -->
    <div class="hidden md:flex w-1/2 bg-[#2e3440]" aria-hidden="true">
      <canvas ref="canvasRef" class="w-full h-full" />
    </div>

    <!-- Right panel: auth form -->
    <div class="w-full md:w-1/2 bg-background flex flex-col items-center justify-center p-8">
      <div class="w-full max-w-sm">
        <slot />
      </div>
    </div>

    <!-- Dark mode toggle (sits above both panels) -->
    <button
      @click="toggleTheme"
      class="fixed top-4 right-4 z-10 inline-flex items-center justify-center w-9 h-9 rounded-md text-muted-foreground hover:text-foreground hover:bg-accent transition-colors"
      :aria-label="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
      :title="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
    >
      <Sun v-if="isDark" class="w-4 h-4" />
      <Moon v-else class="w-4 h-4" />
    </button>

  </div>
</template>
```

Key changes from the old layout:
- Root `<div>` changes from `min-h-screen bg-background flex items-center justify-center` → `flex h-screen overflow-hidden`
- Left panel added: `hidden md:flex w-1/2 bg-[#2e3440]` wrapping `<canvas ref="canvasRef" class="w-full h-full" />`
- Right panel wraps the slot: `w-full md:w-1/2 bg-background flex flex-col items-center justify-center p-8` with inner `w-full max-w-sm`
- Dark mode toggle gains `z-10`
- `script setup` gains `ref`, `onMounted`, `onUnmounted` imports and composable wiring

- [ ] **Step 2: Commit**

```bash
git add resources/js/Layouts/AuthLayout.vue
git commit -m "feat: replace centered-card AuthLayout with split-panel particle canvas"
```

---

### Task 3: Build and verify

**Files:** none (verification only)

- [ ] **Step 1: Build assets**

```bash
npm run build
```

Expected: build succeeds with no errors (chunk-size warning for the main bundle is expected and harmless).

- [ ] **Step 2: Run the test suite**

```bash
php artisan test
```

Expected: all tests pass (count should match the pre-feature baseline — 307 at time of writing).

- [ ] **Step 3: Serve and visually verify**

```bash
php artisan serve
```

Open `http://localhost:8000/login` in a browser and confirm:

**Desktop (≥768 px wide):**
- [ ] Left half is dark (`#2e3440`), right half is the light/dark-theme background
- [ ] Particles (~70 small dots) drift slowly across the left panel
- [ ] Lines appear between nearby particles and fade with distance
- [ ] The auth form (email, password, button) is visible and usable on the right
- [ ] Toggling dark mode changes only the right panel background — left stays dark
- [ ] The dark mode toggle button (top-right) is fully visible and clickable; it is not obscured by the canvas container (confirms `z-10` is working)

**Mobile (resize to <768 px):**
- [ ] Left panel is completely hidden
- [ ] The form takes full viewport width

**Navigation:**
- [ ] Visit `/forgot-password` — same split layout, different form content
- [ ] Visit `/reset-password/{token}` — same split layout

- [ ] **Step 4: Commit verification result**

If any visual issue is found, fix it in `AuthLayout.vue` or `useParticleCanvas.js`, rebuild, re-verify, then commit the fix. If all looks good:

```bash
# No additional commit needed — verification is complete.
# The feature is ready for finishing-a-development-branch.
```
