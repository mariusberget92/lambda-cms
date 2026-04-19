# Block Editor Features Design
**Date:** 2026-03-27

## Overview

Add three features to the block editor: Undo/Redo, Duplicate, and Copy/Paste. All logic lives in `BlockEditor.vue` (the state owner). UI controls live in the Layers panel per-block row (`LayerItem.vue`) plus keyboard shortcuts.

---

## 1. Undo / Redo

### State

```js
const history      = ref([])   // array of JSON snapshots of localBlocks
const historyIndex = ref(-1)   // pointer into history
```

### History management

`pushHistory()` — called after every mutation that changes `localBlocks`:
- Slice off any "future" entries above `historyIndex` (redo stack cleared on new action)
- Push `JSON.parse(JSON.stringify(localBlocks.value))`
- Trim to 50 entries (drop oldest)
- Increment `historyIndex`

Must be added to: `addBlock` (when BlockTypePanel emits add), `removeBlock`, `updateBlock`, `onReorder`, `onUpdateChildren`, `duplicateBlock`, `pasteBlock`.

### Actions

- `undo()` — if `historyIndex > 0`: decrement pointer, restore `localBlocks` from snapshot, clear `selectedBlockId`, emit
- `redo()` — if `historyIndex < history.length - 1`: increment pointer, restore, clear `selectedBlockId`, emit

### Keyboard shortcuts

Registered via `document.addEventListener('keydown', onKeydown)` in `onMounted`, removed in `onUnmounted`.

```
Ctrl+Z           → undo()
Ctrl+Y           → redo()
Ctrl+Shift+Z     → redo()
```

Only fire when the event target is not an `<input>`, `<textarea>`, or `[contenteditable]` element (avoid intercepting text editing).

### UI

Two icon buttons in the **Layers panel header** (top of `BlockLayers.vue`), passed via emits up from `BlockEditor.vue`:
- Undo button: `RotateCcw` icon, disabled when `historyIndex <= 0`
- Redo button: `RotateCw` icon, disabled when `historyIndex >= history.length - 1`

---

## 2. Duplicate

### Helper

`cloneWithNewIds(block)` — recursive deep clone:
- Assigns a new `crypto.randomUUID()` to the block
- Recursively clones `children` array (if present) with new IDs
- Deep copies `data` and all other properties

### Action

`duplicateBlock(id)`:
1. Find the block in `localBlocks`
2. Clone it with new IDs
3. Insert the clone as an **immediate sibling after the original** (at the same nesting depth)
4. Select the new block
5. Push history

### Keyboard shortcut

`Ctrl+D` — fires only when a block is selected, prevents default (avoids browser bookmark).

### UI

A duplicate icon button per row in `LayerItem.vue`, visible on hover alongside the existing delete button. Emits `duplicate` event upward to `BlockEditor.vue`.

---

## 3. Copy / Paste

### State

```js
const clipboard = ref(null)  // deep clone of copied block (IDs NOT yet replaced)
```

### Actions

`copyBlock(id)`:
- Deep clone the block into `clipboard` (preserve original IDs — new IDs assigned on paste)

`pasteBlock(targetId?)`:
- If `clipboard` is null: no-op
- Clone clipboard with new IDs via `cloneWithNewIds`
- Determine insertion point:
  - If `targetId` provided (paste-into button on container): insert as last child of that block
  - Else if selected block supports children (container/section/loop): insert as last child of selected
  - Else if selected block doesn't support children: insert as sibling after selected
  - Else (nothing selected): append to end of top-level list
- Select the new block
- Push history

**Container-capable types:** `container`, `section`, `loop`, `archive-loop`

### Keyboard shortcuts

```
Ctrl+C  → copyBlock(selectedBlockId)   (no-op if nothing selected)
Ctrl+V  → pasteBlock()                 (no-op if clipboard empty)
```

### UI

In `LayerItem.vue` per row:
- **Copy button** (`Copy` icon) — always visible on hover, emits `copy` event
- **Paste button** (`Clipboard` icon) — shown only on container-capable blocks AND when `clipboard` prop is non-null; emits `paste` event with the block's ID as `targetId`

`clipboard` is passed as a prop from `BlockEditor.vue` → `BlockLayers.vue` → `LayerItem.vue`.

---

## Files Changed

| File | Change |
|------|--------|
| `BlockEditor.vue` | Add history state, `pushHistory`, `undo`, `redo`, `cloneWithNewIds`, `duplicateBlock`, `copyBlock`, `pasteBlock`, keyboard listener, pass `clipboard`/`canUndo`/`canRedo` props down |
| `BlockLayers.vue` | Accept `clipboard`/`canUndo`/`canRedo` props, show undo/redo buttons in header, pass `clipboard` to LayerItem, emit `duplicate`/`copy`/`paste`/`undo`/`redo` up |
| `LayerItem.vue` | Add duplicate, copy, paste buttons; accept `clipboard` prop |
