<template>
  <div>
    <!-- Tab bar -->
    <div
      class="tabs-bar flex flex-wrap gap-1"
      :class="[
        alignment === 'center' ? 'justify-center' : alignment === 'right' ? 'justify-end' : 'justify-start',
        tabStyle === 'underline' ? 'tabs-bar--underline' : '',
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
      <p v-else class="tabs-empty">Empty tab</p>
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
  const base   = ['tab-btn', 'px-4', 'py-2', 'text-sm', 'font-medium', 'transition-colors', 'focus:outline-none']

  if (tabStyle.value === 'underline') {
    return [...base, '-mb-px', 'tab-btn--underline', active ? 'tab-btn--underline-active' : 'tab-btn--underline-inactive']
  }
  if (tabStyle.value === 'pills') {
    return [...base, 'tab-btn--pill', active ? 'tab-btn--pill-active' : 'tab-btn--pill-inactive']
  }
  return [...base, 'tab-btn--button', active ? 'tab-btn--button-active' : 'tab-btn--button-inactive']
}
</script>

<style scoped>
.tabs-bar--underline { border-bottom: 1px solid var(--line-strong); }
.tabs-empty { font-size: 0.875rem; color: var(--soft); font-style: italic; }

.tab-btn { background: transparent; border: none; cursor: pointer; }

/* Underline style */
.tab-btn--underline { border-bottom: 2px solid transparent; }
.tab-btn--underline-active  { border-bottom-color: var(--accent); color: var(--accent); }
.tab-btn--underline-inactive { color: var(--soft); }
.tab-btn--underline-inactive:hover { color: var(--ink); border-bottom-color: var(--line-strong); }

/* Pills style */
.tab-btn--pill { border-radius: 9999px; }
.tab-btn--pill-active   { background: var(--accent); color: var(--accent-ink); }
.tab-btn--pill-inactive { color: var(--soft); }
.tab-btn--pill-inactive:hover { color: var(--ink); background: var(--bg); }

/* Button style */
.tab-btn--button { border-radius: var(--blog-radius); border: 1px solid transparent; }
.tab-btn--button-active   { background: var(--accent); border-color: var(--accent); color: var(--accent-ink); }
.tab-btn--button-inactive { border-color: var(--line-strong); color: var(--soft); }
.tab-btn--button-inactive:hover { color: var(--ink); background: var(--bg); }
</style>
