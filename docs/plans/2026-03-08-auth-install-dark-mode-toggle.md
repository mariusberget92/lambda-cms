# Auth & Installer Dark Mode Toggle Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a working dark mode toggle to the auth pages (`/login`, `/forgot-password`, `/reset-password`) and installer pages, and ensure the saved theme preference is applied on every first page load.

**Architecture:** Move the global `initTheme()` call to `app.js` so it fires on every page load. Create `AuthLayout.vue` — a shared wrapper for the three auth pages that provides the centered-card chrome and a fixed-position toggle button. Update `InstallLayout.vue` with the same fixed toggle. Auth pages switch from `layout: null` to `layout: AuthLayout` and shed their outer wrapper markup.

**Tech Stack:** Vue 3, Inertia 2, `lucide-vue-next` (Sun/Moon icons already in `package.json`), `useTheme` composable at `resources/js/composables/useTheme.js`.

---

## File Map

| File | Action |
|------|--------|
| `resources/js/app.js` | Modify — add `initTheme()` call before `createApp` |
| `resources/js/Layouts/AuthLayout.vue` | **Create** — centered card + fixed toggle |
| `resources/js/Layouts/InstallLayout.vue` | Modify — add fixed toggle button |
| `resources/js/Pages/Auth/Login.vue` | Modify — use `AuthLayout`, remove outer wrapper |
| `resources/js/Pages/Auth/ForgotPassword.vue` | Modify — use `AuthLayout`, remove outer wrapper |
| `resources/js/Pages/Auth/ResetPassword.vue` | Modify — use `AuthLayout`, remove outer wrapper |

---

## Task 1: Global `initTheme()` in `app.js`

**Files:**
- Modify: `resources/js/app.js`

- [ ] **Step 1: Update `app.js`**

Replace the current content of `resources/js/app.js` with:

```js
import '../css/app.css';
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { useTheme } from '@/composables/useTheme.js';

const { initTheme } = useTheme();
initTheme();

createInertiaApp({
    title: (title) => title ? `${title} — Lambda CMS` : 'Lambda CMS',
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });
        return pages[`./Pages/${name}.vue`];
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mixin({ methods: { route } })
            .mount(el);
    },
    progress: {
        color: '#6366f1',
        showSpinner: false,
    },
});
```

> Note: `AppLayout.vue` also calls `initTheme()` in its `onMounted`. That is now redundant but harmless — calling `initTheme()` twice applies the same value. Leave `AppLayout.vue` unchanged.

- [ ] **Step 2: Commit**

```bash
git add resources/js/app.js
git commit -m "feat: call initTheme() globally in app.js on every page load"
```

---

## Task 2: Create `AuthLayout.vue`

**Files:**
- Create: `resources/js/Layouts/AuthLayout.vue`

- [ ] **Step 1: Create the layout**

Create `resources/js/Layouts/AuthLayout.vue`:

```vue
<script setup>
import { onMounted } from 'vue'
import { Sun, Moon } from 'lucide-vue-next'
import { useTheme } from '@/composables/useTheme.js'

const { isDark, toggleTheme } = useTheme()
</script>

<template>
  <div class="min-h-screen bg-background flex items-center justify-center">
    <!-- Dark mode toggle — fixed top-right -->
    <button
      @click="toggleTheme"
      class="fixed top-4 right-4 inline-flex items-center justify-center w-9 h-9 rounded-md text-muted-foreground hover:text-foreground hover:bg-accent transition-colors"
      :aria-label="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
      :title="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
    >
      <Sun v-if="isDark" class="w-4 h-4" />
      <Moon v-else class="w-4 h-4" />
    </button>

    <div class="w-full max-w-sm">
      <slot />
    </div>
  </div>
</template>
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/Layouts/AuthLayout.vue
git commit -m "feat: add AuthLayout with dark mode toggle for auth pages"
```

---

## Task 3: Update auth pages to use `AuthLayout`

**Files:**
- Modify: `resources/js/Pages/Auth/Login.vue`
- Modify: `resources/js/Pages/Auth/ForgotPassword.vue`
- Modify: `resources/js/Pages/Auth/ResetPassword.vue`

Each auth page currently has:
1. `defineOptions({ layout: null })` — remove the outer wrapper, switch to `AuthLayout`
2. An outer `<div class="min-h-screen bg-background flex items-center justify-center">` wrapping a `<div class="w-full max-w-sm">` — these move into `AuthLayout`, so remove them from the page template

- [ ] **Step 1: Update `Login.vue`**

Replace the entire file with:

