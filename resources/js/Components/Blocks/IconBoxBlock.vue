<template>
  <div :style="wrapStyle">
    <!-- Icon -->
    <div :style="iconWrapStyle">
      <Icon v-if="d.icon?.name" :icon="d.icon.name" :style="{ fontSize: d.icon.size ?? '2rem', color: d.icon.color ?? 'var(--primary)' }" />
      <svg v-else xmlns="http://www.w3.org/2000/svg" :style="{ width: d.icon?.size ?? '2rem', height: d.icon?.size ?? '2rem', color: 'var(--primary)' }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
    </div>

    <!-- Text -->
    <div :style="textWrapStyle">
      <h4 v-if="d.title" :style="{ fontWeight: '700', fontSize: '1rem', color: 'var(--foreground)', margin: '0 0 0.375rem' }">{{ d.title }}</h4>
      <p v-if="d.description" :style="{ fontSize: '0.875rem', color: 'var(--muted-foreground)', lineHeight: '1.6', margin: 0 }">{{ d.description }}</p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Icon } from '@iconify/vue'

const props = defineProps({ block: { type: Object, required: true } })

const d        = computed(() => props.block.data ?? {})
const layout   = computed(() => d.value.layout ?? 'vertical')
const align    = computed(() => d.value.alignment ?? 'center')
const iconStyle = computed(() => d.value.iconStyle ?? 'plain')

const wrapStyle = computed(() => {
  if (layout.value === 'horizontal') {
    return { display: 'flex', alignItems: 'flex-start', gap: '1rem', textAlign: 'left' }
  }
  const textAlign = align.value
  return { display: 'flex', flexDirection: 'column', alignItems: textAlign === 'center' ? 'center' : textAlign === 'right' ? 'flex-end' : 'flex-start', textAlign, gap: '0.875rem' }
})

const iconWrapStyle = computed(() => {
  const bg  = d.value.icon?.bgColor
  const pad = d.value.icon?.padding ?? '0.75rem'
  const base = { display: 'inline-flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }
  if (iconStyle.value === 'boxed')  return { ...base, backgroundColor: bg ?? 'color-mix(in srgb, var(--primary) 12%, transparent)', borderRadius: '0.5rem', padding: pad }
  if (iconStyle.value === 'circle') return { ...base, backgroundColor: bg ?? 'color-mix(in srgb, var(--primary) 12%, transparent)', borderRadius: '50%', padding: pad }
  return base
})

const textWrapStyle = computed(() => {
  if (layout.value === 'horizontal') return { flex: 1, minWidth: 0 }
  return {}
})
</script>
