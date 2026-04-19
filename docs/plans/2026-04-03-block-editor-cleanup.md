# Block Editor Cleanup Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Fix cross-list drag duplication, add inline layer renaming, remove obsolete component/post-list blocks, fully delete the Navigation admin feature, and hide the hex code from the Appearance settings.

**Architecture:** All changes are frontend Vue/JS except Navigation removal which also touches PHP routes, controller, model, middleware, and one Vue page. Each task is self-contained and can be committed independently.

**Tech Stack:** Vue 3 `<script setup>`, VueDraggable Plus, Laravel 12, Inertia.js, Tailwind CSS 4.

---

### Task 1: Fix cross-list drag duplication in EditorLoopBlock

**Files:**
- Modify: `resources/js/components/BlockEditor/EditorLoopBlock.vue`

The bug: when a child block is dragged *out* of a Loop's inner `VueDraggable` list into the parent canvas, VueDraggable adds it to the destination but the source list doesn't remove it — because `localChildren` is a computed getter/setter and the `onAdd` handler only fires on the destination. Fix: listen to the `@remove` event on the Loop's inner `VueDraggable` and splice the item out of `block.data.children`.

**Step 1: Read the file**

Read `resources/js/components/BlockEditor/EditorLoopBlock.vue` in full to understand `localChildren` and `onAdd`.

**Step 2: Add `@remove` handler**

In the `<VueDraggable>` element (around line 8), add `@remove="onRemove"` alongside the existing `@add="onAdd"`.

Then in `<script setup>`, add:
```js
function onRemove(evt) {
  // VueDraggable has already mutated localChildren via v-model,
  // but we need to ensure block.data.children stays in sync.
  // Emit update-children with the current localChildren value.
  emit('update-children', { id: block.id, children: localChildren.value })
}
```

Also ensure the `<VueDraggable>` group config explicitly allows pulling:
```
:group="{ name: 'canvas', pull: true, put: true }"
```

**Step 3: Verify**

Open the block editor, add a Loop block, add a Heading inside it, then drag the Heading out to the canvas. It should move (not duplicate).

**Step 4: Commit**
```bash
git add resources/js/components/BlockEditor/EditorLoopBlock.vue
git commit -m "fix: remove block from loop when dragged out to parent canvas"
```

---

### Task 2: Inline layer renaming on double-click

**Files:**
- Modify: `resources/js/components/BlockEditor/BlockLayers.vue`

Each block row in the layers panel shows `block.data.blockName || LABELS[block.type]`. On `dblclick`, swap to an `<input>` pre-filled with that value. On `blur` or `Enter`, save back to `block.data.blockName` and return to display mode.

**Step 1: Read BlockLayers.vue**

Read `resources/js/components/BlockEditor/BlockLayers.vue` in full. Note where the block label is rendered (search for `LABELS` usage around line 285) and how `emit('update', ...)` works.

**Step 2: Add editing state**

In `<script setup>`, add:
```js
const editingId = ref(null)
const editingName = ref('')

function startRename(block) {
  editingId.value = block.id
  editingName.value = block.data?.blockName || displayName(block.type)
  nextTick(() => {
    document.getElementById(`rename-${block.id}`)?.select()
  })
}

function commitRename(block) {
  const trimmed = editingName.value.trim()
  if (trimmed && trimmed !== displayName(block.type)) {
    block.data = { ...block.data, blockName: trimmed }
    emit('update', block)
  } else if (!trimmed) {
    // Clear custom name — fall back to default label
    const { blockName, ...rest } = block.data ?? {}
    block.data = rest
    emit('update', block)
  }
  editingId.value = null
}
```

Make sure `nextTick` is imported from `'vue'`.

**Step 3: Update the template**

Find where the block label text is rendered (the `<span>` showing `displayName(block.type)` or `block.data.blockName`). Replace it with:

```vue
<template v-if="editingId === block.id">
  <input
    :id="`rename-${block.id}`"
    v-model="editingName"
    type="text"
    class="flex-1 bg-transparent text-xs font-semibold uppercase tracking-wider outline-none border-b border-primary min-w-0"
    @blur="commitRename(block)"
    @keydown.enter.prevent="commitRename(block)"
    @keydown.escape="editingId = null"
    @click.stop
  />
</template>
<template v-else>
  <span
    class="flex-1 truncate text-xs font-semibold uppercase tracking-wider"
    @dblclick.stop="startRename(block)"
  >{{ block.data?.blockName || displayName(block.type) }}</span>
</template>
```

Apply the same pattern to **all** block row label renders in the file (there may be multiple — one for each nesting level / block type group).

**Step 4: Commit**
```bash
git add resources/js/components/BlockEditor/BlockLayers.vue
git commit -m "feat: double-click to rename blocks inline in layers panel"
```

