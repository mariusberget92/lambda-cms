# Page Builder Full-Screen Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Replace the AppLayout-wrapped page editor with a dedicated full-screen dark builder that owns the entire viewport, with page metadata accessible via a thin top bar.

**Architecture:** A new `PageBuilderLayout.vue` (fixed inset-0, dark) replaces `AppLayout` in Pages/Create and Pages/Edit. A new `PageBuilderBar.vue` component sits at the top (44px) and receives all page metadata as props, emitting changes up. The `BlockEditor` fills the remaining height flush to the viewport with no border or rounded corners.

**Tech Stack:** Vue 3 `<script setup>`, Inertia.js, Tailwind CSS 4, lucide-vue-next, existing `useNotifications` composable.

---

## Task 1: Create `PageBuilderLayout.vue`

**Files:**
- Create: `resources/js/Layouts/PageBuilderLayout.vue`

This layout owns the full viewport. It renders a slot for the top bar and a slot for the editor body. It uses `data-theme="dark"` so all children inherit the dark token set without any extra config.

**Step 1: Create the file**

```vue
<!-- resources/js/Layouts/PageBuilderLayout.vue -->
<template>
  <div data-theme="dark" class="fixed inset-0 z-40 flex flex-col bg-[#1e1e2e] overflow-hidden">
    <slot name="bar" />
    <div class="flex-1 overflow-hidden">
      <slot />
    </div>
  </div>
</template>

<script setup>
// No logic needed — purely structural.
</script>
```

**Step 2: Verify visually (no test needed for a structural layout)**

Nothing to test yet — will be verified when wired into pages.

**Step 3: Commit**

```bash
git add resources/js/Layouts/PageBuilderLayout.vue
git commit -m "feat: add PageBuilderLayout full-screen layout"
```

---

## Task 2: Create `PageBuilderBar.vue`

**Files:**
- Create: `resources/js/Components/PageBuilderBar.vue`

The top bar is 44px tall, dark, and contains:
- Left: back arrow (navigates to `pages.index`)
- Centre-left: inline title input
- Right: slug input (compact), draft/published pills, SEO popover, Revisions popover, Save button

The bar is **purely presentational** — all form state lives in the parent page. It receives props and emits events upward.

**Step 1: Create the file**

