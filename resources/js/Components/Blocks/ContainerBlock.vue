<template>
  <div :class="containerClasses">
    <BlockRenderer :blocks="block.children ?? []" />
  </div>
</template>

<script setup>
import { computed } from 'vue'
import BlockRenderer from '@/Components/BlockRenderer.vue'

const props = defineProps({ block: { type: Object, required: true } })

const DIRECTION_MAP = { row: 'flex-row', column: 'flex-col' }
const JUSTIFY_MAP   = { start: 'justify-start', center: 'justify-center', end: 'justify-end', between: 'justify-between', around: 'justify-around' }
const ALIGN_MAP     = { start: 'items-start', center: 'items-center', end: 'items-end', stretch: 'items-stretch' }
const MAX_WIDTH_MAP = { full: 'max-w-full', prose: 'max-w-prose', sm: 'max-w-sm', md: 'max-w-md', lg: 'max-w-lg', xl: 'max-w-xl', '2xl': 'max-w-2xl' }
const GAP_MAP       = { 0:'gap-0',1:'gap-1',2:'gap-2',3:'gap-3',4:'gap-4',5:'gap-5',6:'gap-6',7:'gap-7',8:'gap-8',9:'gap-9',10:'gap-10',11:'gap-11',12:'gap-12',13:'gap-13',14:'gap-14',15:'gap-15',16:'gap-16' }
const PADDING_MAP   = { 0:'p-0',1:'p-1',2:'p-2',3:'p-3',4:'p-4',5:'p-5',6:'p-6',7:'p-7',8:'p-8',9:'p-9',10:'p-10',11:'p-11',12:'p-12',13:'p-13',14:'p-14',15:'p-15',16:'p-16' }

const containerClasses = computed(() => {
  const d = props.block.data ?? {}
  return [
    'flex',
    DIRECTION_MAP[d.direction]  ?? 'flex-row',
    d.wrap ? 'flex-wrap' : 'flex-nowrap',
    GAP_MAP[d.gap]              ?? 'gap-4',
    JUSTIFY_MAP[d.justify]      ?? 'justify-start',
    ALIGN_MAP[d.align]          ?? 'items-start',
    MAX_WIDTH_MAP[d.maxWidth]   ?? 'max-w-full',
    PADDING_MAP[d.padding]      ?? 'p-4',
  ].join(' ')
})
</script>