---

### Task 3: Remove component / post-list block type

**Files:**
- Modify: `resources/js/components/BlockEditor/BlockTypePanel.vue`
- Modify: `resources/js/components/BlockEditor/BlockCanvas.vue`
- Modify: `resources/js/components/BlockRenderer.vue`
- Delete: `resources/js/components/Blocks/PostListBlock.vue` (or leave as dead code)

**Step 1: BlockTypePanel.vue**

Read the file. Remove:
- The `{ type: 'component', label: 'Component', icon: Puzzle, group: 'Interactive' }` entry from `ALL_TYPES`
- The `component: { component: 'post-list', limit: 6, ... }` entry from `DEFAULT_DATA`
- The `Puzzle` icon import if it's only used for the component block

**Step 2: BlockCanvas.vue**

Read the file. Remove the `component` entry from `BLOCK_MAP` (the editor canvas component map) and its import if it exists. Remove `'component'` from `LABELS`.

**Step 3: BlockRenderer.vue**

Read the file. Remove:
```js
import PostListBlock from '@/Components/Blocks/PostListBlock.vue'
```
And remove the `component: PostListBlock` entry from the `BLOCK_MAP`.

**Step 4: Commit**
```bash
git add resources/js/components/BlockEditor/BlockTypePanel.vue resources/js/components/BlockEditor/BlockCanvas.vue resources/js/components/BlockRenderer.vue
git commit -m "feat: remove obsolete component/post-list block type"
```

---

### Task 4: Remove Navigation admin — backend

**Files:**
- Modify: `routes/web.php`
- Modify: `app/Http/Middleware/HandleInertiaRequests.php`
- Delete: `app/Http/Controllers/NavigationController.php`
- Delete: `app/Models/NavItem.php`

**Step 1: routes/web.php**

Read `routes/web.php`. Remove:
- Line `use App\Http\Controllers\NavigationController;`
- The 4 navigation routes (lines 157–160)
- Remove `navigation` from the slug exclusion regex (line 166) — keep the rest of the regex intact

**Step 2: HandleInertiaRequests.php**

Read `app/Http/Middleware/HandleInertiaRequests.php`. Remove:
- `use App\Models\NavItem;`
- The `'navItems' => fn () => NavItem::with('page')...` shared prop entry

**Step 3: Delete files**

```bash
rm app/Http/Controllers/NavigationController.php
rm app/Models/NavItem.php
```

(Leave the `nav_items` DB table — no destructive migration needed.)

**Step 4: Commit**
```bash
git add routes/web.php app/Http/Middleware/HandleInertiaRequests.php
git commit -m "feat: remove navigation admin backend — routes, controller, model, shared prop"
```

---

### Task 5: Remove Navigation admin — frontend

**Files:**
- Modify: `resources/js/Layouts/AppLayout.vue`
- Delete: `resources/js/Pages/Navigation/Index.vue`

**Step 1: AppLayout.vue**

Read `resources/js/Layouts/AppLayout.vue`. Find the navigation sidebar link (around line 70, using `route('navigation.index')`). Remove that entire `<a>` / nav item element.

**Step 2: Delete Vue page**

```bash
rm resources/js/Pages/Navigation/Index.vue
rmdir resources/js/Pages/Navigation 2>/dev/null || true
```

**Step 3: Commit**
```bash
git add resources/js/Layouts/AppLayout.vue
git commit -m "feat: remove navigation admin frontend — sidebar link and page"
```

---

### Task 6: Hide hex code in Appearance settings

**Files:**
- Modify: `resources/js/Pages/Settings/Index.vue`

**Step 1: Read the file**

Find line 521:
```vue
Selected: <code class="font-mono">{{ appearanceForm['site.accent_color'] }}</code>
```

**Step 2: Remove it**

Delete that line entirely (or its surrounding `<p>` tag if it wraps just this content).

**Step 3: Commit**
```bash
git add resources/js/Pages/Settings/Index.vue
git commit -m "fix: hide hex code from accent color swatch picker"
```

---

### Task 7: Build and verify

**Step 1: Run npm build**
```bash
npm run build 2>&1 | tail -15
```
Expected: `✓ built in X.XXs` with no errors. Warnings about chunk size are pre-existing and fine.

**Step 2: Run PHP tests**
```bash
php artisan test --no-coverage 2>&1 | tail -10
```
Expected: All tests pass (currently 470). If NavigationController tests exist, they'll need to be deleted too — check `tests/Feature/NavigationTest.php` and remove it if present.

**Step 3: Commit test cleanup if needed**
```bash
git add tests/
git commit -m "chore: remove navigation tests after feature deletion"
```