```vue
<!-- resources/js/Components/PageBuilderBar.vue -->
<template>
  <header class="flex items-center gap-3 px-3 h-11 shrink-0 border-b border-white/10 bg-[#181825] z-10">

    <!-- Back -->
    <a
      :href="backHref"
      class="inline-flex items-center justify-center w-7 h-7 rounded text-white/50 hover:text-white hover:bg-white/10 transition-colors shrink-0"
      title="Back to pages"
    >
      <ArrowLeft class="w-4 h-4" />
    </a>

    <div class="w-px h-5 bg-white/10 shrink-0" />

    <!-- Title -->
    <input
      :value="title"
      type="text"
      placeholder="Page title…"
      class="flex-1 min-w-0 bg-transparent text-sm font-medium text-white placeholder:text-white/30 focus:outline-none"
      @input="$emit('update:title', $event.target.value)"
    />

    <div class="w-px h-5 bg-white/10 shrink-0" />

    <!-- Slug -->
    <div class="flex items-center gap-1.5 shrink-0">
      <span class="text-xs text-white/40">/</span>
      <input
        :value="slug"
        type="text"
        placeholder="page-slug"
        class="w-36 bg-white/5 border border-white/10 rounded px-2 py-1 text-xs text-white/80 focus:outline-none focus:border-white/30 focus:bg-white/10 transition-colors"
        @input="$emit('update:slug', $event.target.value)"
      />
    </div>

    <div class="w-px h-5 bg-white/10 shrink-0" />

    <!-- Status pills -->
    <div class="flex items-center gap-1 shrink-0">
      <button
        type="button"
        class="px-2.5 py-1 rounded text-xs font-medium transition-colors"
        :class="status === 'draft'
          ? 'bg-primary text-primary-foreground'
          : 'text-white/50 hover:text-white hover:bg-white/10'"
        @click="$emit('update:status', 'draft')"
      >Draft</button>
      <button
        type="button"
        class="px-2.5 py-1 rounded text-xs font-medium transition-colors"
        :class="status === 'published'
          ? 'bg-primary text-primary-foreground'
          : 'text-white/50 hover:text-white hover:bg-white/10'"
        @click="$emit('update:status', 'published')"
      >Published</button>
    </div>

    <div class="w-px h-5 bg-white/10 shrink-0" />

    <!-- SEO popover -->
    <div class="relative shrink-0" ref="seoRef">
      <button
        type="button"
        class="flex items-center gap-1 px-2 py-1 rounded text-xs text-white/50 hover:text-white hover:bg-white/10 transition-colors"
        @click="seoOpen = !seoOpen"
      >
        SEO
        <ChevronDown class="w-3 h-3 transition-transform" :class="{ 'rotate-180': seoOpen }" />
      </button>
      <Transition name="popover">
        <div
          v-if="seoOpen"
          class="absolute right-0 top-full mt-1 w-80 rounded-lg border border-white/10 bg-[#181825] shadow-2xl p-4 z-50 space-y-3"
        >
          <p class="text-xs font-semibold text-white/50 uppercase tracking-wider">SEO</p>
          <div>
            <label class="text-xs text-white/40 block mb-1">Meta title</label>
            <input
              :value="metaTitle"
              type="text"
              class="w-full bg-white/5 border border-white/10 rounded px-2 py-1.5 text-xs text-white focus:outline-none focus:border-white/30 transition-colors"
              @input="$emit('update:metaTitle', $event.target.value)"
            />
          </div>
          <div>
            <label class="text-xs text-white/40 block mb-1">Meta description</label>
            <textarea
              :value="metaDescription"
              rows="2"
              class="w-full bg-white/5 border border-white/10 rounded px-2 py-1.5 text-xs text-white resize-none focus:outline-none focus:border-white/30 transition-colors"
              @input="$emit('update:metaDescription', $event.target.value)"
            />
          </div>
          <div>
            <label class="text-xs text-white/40 block mb-1">Meta keywords</label>
            <input
              :value="metaKeywords"
              type="text"
              class="w-full bg-white/5 border border-white/10 rounded px-2 py-1.5 text-xs text-white focus:outline-none focus:border-white/30 transition-colors"
              @input="$emit('update:metaKeywords', $event.target.value)"
            />
          </div>
        </div>
      </Transition>
    </div>

    <!-- Revisions popover (only shown when revisions prop is provided) -->
    <div v-if="showRevisions" class="relative shrink-0" ref="revisionsRef">
      <button
        type="button"
        class="flex items-center gap-1 px-2 py-1 rounded text-xs text-white/50 hover:text-white hover:bg-white/10 transition-colors"
        @click="onRevisionsToggle"
      >
        Revisions
        <ChevronDown class="w-3 h-3 transition-transform" :class="{ 'rotate-180': revisionsOpen }" />
      </button>
      <Transition name="popover">
        <div
          v-if="revisionsOpen"
          class="absolute right-0 top-full mt-1 w-72 rounded-lg border border-white/10 bg-[#181825] shadow-2xl p-4 z-50"
        >
          <p class="text-xs font-semibold text-white/50 uppercase tracking-wider mb-3">Revisions</p>
          <div v-if="revisionsLoading" class="text-xs text-white/40 text-center py-3">Loading…</div>
          <div v-else-if="!revisions.length" class="text-xs text-white/40 text-center py-3">No revisions yet.</div>
          <div v-else class="space-y-1.5 max-h-60 overflow-y-auto">
            <div
              v-for="rev in revisions"
              :key="rev.id"
              class="flex items-center justify-between gap-2 rounded border border-white/10 px-2.5 py-1.5 hover:bg-white/5"
            >
              <div class="min-w-0">
                <p class="text-xs font-medium text-white truncate">{{ rev.user?.name ?? 'Unknown' }}</p>
                <p class="text-[11px] text-white/40">{{ new Date(rev.created_at).toLocaleString() }}</p>
              </div>
              <button
                type="button"
                class="shrink-0 rounded border border-white/20 px-2 py-0.5 text-xs text-white/70 hover:bg-white/10 transition-colors"
                @click="$emit('restoreRevision', rev)"
              >Restore</button>
            </div>
          </div>
        </div>
      </Transition>
    </div>

    <div class="w-px h-5 bg-white/10 shrink-0" />

    <!-- Save -->
    <button
      type="button"
      :disabled="processing"
      class="shrink-0 rounded px-3 py-1.5 text-xs font-medium bg-primary text-primary-foreground hover:bg-[var(--primary-hover)] disabled:opacity-50 transition-colors"
      @click="$emit('save')"
    >
      {{ processing ? 'Saving…' : saveLabel }}
    </button>

  </header>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { ArrowLeft, ChevronDown } from 'lucide-vue-next'

const props = defineProps({
  backHref:        { type: String,  required: true },
  title:           { type: String,  default: '' },
  slug:            { type: String,  default: '' },
  status:          { type: String,  default: 'draft' },
  metaTitle:       { type: String,  default: '' },
  metaDescription: { type: String,  default: '' },
  metaKeywords:    { type: String,  default: '' },
  processing:      { type: Boolean, default: false },
  saveLabel:       { type: String,  default: 'Save page' },
  showRevisions:   { type: Boolean, default: false },
  revisions:       { type: Array,   default: () => [] },
  revisionsLoading:{ type: Boolean, default: false },
})

const emit = defineEmits([
  'update:title', 'update:slug', 'update:status',
  'update:metaTitle', 'update:metaDescription', 'update:metaKeywords',
  'save', 'restoreRevision', 'revisionsOpen',
])

const seoOpen       = ref(false)
const revisionsOpen = ref(false)
const seoRef        = ref(null)
const revisionsRef  = ref(null)

function onRevisionsToggle() {
  revisionsOpen.value = !revisionsOpen.value
  if (revisionsOpen.value) emit('revisionsOpen')
}

// Close popovers when clicking outside
function onClickOutside(e) {
  if (seoRef.value && !seoRef.value.contains(e.target)) seoOpen.value = false
  if (revisionsRef.value && !revisionsRef.value.contains(e.target)) revisionsOpen.value = false
}

onMounted(() => document.addEventListener('mousedown', onClickOutside))
onBeforeUnmount(() => document.removeEventListener('mousedown', onClickOutside))
</script>

<style scoped>
.popover-enter-active, .popover-leave-active { transition: opacity 0.1s, transform 0.1s; }
.popover-enter-from, .popover-leave-to { opacity: 0; transform: translateY(-4px); }
</style>
```

