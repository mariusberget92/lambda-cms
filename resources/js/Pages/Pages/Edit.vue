<!-- resources/js/Pages/Pages/Edit.vue -->
<script setup>
import AppLayout   from '@/Layouts/AppLayout.vue'
import BlockEditor from '@/Components/BlockEditor/BlockEditor.vue'
import { useForm, usePage, Head } from '@inertiajs/vue3'
import { filterEmptyBlocks } from '@/lib/utils.js'
import { ref, watch, onMounted, onBeforeUnmount } from 'vue'
import axios from 'axios'
import { ChevronDown, ArrowLeft } from 'lucide-vue-next'
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

// Autosave
let autosaveTimer = null
let autosaveToastId = null

watch(form, () => {
  clearTimeout(autosaveTimer)
  autosaveTimer = setTimeout(doAutosave, 10000)
}, { deep: true })

async function doAutosave() {
  try {
    const res = await axios.post(route('pages.autosave', props.page.id), {
      payload: form.data(),
    })
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
    Object.keys(payload).forEach(key => {
      if (key in form) form[key] = payload[key]
    })
    await axios.delete(route('pages.autosave.destroy', props.page.id))
    notify('Draft restored.', 'success')
  } catch {
    notify('Failed to restore draft.', 'error')
  }
}

async function dismissAutosave() {
  try {
    await axios.delete(route('pages.autosave.destroy', props.page.id))
  } catch {
    // non-critical
  }
}

onMounted(() => {
  if (
    props.autosave &&
    props.page.updated_at &&
    new Date(props.autosave.updated_at) > new Date(props.page.updated_at)
  ) {
    notify(
      'You have unsaved changes from a previous session.',
      'info',
      {
        duration: null,
        actions: [
          { label: 'Restore', handler: restoreAutosave },
          { label: 'Dismiss', handler: dismissAutosave },
        ],
      }
    )
  }
})

onBeforeUnmount(() => clearTimeout(autosaveTimer))

// Revisions
const seoOpen          = ref(false)
const revisionsOpen    = ref(false)
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

function toggleRevisions() {
  revisionsOpen.value = !revisionsOpen.value
  if (revisionsOpen.value) loadRevisions()
}

function restoreRevision(revision) {
  restoreTarget.value = revision
}

async function confirmRestore() {
  try {
    const res = await axios.get(route('revisions.restore', restoreTarget.value.id))
    const payload = res.data
    Object.keys(payload).forEach(key => {
      if (key in form) form[key] = payload[key]
    })
    revisions.value = []
    revisionsOpen.value = false
    restoreTarget.value = null
  } catch {
    notify('Failed to restore revision. Please try again.', 'error')
    restoreTarget.value = null
  }
}
</script>