```vue
<template>
  <div>
    <div class="mb-8 text-center">
      <h1 class="text-2xl font-bold tracking-tight">Lambda CMS</h1>
      <p class="text-muted-foreground text-sm mt-1">Sign in to your account</p>
    </div>

    <form @submit.prevent="submit" class="space-y-4">
      <div v-if="$page.props.flash?.status" class="text-sm text-green-600 bg-green-50 border border-green-200 rounded-md px-4 py-3">
        {{ $page.props.flash.status }}
      </div>

      <div class="space-y-1">
        <label for="email" class="text-sm font-medium">Email</label>
        <input
          id="email"
          v-model="form.email"
          type="email"
          autocomplete="email"
          required
          class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring disabled:opacity-50"
          :class="{ 'border-destructive': form.errors.email }"
          placeholder="you@example.com"
        />
        <p v-if="form.errors.email" class="text-xs text-destructive">{{ form.errors.email }}</p>
      </div>

      <div class="space-y-1">
        <div class="flex items-center justify-between">
          <label for="password" class="text-sm font-medium">Password</label>
          <a :href="route('password.request')" class="text-xs text-muted-foreground hover:text-foreground underline-offset-4 hover:underline">
            Forgot password?
          </a>
        </div>
        <input
          id="password"
          v-model="form.password"
          type="password"
          autocomplete="current-password"
          required
          class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring disabled:opacity-50"
          :class="{ 'border-destructive': form.errors.password }"
        />
        <p v-if="form.errors.password" class="text-xs text-destructive">{{ form.errors.password }}</p>
      </div>

      <div class="flex items-center gap-2">
        <input id="remember" v-model="form.remember" type="checkbox" class="rounded border" />
        <label for="remember" class="text-sm">Remember me</label>
      </div>

      <button
        type="submit"
        :disabled="form.processing"
        class="w-full rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 disabled:opacity-50"
      >
        <span v-if="form.processing">Signing in...</span>
        <span v-else>Sign in</span>
      </button>
    </form>
  </div>
</template>

<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue'
import { useForm } from "@inertiajs/vue3";

defineOptions({ layout: AuthLayout });

const form = useForm({
  email: "",
  password: "",
  remember: false,
});

function submit() {
  form.post(route("auth.login"), {
    onFinish: () => form.reset("password"),
  });
}
</script>
```

- [ ] **Step 2: Update `ForgotPassword.vue`**

Replace the entire file with:

```vue
<template>
  <div>
    <div class="mb-8 text-center">
      <h1 class="text-2xl font-bold tracking-tight">Reset your password</h1>
      <p class="text-muted-foreground text-sm mt-1">
        Enter your email and we will send you a reset link.
      </p>
    </div>

    <form @submit.prevent="submit" class="space-y-4">
      <div v-if="$page.props.flash?.status" class="text-sm text-green-600 bg-green-50 border border-green-200 rounded-md px-4 py-3">
        {{ $page.props.flash.status }}
      </div>

      <div class="space-y-1">
        <label for="email" class="text-sm font-medium">Email</label>
        <input
          id="email"
          v-model="form.email"
          type="email"
          autocomplete="email"
          required
          class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring disabled:opacity-50"
          :class="{ 'border-destructive': form.errors.email }"
          placeholder="you@example.com"
        />
        <p v-if="form.errors.email" class="text-xs text-destructive">{{ form.errors.email }}</p>
      </div>

      <button
        type="submit"
        :disabled="form.processing"
        class="w-full rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 disabled:opacity-50"
      >
        <span v-if="form.processing">Sending...</span>
        <span v-else>Send reset link</span>
      </button>

      <p class="text-center text-sm text-muted-foreground">
        Remember your password?
        <a :href="route('login')" class="underline underline-offset-4 hover:text-foreground">Sign in</a>
      </p>
    </form>
  </div>
</template>

<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue'
import { useForm } from "@inertiajs/vue3";

defineOptions({ layout: AuthLayout });

const form = useForm({
  email: "",
});

function submit() {
  form.post(route("password.email"));
}
</script>
```

- [ ] **Step 3: Update `ResetPassword.vue`**

Replace the entire file with:

```vue
<template>
  <div>
    <div class="mb-8 text-center">
      <h1 class="text-2xl font-bold tracking-tight">Set a new password</h1>
      <p class="text-muted-foreground text-sm mt-1">Choose a strong password for your account.</p>
    </div>

    <form @submit.prevent="submit" class="space-y-4">
      <input type="hidden" v-model="form.token" />

      <div class="space-y-1">
        <label for="email" class="text-sm font-medium">Email</label>
        <input
          id="email"
          v-model="form.email"
          type="email"
          autocomplete="email"
          required
          class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring disabled:opacity-50"
          :class="{ 'border-destructive': form.errors.email }"
        />
        <p v-if="form.errors.email" class="text-xs text-destructive">{{ form.errors.email }}</p>
      </div>

      <div class="space-y-1">
        <label for="password" class="text-sm font-medium">New password</label>
        <input
          id="password"
          v-model="form.password"
          type="password"
          autocomplete="new-password"
          required
          class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring disabled:opacity-50"
          :class="{ 'border-destructive': form.errors.password }"
        />
        <p v-if="form.errors.password" class="text-xs text-destructive">{{ form.errors.password }}</p>
      </div>

      <div class="space-y-1">
        <label for="password_confirmation" class="text-sm font-medium">Confirm password</label>
        <input
          id="password_confirmation"
          v-model="form.password_confirmation"
          type="password"
          autocomplete="new-password"
          required
          class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring disabled:opacity-50"
        />
      </div>

      <button
        type="submit"
        :disabled="form.processing"
        class="w-full rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 disabled:opacity-50"
      >
        <span v-if="form.processing">Resetting...</span>
        <span v-else>Reset password</span>
      </button>
    </form>
  </div>
</template>

<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue'
import { useForm } from "@inertiajs/vue3";

defineOptions({ layout: AuthLayout });

const props = defineProps({
  token: String,
  email: String,
});

const form = useForm({
  token: props.token,
  email: props.email ?? "",
  password: "",
  password_confirmation: "",
});

function submit() {
  form.post(route("password.update"), {
    onFinish: () => form.reset("password", "password_confirmation"),
  });
}
</script>
```

