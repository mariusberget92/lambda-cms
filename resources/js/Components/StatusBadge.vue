<!-- resources/js/Components/StatusBadge.vue -->
<template>
  <span
    class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium"
    :class="classes"
  >
    <span class="relative flex w-1.5 h-1.5 shrink-0">
      <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75" :class="dotClass" />
      <span class="relative inline-flex rounded-full w-1.5 h-1.5" :class="dotClass" />
    </span>
    <slot>{{ label }}</slot>
  </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  status: { type: String, required: true },
})

const MAP = {
  published: {
    classes:  'bg-status-success-bg text-status-success-fg',
    dotClass: 'bg-status-success-fg',
    label:    'Published',
  },
  draft: {
    classes:  'bg-status-warning-bg text-status-warning-fg',
    dotClass: 'bg-status-warning-fg',
    label:    'Draft',
  },
  scheduled: {
    classes:  'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300',
    dotClass: 'bg-indigo-500 dark:bg-indigo-400',
    label:    'Scheduled',
  },
  pending: {
    classes:  'bg-status-warning-bg text-status-warning-fg',
    dotClass: 'bg-status-warning-fg',
    label:    'Pending',
  },
  approved: {
    classes:  'bg-status-success-bg text-status-success-fg',
    dotClass: 'bg-status-success-fg',
    label:    'Approved',
  },
  rejected: {
    classes:  'bg-status-error-bg text-status-error-fg',
    dotClass: 'bg-status-error-fg',
    label:    'Rejected',
  },
  banned: {
    classes:  'bg-status-error-bg text-status-error-fg',
    dotClass: 'bg-status-error-fg',
    label:    'Banned',
  },
}

const entry   = computed(() => MAP[props.status] ?? MAP.draft)
const classes  = computed(() => entry.value.classes)
const dotClass = computed(() => entry.value.dotClass)
const label    = computed(() => entry.value.label)
</script>
