# Block Editor Features Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add Undo/Redo (50 steps), Duplicate, and Copy/Paste to the block editor with keyboard shortcuts and per-block buttons in the Layers panel.

**Architecture:** All state and logic lives in `BlockEditor.vue` (the single state owner). `BlockLayers.vue` receives new props and forwards events. `LayerItem.vue` gets new action buttons. Keyboard shortcuts are registered globally on `document` in `onMounted`/`onUnmounted`.

**Tech Stack:** Vue 3 `<script setup>`, `crypto.randomUUID()`, lucide-vue-next icons

---

## Task 1: BlockEditor.vue — core logic

**Files:**
- Modify: `resources/js/Components/BlockEditor/BlockEditor.vue`

### Step 1: Read the full file

Read `resources/js/Components/BlockEditor/BlockEditor.vue` to understand the exact current state before editing.

### Step 2: Add imports

In `<script setup>`, change:
```js
import { ref, computed, watch } from 'vue'
```
to:
```js
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
```

### Step 3: Add history state + clipboard state

After `const selectedBlockId = ref(null)`, add:
```js
// ── Undo / Redo ───────────────────────────────────────────────────────────────
const history      = ref([])   // array of JSON snapshots of localBlocks
const historyIndex = ref(-1)

const canUndo = computed(() => historyIndex.value > 0)
const canRedo = computed(() => historyIndex.value < history.value.length - 1)

function pushHistory() {
  // Discard any redo future
  history.value = history.value.slice(0, historyIndex.value + 1)
  // Snapshot
  history.value.push(JSON.parse(JSON.stringify(localBlocks.value)))
  // Cap at 50
  if (history.value.length > 50) {
    history.value.shift()
  } else {
    historyIndex.value++
  }
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
const clipboard = ref(null)  // deep clone of copied block (IDs replaced on paste)
```

### Step 4: Add cloneWithNewIds helper

After the `findLoopAncestor` function, add:
```js
// ── Clone helpers ─────────────────────────────────────────────────────────────

function newId() {
  return typeof crypto !== 'undefined' && crypto.randomUUID
    ? crypto.randomUUID()
    : Math.random().toString(36).slice(2) + Date.now().toString(36)
}

function cloneWithNewIds(block) {
  return {
    ...JSON.parse(JSON.stringify(block)),
    id: newId(),
    ...(block.children?.length
      ? { children: block.children.map(cloneWithNewIds) }
      : {}),
  }
}
```

### Step 5: Add insertAfter and insertAsLastChild helpers

After `cloneWithNewIds`, add:
```js
// Insert a block as an immediate sibling after the block with targetId (recursive)
function insertAfterInList(blocks, targetId, newBlock) {
  const result = []
  for (const b of blocks) {
    result.push(b)
    if (b.id === targetId) {
      result.push(newBlock)
      continue
    }
    if (hasChildren(b) && b.children?.length) {
      const newChildren = insertAfterInList(b.children, targetId, newBlock)
      if (newChildren !== b.children) {
        result[result.length - 1] = { ...b, children: newChildren }
      }
    }
  }
  // Return original reference if nothing changed (for the !== check above)
  return result.length !== blocks.length || result.some((b, i) => b !== blocks[i])
    ? result
    : blocks
}

// Append newBlock as the last child of the block with targetId (recursive)
function appendChildInList(blocks, targetId, newBlock) {
  return blocks.map(b => {
    if (b.id === targetId) {
      return { ...b, children: [...(b.children ?? []), newBlock] }
    }
    if (hasChildren(b) && b.children?.length) {
      return { ...b, children: appendChildInList(b.children, targetId, newBlock) }
    }
    return b
  })
}
```

### Step 6: Add CHILD_CAPABLE constant

Near the top of the `<script setup>` (after the props definition), add:
```js
const CHILD_CAPABLE = new Set(['container', 'section', 'loop', 'archive-loop'])
```

### Step 7: Add duplicateBlock, copyBlock, pasteBlock

