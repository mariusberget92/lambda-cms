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
        class="accordion-btn"
        :class="[
          borderStyle === 'separated' ? 'accordion-btn--sep-top' : '',
          isOpen(idx) ? 'accordion-btn--open' : '',
        ]"
        :aria-expanded="isOpen(idx)"
        @click="toggle(idx)"
      >
        <span>{{ item.data?.title || `Item ${idx + 1}` }}</span>
        <ChevronDown
          class="accordion-chevron w-4 h-4 shrink-0 transition-transform duration-200"
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
          <div class="accordion-body" :class="borderStyle === 'bordered' ? 'accordion-body--divided' : ''">
            <BlockRenderer
              v-if="item.children?.length"
              :blocks="item.children"
              wrapper-class="space-y-3"
            />
            <p v-else class="accordion-empty">Empty item</p>
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

function isOpen(idx) { return openSet.value.has(idx) }

function toggle(idx) {
  const next = new Set(openSet.value)
  if (next.has(idx)) next.delete(idx)
  else next.add(idx)
  openSet.value = next
}

function onEnter(el) {
  el.style.height = '0'
  el.style.transition = 'height 0.2s ease'
  requestAnimationFrame(() => { el.style.height = el.scrollHeight + 'px' })
}
function onAfterEnter(el) { el.style.height = ''; el.style.transition = '' }
function onLeave(el) {
  el.style.height = el.scrollHeight + 'px'
  el.style.transition = 'height 0.2s ease'
  requestAnimationFrame(() => { el.style.height = '0' })
}

const wrapperClass = computed(() => {
  if (borderStyle.value === 'separated') return 'space-y-2'
  if (borderStyle.value === 'borderless') return 'accordion-borderless'
  return 'accordion-bordered'
})

const itemClass = computed(() => {
  if (borderStyle.value === 'separated') return 'accordion-item-sep'
  return ''
})
</script>

<style scoped>
.accordion-bordered {
  border: 1px solid var(--line-strong);
  border-radius: var(--blog-radius);
  overflow: hidden;
}
.accordion-bordered > * + * { border-top: 1px solid var(--line); }

.accordion-borderless > * + * { border-top: 1px solid transparent; }

.accordion-item-sep {
  border: 1px solid var(--line-strong);
  border-radius: var(--blog-radius);
  overflow: hidden;
}

.accordion-btn {
  display: flex;
  width: 100%;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  text-align: left;
  font-weight: 500;
  transition: background 150ms;
  background: transparent;
  color: var(--soft);
  border: none;
  cursor: pointer;
}
.accordion-btn:hover { background: var(--bg); }
.accordion-btn--open { color: var(--ink); }
.accordion-btn--sep-top { border-radius: var(--blog-radius) var(--blog-radius) 0 0; }

.accordion-chevron { color: var(--soft); }

.accordion-body { padding: 0.25rem 1rem 1rem; }
.accordion-body--divided { border-top: 1px solid var(--line); }

.accordion-empty { font-size: 0.875rem; color: var(--soft); font-style: italic; }
</style>
