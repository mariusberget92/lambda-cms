<template>
  <div class="rounded-lg border bg-card p-6">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-sm font-semibold">Activity Log</h3>
      <button
        type="button"
        class="inline-flex items-center gap-1.5 rounded-md border px-3 py-1.5 text-xs font-medium hover:bg-accent transition-colors"
        @click="showForm = !showForm"
      >
        <Plus class="w-3.5 h-3.5" />
        Log activity
      </button>
    </div>

    <!-- Add activity form -->
    <div v-if="showForm" class="mb-4 rounded-md border bg-muted/20 p-4 space-y-3">
      <div class="grid grid-cols-2 gap-3">
        <div class="space-y-1">
          <label class="text-xs font-medium">Type</label>
          <select
            v-model="form.type"
            class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
          >
            <option v-for="t in types" :key="t" :value="t">{{ capitalize(t) }}</option>
          </select>
        </div>
        <div class="space-y-1">
          <label class="text-xs font-medium">Date</label>
          <input
            v-model="form.occurred_at"
            type="datetime-local"
            class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
          />
        </div>
      </div>
      <div class="space-y-1">
        <label class="text-xs font-medium">Description</label>
        <textarea
          v-model="form.description"
          rows="2"
          placeholder="What happened?"
          class="w-full rounded-md border bg-background px-3 py-2 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-ring"
        />
      </div>
      <div class="flex gap-2">
        <button
          type="button"
          class="rounded-md bg-primary px-3 py-1.5 text-xs font-medium text-primary-foreground hover:bg-[var(--primary-hover)] disabled:opacity-50"
          :disabled="!form.description.trim() || submitting"
          @click="submit"
        >{{ submitting ? 'Saving...' : 'Save' }}</button>
        <button
          type="button"
          class="rounded-md border px-3 py-1.5 text-xs font-medium hover:bg-accent"
          @click="showForm = false"
        >Cancel</button>
      </div>
    </div>

    <!-- Timeline -->
    <div v-if="activities.length === 0 && !showForm" class="py-6 text-center text-sm text-muted-foreground">
      No activities yet.
    </div>
    <div v-else class="space-y-0">
      <div
        v-for="activity in activities"
        :key="activity.id"
        class="relative flex gap-3 pb-4 last:pb-0"
      >
        <!-- Timeline line -->
        <div class="flex flex-col items-center">
          <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0" :class="iconBg(activity.type)">
            <component :is="iconFor(activity.type)" class="w-3.5 h-3.5" />
          </div>
          <div class="w-px flex-1 bg-border mt-1" />
        </div>

        <!-- Content -->
        <div class="flex-1 min-w-0 pt-1">
          <div class="flex items-center gap-2 flex-wrap">
            <span class="text-xs font-medium px-2 py-0.5 rounded-full" :class="typeBadge(activity.type)">{{ capitalize(activity.type) }}</span>
            <span class="text-xs text-muted-foreground">{{ activity.creator_name }}</span>
            <span class="text-xs text-muted-foreground">· {{ formatDateTime(activity.occurred_at) }}</span>
          </div>
          <p class="text-sm mt-1 text-foreground whitespace-pre-wrap">{{ activity.description }}</p>
        </div>

        <!-- Delete button -->
        <button
          type="button"
          class="shrink-0 mt-1 opacity-0 group-hover/activity:opacity-100 hover:!opacity-100 w-7 h-7 rounded-md flex items-center justify-center text-muted-foreground hover:bg-destructive/10 hover:text-destructive transition-all"
          title="Delete"
          @click="$emit('delete', activity.id)"
        >
          <Trash2 class="w-3.5 h-3.5" />
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { Plus, Trash2, StickyNote, Phone, Mail, CalendarDays, ListChecks } from '@lucide/vue'
import { formatDateTime } from '@/lib/utils.js'

const props = defineProps({
  activities: { type: Array, default: () => [] },
  subjectType: { type: String, required: true },
  subjectId: { type: Number, required: true },
})

defineEmits(['delete'])

const types = ['note', 'call', 'email', 'meeting', 'task']

const showForm = ref(false)
const submitting = ref(false)
const form = ref({
  type: 'note',
  description: '',
  occurred_at: new Date().toISOString().slice(0, 16),
})

function capitalize(str) {
  return str.charAt(0).toUpperCase() + str.slice(1)
}

function submit() {
  submitting.value = true
  router.post(route('activities.store'), {
    subject_type: props.subjectType,
    subject_id: props.subjectId,
    ...form.value,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      form.value = { type: 'note', description: '', occurred_at: new Date().toISOString().slice(0, 16) }
      showForm.value = false
    },
    onFinish: () => { submitting.value = false },
  })
}

const iconMap = { note: StickyNote, call: Phone, email: Mail, meeting: CalendarDays, task: ListChecks }
function iconFor(type) { return iconMap[type] || StickyNote }

function iconBg(type) {
  const map = {
    note: 'bg-blue-500/10 text-blue-500',
    call: 'bg-green-500/10 text-green-500',
    email: 'bg-purple-500/10 text-purple-500',
    meeting: 'bg-orange-500/10 text-orange-500',
    task: 'bg-yellow-500/10 text-yellow-500',
  }
  return map[type] || 'bg-muted text-muted-foreground'
}

function typeBadge(type) {
  const map = {
    note: 'bg-blue-500/10 text-blue-600',
    call: 'bg-green-500/10 text-green-600',
    email: 'bg-purple-500/10 text-purple-600',
    meeting: 'bg-orange-500/10 text-orange-600',
    task: 'bg-yellow-500/10 text-yellow-600',
  }
  return map[type] || 'bg-muted text-muted-foreground'
}
</script>