After the `onUpdateChildren` function, add:
```js
// ── Duplicate ─────────────────────────────────────────────────────────────────

function duplicateBlock(id) {
  const block = findBlock(localBlocks.value, id)
  if (!block) return
  const clone = cloneWithNewIds(block)
  const newList = insertAfterInList(localBlocks.value, id, clone)
  localBlocks.value = newList.length !== localBlocks.value.length
    ? newList
    : [...localBlocks.value, clone]  // fallback: append top-level if not found nested
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

  // targetId provided means "paste as last child of this specific block"
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
    // Paste as last child of selected container
    localBlocks.value = appendChildInList(localBlocks.value, sel.id, clone)
  } else if (sel) {
    // Paste as sibling after selected non-container
    const result = insertAfterInList(localBlocks.value, sel.id, clone)
    localBlocks.value = result !== localBlocks.value ? result : [...localBlocks.value, clone]
  } else {
    // Nothing selected — append to top level
    localBlocks.value = [...localBlocks.value, clone]
  }

  selectedBlockId.value = clone.id
  pushHistory()
  emit('update:modelValue', localBlocks.value)
}
```

### Step 8: Add pushHistory() calls to existing mutations

Find `removeBlock`, `updateBlock`, `onReorder`, `onUpdateChildren` — add `pushHistory()` call at the end of each, BEFORE the `emit('update:modelValue', ...)` call.

For `removeBlock`, add after `if (selectedBlockId.value === id) selectedBlockId.value = null`:
```js
  pushHistory()
```

For `updateBlock`, add before `emit('update:modelValue', localBlocks.value)`:
```js
  pushHistory()
```

For `onReorder`, add before `emit('update:modelValue', localBlocks.value)`:
```js
  pushHistory()
```

For `onUpdateChildren`, add before `emit('update:modelValue', localBlocks.value)`:
```js
  pushHistory()
```

Also seed the initial history snapshot after `localBlocks` is defined. After `const selectedBlockId = ref(null)`, add:
```js
// Seed initial history entry so undo has a "before" state
history.value.push(JSON.parse(JSON.stringify(localBlocks.value)))
historyIndex.value = 0
```

Wait — we already have the history state added in step 3. The seeding should happen AFTER both `localBlocks` and `history`/`historyIndex` are defined. Add this right after `historyIndex.value = -1` in the state block by initialising differently. Actually, just initialise history directly:

Replace in Step 3:
```js
const history      = ref([])
const historyIndex = ref(-1)
```
with:
```js
const history      = ref([JSON.parse(JSON.stringify(props.modelValue ?? []))])
const historyIndex = ref(0)
```

### Step 9: Add keyboard shortcuts

After the `pasteBlock` function, add:
```js
// ── Keyboard shortcuts ────────────────────────────────────────────────────────

function onKeydown(e) {
  // Don't intercept when typing in an input/textarea/contenteditable
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
onUnmounted(() => document.removeEventListener('keydown', onKeydown))
```

### Step 10: Update BlockLayers binding in the template

Find the `<BlockLayers` component in the template. It currently has:
```html
    <BlockLayers
      :blocks="localBlocks"
      :selected-id="selectedBlockId"
      :selected-block="selectedBlock"
      :is-admin="isAdmin"
      :meta="meta"
      :loop-fields="loopFields"
      :available-fields="availableFields"
      @select="selectBlock"
      @remove="removeBlock"
      @update="updateBlock"
      @reorder="onReorder"
    />
```

Replace with:
```html
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
      @duplicate="duplicateBlock"
      @copy="copyBlock"
      @paste="pasteBlock"
      @undo="undo"
      @redo="redo"
    />
```

### Step 11: Build check

```bash
npm run build 2>&1 | tail -5
```

Expected: `✓ built in Xs`

### Step 12: Commit

```bash
git add resources/js/Components/BlockEditor/BlockEditor.vue
git commit -m "feat: BlockEditor — undo/redo/duplicate/copy/paste core logic"
```

---

## Task 2: BlockLayers.vue — props, undo/redo header, event forwarding

**Files:**
- Modify: `resources/js/Components/BlockEditor/BlockLayers.vue`

### Step 1: Read the full file

Read `resources/js/Components/BlockEditor/BlockLayers.vue`.

### Step 2: Add new props

