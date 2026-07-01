<template>
  <AppLayout title="Edit Template">
    <Head :title="`Edit — ${template.name}`" />

    <PageHeader :title="template.name" :description="template.description">
      <template #actions>
        <button
          type="button"
          @click="showPreview = true"
          class="inline-flex items-center gap-2 rounded-md border px-4 py-2 text-sm font-medium hover:bg-accent transition-colors"
        >
          <Eye class="w-4 h-4" />
          Preview
        </button>
        <button
          type="button"
          @click="showResetModal = true"
          class="inline-flex items-center gap-2 rounded-md border px-4 py-2 text-sm font-medium text-destructive hover:bg-destructive/10 transition-colors"
        >
          <RotateCcw class="w-4 h-4" />
          Reset to default
        </button>
      </template>
    </PageHeader>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <div class="lg:col-span-2 space-y-6">
        <ContentCard title="Subject">
          <div class="relative">
            <input
              v-model="form.subject"
              type="text"
              class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
              placeholder="Email subject line..."
            />
            <p v-if="form.errors.subject" class="text-xs text-destructive mt-1">{{ form.errors.subject }}</p>
          </div>
        </ContentCard>

        <ContentCard title="Body">
          <TiptapEditor v-model="form.body" placeholder="Email body content..." />
          <p v-if="form.errors.body" class="text-xs text-destructive mt-1">{{ form.errors.body }}</p>
        </ContentCard>

        <div class="flex justify-end">
          <button
            type="button"
            :disabled="form.processing"
            @click="save"
            class="inline-flex items-center gap-2 rounded-md bg-primary px-6 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)] disabled:opacity-50"
          >
            {{ form.processing ? 'Saving...' : 'Save changes' }}
          </button>
        </div>
      </div>

      <!-- Merge tags sidebar -->
      <div>
        <ContentCard title="Merge Tags" description="Click a tag to copy it">
          <div class="space-y-2">
            <button
              v-for="tag in template.merge_tags"
              :key="tag.tag"
              type="button"
              class="w-full text-left px-3 py-2 rounded-md border hover:bg-accent transition-colors group"
              @click="copyTag(tag.tag)"
            >
              <div class="flex items-center justify-between">
                <code class="text-xs font-mono text-primary">{{ tag.tag }}</code>
                <Copy class="w-3 h-3 text-muted-foreground opacity-0 group-hover:opacity-100 transition-opacity" />
              </div>
              <p class="text-xs text-muted-foreground mt-0.5">{{ tag.description }}</p>
            </button>
          </div>
        </ContentCard>
      </div>
    </div>

    <!-- Reset confirm modal -->
    <ConfirmModal
      :open="showResetModal"
      title="Reset to default?"
      description="This will replace the current subject and body with the original defaults. This cannot be undone."
      confirm-label="Reset"
      :processing="resetting"
      @close="showResetModal = false"
      @confirm="resetTemplate"
    />

    <!-- Preview modal -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showPreview" class="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="showPreview = false" />
          <div class="modal-panel relative bg-card border rounded-xl w-full max-w-2xl max-h-[80vh] flex flex-col overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b">
              <h3 class="font-semibold text-base">Email Preview</h3>
              <button
                type="button"
                @click="showPreview = false"
                class="inline-flex items-center justify-center w-8 h-8 rounded-md hover:bg-accent transition-colors"
              >
                <X class="w-4 h-4" />
              </button>
            </div>
            <div class="flex-1 overflow-auto p-1">
              <iframe
                v-if="previewHtml"
                :srcdoc="previewHtml"
                class="w-full h-full min-h-[400px] border-0 rounded"
              />
              <div v-else class="flex items-center justify-center h-64 text-sm text-muted-foreground">
                Loading preview...
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </AppLayout>
</template>

<script setup>
import { ref, watch } from 'vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/PageHeader.vue'
import ContentCard from '@/Components/ContentCard.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'
import TiptapEditor from '@/Components/TiptapEditor.vue'
import { Eye, RotateCcw, Copy, X } from '@lucide/vue'
import { useNotifications } from '@/composables/useNotifications.js'

const { notify } = useNotifications()

const props = defineProps({
  template: { type: Object, required: true },
})

const form = useForm({
  subject: props.template.subject,
  body: props.template.body,
})

const showResetModal = ref(false)
const resetting = ref(false)
const showPreview = ref(false)
const previewHtml = ref('')

function save() {
  form.put(route('email-templates.update', props.template.id))
}

function resetTemplate() {
  resetting.value = true
  router.post(route('email-templates.reset', props.template.id), {}, {
    preserveScroll: true,
    onSuccess: () => {
      showResetModal.value = false
      resetting.value = false
    },
    onError: () => {
      resetting.value = false
    },
  })
}

function copyTag(tag) {
  navigator.clipboard.writeText(tag)
  notify('Tag copied to clipboard', 'success')
}

watch(showPreview, async (open) => {
  if (open) {
    previewHtml.value = ''
    try {
      const response = await fetch(route('email-templates.preview', props.template.id), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
        },
      })
      previewHtml.value = await response.text()
    } catch {
      previewHtml.value = '<p style="padding:24px;color:#999;">Failed to load preview.</p>'
    }
  }
})
</script>

<style scoped>
.modal-panel {
  box-shadow:
    0 16px 40px -8px rgb(0 0 0 / 0.18),
    0 4px 12px -2px rgb(0 0 0 / 0.08);
}
.modal-enter-active { transition: opacity 0.15s ease-out; }
.modal-enter-active .modal-panel { transition: transform 0.15s ease-out, opacity 0.15s ease-out; }
.modal-leave-active { transition: opacity 0.1s ease-in; }
.modal-leave-active .modal-panel { transition: transform 0.1s ease-in, opacity 0.1s ease-in; }
.modal-enter-from { opacity: 0; }
.modal-enter-from .modal-panel { transform: scale(0.95) translateY(4px); opacity: 0; }
.modal-leave-to { opacity: 0; }
.modal-leave-to .modal-panel { transform: scale(0.97); opacity: 0; }
</style>
