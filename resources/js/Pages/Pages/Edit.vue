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
  custom_js:        props.page.custom_js ?? '',
})

function submit() {
  form.blocks = filterEmptyBlocks(form.blocks)
  form.put(route('pages.update', props.page.id), {
    preserveState:  true,
    preserveScroll: true,
    onSuccess: () => notify('Page saved.', 'success'),
    onError: (errors) => notify('Please fix the following:', 'error', { items: Object.values(errors) }),
  })
}

// ── Autosave ──────────────────────────────────────────────────────────────────
let autosaveTimer   = null
let autosaveToastId = null

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
        :preview-href="props.page.preview_token ? route('preview.page', props.page.preview_token) : null"
        :title="form.title"
        :slug="form.slug"
        :status="form.status"
        :meta-title="form.meta_title"
        :meta-description="form.meta_description"
        :meta-keywords="form.meta_keywords"
        :custom-js="form.custom_js"
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
        @update:custom-js="form.custom_js = $event"
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