In `defineProps`, add after `availableFields`:
```js
  clipboard: { type: Object,  default: null },
  canUndo:   { type: Boolean, default: false },
  canRedo:   { type: Boolean, default: false },
```

### Step 3: Add new emits

In `defineEmits`, change:
```js
const emit = defineEmits(['select', 'remove', 'update', 'reorder'])
```
to:
```js
const emit = defineEmits(['select', 'remove', 'update', 'reorder', 'duplicate', 'copy', 'paste', 'undo', 'redo'])
```

### Step 4: Add undo/redo buttons to the Layers header

Find the Layers panel header div:
```html
      <div class="px-3 py-2 border-b shrink-0">
        <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Layers</p>
      </div>
```

Replace with:
```html
      <div class="px-3 py-2 border-b shrink-0 flex items-center justify-between">
        <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Layers</p>
        <div class="flex items-center gap-1">
          <button
            type="button"
            :disabled="!canUndo"
            class="p-1 rounded text-muted-foreground hover:text-foreground hover:bg-accent transition-colors disabled:opacity-30 disabled:cursor-not-allowed"
            title="Undo (Ctrl+Z)"
            @click="$emit('undo')"
          >
            <RotateCcw class="w-3.5 h-3.5" />
          </button>
          <button
            type="button"
            :disabled="!canRedo"
            class="p-1 rounded text-muted-foreground hover:text-foreground hover:bg-accent transition-colors disabled:opacity-30 disabled:cursor-not-allowed"
            title="Redo (Ctrl+Y)"
            @click="$emit('redo')"
          >
            <RotateCw class="w-3.5 h-3.5" />
          </button>
        </div>
      </div>
```

### Step 5: Add RotateCcw / RotateCw to lucide import

Find:
```js
import LayerItem from './LayerItem.vue'
```

Add above it:
```js
import { RotateCcw, RotateCw } from 'lucide-vue-next'
```

### Step 6: Pass clipboard to LayerItem and add new event forwarding

Find the `<LayerItem` in the template:
```html
            <LayerItem
              :block="block"
              :selected-id="selectedId"
              @select="$emit('select', $event)"
              @remove="$emit('remove', $event)"
            />
```

Replace with:
```html
            <LayerItem
              :block="block"
              :selected-id="selectedId"
              :clipboard="clipboard"
              @select="$emit('select', $event)"
              @remove="$emit('remove', $event)"
              @duplicate="$emit('duplicate', $event)"
              @copy="$emit('copy', $event)"
              @paste="$emit('paste', $event)"
            />
```

### Step 7: Build check

```bash
npm run build 2>&1 | tail -5
```

Expected: `✓ built in Xs`

### Step 8: Commit

```bash
git add resources/js/Components/BlockEditor/BlockLayers.vue
git commit -m "feat: BlockLayers — undo/redo header buttons + event forwarding"
```

---

## Task 3: LayerItem.vue — duplicate, copy, paste buttons

**Files:**
- Modify: `resources/js/Components/BlockEditor/LayerItem.vue`

### Step 1: Read the full file

Read `resources/js/Components/BlockEditor/LayerItem.vue`.

### Step 2: Add new props and emits

In `defineProps`, change:
```js
defineProps({
  block:      { type: Object, required: true },
  selectedId: { type: String, default: null },
})
```
to:
```js
defineProps({
  block:      { type: Object, required: true },
  selectedId: { type: String, default: null },
  clipboard:  { type: Object, default: null },
})
```

In `defineEmits`, change:
```js
defineEmits(['select', 'remove'])
```
to:
```js
defineEmits(['select', 'remove', 'duplicate', 'copy', 'paste'])
```

### Step 3: Add CHILD_CAPABLE constant

After `defineOptions({ name: 'LayerItem' })`, add:
```js
const CHILD_CAPABLE = new Set(['container', 'section', 'loop', 'archive-loop'])
```

### Step 4: Add lucide icons import

Change:
```js
import { GripVertical, X } from 'lucide-vue-next'
```
to:
```js
import { GripVertical, X, Copy, CopyPlus, Clipboard } from 'lucide-vue-next'
```

(`CopyPlus` for duplicate, `Copy` for copy, `Clipboard` for paste)

### Step 5: Add action buttons to the layer row

