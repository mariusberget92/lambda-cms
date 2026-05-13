<template>
  <AppLayout title="Form Submissions">
    <Head title="Form Submissions" />

    <PageHeader
      title="Form Submissions"
      :description="`${total} total submission${total === 1 ? '' : 's'}`"
    />

    <!-- Empty state -->
    <div v-if="submissions.data.length === 0" class="py-16 text-center">
      <Inbox class="w-10 h-10 mx-auto mb-3 text-muted-foreground/30" />
      <p class="text-muted-foreground text-sm">No form submissions yet.</p>
    </div>

    <!-- Submission cards -->
    <div v-else class="space-y-3">
      <div
        v-for="submission in submissions.data"
        :key="submission.id"
        class="rounded-lg border bg-card overflow-hidden"
      >
        <!-- Card header / summary row -->
        <div
          class="flex items-start gap-3 p-4 cursor-pointer hover:bg-muted/30 transition-colors"
          @click="toggleExpanded(submission.id)"
        >
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
              <span class="text-sm font-semibold">{{ submission.form_name ?? 'Unnamed form' }}</span>
              <span
                v-if="submission.page_slug"
                class="text-xs bg-muted px-1.5 py-0.5 rounded font-mono text-muted-foreground"
              >/{{ submission.page_slug }}</span>
              <span class="text-xs text-muted-foreground">· {{ submission.created_at }}</span>
            </div>

            <!-- Data preview — first 3 fields -->
            <div class="flex flex-wrap gap-1.5 mt-2">
              <span
                v-for="([key, val], i) in dataPreview(submission.data)"
                :key="i"
                class="inline-flex items-center gap-1 rounded-full bg-muted/60 px-2 py-0.5 text-xs text-muted-foreground"
              >
                <span class="font-medium text-foreground/70">{{ key }}:</span>
                <span class="truncate max-w-[120px]">{{ val }}</span>
              </span>
            </div>
          </div>

          <div class="flex items-center gap-2 shrink-0">
            <ChevronDown
              class="w-4 h-4 text-muted-foreground transition-transform duration-200"
              :class="expanded[submission.id] ? 'rotate-180' : ''"
            />
            <button
              type="button"
              class="rounded-md bg-destructive/10 px-2.5 py-1 text-xs font-medium text-destructive hover:bg-destructive/20 transition-colors"
              @click.stop="confirmDelete(submission)"
            >Delete</button>
          </div>
        </div>

        <!-- Expanded detail -->
        <div v-if="expanded[submission.id]" class="border-t px-4 py-3 bg-muted/10">
          <dl class="space-y-2">
            <div v-for="(val, key) in submission.data" :key="key" class="grid grid-cols-[180px_1fr] gap-2 text-sm">
              <dt class="font-medium text-muted-foreground truncate">{{ key }}</dt>
              <dd class="text-foreground whitespace-pre-wrap break-words">{{ val }}</dd>
            </div>
          </dl>
          <p v-if="submission.ip_address" class="mt-3 text-xs text-muted-foreground/60">
            Submitted from {{ submission.ip_address }}
          </p>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="submissions.last_page > 1" class="mt-6 flex justify-center gap-2">
      <a
        v-for="page in submissions.links"
        :key="page.label"
        :href="page.url"
        class="rounded-md border px-3 py-1.5 text-sm transition-colors"
        :class="page.active
          ? 'bg-primary text-primary-foreground border-primary'
          : page.url ? 'hover:bg-accent' : 'opacity-40 cursor-default pointer-events-none'"
      >{{ decodeHtmlEntities(page.label) }}</a>
    </div>

    <!-- Delete confirmation modal -->
    <Transition name="fade">
      <div v-if="deleteTarget" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="deleteTarget = null" />
        <div class="relative bg-card border rounded-xl shadow-xl w-full max-w-sm p-6">
          <h3 class="font-semibold text-base mb-2">Delete submission?</h3>
          <p class="text-sm text-muted-foreground mb-5">
            This form submission will be permanently deleted.
          </p>
          <div class="flex gap-3 justify-end">
            <button
              type="button"
              @click="deleteTarget = null"
              class="rounded-md border px-4 py-2 text-sm font-medium hover:bg-accent transition-colors"
            >Cancel</button>
            <button
              type="button"
              @click="doDelete"
              class="rounded-md bg-destructive px-4 py-2 text-sm font-medium text-destructive-foreground hover:bg-destructive/90 transition-colors"
            >Delete</button>
          </div>
        </div>
      </div>
    </Transition>
  </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import { Inbox, ChevronDown } from 'lucide-vue-next'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/PageHeader.vue'
import { decodeHtmlEntities } from '@/lib/utils.js'

defineProps({
  submissions: Object,
  total:       { type: Number, default: 0 },
})

const expanded    = reactive({})
const deleteTarget = ref(null)

function toggleExpanded(id) {
  expanded[id] = !expanded[id]
}

function dataPreview(data) {
  return Object.entries(data ?? {}).slice(0, 3)
}

function confirmDelete(submission) {
  deleteTarget.value = submission
}

function doDelete() {
  if (!deleteTarget.value) return
  router.delete(route('form-submissions.destroy', deleteTarget.value.id), {
    onFinish: () => { deleteTarget.value = null },
  })
}
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