**Step 2: Commit**

```bash
git add resources/js/Components/PageBuilderBar.vue
git commit -m "feat: add PageBuilderBar top bar component for full-screen page builder"
```

---

## Task 3: Update `BlockEditor.vue` — add `fullscreen` prop

**Files:**
- Modify: `resources/js/components/BlockEditor/BlockEditor.vue:3-7`

When `fullscreen` is true the editor fills its container (100% height, no border, no rounded corners). The default behaviour (used by the posts editor) is unchanged.

**Step 1: Add the `fullscreen` prop and conditional classes/styles**

Find the opening `<div>` of the template (lines 3-7):

```vue
<!-- BEFORE -->
<div
  data-theme="dark"
  class="flex border border-white/10 rounded-xl overflow-hidden bg-background"
  style="min-height: 500px; max-height: calc(100vh - 220px)"
>
```

Replace with:

```vue
<!-- AFTER -->
<div
  data-theme="dark"
  class="flex overflow-hidden bg-background"
  :class="fullscreen
    ? 'h-full w-full'
    : 'border border-white/10 rounded-xl'"
  :style="fullscreen ? {} : { minHeight: '500px', maxHeight: 'calc(100vh - 220px)' }"
>
```

**Step 2: Add the prop to `<script setup>`**

Find the `defineProps` call and add `fullscreen`:

```js
const props = defineProps({
  // ... existing props ...
  fullscreen: { type: Boolean, default: false },
})
```

**Step 3: Commit**

```bash
git add resources/js/components/BlockEditor/BlockEditor.vue
git commit -m "feat: add fullscreen prop to BlockEditor — fills container, removes border/radius"
```

---

## Task 4: Rewrite `Pages/Edit.vue` to use the full-screen builder

**Files:**
- Modify: `resources/js/Pages/Pages/Edit.vue`

Replace the entire file. All existing logic (autosave, revisions, form) is preserved — only the template changes: swap `AppLayout` → `PageBuilderLayout`, remove the meta card, wire `PageBuilderBar`.

**Step 1: Replace the file**

