<!-- resources/js/Pages/Templates/Edit.vue -->
<script setup>
import AppLayout   from '@/Layouts/AppLayout.vue'
import BlockEditor from '@/Components/BlockEditor/BlockEditor.vue'
import { useForm, usePage, Head } from '@inertiajs/vue3'
import { POST_CONTEXT_FIELDS } from '@/lib/loopSources.js'
import { filterEmptyBlocks } from '@/lib/utils.js'
import { ref, watch, onMounted, onBeforeUnmount } from 'vue'
import axios from 'axios'
import { ChevronDown, ArrowLeft } from 'lucide-vue-next'
import { useNotifications } from '@/composables/useNotifications.js'

const authUser = usePage().props.auth.user
const { notify, dismiss } = useNotifications()

const props = defineProps({
  template: { type: Object, required: true },
  autosave: { type: Object, default: null },
})

const TYPE_LABELS = {
  'blog-index':     'Blog Index',
  'single-post':    'Single Post',
  'archive':        'Archive',
  'search-results': 'Search Results',
}

const form = useForm({
  name:             props.template.name,
  type:             props.template.type,
  status:           props.template.status,
  blocks:           props.template.blocks ?? [],
  meta_title:       props.template.meta_title ?? '',
  meta_description: props.template.meta_description ?? '',
  meta_keywords:    props.template.meta_keywords ?? '',
})

function submit() {
  form.blocks = filterEmptyBlocks(form.blocks)
  form.put(route('templates.update', props.template.id))
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
    const res = await axios.post(route('templates.autosave', props.template.id), {
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
    await axios.delete(route('templates.autosave.destroy', props.template.id))
    notify('Draft restored.', 'success')
  } catch {
    notify('Failed to restore draft.', 'error')
  }
}

async function dismissAutosave() {
  try {
    await axios.delete(route('templates.autosave.destroy', props.template.id))
  } catch {
    // non-critical
  }
}

onMounted(() => {
  if (
    props.autosave &&
    props.template.updated_at &&
    new Date(props.autosave.updated_at) > new Date(props.template.updated_at)
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

async function loadRevisions() {
  if (revisions.value.length > 0) return
  revisionsLoading.value = true
  try {
    const res = await axios.get(route('templates.revisions', props.template.id))
    revisions.value = res.data
  } finally {
    revisionsLoading.value = false
  }
}

function toggleRevisions() {
  revisionsOpen.value = !revisionsOpen.value
  if (revisionsOpen.value) loadRevisions()
}

async function restoreRevision(revision) {
  if (!window.confirm('Restore this version? Your current changes will be replaced.')) return
  const res = await axios.get(route('revisions.restore', revision.id))
  const payload = res.data
  Object.keys(payload).forEach(key => {
    if (key in form) form[key] = payload[key]
  })
  revisions.value = []
  revisionsOpen.value = false
}
</script>

<template>
  <AppLayout title="Edit Template">
    <Head title="Edit Template" />
    <form @submit.prevent="submit" class="space-y-4">

      <!-- Header -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <a :href="route('templates.index')" class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-accent transition-colors">
            <ArrowLeft class="w-4 h-4" />
          </a>
          <div>
            <h2 class="text-lg font-semibold">Edit template</h2>
            <p class="text-sm text-muted-foreground mt-0.5 line-clamp-1">
              {{ TYPE_LABELS[template.type] ?? template.type }} — {{ template.name }}
            </p>
          </div>
        </div>
        <button type="submit" :disabled="form.processing"
          class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-[var(--primary-hover)] disabled:opacity-50 transition-colors">
          {{ form.processing ? 'Saving...' : 'Update template' }}
        </button>
      </div>

      <!-- Meta card: name + status/SEO/revisions inline -->
      <div class="rounded-lg border bg-card p-4 space-y-3">
        <!-- Name -->
        <div>
          <input
            v-model="form.name"
            type="text"
            class="w-full rounded-lg border bg-background px-4 py-3 text-xl font-semibold focus:outline-none focus:ring-2 focus:ring-ring"
            :class="{ 'border-destructive': form.errors.name }"
          />
          <p v-if="form.errors.name" class="mt-1 text-xs text-destructive">{{ form.errors.name }}</p>
        </div>

        <!-- Inline sub-fields: status · SEO · Revisions -->
        <div class="flex flex-wrap items-center gap-x-4 gap-y-2 pt-2 border-t border-border/50">
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
        :context-fields="template.type === 'single-post' ? POST_CONTEXT_FIELDS : []"
        @update:model-value="form.blocks = $event"
      />

    </form>
  </AppLayout>
</template>
