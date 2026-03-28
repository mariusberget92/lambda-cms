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
      :available-fields="availableFields"
      :clipboard="clipboard"
      :can-undo="canUndo"
      :can-redo="canRedo"
      @select="selectBlock"
      @remove="removeBlock"
      @update="updateBlock"
      @reorder="onReorder"
      @update-children="onUpdateChildren"
      @duplicate="duplicateBlock"
      @copy="copyBlock"
      @paste="pasteBlock"
      @undo="undo"
      @redo="redo"
    />

    <!-- Remove block confirmation modal -->
    <Transition name="fade">
      <div v-if="removeTarget" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="cancelRemove" />
        <div class="relative bg-card border rounded-xl shadow-xl w-full max-w-sm p-6">
          <h3 class="font-semibold text-base mb-2">Remove block?</h3>
          <p class="text-sm text-muted-foreground mb-5">
            This block and its content will be permanently removed.
          </p>
          <div class="flex gap-3 justify-end">
            <button
              type="button"
              @click="cancelRemove"
              class="rounded-md border px-4 py-2 text-sm font-medium hover:bg-accent transition-colors"
            >
              Cancel
            </button>
            <button
              type="button"
              @click="confirmRemove"
              class="rounded-md bg-destructive px-4 py-2 text-sm font-medium text-destructive-foreground hover:bg-destructive/90 transition-colors"
            >
              Remove
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import BlockTypePanel from './BlockTypePanel.vue'
import BlockCanvas    from './BlockCanvas.vue'
import BlockLayers    from './BlockLayers.vue'
import { SOURCE_FIELDS, SOURCES } from '@/lib/loopSources.js'

const props = defineProps({
  modelValue:    { type: Array,   default: () => [] },
  isAdmin:       { type: Boolean, default: false },
  meta:          { type: Object,  default: () => ({}) },
  contextFields: { type: Array,   default: () => [] },
})

const emit = defineEmits(['update:modelValue'])

const CHILD_CAPABLE = new Set(['container', 'section', 'loop', 'archive-loop'])

// ── Internal state ────────────────────────────────────────────────────────────

const localBlocks     = ref([...(props.modelValue ?? [])])
const selectedBlockId = ref(null)
const removeTarget    = ref(null)

// ── Undo / Redo ───────────────────────────────────────────────────────────────
const history      = ref([JSON.parse(JSON.stringify(props.modelValue ?? []))])
const historyIndex = ref(0)

const canUndo = computed(() => historyIndex.value > 0)
const canRedo = computed(() => historyIndex.value < history.value.length - 1)

function pushHistory() {
  // Discard redo future
  history.value = history.value.slice(0, historyIndex.value + 1)
  history.value.push(JSON.parse(JSON.stringify(localBlocks.value)))
  if (history.value.length > 50) {
    history.value.shift()
  } else {
    historyIndex.value++
  }
}

// Debounced version for text-editing changes — waits 800ms of inactivity
let _pushHistoryTimer = null
function pushHistoryDebounced() {
  clearTimeout(_pushHistoryTimer)
  _pushHistoryTimer = setTimeout(() => pushHistory(), 800)
}

function undo() {
  if (!canUndo.value) return
  historyIndex.value--
  localBlocks.value = JSON.parse(JSON.stringify(history.value[historyIndex.value]))
  selectedBlockId.value = null
  emit('update:modelValue', localBlocks.value)
}

function redo() {
  if (!canRedo.value) return
  historyIndex.value++
  localBlocks.value = JSON.parse(JSON.stringify(history.value[historyIndex.value]))
  selectedBlockId.value = null
  emit('update:modelValue', localBlocks.value)
}

// ── Clipboard ─────────────────────────────────────────────────────────────────
const clipboard = ref(null)

// ── Recursive helpers ─────────────────────────────────────────────────────────

function hasChildren(block) {
  return block.type === 'container' || block.type === 'section' || block.type === 'loop' || block.type === 'archive-loop'
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
    const nextLoop = (b.type === 'loop' || b.type === 'archive-loop') ? b : currentLoop
    if (hasChildren(b) && b.children?.length) {
      const found = findLoopAncestor(b.children, targetId, nextLoop)
      if (found !== undefined) return found
    }
  }
  return undefined
}

