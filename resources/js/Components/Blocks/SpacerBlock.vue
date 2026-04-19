<script setup>
import { computed }         from 'vue'
import { resolveResponsive } from '@/lib/blockUtils.js'

const props = defineProps({ block: { type: Object, required: true } })

const spacerStyle = computed(() => {
  const h = props.block.data?.height
  // New format: CSS string values per breakpoint — use the 'default' value as inline style
  // (responsive CSS strings would need media queries; just use default for now)
  if (typeof h === 'string') return { height: h }
  if (typeof h === 'object' && h !== null) {
    const defaultVal = h.default
    if (typeof defaultVal === 'string') return { height: defaultVal }
  }
  return null
})

// Legacy format: Tailwind integer heights (h-N)
const spacerClass = computed(() => {
  if (spacerStyle.value) return null
  const h = props.block.data?.height ?? { default: 8 }
  return resolveResponsive(h, v => `h-${v}`)
})
</script>

<template>
  <div :class="spacerClass" :style="spacerStyle" aria-hidden="true" />
</template>
