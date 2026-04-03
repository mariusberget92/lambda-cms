<!-- resources/js/components/Blocks/PaginationBlock.vue -->
<template>
  <nav
    class="my-6 flex items-center gap-2"
    :class="{
      'justify-start':  block.data.alignment === 'left',
      'justify-center': block.data.alignment === 'center' || !block.data.alignment,
      'justify-end':    block.data.alignment === 'right',
    }"
    aria-label="Pagination"
  >
    <button
      :class="btnClass"
      disabled
    >
      {{ block.data.prevLabel || '← Previous' }}
    </button>
    <button
      :class="btnClass"
      disabled
    >
      {{ block.data.nextLabel || 'Next →' }}
    </button>
  </nav>
</template>
<script setup>
import { computed } from 'vue'

const props = defineProps({ block: { type: Object, required: true } })

const btnClass = computed(() => {
  const base = 'px-4 py-2 rounded text-sm font-medium transition-colors opacity-60 cursor-default'
  if (props.block.data.buttonStyle === 'solid') {
    return base + ' bg-primary text-primary-foreground'
  }
  return base + ' border border-border bg-background text-foreground'
})
</script>
