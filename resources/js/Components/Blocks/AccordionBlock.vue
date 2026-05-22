<!-- resources/js/Components/Blocks/AccordionBlock.vue -->
<template>
  <div :class="wrapperClass">
    <div
      v-for="(item, idx) in block.children ?? []"
      :key="item.id"
      :class="itemClass"
    >
      <!-- Item header -->
      <button
        type="button"
        :class="[
          'flex w-full items-center justify-between gap-3 px-4 py-3 text-left font-medium transition-colors',
          'hover:bg-muted/40',
          isOpen(idx) ? 'text-foreground' : 'text-foreground/80',
          borderStyle === 'separated' ? 'rounded-t-md' : '',
        ]"
        :aria-expanded="isOpen(idx)"
        @click="toggle(idx)"
      >
        <span>{{ item.data?.title || `Item ${idx + 1}` }}</span>
        <ChevronDown
          class="w-4 h-4 shrink-0 text-muted-foreground transition-transform duration-200"
          :class="isOpen(idx) ? 'rotate-180' : ''"
        />
      </button>

      <!-- Item body -->
      <Transition
        @enter="onEnter"
        @after-enter="onAfterEnter"
        @leave="onLeave"
      >
        <div v-if="isOpen(idx)" class="overflow-hidden">
          <div :class="['px-4 pb-4 pt-1', borderStyle === 'bordered' ? 'border-t border-border' : '']">
            <BlockRenderer
              v-if="item.children?.length"
              :blocks="item.children"
              wrapper-class="space-y-3"
            />
            <p v-else class="text-sm text-muted-foreground italic">Empty item</p>
          </div>
        </div>
      </Transition>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { ChevronDown } from '@lucide/vue'
import BlockRenderer from '@/Components/BlockRenderer.vue'

const props = defineProps({ block: { type: Object, required: true } })

const defaultState = computed(() => props.block.data?.defaultState ?? 'first-open')
const borderStyle  = computed(() => props.block.data?.borderStyle  ?? 'bordered')

const openSet = ref(new Set())

onMounted(() => {
  const items = props.block.children ?? []
  if (defaultState.value === 'first-open' && items.length) {
    openSet.value = new Set([0])
  } else if (defaultState.value === 'all-open') {
    openSet.value = new Set(items.map((_, i) => i))
  }
})

function isOpen(idx) {
  return openSet.value.has(idx)
}

function toggle(idx) {
  const next = new Set(openSet.value)
  if (next.has(idx)) {
    next.delete(idx)
  } else {
    next.add(idx)
  }
  openSet.value = next
}

// CSS transition helpers (height: 0 → auto)
function onEnter(el) {
  el.style.height = '0'
  el.style.transition = 'height 0.2s ease'
  requestAnimationFrame(() => { el.style.height = el.scrollHeight + 'px' })
}
function onAfterEnter(el) {
  el.style.height = ''
  el.style.transition = ''
}
function onLeave(el) {
  el.style.height = el.scrollHeight + 'px'
  el.style.transition = 'height 0.2s ease'
  requestAnimationFrame(() => { el.style.height = '0' })
}

const wrapperClass = computed(() => {
  if (borderStyle.value === 'separated') return 'space-y-2'
  if (borderStyle.value === 'borderless') return 'divide-y divide-transparent'
  return 'divide-y divide-border border border-border rounded-md overflow-hidden'
})

const itemClass = computed(() => {
  if (borderStyle.value === 'separated') return 'border border-border rounded-md overflow-hidden'
  return ''
})
</script>
