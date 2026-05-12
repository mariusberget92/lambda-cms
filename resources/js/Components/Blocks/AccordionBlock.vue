<!-- resources/js/Components/Blocks/AccordionBlock.vue -->
<template>
  <div :class="wrapperClass">
    <div
      v-for="(item, idx) in block.children ?? []"
      :key="item.id"
      :class="itemClass"
    >
      <button
        type="button"
        :class="headerClass"
        :aria-expanded="isOpen(idx)"
        @click="toggle(idx)"
      >
        <span class="font-medium text-sm">{{ item.data?.title || `Item ${idx + 1}` }}</span>
        <ChevronDown
          class="w-4 h-4 text-muted-foreground transition-transform duration-200 shrink-0"
          :class="isOpen(idx) ? 'rotate-180' : ''"
        />
      </button>
      <div v-show="isOpen(idx)" class="px-4 pb-4 pt-1">
        <BlockRenderer :blocks="item.children ?? []" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { ChevronDown } from 'lucide-vue-next'
import BlockRenderer from '@/Components/BlockRenderer.vue'

const props = defineProps({ block: { type: Object, required: true } })

const openItems = ref(new Set())

const defaultState = computed(() => props.block.data?.defaultState ?? 'first-open')

;(function initOpenState() {
  const count = props.block.children?.length ?? 0
  if (defaultState.value === 'first-open' && count > 0) {
    openItems.value = new Set([0])
  } else if (defaultState.value === 'all-open') {
    openItems.value = new Set(Array.from({ length: count }, (_, i) => i))
  } else {
    openItems.value = new Set()
  }
})()

function isOpen(idx) { return openItems.value.has(idx) }

function toggle(idx) {
  const next = new Set(openItems.value)
  if (next.has(idx)) { next.delete(idx) } else { next.add(idx) }
  openItems.value = next
}

const borderStyle = computed(() => props.block.data?.borderStyle ?? 'bordered')

const wrapperClass = computed(() => {
  if (borderStyle.value === 'bordered')  return 'divide-y divide-border border border-border rounded-lg overflow-hidden'
  if (borderStyle.value === 'separated') return 'space-y-2'
  return 'divide-y divide-border'
})

const itemClass = computed(() => {
  if (borderStyle.value === 'separated') return 'border border-border rounded-lg overflow-hidden'
  return ''
})

const headerClass = 'w-full flex items-center justify-between px-4 py-3 text-left hover:bg-muted/50 transition-colors'
</script>
