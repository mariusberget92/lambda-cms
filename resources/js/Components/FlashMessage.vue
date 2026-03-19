<script setup>
import { usePage } from '@inertiajs/vue3'

const page = usePage()
</script>

<template>
  <Transition name="fade">
    <div
      v-if="page.props.flash?.status"
      class="mb-4 flex items-center gap-2 rounded-md bg-status-success-bg border border-status-success-border px-4 py-3 text-sm text-status-success-fg"
    >
      <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
      {{ page.props.flash.status }}
    </div>
  </Transition>
</template>
