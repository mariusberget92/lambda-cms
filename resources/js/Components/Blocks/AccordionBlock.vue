<template>
  <div :class="wrapperClass">
    <div
      v-for="(item, idx) in items"
      :key="item.id"
      :class="itemClass(idx)"
    >
      <button
        type="button"
        :class="headerClass(idx)"
        @click="toggle(idx)"
      >
        <span class="font-medium text-left flex-1">{{ item.data?.title || `Item ${idx + 1}` }}</span>
        <ChevronDown
          class="w-4 h-4 shrink-0 transition-transform duration-200"
          :class="{ 'rotate-180': isOpen(idx) }"
        />
      </button>

      <div v-show="isOpen(idx)" class="px-4 py-3">
        <BlockRenderer :blocks="item.children ?? []" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { ChevronDown } from 'lucide-vue-next'
import BlockRenderer from '@/Components/BlockRenderer.vue'

const props = defineProps({ block: { type: Object, required: true } })

const items        = computed(() => props.block.children ?? [])
const defaultState = computed(() => props.block.data?.defaultState ?? 'first-open')
const borderStyle  = computed(() => props.block.data?.borderStyle  ?? 'bordered')
const multiExpand  = computed(() => defaultState.value === 'all-open')

const openSet = ref(new Set())  // used when multiExpand
const openIdx = ref(-1)         // used when exclusive

onMounted(() => {
  if (defaultState.value === 'all-open') {
    openSet.value = new Set(items.value.map((_, i) => i))
  } else if (defaultState.value === 'first-open' && items.value.length > 0) {
    openIdx.value = 0
  }
})

function isOpen(idx) {
  return multiExpand.value ? openSet.value.has(idx) : openIdx.value === idx
}

function toggle(idx) {
  if (multiExpand.value) {
    const next = new Set(openSet.value)
    next.has(idx) ? next.delete(idx) : next.add(idx)
    openSet.value = next
  } else {
    openIdx.value = openIdx.value === idx ? -1 : idx
  }
}

const wrapperClass = computed(() => {
  if (borderStyle.value === 'separated')  return 'space-y-2'
  if (borderStyle.value === 'borderless') return 'divide-y divide-gray-200'
  return 'border border-gray-200 rounded-lg divide-y divide-gray-200 overflow-hidden'
})

function itemClass(idx) {
  if (borderStyle.value === 'separated') return 'border border-gray-200 rounded-lg overflow-hidden'
  return ''
}

function headerClass(idx) {
  return [
    'flex items-center justify-between gap-3 w-full px-4 py-3 text-left transition-colors',
    'hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-primary',
    isOpen(idx) ? 'bg-gray-50' : 'bg-white',
  ].join(' ')
}
</script>
