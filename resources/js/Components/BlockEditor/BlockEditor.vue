<!-- resources/js/Components/BlockEditor/BlockEditor.vue -->
<template>
  <div class="flex border rounded-xl overflow-hidden bg-background" style="min-height: 500px; max-height: calc(100vh - 220px)">
    <!-- Left: block type palette -->
    <BlockTypePanel :is-admin="isAdmin" />

    <!-- Centre: canvas drop zone + reorder -->
    <BlockCanvas
      :blocks="localBlocks"
      :selected-id="selectedBlockId"
      @select="selectBlock"
      @reorder="onReorder"
      @update-children="onUpdateChildren"
    />

    <!-- Right: layers list + settings -->
    <BlockLayers
      :blocks="localBlocks"
      :selected-id="selectedBlockId"
      :selected-block="selectedBlock"
      :is-admin="isAdmin"
      :meta="meta"
      :loop-fields="loopFields"
      @select="selectBlock"
      @remove="removeBlock"
      @update="updateBlock"
      @reorder="onReorder"
    />
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import BlockTypePanel from './BlockTypePanel.vue'
import BlockCanvas    from './BlockCanvas.vue'
import BlockLayers    from './BlockLayers.vue'
import { SOURCE_FIELDS } from '@/lib/loopSources.js'

const props = defineProps({
  modelValue: { type: Array,   default: () => [] },
  isAdmin:    { type: Boolean, default: false },
  meta:       { type: Object,  default: () => ({}) },
})

const emit = defineEmits(['update:modelValue'])

// ── Internal state ────────────────────────────────────────────────────────────

const localBlocks     = ref([...(props.modelValue ?? [])])
const selectedBlockId = ref(null)

// ── Recursive helpers ─────────────────────────────────────────────────────────

function hasChildren(block) {
  return block.type === 'container' || block.type === 'section' || block.type === 'loop'
}

function findBlock(blocks, id) {
  if (!id) return null
  for (const b of blocks) {
    if (b.id === id) return b
    if (hasChildren(b) && b.children?.length) {
      const found = findBlock(b.children, id)
      if (found) return found
    }
  }
  return null
}

// Returns a new array with the target block updated (immutable, recursive)
function updateBlockInList(blocks, id, data, attrs) {
  return blocks.map(b => {
    if (b.id === id) {
      return {
        ...b,
        ...attrs,
        ...(data !== undefined ? { data: { ...b.data, ...data } } : {}),
      }
    }
    if (hasChildren(b) && b.children?.length) {
      return { ...b, children: updateBlockInList(b.children, id, data, attrs) }
    }
    return b
  })
}

// Returns a new array with the target block removed (immutable, recursive)
function removeFromList(blocks, id) {
  return blocks
    .filter(b => b.id !== id)
    .map(b => {
      if (hasChildren(b) && b.children?.length) {
        return { ...b, children: removeFromList(b.children, id) }
      }
      return b
    })
}

// ── Recursive loop ancestor finder ───────────────────────────────────────────

/**
 * Returns the nearest loop block ancestor of the target block id.
 * Returns null if the block is not inside a loop.
 * Returns undefined if the block wasn't found in this subtree (internal use).
 */
function findLoopAncestor(blocks, targetId, currentLoop = null) {
  for (const b of blocks) {
    if (b.id === targetId) return currentLoop
    const nextLoop = b.type === 'loop' ? b : currentLoop
    if (hasChildren(b) && b.children?.length) {
      const found = findLoopAncestor(b.children, targetId, nextLoop)
      if (found !== undefined) return found
    }
  }
  return undefined
}

// ── Computed ──────────────────────────────────────────────────────────────────

const selectedBlock = computed(() =>
  findBlock(localBlocks.value, selectedBlockId.value)
)

// The nearest loop block that is an ancestor of the selected block (or null)
const loopAncestor = computed(() => {
  if (!selectedBlockId.value) return null
  const result = findLoopAncestor(localBlocks.value, selectedBlockId.value)
  return result ?? null
})

// Field names exposed by the loop ancestor's source — used to populate binding dropdowns
const loopFields = computed(() => {
  if (!loopAncestor.value) return []
  return SOURCE_FIELDS[loopAncestor.value.data?.source ?? 'posts'] ?? []
})

// ── Sync: parent → local (skip our own echo-back) ────────────────────────────

watch(
  () => props.modelValue,
  (newVal) => {
    if (newVal === localBlocks.value) return
    localBlocks.value = [...(newVal ?? [])]
    if (!findBlock(localBlocks.value, selectedBlockId.value)) {
      selectedBlockId.value = null
    }
  }
)

// ── Mutations ─────────────────────────────────────────────────────────────────

function selectBlock(id) {
  selectedBlockId.value = id
}

function onReorder(newList) {
  localBlocks.value = newList
  emit('update:modelValue', localBlocks.value)
}

function removeBlock(id) {
  const block = findBlock(localBlocks.value, id)
  if (block) {
    const hasContent = Object.values(block.data ?? {}).some(v =>
      v !== null && v !== '' && !(Array.isArray(v) && v.length === 0)
    )
    const hasChildBlocks = (block.children?.length ?? 0) > 0
    if ((hasContent || hasChildBlocks) && !confirm('Remove this block? Its content will be lost.')) return
  }
  localBlocks.value = removeFromList(localBlocks.value, id)
  if (selectedBlockId.value === id) selectedBlockId.value = null
  emit('update:modelValue', localBlocks.value)
}

// data merges into block.data; remaining attrs (customId, fontFamily, children, etc.) go top-level
function updateBlock({ id, data, ...attrs }) {
  localBlocks.value = updateBlockInList(localBlocks.value, id, data, attrs)
  emit('update:modelValue', localBlocks.value)
}

function onUpdateChildren({ id, children }) {
  localBlocks.value = updateBlockInList(localBlocks.value, id, undefined, { children })
  emit('update:modelValue', localBlocks.value)
}
</script>