- [ ] **Step 4: Commit**

```bash
git add resources/js/Pages/Auth/Login.vue resources/js/Pages/Auth/ForgotPassword.vue resources/js/Pages/Auth/ResetPassword.vue
git commit -m "feat: switch auth pages to AuthLayout with dark mode toggle"
```

---

## Task 4: Add toggle to `InstallLayout.vue`

**Files:**
- Modify: `resources/js/Layouts/InstallLayout.vue`

- [ ] **Step 1: Add toggle to InstallLayout**

Add the `<script setup>` block and the toggle button. Replace the current file with:

```vue
<script setup>
import { Sun, Moon } from 'lucide-vue-next'
import { useTheme } from '@/composables/useTheme.js'

const props = defineProps({
  step: {
    type: Number,
    default: 1,
  },
})

const steps = [
  { number: 1, label: 'Database' },
  { number: 2, label: 'Site' },
  { number: 3, label: 'Admin' },
  { number: 4, label: 'Mail' },
]

const { isDark, toggleTheme } = useTheme()
</script>

<template>
  <div class="min-h-screen bg-background flex items-center justify-center p-4">

    <!-- Dark mode toggle — fixed top-right -->
    <button
      @click="toggleTheme"
      class="fixed top-4 right-4 inline-flex items-center justify-center w-9 h-9 rounded-md text-muted-foreground hover:text-foreground hover:bg-accent transition-colors"
      :aria-label="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
      :title="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
    >
      <Sun v-if="isDark" class="w-4 h-4" />
      <Moon v-else class="w-4 h-4" />
    </button>

    <div class="w-full max-w-lg">
      <!-- Logo -->
      <div class="flex items-center justify-center gap-2 mb-8">
        <div class="w-8 h-8 rounded-md bg-primary flex items-center justify-center">
          <svg class="w-5 h-5 text-primary-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3L2 7l10 4 10-4-10-4zM2 17l10 4 10-4M2 12l10 4 10-4" />
          </svg>
        </div>
        <span class="text-xl font-semibold tracking-tight">Lambda CMS</span>
      </div>

      <!-- Card -->
      <div class="bg-card border rounded-xl shadow-sm">
        <!-- Step progress -->
        <div class="border-b px-6 py-4">
          <div class="flex items-center justify-between gap-2">
            <template v-for="(s, index) in steps" :key="s.number">
              <!-- Step pill -->
              <div class="flex items-center gap-2">
                <div
                  class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-semibold shrink-0 transition-colors"
                  :class="{
                    'bg-primary text-primary-foreground': s.number === step,
                    'bg-primary/20 text-primary': s.number < step,
                    'bg-muted text-muted-foreground': s.number > step,
                  }"
                >
                  <svg v-if="s.number < step" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                  </svg>
                  <span v-else>{{ s.number }}</span>
                </div>
                <span
                  class="text-xs font-medium hidden sm:inline transition-colors"
                  :class="{
                    'text-foreground': s.number === step,
                    'text-primary': s.number < step,
                    'text-muted-foreground': s.number > step,
                  }"
                >{{ s.label }}</span>
              </div>

              <!-- Connector line -->
              <div
                v-if="index < steps.length - 1"
                class="flex-1 h-px transition-colors"
                :class="s.number < step ? 'bg-primary/40' : 'bg-border'"
              />
            </template>
          </div>
        </div>

        <!-- Page content slot -->
        <div class="p-6">
          <slot />
        </div>
      </div>
    </div>
  </div>
</template>
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/Layouts/InstallLayout.vue
git commit -m "feat: add dark mode toggle to InstallLayout"
```

---

## Task 5: Build and verify

- [ ] **Step 1: Build assets**

```bash
npm run build
```

Expected: build completes with no errors.

- [ ] **Step 2: Verify manually**

1. Visit `https://lambda-cms.test/login` — toggle button visible top-right
2. Click toggle — page switches between light and dark
3. Refresh — saved preference restored
4. Visit `/forgot-password` and `/reset-password` — same toggle present
5. Visit `/install/database` — toggle visible, theme persists from auth pages
6. Visit `/dashboard` — existing topbar toggle still works, unaffected

- [ ] **Step 3: Run full test suite to confirm no regressions**

```bash
php artisan test
```

Expected: all tests pass (this change has no PHP surface).
