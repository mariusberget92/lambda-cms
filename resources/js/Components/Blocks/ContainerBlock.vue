<script setup>
import { computed }         from 'vue'
import BlockRenderer         from '@/Components/BlockRenderer.vue'
import { resolveResponsive } from '@/lib/blockUtils.js'

const props = defineProps({ block: { type: Object, required: true } })

const JUSTIFY_MAP   = { start: 'justify-start', center: 'justify-center', end: 'justify-end', between: 'justify-between', around: 'justify-around' }
const ALIGN_MAP     = { start: 'items-start',   center: 'items-center',   end: 'items-end',   stretch: 'items-stretch' }
const MAX_WIDTH_MAP = { full: 'max-w-full', prose: 'max-w-prose', sm: 'max-w-sm', md: 'max-w-md', lg: 'max-w-lg', xl: 'max-w-xl', '2xl': 'max-w-2xl' }
const GAP_MAP     = Object.fromEntries(Array.from({length: 17}, (_, i) => [i, `gap-${i}`]))
const PADDING_MAP = Object.fromEntries(Array.from({length: 17}, (_, i) => [i, `p-${i}`]))

const containerClasses = computed(() => {
  const d = props.block.data ?? {}
  const mode = d.mode ?? 'flex'

  if (mode === 'grid') {
    return [
      'grid',
      resolveResponsive(d.columns ?? { default: 2 }, v => `grid-cols-${v}`),
      GAP_MAP[d.gap]             ?? 'gap-4',
      MAX_WIDTH_MAP[d.maxWidth]  ?? 'max-w-full',
      PADDING_MAP[d.padding]     ?? 'p-4',
    ].filter(Boolean).join(' ')
  }

  // flex mode (default)
  return [
    'flex',
    resolveResponsive(d.direction ?? 'row', v => v === 'column' ? 'flex-col' : 'flex-row'),
    d.wrap ? 'flex-wrap' : 'flex-nowrap',
    GAP_MAP[d.gap]             ?? 'gap-4',
    JUSTIFY_MAP[d.justify]     ?? 'justify-start',
    ALIGN_MAP[d.align]         ?? 'items-start',
    MAX_WIDTH_MAP[d.maxWidth]  ?? 'max-w-full',
    PADDING_MAP[d.padding]     ?? 'p-4',
  ].filter(Boolean).join(' ')
})
</script>

<template>
  <div :class="containerClasses">
    <BlockRenderer :blocks="block.children ?? []" />
  </div>
</template>
