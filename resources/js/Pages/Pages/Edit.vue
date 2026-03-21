<!-- resources/js/Pages/Pages/Edit.vue -->
<script setup>
import AppLayout   from '@/Layouts/AppLayout.vue'
import BlockEditor from '@/Components/BlockEditor/BlockEditor.vue'
import { useForm, usePage, Head } from '@inertiajs/vue3'
import { filterEmptyBlocks } from '@/lib/utils.js'
import { ref, watch, onBeforeUnmount } from 'vue'
import axios from 'axios'

const authUser = usePage().props.auth.user

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
  form.put(route('pages.update', props.page.id))
}

// Autosave
const autosaveStatus  = ref(null)
const autosaveSavedAt = ref(null)

const showRestoreBanner = ref(
  props.autosave !== null &&
  props.page.updated_at !== null &&
  new Date(props.autosave.updated_at) > new Date(props.page.updated_at)
)

let autosaveTimer = null

watch(form, () => {
  clearTimeout(autosaveTimer)
  autosaveTimer = setTimeout(doAutosave, 10000)
}, { deep: true })

async function doAutosave() {
  autosaveStatus.value = 'saving'
  try {
    const res = await axios.post(route('pages.autosave', props.page.id), {
      payload: form.data(),
    })
    autosaveSavedAt.value = res.data.saved_at
    autosaveStatus.value  = 'saved'
  } catch {
    autosaveStatus.value = 'error'
  }
}

async function restoreAutosave() {
  const payload = props.autosave.payload
  Object.keys(payload).forEach(key => {
    if (key in form) form[key] = payload[key]
  })
  showRestoreBanner.value = false
  await axios.delete(route('pages.autosave.destroy', props.page.id))
}

async function dismissAutosave() {
  showRestoreBanner.value = false
  await axios.delete(route('pages.autosave.destroy', props.page.id))
}

onBeforeUnmount(() => clearTimeout(autosaveTimer))
</script>

<template>
  <AppLayout title="Edit Page">
    <Head title="Edit Page" />
    <form @submit.prevent="submit">
      <!-- Autosave recovery banner -->
      <div
        v-if="showRestoreBanner"
        class="mb-4 flex items-center gap-3 rounded-md border border-amber-300 bg-amber-50 dark:bg-amber-950/30 dark:border-amber-700 px-4 py-3 text-sm"
      >
        <span class="flex-1 text-amber-800 dark:text-amber-300">
          You have unsaved changes from a previous session.
        </span>
        <button type="button" @click="restoreAutosave" class="rounded-md bg-primary px-3 py-1.5 text-xs font-medium text-primary-foreground hover:bg-[var(--primary-hover)]">Restore</button>
        <button type="button" @click="dismissAutosave" class="rounded-md border px-3 py-1.5 text-xs font-medium hover:bg-accent">Dismiss</button>
      </div>

      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
          <a :href="route('pages.index')" class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-accent transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
          </a>
          <div>
            <h2 class="text-lg font-semibold">Edit page</h2>
            <p class="text-sm text-muted-foreground mt-0.5 line-clamp-1">{{ page.title }}</p>
          </div>
        </div>
        <div class="flex items-center gap-3">
          <span v-if="autosaveStatus === 'saving'" class="text-xs text-muted-foreground">Saving draft…</span>
          <span v-else-if="autosaveStatus === 'saved'" class="text-xs text-muted-foreground">Draft saved at {{ autosaveSavedAt }}</span>
          <span v-else-if="autosaveStatus === 'error'" class="text-xs text-destructive">Autosave failed</span>
          <button
            type="submit"
            :disabled="form.processing"
            class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-[var(--primary-hover)] disabled:opacity-50 transition-colors"
          >
            {{ form.processing ? 'Saving...' : 'Update page' }}
          </button>
        </div>
      </div>

      <!-- Same layout as Create: 2-col main + sidebar -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-4">
          <div>
            <input
              v-model="form.title"
              type="text"
              class="w-full rounded-lg border bg-background px-4 py-3 text-xl font-semibold focus:outline-none focus:ring-2 focus:ring-ring"
              :class="{ 'border-destructive': form.errors.title }"
            />
            <p v-if="form.errors.title" class="mt-1 text-xs text-destructive">{{ form.errors.title }}</p>
          </div>
          <BlockEditor
            :model-value="form.blocks"
            :is-admin="authUser?.role === 'administrator'"
            :meta="{ categories: props.categories, tags: props.tags }"
            @update:model-value="form.blocks = $event"
          />
        </div>

        <div class="space-y-4">
          <div class="rounded-lg border bg-card p-4">
            <h3 class="text-sm font-medium mb-3">URL Slug</h3>
            <div class="flex items-center gap-1">
              <span class="text-sm text-muted-foreground">/</span>
              <input
                v-model="form.slug"
                type="text"
                class="flex-1 rounded border bg-background px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                :class="{ 'border-destructive': form.errors.slug }"
              />
            </div>
            <p v-if="form.errors.slug" class="mt-1 text-xs text-destructive">{{ form.errors.slug }}</p>
          </div>

          <div class="rounded-lg border bg-card p-4">
            <h3 class="text-sm font-medium mb-3">Status</h3>
            <div class="space-y-2">
              <label class="flex items-center gap-3 cursor-pointer">
                <input type="radio" v-model="form.status" value="draft" class="accent-primary" />
                <span class="text-sm font-medium">Draft</span>
              </label>
              <label class="flex items-center gap-3 cursor-pointer">
                <input type="radio" v-model="form.status" value="published" class="accent-primary" />
                <span class="text-sm font-medium">Published</span>
              </label>
            </div>
          </div>

          <details class="rounded-lg border bg-card">
            <summary class="px-4 py-3 text-sm font-medium cursor-pointer">SEO (optional)</summary>
            <div class="px-4 pb-4 space-y-3 border-t pt-3">
              <div>
                <label class="text-xs text-muted-foreground block mb-1">Meta title</label>
                <input v-model="form.meta_title" type="text" class="w-full rounded border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
              </div>
              <div>
                <label class="text-xs text-muted-foreground block mb-1">Meta description</label>
                <textarea v-model="form.meta_description" rows="3" class="w-full rounded border bg-background px-2 py-1.5 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-ring" />
              </div>
              <div>
                <label class="text-xs text-muted-foreground block mb-1">Meta keywords</label>
                <input v-model="form.meta_keywords" type="text" class="w-full rounded border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
              </div>
            </div>
          </details>
        </div>
      </div>
    </form>
  </AppLayout>
</template>
