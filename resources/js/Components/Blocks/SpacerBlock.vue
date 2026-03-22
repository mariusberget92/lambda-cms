<script setup>
import { computed }         from 'vue'
import { resolveResponsive } from '@/lib/blockUtils.js'

const props = defineProps({ block: { type: Object, required: true } })

const spacerClass = computed(() => {
  const h = props.block.data?.height ?? { default: 8 }
  return resolveResponsive(h, v => `h-${v}`)
})
</script>

<template>
  <div :class="spacerClass" aria-hidden="true" />
</template>
