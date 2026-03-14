<!-- resources/js/Components/BlockEditor/BlockEditor.vue -->
<template>
  <div class="flex border rounded-lg overflow-hidden bg-background" style="min-height: 500px">
    <!-- Left panel: block list -->
    <BlockList
      :blocks="localBlocks"
      :selected-id="selectedBlockId"
      :is-admin="isAdmin"
      @select="selectBlock"
      @add="addBlock"
      @remove="removeBlock"
      @reorder="onReorder"
    />

    <!-- Centre panel: live preview -->
    <BlockPreview :blocks="localBlocks" />

    <!-- Right panel: settings -->
    <BlockSettings
      :block="selectedBlock"
      :is-admin="isAdmin"
      @update="updateBlock"
    />
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import BlockList     from './BlockList.vue'
import BlockPreview  from './BlockPreview.vue'
import BlockSettings from './BlockSettings.vue'

const props = defineProps({
  modelValue: { type: Array, default: () => [] },
  isAdmin:    { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue'])

// ── Internal state ────────────────────────────────────────────────────────────

const localBlocks     = ref([...(props.modelValue ?? [])])
const selectedBlockId = ref(null)

const selectedBlock = computed(() =>
  localBlocks.value.find(b => b.id === selectedBlockId.value) ?? null
)

// ── Sync: parent → local (e.g. tab switch clears blocks) ─────────────────────

watch(
  () => props.modelValue,
  (newVal) => {
    localBlocks.value = [...(newVal ?? [])]
    if (!localBlocks.value.find(b => b.id === selectedBlockId.value)) {
      selectedBlockId.value = null
    }
  },
  { deep: true }
)

// ── Default data per block type ───────────────────────────────────────────────

function defaultData(type) {
  const defaults = {
    paragraph: { content: '' },
    heading:   { level: 2, text: '' },
    image:     { media_id: null, url: '', caption: '', alt: '' },
    quote:     { text: '', attribution: '' },
    code:      { code: '', language: 'javascript' },
    gallery:   { items: [] },
    video:     { url: '', caption: '' },
    divider:   { style: 'line' },
    cta:       { headline: '', text: '', button_label: '', button_url: '' },
    html:      { content: '' },
  }
  return defaults[type] ?? {}
}

// ── Mutations (each immediately emits up) ─────────────────────────────────────

function addBlock(type) {
  const block = { id: generateId(), type, data: defaultData(type) }
  localBlocks.value = [...localBlocks.value, block]
  selectedBlockId.value = block.id
  emit('update:modelValue', localBlocks.value)
}

function removeBlock(id) {
  const block = localBlocks.value.find(b => b.id === id)
  if (block) {
    const hasContent = Object.values(block.data ?? {}).some(v =>
      v !== null && v !== '' && !(Array.isArray(v) && v.length === 0)
    )
    if (hasContent && !confirm('Remove this block? Its content will be lost.')) return
  }
  localBlocks.value = localBlocks.value.filter(b => b.id !== id)
  if (selectedBlockId.value === id) selectedBlockId.value = null
  emit('update:modelValue', localBlocks.value)
}

function selectBlock(id) {
  selectedBlockId.value = id
}

function onReorder(newList) {
  localBlocks.value = newList
  emit('update:modelValue', localBlocks.value)
}

function updateBlock({ id, data }) {
  localBlocks.value = localBlocks.value.map(b =>
    b.id === id ? { ...b, data: { ...b.data, ...data } } : b
  )
  emit('update:modelValue', localBlocks.value)
}

function generateId() {
  if (typeof crypto !== 'undefined' && crypto.randomUUID) {
    return crypto.randomUUID()
  }
  return Math.random().toString(36).slice(2) + Date.now().toString(36)
}
</script>
