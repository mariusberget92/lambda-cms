<template>
  <div>
    <!-- Tab bar -->
    <div :class="tabBarClass">
      <button
        v-for="(item, idx) in items"
        :key="item.id"
        type="button"
        :class="tabButtonClass(idx)"
        @click="activeIdx = idx"
      >
        {{ item.data?.label || `Tab ${idx + 1}` }}
      </button>
    </div>

    <!-- Panels — rendered but hidden to avoid remounting children on switch -->
    <div
      v-for="(item, idx) in items"
      :key="item.id"
      v-show="activeIdx === idx"
      class="pt-4"
    >
      <BlockRenderer :blocks="item.children ?? []" />
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import BlockRenderer from '@/Components/BlockRenderer.vue'

const props = defineProps({ block: { type: Object, required: true } })

const items     = computed(() => props.block.children ?? [])
const tabStyle  = computed(() => props.block.data?.tabStyle  ?? 'underline')
const alignment = computed(() => props.block.data?.alignment ?? 'left')
const activeIdx = ref(0)

const alignClass = computed(() => ({
  center: 'justify-center',
  right:  'justify-end',
})[alignment.value] ?? 'justify-start')

const tabBarClass = computed(() => {
  const shared = ['flex flex-wrap', alignClass.value]
  if (tabStyle.value === 'underline') return [...shared, 'border-b border-gray-200 gap-0'].join(' ')
  if (tabStyle.value === 'pills')     return [...shared, 'gap-1 mb-4'].join(' ')
  return [...shared, 'mb-4'].join(' ')   // buttons
})

function tabButtonClass(idx) {
  const active = activeIdx.value === idx

  if (tabStyle.value === 'underline') {
    return [
      'px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors whitespace-nowrap',
      active
        ? 'border-primary text-primary'
        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
    ].join(' ')
  }

  if (tabStyle.value === 'pills') {
    return [
      'px-4 py-1.5 text-sm font-medium rounded-full transition-colors whitespace-nowrap',
      active
        ? 'bg-primary text-primary-foreground'
        : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100',
    ].join(' ')
  }

  // buttons — grouped segmented style
  const isFirst = idx === 0
  const isLast  = idx === items.value.length - 1
  return [
    'px-4 py-2 text-sm font-medium border transition-colors whitespace-nowrap',
    isFirst ? 'rounded-l-md' : '-ml-px',
    isLast  ? 'rounded-r-md' : '',
    active
      ? 'bg-primary text-primary-foreground border-primary z-10 relative'
      : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50',
  ].filter(Boolean).join(' ')
}
</script>