<template>
  <AppLayout title="Edit Page">
    <Head title="Edit Page" />
    <form @submit.prevent="submit" class="space-y-4">

      <!-- Header -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <a :href="route('pages.index')" class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-accent transition-colors">
            <ArrowLeft class="w-4 h-4" />
          </a>
          <div>
            <h2 class="text-lg font-semibold">Edit page</h2>
            <p class="text-sm text-muted-foreground mt-0.5 line-clamp-1">{{ page.title }}</p>
          </div>
        </div>
        <button type="submit" :disabled="form.processing"
          class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-[var(--primary-hover)] disabled:opacity-50 transition-colors">
          {{ form.processing ? 'Saving...' : 'Update page' }}
        </button>
      </div>

      <!-- Meta card: title + slug/status/SEO/revisions inline -->
      <div class="rounded-lg border bg-card p-4 space-y-3">
        <!-- Title -->
        <div>
          <input
            v-model="form.title"
            type="text"
            class="w-full rounded-lg border bg-background px-4 py-3 text-xl font-semibold focus:outline-none focus:ring-2 focus:ring-ring"
            :class="{ 'border-destructive': form.errors.title }"
          />
          <p v-if="form.errors.title" class="text-xs text-destructive mt-1">{{ form.errors.title }}</p>
        </div>

        <!-- Inline sub-fields: slug · status · SEO · Revisions -->
        <div class="flex flex-wrap items-center gap-x-4 gap-y-2 pt-2 border-t border-border/50">
          <!-- Slug -->
          <div class="flex items-center gap-1.5 min-w-0">
            <span class="text-xs font-medium text-muted-foreground shrink-0">Slug /</span>
            <input
              v-model="form.slug"
              type="text"
              class="w-44 rounded border bg-background px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
              :class="{ 'border-destructive': form.errors.slug }"
            />
            <p v-if="form.errors.slug" class="text-xs text-destructive mt-1">{{ form.errors.slug }}</p>
          </div>
          <div class="h-4 w-px bg-border hidden sm:block shrink-0" />

          <!-- Status -->
          <div class="flex items-center gap-4 shrink-0">
            <label class="flex items-center gap-1.5 cursor-pointer">
              <input type="radio" v-model="form.status" value="draft" class="accent-primary" />
              <span class="text-sm font-medium">Draft</span>
            </label>
            <label class="flex items-center gap-1.5 cursor-pointer">
              <input type="radio" v-model="form.status" value="published" class="accent-primary" />
              <span class="text-sm font-medium">Published</span>
            </label>
          </div>

          <div class="h-4 w-px bg-border hidden sm:block shrink-0" />

          <!-- SEO toggle -->
          <button type="button" @click="seoOpen = !seoOpen"
            class="flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground transition-colors shrink-0">
            SEO
            <ChevronDown class="w-3.5 h-3.5 transition-transform" :class="{ 'rotate-180': seoOpen }" />
          </button>

          <div class="h-4 w-px bg-border hidden sm:block shrink-0" />

          <!-- Revisions toggle -->
          <button type="button" @click="toggleRevisions"
            class="flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground transition-colors shrink-0">
            Revisions
            <ChevronDown class="w-3.5 h-3.5 transition-transform" :class="{ 'rotate-180': revisionsOpen }" />
          </button>
        </div>

        <!-- SEO fields (expanded) -->
        <div v-if="seoOpen" class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2 border-t border-border/50">
          <div>
            <label class="text-xs text-muted-foreground block mb-1">Meta title</label>
            <input v-model="form.meta_title" type="text"
              class="w-full rounded border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
          </div>
          <div>
            <label class="text-xs text-muted-foreground block mb-1">Meta description</label>
            <textarea v-model="form.meta_description" rows="2"
              class="w-full rounded border bg-background px-2 py-1.5 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-ring" />
          </div>
          <div>
            <label class="text-xs text-muted-foreground block mb-1">Meta keywords</label>
            <input v-model="form.meta_keywords" type="text"
              class="w-full rounded border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
          </div>
        </div>

        <!-- Revisions list (expanded) -->
        <div v-if="revisionsOpen" class="pt-2 border-t border-border/50">
          <div v-if="revisionsLoading" class="text-xs text-muted-foreground text-center py-3">Loading…</div>
          <div v-else-if="revisions.length === 0" class="text-xs text-muted-foreground text-center py-3">No revisions yet.</div>
          <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2">
            <div
              v-for="rev in revisions"
              :key="rev.id"
              class="flex items-center justify-between gap-2 rounded-md border px-3 py-2 hover:bg-muted/50"
            >
              <div class="min-w-0">
                <p class="text-xs font-medium truncate">{{ rev.user?.name ?? 'Unknown' }}</p>
                <p class="text-[11px] text-muted-foreground">{{ new Date(rev.created_at).toLocaleString() }}</p>
              </div>
              <button
                type="button"
                class="shrink-0 rounded border px-2 py-0.5 text-xs hover:bg-accent transition-colors"
                @click="restoreRevision(rev)"
              >Restore</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Block editor: full remaining width -->
      <BlockEditor
        :model-value="form.blocks"
        :is-admin="authUser?.role === 'administrator'"
        :meta="{ categories: props.categories, tags: props.tags }"
        @update:model-value="form.blocks = $event"
      />

    </form>

    <!-- Restore revision confirmation modal -->
    <Transition name="fade">
      <div v-if="restoreTarget" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="restoreTarget = null" />
        <div class="relative bg-card border rounded-xl shadow-xl w-full max-w-sm p-6">
          <h3 class="font-semibold text-base mb-2">Restore this version?</h3>
          <p class="text-sm text-muted-foreground mb-5">
            Your current changes will be replaced with the selected revision.
          </p>
          <div class="flex gap-3 justify-end">
            <button
              type="button"
              @click="restoreTarget = null"
              class="rounded-md border px-4 py-2 text-sm font-medium hover:bg-accent transition-colors"
            >
              Cancel
            </button>
            <button
              type="button"
              @click="confirmRestore"
              class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-[var(--primary-hover)] transition-colors"
            >
              Restore
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </AppLayout>
</template>
