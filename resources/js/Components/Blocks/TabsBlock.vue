<!-- resources/js/Components/Blocks/TabsBlock.vue -->
<template>
  <div>
    <!-- Tab bar -->
    <div
      :class="[
        'flex flex-wrap gap-1',
        alignment === 'center' ? 'justify-center' : alignment === 'right' ? 'justify-end' : 'justify-start',
        tabStyle === 'underline' ? 'border-b border-border' : '',
      ]"
    >
      <button
        v-for="(item, i) in block.children ?? []"
        :key="item.id"
        type="button"
        :class="tabButtonClass(i)"
        @click="activeIdx = i"
      >
        {{ item.data?.label || `Tab ${i + 1}` }}
      </button>
    </div>

    <!-- Active tab content -->
    <div
      v-for="(item, i) in block.children ?? []"
      :key="item.id"
      v-show="activeIdx === i"
      class="pt-4"
    >
      <BlockRenderer
        v-if="item.children?.length"
        :blocks="item.children"
        wrapper-class="space-y-4"
      />
      <p v-else class="text-sm text-muted-foreground italic">Empty tab</p>
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

function tabButtonClass(i) {
  const active = activeIdx.value === i
  const base   = 'px-4 py-2 text-sm font-medium transition-colors focus:outline-none'

  if (tabStyle.value === 'underline') {
    return [
      base,
      '-mb-px border-b-2',
      active
        ? 'border-primary text-primary'
        : 'border-transparent text-muted-foreground hover:text-foreground hover:border-border',
    ]
  }
  if (tabStyle.value === 'pills') {
    return [
      base,
      'rounded-full',
      active
        ? 'bg-primary text-primary-foreground'
        : 'text-muted-foreground hover:text-foreground hover:bg-muted',
    ]
  }
  // buttons
  return [
    base,
    'rounded-md border',
    active
      ? 'bg-primary border-primary text-primary-foreground'
      : 'border-border text-muted-foreground hover:text-foreground hover:bg-muted',
  ]
}
</script>