// ── Clone + insert helpers ────────────────────────────────────────────────────

function newId() {
  return typeof crypto !== 'undefined' && crypto.randomUUID
    ? crypto.randomUUID()
    : Math.random().toString(36).slice(2) + Date.now().toString(36)
}

function cloneWithNewIds(block) {
  const cloned = JSON.parse(JSON.stringify(block))
  cloned.id = newId()
  if (cloned.children?.length) {
    cloned.children = cloned.children.map(cloneWithNewIds)
  }
  return cloned
}

function insertAfterInList(blocks, targetId, newBlock) {
  const result = []
  let inserted = false
  for (const b of blocks) {
    result.push(b)
    if (b.id === targetId) {
      result.push(newBlock)
      inserted = true
      continue
    }
    if (b.children?.length) {
      const newChildren = insertAfterInList(b.children, targetId, newBlock)
      if (newChildren !== b.children) {
        result[result.length - 1] = { ...b, children: newChildren }
        inserted = true
      }
    }
  }
  return inserted ? result : blocks
}

function appendChildInList(blocks, targetId, newBlock) {
  return blocks.map(b => {
    if (b.id === targetId) {
      return { ...b, children: [...(b.children ?? []), newBlock] }
    }
    if (b.children?.length) {
      return { ...b, children: appendChildInList(b.children, targetId, newBlock) }
    }
    return b
  })
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

// availableFields: merged prefixed list for DynamicField dropdowns.
// Groups: "Current Post" (from contextFields) + "Loop — <Source>" (from loop ancestor).
const availableFields = computed(() => {
  const fields = []

  if (props.contextFields.length) {
    fields.push(
      ...props.contextFields.map(f => ({ value: f.value, label: f.label, group: 'Current Post' }))
    )
  }

  if (loopFields.value.length) {
    const source      = loopAncestor.value?.data?.source ?? 'posts'
    const sourceLabel = SOURCES.find(s => s.value === source)?.label ?? source
    fields.push(
      ...loopFields.value.map(f => ({
        value: `loop:${f.value}`,
        label: f.label,
        group: `Loop — ${sourceLabel}`,
      }))
    )
  }

  return fields
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
  // newList comes from VueDraggable's internal array, which holds block object references
  // that may be stale (not yet updated by the async _list watcher in BlockCanvas).
  // Cross-list drags can cause the top-level setter to fire after onUpdateChildren has
  // already updated localBlocks with a fresh block object — if we replace wholesale we
  // lose those updates and get duplicates or disappearing blocks.
  // Fix: keep the fresh block objects from localBlocks; only apply the new ordering.
  const current = new Map(localBlocks.value.map(b => [b.id, b]))
  localBlocks.value = newList.map(b => current.get(b.id) ?? b)
  pushHistory()
  emit('update:modelValue', localBlocks.value)
}

function removeBlock(id) {
  const block = findBlock(localBlocks.value, id)
  if (block) {
    const hasContent = Object.values(block.data ?? {}).some(v =>
      v !== null && v !== '' && !(Array.isArray(v) && v.length === 0)
    )
    const hasChildBlocks = (block.children?.length ?? 0) > 0
    if (hasContent || hasChildBlocks) {
      removeTarget.value = id
      return
    }
  }
  localBlocks.value = removeFromList(localBlocks.value, id)
  if (selectedBlockId.value === id) selectedBlockId.value = null
  pushHistory()
  emit('update:modelValue', localBlocks.value)
}

function confirmRemove() {
  const id = removeTarget.value
  if (!id) return
  localBlocks.value = removeFromList(localBlocks.value, id)
  if (selectedBlockId.value === id) selectedBlockId.value = null
  removeTarget.value = null
  pushHistory()
  emit('update:modelValue', localBlocks.value)
}

function cancelRemove() {
  removeTarget.value = null
}

// data merges into block.data; remaining attrs (customId, fontFamily, children, etc.) go top-level
function updateBlock({ id, data, ...attrs }) {
  localBlocks.value = updateBlockInList(localBlocks.value, id, data, attrs)
  pushHistoryDebounced()
  emit('update:modelValue', localBlocks.value)
}

function onUpdateChildren({ id, children }) {
  // The `children` array arrives from EditorContainerBlock / EditorLoopBlock /
  // EditorSectionBlock, which each hold a LOCAL _children ref driven by an *async*
  // watcher.  When a parent block gets a new sibling dropped in, its VueDraggable
  // still references the OLD child objects — those objects may be stale (they
  // pre-date deeper nesting that onUpdateChildren already committed to localBlocks).
  // Replacing localBlocks with those stale objects silently throws away all the
  // deeply-nested data that was already saved.
  //
  // Fix (same pattern as onReorder): build a flat map of EVERY block currently
  // known in localBlocks, then swap each incoming child for its freshest version.
  const allCurrent = new Map()
  function collectAll(blocks) {
    for (const b of blocks) {
      allCurrent.set(b.id, b)
      if (b.children?.length) collectAll(b.children)
    }
  }
  collectAll(localBlocks.value)

  const freshChildren = children.map(c => allCurrent.get(c.id) ?? c)
  localBlocks.value = updateBlockInList(localBlocks.value, id, undefined, { children: freshChildren })
  pushHistory()
  emit('update:modelValue', localBlocks.value)
}

// ── Duplicate ─────────────────────────────────────────────────────────────────

function duplicateBlock(id) {
  const block = findBlock(localBlocks.value, id)
  if (!block) return
  const clone = cloneWithNewIds(block)
  const newList = insertAfterInList(localBlocks.value, id, clone)
  localBlocks.value = newList !== localBlocks.value ? newList : [...localBlocks.value, clone]
  selectedBlockId.value = clone.id
  pushHistory()
  emit('update:modelValue', localBlocks.value)
}

// ── Copy / Paste ──────────────────────────────────────────────────────────────

function copyBlock(id) {
  const block = findBlock(localBlocks.value, id)
  if (!block) return
  clipboard.value = JSON.parse(JSON.stringify(block))
}

function pasteBlock(targetId) {
  if (!clipboard.value) return
  const clone = cloneWithNewIds(clipboard.value)

  if (targetId) {
    localBlocks.value = appendChildInList(localBlocks.value, targetId, clone)
    selectedBlockId.value = clone.id
    pushHistory()
    emit('update:modelValue', localBlocks.value)
    return
  }

  const sel = selectedBlockId.value
    ? findBlock(localBlocks.value, selectedBlockId.value)
    : null

  if (sel && CHILD_CAPABLE.has(sel.type)) {
    localBlocks.value = appendChildInList(localBlocks.value, sel.id, clone)
  } else if (sel) {
    const result = insertAfterInList(localBlocks.value, sel.id, clone)
    localBlocks.value = result !== localBlocks.value ? result : [...localBlocks.value, clone]
  } else {
    localBlocks.value = [...localBlocks.value, clone]
  }

  selectedBlockId.value = clone.id
  pushHistory()
  emit('update:modelValue', localBlocks.value)
}

// ── Keyboard shortcuts ────────────────────────────────────────────────────────

function onKeydown(e) {
  const tag = document.activeElement?.tagName?.toLowerCase()
  if (tag === 'input' || tag === 'textarea' || document.activeElement?.isContentEditable) return

  const ctrl = e.ctrlKey || e.metaKey

  if (ctrl && e.key === 'z' && !e.shiftKey) {
    e.preventDefault()
    undo()
  } else if (ctrl && (e.key === 'y' || (e.key === 'z' && e.shiftKey))) {
    e.preventDefault()
    redo()
  } else if (ctrl && e.key === 'd') {
    e.preventDefault()
    if (selectedBlockId.value) duplicateBlock(selectedBlockId.value)
  } else if (ctrl && e.key === 'c') {
    if (selectedBlockId.value) copyBlock(selectedBlockId.value)
  } else if (ctrl && e.key === 'v') {
    e.preventDefault()
    pasteBlock()
  }
}

onMounted(() => document.addEventListener('keydown', onKeydown))
onUnmounted(() => {
  document.removeEventListener('keydown', onKeydown)
  clearTimeout(_pushHistoryTimer)
})
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
