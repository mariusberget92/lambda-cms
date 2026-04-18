<!-- resources/js/Pages/Templates/Edit.vue -->
<script setup>
import PageBuilderLayout  from '@/Layouts/PageBuilderLayout.vue'
import TemplateBuilderBar from '@/Components/TemplateBuilderBar.vue'
import BlockEditor        from '@/Components/BlockEditor/BlockEditor.vue'
import { useForm, usePage, Head } from '@inertiajs/vue3'
import { POST_CONTEXT_FIELDS } from '@/lib/loopSources.js'
import { filterEmptyBlocks } from '@/lib/utils.js'
import { ref, watch, computed, onMounted, onBeforeUnmount } from 'vue'
import axios from 'axios'
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
  'partial':        'Partial',
}

const typeLabel = TYPE_LABELS[props.template.type] ?? props.template.type

const form = useForm({
  title:            props.template.title,
  type:             props.template.type,
  loop_source:      props.template.loop_source ?? 'posts',
  status:           props.template.status,
  blocks:           props.template.blocks ?? [],
  meta_title:       props.template.meta_title ?? '',
  meta_description: props.template.meta_description ?? '',
  meta_keywords:    props.template.meta_keywords ?? '',
})

const isSinglePost      = computed(() => props.template.type === 'single-post')
const defaultLoopSource = computed(() => isSinglePost.value ? null : form.loop_source)

function submit() {
  form.blocks = filterEmptyBlocks(form.blocks)
  form.put(route('templates.update', props.template.id), {
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
    const res = await axios.post(route('templates.autosave', props.template.id), { payload: form.data() })
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
    await axios.delete(route('templates.autosave.destroy', props.template.id))
    notify('Draft restored.', 'success')
  } catch {
    notify('Failed to restore draft.', 'error')
  }
}

async function dismissAutosave() {
  try { await axios.delete(route('templates.autosave.destroy', props.template.id)) } catch { /* non-critical */ }
}

onMounted(() => {
  if (props.autosave && props.template.updated_at &&
      new Date(props.autosave.updated_at) > new Date(props.template.updated_at)) {
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
    const res = await axios.get(route('templates.revisions', props.template.id))
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
    <Head :title="`Edit Template — ${template.title}`" />

    <template #bar>
      <TemplateBuilderBar
        :back-href="route('templates.index')"
        :title="form.title"
        :type-label="typeLabel"
        :loop-source="form.loop_source"
        :show-loop-source="!isSinglePost"
        :status="form.status"
        :meta-title="form.meta_title"
        :meta-description="form.meta_description"
        :meta-keywords="form.meta_keywords"
        :processing="form.processing"
        save-label="Save template"
        show-revisions
        :revisions="revisions"
        :revisions-loading="revisionsLoading"
        @update:title="form.title = $event"
        @update:loop-source="form.loop_source = $event"
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
      :fullscreen="true"
      :model-value="form.blocks"
      :is-admin="authUser?.role === 'administrator'"
      :context-fields="isSinglePost ? POST_CONTEXT_FIELDS : []"
      :default-loop-source="defaultLoopSource"
      @update:model-value="form.blocks = $event"
    />

  </PageBuilderLayout>

  <!-- Restore revision confirmation modal -->
  <Transition
    enter-active-class="transition ease-out duration-150"
    enter-from-class="opacity-0"
    enter-to-class="opacity-100"
    leave-active-class="transition ease-in duration-100"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0"
  >
    <div v-if="restoreTarget" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" @click.self="restoreTarget = null">
      <div class="w-full max-w-sm rounded-lg border bg-card p-6 shadow-lg space-y-4">
        <h3 class="text-base font-semibold">Restore version?</h3>
        <p class="text-sm text-muted-foreground">Your current unsaved changes will be replaced with the selected revision. This cannot be undone.</p>
        <div class="flex justify-end gap-2">
          <button
            type="button"
            class="rounded-md border px-4 py-2 text-sm font-medium hover:bg-accent transition-colors"
            @click="restoreTarget = null"
          >Cancel</button>
          <button
            type="button"
            class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-[var(--primary-hover)] transition-colors"
            @click="confirmRestore"
          >Restore</button>
        </div>
      </div>
    </div>
  </Transition>
</template>
