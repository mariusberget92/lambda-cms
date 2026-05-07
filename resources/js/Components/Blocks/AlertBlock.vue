<template>
  <div :style="alertStyle" style="display:flex;align-items:flex-start;gap:0.75rem;padding:1rem;border-radius:0.5rem;border-width:1px;border-style:solid;">
    <svg v-if="d.showIcon !== false" xmlns="http://www.w3.org/2000/svg" :style="{ color: colors.icon, flexShrink: 0, marginTop: '0.125rem' }" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <!-- info -->
      <template v-if="d.type === 'info' || !d.type">
        <circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>
      </template>
      <!-- success -->
      <template v-else-if="d.type === 'success'">
        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
      </template>
      <!-- warning -->
      <template v-else-if="d.type === 'warning'">
        <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
      </template>
      <!-- error -->
      <template v-else>
        <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
      </template>
    </svg>
    <div style="flex:1;min-width:0;">
      <p v-if="d.title" :style="{ color: colors.text, fontWeight: '600', marginBottom: d.message ? '0.25rem' : '0', fontSize: '0.9375rem' }">{{ d.title }}</p>
      <div v-if="d.message" class="prose prose-sm max-w-none" :style="{ color: colors.text }" v-html="d.message" />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({ block: { type: Object, required: true } })

const d = computed(() => props.block.data ?? {})

const TYPE_COLORS = {
  info:    { bg: '#e8f4fb', border: '#88c0d0', icon: '#4a8faa', text: '#2e3440' },
  success: { bg: '#edf3e8', border: '#a3be8c', icon: '#5c7f47', text: '#2e3440' },
  warning: { bg: '#fdf5df', border: '#ebcb8b', icon: '#b08020', text: '#2e3440' },
  error:   { bg: '#faeaeb', border: '#bf616a', icon: '#a3434c', text: '#2e3440' },
}

const colors = computed(() => TYPE_COLORS[d.value.type ?? 'info'])

const alertStyle = computed(() => ({
  backgroundColor: colors.value.bg,
  borderColor: colors.value.border,
}))
</script>
