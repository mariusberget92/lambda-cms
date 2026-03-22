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

const mode = computed(() => props.block.data?.mode ?? 'flex')

const containerClasses = computed(() => {
  const d = props.block.data ?? {}

  if (mode.value === 'grid') {
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

// When this container is flex or grid, the BlockRenderer wrapper must be layout-transparent
// ('contents') so each child block becomes a direct flex/grid item — not nested inside a
// 'space-y-4' div that collapses the whole row into a single column.
// In flex-row mode each item also gets 'flex-1 min-w-0' so siblings share space equally.
const isFlexRow = computed(() => {
  if (mode.value !== 'flex') return false
  const dir = props.block.data?.direction
  // direction can be a plain string or a responsive object; default is row
  const defaultDir = typeof dir === 'object' ? (dir?.default ?? 'row') : (dir ?? 'row')
  return defaultDir !== 'column'
})

const rendererWrapperClass = computed(() =>
  mode.value === 'flex' || mode.value === 'grid' ? 'contents' : 'space-y-4'
)

const rendererItemClass = computed(() =>
  isFlexRow.value ? 'flex-1 min-w-0' : ''
)
</script>

<template>
  <div :class="containerClasses">
    <BlockRenderer
      :blocks="block.children ?? []"
      :wrapper-class="rendererWrapperClass"
      :item-class="rendererItemClass"
    />
  </div>
</template>
