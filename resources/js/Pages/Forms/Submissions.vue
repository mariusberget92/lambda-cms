<!-- resources/js/Pages/Forms/Submissions.vue -->
<template>
  <AppLayout :title="`Submissions: ${form.name}`">
    <Head :title="`Submissions — ${form.name}`" />

    <div class="flex items-center gap-3 mb-6">
      <a
        :href="route('forms.index')"
        class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-accent hover:text-accent-foreground transition-colors"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
      </a>
      <div class="flex-1">
        <h2 class="text-lg font-semibold">{{ form.name }}</h2>
        <p class="text-sm text-muted-foreground mt-0.5">{{ submissions.total }} submission{{ submissions.total !== 1 ? 's' : '' }}</p>
      </div>
      <a
        :href="route('forms.edit', form.id)"
        class="inline-flex items-center gap-2 rounded-md border px-3 py-2 text-sm font-medium hover:bg-accent transition-colors"
      >
        Edit form
      </a>
    </div>

    <!-- Empty state -->
    <div v-if="submissions.data.length === 0" class="rounded-lg border bg-card p-12 text-center">
      <svg class="w-10 h-10 mx-auto mb-3 text-muted-foreground/40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
      </svg>
      <p class="text-sm text-muted-foreground">No submissions yet.</p>
    </div>

    <!-- Submissions list -->
    <div v-else class="space-y-3">
      <div
        v-for="submission in submissions.data"
        :key="submission.id"
        class="rounded-lg border bg-card overflow-hidden"
      >
        <!-- Header -->
        <div
          class="flex items-center gap-3 px-4 py-3 cursor-pointer hover:bg-muted/30 transition-colors select-none"
          @click="toggleExpand(submission.id)"
        >
          <svg
            class="w-4 h-4 text-muted-foreground shrink-0 transition-transform duration-150"
            :class="{ 'rotate-90': expanded.has(submission.id) }"
            fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
          >
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
          </svg>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium">{{ submissionPreview(submission) }}</p>
            <p class="text-xs text-muted-foreground mt-0.5">{{ submission.created_at }} · {{ submission.ip_address }}</p>
          </div>
          <button
            type="button"
            class="shrink-0 inline-flex items-center justify-center w-7 h-7 rounded-md text-muted-foreground hover:bg-destructive/10 hover:text-destructive transition-colors"
            title="Delete submission"
            @click.stop="confirmDelete(submission)"
          >
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
          </button>
        </div>

        <!-- Expanded data -->
        <div v-if="expanded.has(submission.id)" class="border-t border-border px-4 py-3">
          <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2">
            <div v-for="(value, key) in submission.data" :key="key" class="min-w-0">
              <dt class="text-xs font-medium text-muted-foreground capitalize">{{ String(key).replace(/_/g, ' ') }}</dt>
              <dd class="text-sm mt-0.5 break-words">{{ Array.isArray(value) ? value.join(', ') : value || '—' }}</dd>
            </div>
          </dl>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="submissions.last_page > 1" class="mt-4 flex justify-center gap-1">
      <a
        v-for="link in submissions.links"
        :key="link.label"
        :href="link.url ?? '#'"
        v-html="link.label"
        class="inline-flex items-center justify-center min-w-[2rem] h-8 px-2 rounded-md text-sm transition-colors"
        :class="link.active ? 'bg-primary text-primary-foreground font-medium' : link.url ? 'hover:bg-accent text-foreground' : 'text-muted-foreground cursor-default'"
      />
    </div>

    <!-- Delete modal -->
    <Transition name="fade">
      <div v-if="deleteTarget" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="deleteTarget = null" />
        <div class="relative bg-card border rounded-xl shadow-xl w-full max-w-sm p-6">
          <h3 class="font-semibold text-base mb-2">Delete submission?</h3>
          <p class="text-sm text-muted-foreground mb-5">This submission will be permanently deleted and cannot be recovered.</p>
          <div class="flex gap-3 justify-end">
            <button type="button" class="rounded-md border px-4 py-2 text-sm font-medium hover:bg-accent transition-colors" @click="deleteTarget = null">Cancel</button>
            <button type="button" class="rounded-md bg-destructive px-4 py-2 text-sm font-medium text-destructive-foreground hover:bg-destructive/90 transition-colors" @click="doDelete">Delete</button>
          </div>
        </div>
      </div>
    </Transition>
  </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  form:        { type: Object, required: true },
  submissions: { type: Object, required: true },
})

const expanded = reactive(new Set())
function toggleExpand(id) {
  expanded.has(id) ? expanded.delete(id) : expanded.add(id)
}

function submissionPreview(submission) {
  const vals = Object.values(submission.data ?? {}).filter(v => v && typeof v === 'string')
  return vals[0] ? (vals[0].length > 60 ? vals[0].slice(0, 60) + '…' : vals[0]) : `Submission #${submission.id}`
}

const deleteTarget = ref(null)
function confirmDelete(submission) { deleteTarget.value = submission }
function doDelete() {
  router.delete(route('forms.submissions.destroy', { form: props.form.id, submission: deleteTarget.value.id }))
  deleteTarget.value = null
}
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