```vue
<!-- resources/js/Pages/Pages/Edit.vue -->
<script setup>
import PageBuilderLayout from '@/Layouts/PageBuilderLayout.vue'
import PageBuilderBar    from '@/Components/PageBuilderBar.vue'
import BlockEditor       from '@/Components/BlockEditor/BlockEditor.vue'
import { useForm, usePage, Head } from '@inertiajs/vue3'
import { filterEmptyBlocks } from '@/lib/utils.js'
import { ref, watch, onMounted, onBeforeUnmount } from 'vue'
import axios from 'axios'
import { useNotifications } from '@/composables/useNotifications.js'

const authUser = usePage().props.auth.user
const { notify, dismiss } = useNotifications()

const props = defineProps({
  page:       { type: Object, required: true },
  categories: { type: Array,  default: () => [] },
  tags:       { type: Array,  default: () => [] },
  autosave:   { type: Object, default: null },
})

const form = useForm({
  title:            props.page.title,
  slug:             props.page.slug,
  status:           props.page.status,
  blocks:           props.page.blocks ?? [],
  meta_title:       props.page.meta_title ?? '',
  meta_description: props.page.meta_description ?? '',
  meta_keywords:    props.page.meta_keywords ?? '',
})

function submit() {
  form.blocks = filterEmptyBlocks(form.blocks)
  form.put(route('pages.update', props.page.id), {
    onError: (errors) => notify('Please fix the following:', 'error', { items: Object.values(errors) }),
  })
}

// ── Autosave ──────────────────────────────────────────────────────────────────
let autosaveTimer    = null
let autosaveToastId  = null

watch(form, () => {
  clearTimeout(autosaveTimer)
  autosaveTimer = setTimeout(doAutosave, 10000)
}, { deep: true })

async function doAutosave() {
  try {
    const res = await axios.post(route('pages.autosave', props.page.id), { payload: form.data() })
    if (autosaveToastId !== null) dismiss(autosaveToastId)
    autosaveToastId = notify(`Draft saved at ${res.data.saved_at}`, 'info')
  } catch {
    notify('Autosave failed — check your connection', 'error')
    autosaveToastId = null
  }
}

async function restoreAutosave() {
  try {
    const payload = props.autosave.payload
    Object.keys(payload).forEach(key => { if (key in form) form[key] = payload[key] })
    await axios.delete(route('pages.autosave.destroy', props.page.id))
    notify('Draft restored.', 'success')
  } catch {
    notify('Failed to restore draft.', 'error')
  }
}

async function dismissAutosave() {
  try { await axios.delete(route('pages.autosave.destroy', props.page.id)) } catch { /* non-critical */ }
}

onMounted(() => {
  if (props.autosave && props.page.updated_at &&
      new Date(props.autosave.updated_at) > new Date(props.page.updated_at)) {
    notify('You have unsaved changes from a previous session.', 'info', {
      duration: null,
      actions: [
        { label: 'Restore', handler: restoreAutosave },
        { label: 'Dismiss', handler: dismissAutosave },
      ],
    })
  }
})

onBeforeUnmount(() => clearTimeout(autosaveTimer))

// ── Revisions ─────────────────────────────────────────────────────────────────
const revisionsLoading = ref(false)
const revisions        = ref([])
const restoreTarget    = ref(null)

async function loadRevisions() {
  if (revisions.value.length > 0) return
  revisionsLoading.value = true
  try {
    const res = await axios.get(route('pages.revisions', props.page.id))
    revisions.value = res.data
  } finally {
    revisionsLoading.value = false
  }
}

function restoreRevision(revision) {
  restoreTarget.value = revision
}

async function confirmRestore() {
  try {
    const res = await axios.get(route('revisions.restore', restoreTarget.value.id))
    const payload = res.data
    Object.keys(payload).forEach(key => { if (key in form) form[key] = payload[key] })
    revisions.value     = []
    restoreTarget.value = null
    notify('Revision restored.', 'success')
  } catch {
    notify('Failed to restore revision. Please try again.', 'error')
    restoreTarget.value = null
  }
}
</script>

<template>
  <PageBuilderLayout>
    <Head title="Edit Page" />

    <template #bar>
      <PageBuilderBar
        :back-href="route('pages.index')"
        :title="form.title"
        :slug="form.slug"
        :status="form.status"
        :meta-title="form.meta_title"
        :meta-description="form.meta_description"
        :meta-keywords="form.meta_keywords"
        :processing="form.processing"
        save-label="Update page"
        :show-revisions="true"
        :revisions="revisions"
        :revisions-loading="revisionsLoading"
        @update:title="form.title = $event"
        @update:slug="form.slug = $event"
        @update:status="form.status = $event"
        @update:meta-title="form.meta_title = $event"
        @update:meta-description="form.meta_description = $event"
        @update:meta-keywords="form.meta_keywords = $event"
        @save="submit"
        @restore-revision="restoreRevision"
        @revisions-open="loadRevisions"
      />
    </template>

    <BlockEditor
      :model-value="form.blocks"
      :is-admin="authUser?.role === 'administrator'"
      :meta="{ categories: props.categories, tags: props.tags }"
      :fullscreen="true"
      @update:model-value="form.blocks = $event"
    />

    <!-- Restore revision confirmation modal -->
    <Transition name="fade">
      <div v-if="restoreTarget" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="restoreTarget = null" />
        <div class="relative bg-[#181825] border border-white/10 rounded-xl shadow-2xl w-full max-w-sm p-6">
          <h3 class="font-semibold text-base text-white mb-2">Restore this version?</h3>
          <p class="text-sm text-white/60 mb-5">Your current changes will be replaced with the selected revision.</p>
          <div class="flex gap-3 justify-end">
            <button type="button" @click="restoreTarget = null"
              class="rounded-md border border-white/20 px-4 py-2 text-sm font-medium text-white/70 hover:bg-white/10 transition-colors">
              Cancel
            </button>
            <button type="button" @click="confirmRestore"
              class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-[var(--primary-hover)] transition-colors">
              Restore
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </PageBuilderLayout>
</template>
```

