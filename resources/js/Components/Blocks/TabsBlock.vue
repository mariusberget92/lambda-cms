<!-- resources/js/Components/Blocks/TabsBlock.vue -->
<template>
  <div>
    <!-- Tab bar -->
    <div :class="tabListClass">
      <button
        v-for="(item, i) in block.children ?? []"
        :key="item.id"
        type="button"
        :class="[tabBaseClass, activeIdx === i ? activeTabClass : inactiveTabClass]"
        @click="activeIdx = i"
      >
        {{ item.data?.label || `Tab ${i + 1}` }}
      </button>
    </div>

    <!-- Active panel -->
    <div v-if="(block.children ?? [])[activeIdx]" class="py-4">
      <BlockRenderer :blocks="(block.children ?? [])[activeIdx].children ?? []" />
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import BlockRenderer from '@/Components/BlockRenderer.vue'

const props = defineProps({ block: { type: Object, required: true } })

const activeIdx = ref(0)

const tabStyle  = computed(() => props.block.data?.tabStyle  ?? 'underline')
const alignment = computed(() => props.block.data?.alignment ?? 'left')

const ALIGN_MAP = { left: 'justify-start', center: 'justify-center', right: 'justify-end' }

const tabListClass = computed(() => {
  const base = 'flex border-b border-border overflow-x-auto'
  return `${base} ${ALIGN_MAP[alignment.value] ?? 'justify-start'}`
})

const tabBaseClass = 'px-4 py-2 text-sm font-medium transition-colors whitespace-nowrap focus:outline-none'

const activeTabClass = computed(() => {
  if (tabStyle.value === 'pills')   return 'bg-primary text-primary-foreground rounded-t'
  if (tabStyle.value === 'buttons') return 'bg-muted text-foreground border-b-2 border-primary'
  return 'text-primary border-b-2 border-primary -mb-px'
})

const inactiveTabClass = 'text-muted-foreground hover:text-foreground'
</script>
