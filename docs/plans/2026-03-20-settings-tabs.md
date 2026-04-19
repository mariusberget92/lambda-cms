# Settings Page Tabs Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Split the single-scroll settings page into five horizontal tabs — General, Mail, Media, Comments, SEO.

**Architecture:** Pure frontend change to one Vue file. Add an `activeTab` ref, render a tab bar above the forms, and wrap each section's form(s) in `v-show="activeTab === '…'"`. Flash messages remain above the tab bar so they're always visible regardless of the active tab. No backend changes.

**Tech Stack:** Vue 3, Inertia.js, Tailwind CSS 4 (Nord theme)

---

### Task 1: Add tabs to Settings/Index.vue

**Files:**
- Modify: `resources/js/Pages/Settings/Index.vue`

**Tab grouping:**
| Tab key | Label | Contains |
|---|---|---|
| `general` | General | Site form + Locale form |
| `mail` | Mail | Mail form + Send test email form |
| `media` | Media | Media form |
| `comments` | Comments | Comments form |
| `seo` | SEO | SEO form |

**Step 1: Add `activeTab` ref to `<script setup>`**

In the `<script setup>` block, add after the imports:

```js
import { ref } from 'vue'
// …existing imports…

const activeTab = ref('general')

const tabs = [
  { key: 'general',  label: 'General'  },
  { key: 'mail',     label: 'Mail'     },
  { key: 'media',    label: 'Media'    },
  { key: 'comments', label: 'Comments' },
  { key: 'seo',      label: 'SEO'      },
]
```

**Step 2: Replace the template structure**

The outer `<div class="max-w-2xl space-y-6">` keeps its width constraint.

Replace the `<!-- Page header -->` section and add the tab bar immediately after it:

```html
<!-- Page header -->
<div>
  <h2 class="text-lg font-semibold">Settings</h2>
  <p class="text-sm text-muted-foreground mt-0.5">Manage site, locale, media, and mail configuration.</p>
</div>

<!-- Flash messages (always visible) -->
<Transition name="fade"> … </Transition>
<Transition name="fade"> … </Transition>
<Transition name="fade"> … </Transition>

<!-- Tab bar -->
<div class="flex border-b border-border">
  <button
    v-for="tab in tabs"
    :key="tab.key"
    type="button"
    class="px-4 py-2 text-sm font-medium transition-colors border-b-2 -mb-px"
    :class="activeTab === tab.key
      ? 'border-primary text-primary'
      : 'border-transparent text-muted-foreground hover:text-foreground'"
    @click="activeTab = tab.key"
  >
    {{ tab.label }}
  </button>
</div>

<!-- Tab panels — v-show preserves form state when switching -->
<div v-show="activeTab === 'general'" class="space-y-6">
  <!-- Site form -->
  <form @submit.prevent="submitSite"> … </form>
  <!-- Locale form -->
  <form @submit.prevent="submitLocale"> … </form>
</div>

<div v-show="activeTab === 'mail'" class="space-y-6">
  <!-- Mail form -->
  <form @submit.prevent="submitMail"> … </form>
  <!-- Test email form -->
  <form @submit.prevent="sendTestEmail"> … </form>
</div>

<div v-show="activeTab === 'media'">
  <form @submit.prevent="submitMedia"> … </form>
</div>

<div v-show="activeTab === 'comments'">
  <form @submit.prevent="submitComments"> … </form>
</div>

<div v-show="activeTab === 'seo'">
  <form @submit.prevent="submitSeo"> … </form>
</div>
```

> Use `v-show` (not `v-if`) on every tab panel so form state (entered values, Inertia processing state) is preserved while the panel is hidden.

**Step 3: Also auto-switch to the relevant tab on flash**

When a flash message is present the user should not have to guess which tab was saved. Add a `watch` on `$page.props.flash` that maps the flash key back to its tab:

```js
import { watch } from 'vue'
import { usePage } from '@inertiajs/vue3'

const page = usePage()

watch(
  () => page.props.flash,
  (flash) => {
    if (!flash) return
    if (flash.mail_status || flash.mail_error) { activeTab.value = 'mail'; return }
    // The generic flash.status is emitted by all forms; we can't distinguish which
    // panel it came from, so leave the tab as-is (user already knows where they saved).
  },
  { immediate: false }
)
```

**Step 4: Build and verify**

```bash
npm run build
```

Expected: `✓ built in X.XXs` with no errors.

**Step 5: Commit**

```bash
git add resources/js/Pages/Settings/Index.vue
git commit -m "feat: split settings page into General / Mail / Media / Comments / SEO tabs"
```