**Step 2: Commit**

```bash
git add resources/js/Pages/Pages/Edit.vue
git commit -m "feat: Pages/Edit — full-screen builder with PageBuilderLayout and PageBuilderBar"
```

---

## Task 5: Rewrite `Pages/Create.vue` to use the full-screen builder

**Files:**
- Modify: `resources/js/Pages/Pages/Create.vue`

Same treatment as Edit.vue. No autosave/revisions on Create (no page ID yet), so the bar is simpler.

**Step 1: Replace the file**

```vue
<!-- resources/js/Pages/Pages/Create.vue -->
<script setup>
import PageBuilderLayout from '@/Layouts/PageBuilderLayout.vue'
import PageBuilderBar    from '@/Components/PageBuilderBar.vue'
import BlockEditor       from '@/Components/BlockEditor/BlockEditor.vue'
import { useForm, usePage, Head } from '@inertiajs/vue3'
import { filterEmptyBlocks } from '@/lib/utils.js'
import { watch } from 'vue'
import { useNotifications } from '@/composables/useNotifications.js'

const { notify } = useNotifications()
const authUser   = usePage().props.auth.user

const props = defineProps({
  categories: { type: Array, default: () => [] },
  tags:       { type: Array, default: () => [] },
})

const form = useForm({
  title:            '',
  slug:             '',
  status:           'draft',
  blocks:           [],
  meta_title:       '',
  meta_description: '',
  meta_keywords:    '',
})

watch(() => form.title, (val, oldVal) => {
  if (!form.slug || form.slug === slugify(oldVal)) {
    form.slug = slugify(val)
  }
})

function slugify(str) {
  return str.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '')
}

function submit() {
  form.blocks = filterEmptyBlocks(form.blocks)
  form.post(route('pages.store'), {
    onError: (errors) => notify('Please fix the following:', 'error', { items: Object.values(errors) }),
  })
}
</script>

<template>
  <PageBuilderLayout>
    <Head title="New Page" />

    <template #bar>
      <PageBuilderBar
        :back-href="route('pages.index')"
        :title="form.title"
        :slug="form.slug"
        :status="form.status"
        :meta-title="form.meta_title"
        :meta-description="form.meta_description"
        :meta-keywords="form.meta_keywords"
        :processing="form.processing"
        save-label="Save page"
        :show-revisions="false"
        @update:title="form.title = $event"
        @update:slug="form.slug = $event"
        @update:status="form.status = $event"
        @update:meta-title="form.meta_title = $event"
        @update:meta-description="form.meta_description = $event"
        @update:meta-keywords="form.meta_keywords = $event"
        @save="submit"
      />
    </template>

    <BlockEditor
      :model-value="form.blocks"
      :is-admin="authUser?.role === 'administrator'"
      :meta="{ categories: props.categories, tags: props.tags }"
      :fullscreen="true"
      @update:model-value="form.blocks = $event"
    />
  </PageBuilderLayout>
</template>
```

**Step 2: Commit**

```bash
git add resources/js/Pages/Pages/Create.vue
git commit -m "feat: Pages/Create — full-screen builder with PageBuilderLayout and PageBuilderBar"
```

---

## Task 6: Build and verify

**Step 1: Run the build**

```bash
npm run build
```

Expected: build succeeds, no new errors.

**Step 2: Smoke-test manually**

1. Navigate to `/pages` → click **New page** → full-screen dark builder opens, no sidebar/topbar
2. Type a title → slug auto-generates in the bar
3. Toggle Draft/Published pills → active pill highlights with primary accent
4. Click SEO → popover opens with three fields, click outside → popover closes
5. Add a block via the palette, save → redirects to pages list or shows autosave toast
6. Open an existing page for editing → same builder loads, Revisions button visible
7. Click Revisions → popover loads and lists revisions
8. Check Posts editor (e.g. `/posts/create`) → BlockEditor still looks normal (border + rounded, constrained height) — `fullscreen` prop is false by default

**Step 3: Commit build artefacts**

```bash
git add public/build
git commit -m "chore: build — full-screen page builder"
```