Find the row's remove button:
```html
      <button
        type="button"
        class="shrink-0 opacity-50 hover:opacity-100 transition-opacity"
        :class="block.id === selectedId ? 'text-primary-foreground' : 'text-muted-foreground'"
        title="Remove block"
        @click.stop="$emit('remove', block.id)"
      >
        <X class="w-3 h-3" />
      </button>
```

Replace with (three action buttons + remove):
```html
      <!-- Duplicate -->
      <button
        type="button"
        class="shrink-0 opacity-0 group-hover:opacity-60 hover:!opacity-100 transition-opacity"
        :class="block.id === selectedId ? 'text-primary-foreground' : 'text-muted-foreground'"
        title="Duplicate (Ctrl+D)"
        @click.stop="$emit('duplicate', block.id)"
      >
        <CopyPlus class="w-3 h-3" />
      </button>

      <!-- Copy -->
      <button
        type="button"
        class="shrink-0 opacity-0 group-hover:opacity-60 hover:!opacity-100 transition-opacity"
        :class="block.id === selectedId ? 'text-primary-foreground' : 'text-muted-foreground'"
        title="Copy (Ctrl+C)"
        @click.stop="$emit('copy', block.id)"
      >
        <Copy class="w-3 h-3" />
      </button>

      <!-- Paste (only on container-capable blocks when clipboard has content) -->
      <button
        v-if="CHILD_CAPABLE.has(block.type) && clipboard"
        type="button"
        class="shrink-0 opacity-0 group-hover:opacity-60 hover:!opacity-100 transition-opacity"
        :class="block.id === selectedId ? 'text-primary-foreground' : 'text-muted-foreground'"
        title="Paste inside (Ctrl+V)"
        @click.stop="$emit('paste', block.id)"
      >
        <Clipboard class="w-3 h-3" />
      </button>

      <!-- Remove -->
      <button
        type="button"
        class="shrink-0 opacity-50 hover:opacity-100 transition-opacity"
        :class="block.id === selectedId ? 'text-primary-foreground' : 'text-muted-foreground'"
        title="Remove block"
        @click.stop="$emit('remove', block.id)"
      >
        <X class="w-3 h-3" />
      </button>
```

### Step 6: Add `group` class to the layer row div

The outer row `<div>` currently has:
```html
    <div
      class="flex items-center gap-1.5 rounded-md px-1.5 py-1.5 cursor-pointer transition-colors text-xs"
```

Add `group` to the class list:
```html
    <div
      class="group flex items-center gap-1.5 rounded-md px-1.5 py-1.5 cursor-pointer transition-colors text-xs"
```

### Step 7: Pass clipboard down to nested LayerItem children

Find the recursive `<LayerItem` in the children list:
```html
        <LayerItem
          :block="child"
          :selected-id="selectedId"
          @select="$emit('select', $event)"
          @remove="$emit('remove', $event)"
        />
```

Replace with:
```html
        <LayerItem
          :block="child"
          :selected-id="selectedId"
          :clipboard="clipboard"
          @select="$emit('select', $event)"
          @remove="$emit('remove', $event)"
          @duplicate="$emit('duplicate', $event)"
          @copy="$emit('copy', $event)"
          @paste="$emit('paste', $event)"
        />
```

### Step 8: Build check + full test run

```bash
npm run build 2>&1 | tail -5
php artisan test
```

Expected: `✓ built in Xs` and all tests pass.

### Step 9: Commit

```bash
git add resources/js/Components/BlockEditor/LayerItem.vue
git commit -m "feat: LayerItem — duplicate, copy, paste buttons per block"
```

---

## Final verification

After all 3 tasks:
1. Open a post/page with the block editor
2. Add several blocks
3. Verify: Ctrl+Z undoes, Ctrl+Y redoes, undo/redo buttons in Layers header are enabled/disabled correctly
4. Select a block → Ctrl+D duplicates it as a sibling
5. Select a block → Ctrl+C copies, Ctrl+V pastes (as sibling if non-container, as child if container)
6. Hover a layer row → duplicate (CopyPlus), copy (Copy), paste (Clipboard on containers only when clipboard non-null), remove (X) buttons appear
