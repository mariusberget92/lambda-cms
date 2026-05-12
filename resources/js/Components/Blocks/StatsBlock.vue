<!-- resources/js/Components/Blocks/StatsBlock.vue -->
<template>
  <div :class="wrapperClass">
    <div
      v-for="(item, i) in block.data?.items ?? []"
      :key="i"
      :class="itemClass"
    >
      <p class="font-bold leading-none" :style="{ fontSize: block.data?.valueSize ?? '2.5rem' }">
        <span v-if="item.prefix" class="text-primary">{{ item.prefix }}</span>{{ item.value }}<span v-if="item.suffix" class="text-primary">{{ item.suffix }}</span>
      </p>
      <p v-if="item.label" class="text-sm text-muted-foreground mt-1">{{ item.label }}</p>
      <p v-if="item.description" class="text-xs text-muted-foreground/70 mt-0.5">{{ item.description }}</p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({ block: { type: Object, required: true } })

const layout = computed(() => props.block.data?.layout ?? 'row')
const align  = computed(() => props.block.data?.align  ?? 'center')

const ALIGN_MAP = { left: 'text-left', center: 'text-center', right: 'text-right' }

const wrapperClass = computed(() => {
  const a = ALIGN_MAP[align.value] ?? 'text-center'
  if (layout.value === 'grid') {
    const cols = props.block.data?.columns ?? 3
    return `grid grid-cols-2 md:grid-cols-${cols} gap-6 ${a}`
  }
  return `flex flex-wrap gap-6 justify-${align.value === 'center' ? 'center' : align.value === 'right' ? 'end' : 'start'} ${a}`
})

const itemClass = computed(() => layout.value === 'grid' ? '' : 'flex-1 min-w-[8rem]')
</script>
