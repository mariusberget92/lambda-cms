# Block Editor: Container Block, Custom Attributes & Font Selection — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add a Container block with true recursive nesting (flex layout), per-block custom CSS/classes/ID, and Google Font selection to the block editor.

**Architecture:** Container blocks carry a `children` array of nested blocks. `BlockEditor` owns the full tree and provides recursive helpers for find/update/remove. `BlockCanvas` renders `EditorContainerBlock` for containers (a nested `VueDraggable`). `BlockRenderer` wraps every block in a `<div>` with scoped `<style>`, custom attributes, and font-family. All new fields serialize into the existing `blocks` JSON column — no DB migration needed.

**Tech Stack:** Vue 3, vue-draggable-plus, lucide-vue-next, Tailwind CSS 4, Laravel 12 (PHP), Inertia.js

---

### Task 1: Add Container type to BlockTypePanel

**Files:**
- Modify: `resources/js/components/BlockEditor/BlockTypePanel.vue`

**Step 1: Add `LayoutTemplate` to the Lucide import**

In the import block, add `LayoutTemplate`:

```js
import {
  AlignLeft, Heading2, ImageIcon, Quote, Code2,
  LayoutGrid, Video, Minus, MousePointerClick, FileCode, Puzzle,
  LayoutTemplate,   // ADD THIS
} from 'lucide-vue-next'
```

**Step 2: Add container to `ALL_TYPES`**

After the `component` entry, add:

```js
{ type: 'container', label: 'Container', icon: LayoutTemplate },
```

**Step 3: Add container to `DEFAULT_DATA`**

```js
container: { direction: 'row', wrap: true, gap: 4, justify: 'start', align: 'start', maxWidth: 'full', padding: 4 },
```

**Step 4: Update `cloneBlock` to include the four new shared top-level fields on every block**

Replace the existing `cloneBlock` function:

```js
function cloneBlock(typeDef) {
  const id = typeof crypto !== 'undefined' && crypto.randomUUID
    ? crypto.randomUUID()
    : Math.random().toString(36).slice(2) + Date.now().toString(36)
  return {
    id,
    type: typeDef.type,
    data: { ...(DEFAULT_DATA[typeDef.type] ?? {}) },
    customId: '',
    customClasses: '',
    customCss: '',
    fontFamily: '',
    ...(typeDef.type === 'container' ? { children: [] } : {}),
  }
}
```

**Step 5: Run the dev build to verify no errors**

```bash
cd /c/Users/mariu/Herd/lambda-cms && npm run build 2>&1 | tail -20
```

Expected: successful build.

**Step 6: Commit**

```bash
git add resources/js/components/BlockEditor/BlockTypePanel.vue
git commit -m "feat: add container block type to palette"
```

---

### Task 2: Create ContainerSettings.vue

**Files:**
- Create: `resources/js/components/BlockEditor/blocks/ContainerSettings.vue`

**Step 1: Create the file**

The component receives `block` and emits `@update` with `{ id, data: { key: value } }` (same shape as all other settings).

```vue
<!-- resources/js/components/BlockEditor/blocks/ContainerSettings.vue -->
<template>
  <div class="space-y-3">

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Direction</label>
      <select :value="block.data.direction" @change="update('direction', $event.target.value)"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-xs">
        <option value="row">Row (horizontal)</option>
        <option value="column">Column (vertical)</option>
      </select>
    </div>

    <div class="flex items-center gap-2">
      <input type="checkbox" :checked="block.data.wrap" @change="update('wrap', $event.target.checked)"
        id="container-wrap" class="rounded border-border" />
      <label for="container-wrap" class="text-xs font-medium text-muted-foreground">Wrap items</label>
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Gap: {{ block.data.gap }}</label>
      <input type="range" min="0" max="16" :value="block.data.gap"
        @input="update('gap', parseInt($event.target.value))"
        class="w-full" />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Justify content</label>
      <select :value="block.data.justify" @change="update('justify', $event.target.value)"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-xs">
        <option value="start">Start</option>
        <option value="center">Center</option>
        <option value="end">End</option>
        <option value="between">Space between</option>
        <option value="around">Space around</option>
      </select>
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Align items</label>
      <select :value="block.data.align" @change="update('align', $event.target.value)"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-xs">
        <option value="start">Start</option>
        <option value="center">Center</option>
        <option value="end">End</option>
        <option value="stretch">Stretch</option>
      </select>
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Max width</label>
      <select :value="block.data.maxWidth" @change="update('maxWidth', $event.target.value)"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-xs">
        <option value="full">Full</option>
        <option value="prose">Prose (65ch)</option>
        <option value="sm">SM (24rem)</option>
        <option value="md">MD (28rem)</option>
        <option value="lg">LG (32rem)</option>
        <option value="xl">XL (36rem)</option>
        <option value="2xl">2XL (42rem)</option>
      </select>
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Padding: {{ block.data.padding }}</label>
      <input type="range" min="0" max="16" :value="block.data.padding"
        @input="update('padding', parseInt($event.target.value))"
        class="w-full" />
    </div>

  </div>
</template>

<script setup>
const props = defineProps({ block: { type: Object, required: true } })
const emit = defineEmits(['update'])

function update(key, value) {
  emit('update', { id: props.block.id, data: { [key]: value } })
}
</script>
```

**Step 2: Commit**

```bash
git add resources/js/components/BlockEditor/blocks/ContainerSettings.vue
git commit -m "feat: add ContainerSettings panel for flex layout"
```

---

### Task 3: Create AdvancedSettings.vue

**Files:**
- Create: `resources/js/components/BlockEditor/blocks/AdvancedSettings.vue`

**Step 1: Create the file**

This component updates top-level block fields (not inside `block.data`), so the emit payload uses top-level keys: `{ id, customId }`, `{ id, fontFamily }`, etc. `BlockEditor.updateBlock` destructures `data` separately and spreads the rest as top-level attrs.

```vue
<!-- resources/js/components/BlockEditor/blocks/AdvancedSettings.vue -->
<template>
  <div class="space-y-3 pt-3 border-t mt-3">
    <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Advanced</p>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Font family</label>
      <select :value="block.fontFamily ?? ''" @change="update('fontFamily', $event.target.value)"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-xs">
        <option value="">Site default</option>
        <option v-for="font in FONTS" :key="font" :value="font">{{ font }}</option>
      </select>
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Custom ID</label>
      <input type="text" :value="block.customId ?? ''" @input="update('customId', $event.target.value)"
        placeholder="my-section"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-xs font-mono" />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Custom classes</label>
      <input type="text" :value="block.customClasses ?? ''" @input="update('customClasses', $event.target.value)"
        placeholder="my-class another-class"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-xs font-mono" />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Custom CSS</label>
      <textarea
        :value="block.customCss ?? ''"
        @input="update('customCss', $event.target.value)"
        rows="4"
        placeholder="color: red;&#10;font-size: 1.2em;"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-xs font-mono resize-none"
      />
      <p class="text-[10px] text-muted-foreground mt-1">Scoped to this block automatically.</p>
    </div>

  </div>
</template>

<script setup>
const FONTS = [
  'Inter', 'Roboto', 'Open Sans', 'Lato', 'Montserrat', 'Poppins', 'Raleway', 'Nunito',
  'Source Sans 3', 'Merriweather', 'Playfair Display', 'Lora', 'PT Serif', 'Libre Baskerville',
  'EB Garamond', 'Oswald', 'Bebas Neue', 'DM Sans', 'DM Serif Display', 'Figtree',
  'Plus Jakarta Sans', 'Outfit', 'Manrope', 'Sora', 'Space Grotesk',
  'JetBrains Mono', 'Fira Code', 'Source Code Pro',
]

const props = defineProps({ block: { type: Object, required: true } })
const emit = defineEmits(['update'])

function update(key, value) {
  emit('update', { id: props.block.id, [key]: value })
}
</script>
```

**Step 2: Commit**

```bash
git add resources/js/components/BlockEditor/blocks/AdvancedSettings.vue
git commit -m "feat: add AdvancedSettings panel (custom ID, classes, CSS, font)"
```

---

### Task 4: Create LayerItem.vue

**Files:**
- Create: `resources/js/components/BlockEditor/LayerItem.vue`

**Step 1: Create the recursive layer row component**

If the block is a container with children, an indented `<ul class="pl-4">` of child `LayerItem`s is shown below the row. Drag handle CSS class is `layer-handle` (same as existing rows — the parent `VueDraggable` in `BlockLayers` uses this handle selector for root-level reordering only; child reordering is not done from the layers panel).

```vue
<!-- resources/js/components/BlockEditor/LayerItem.vue -->
<template>
  <div>
    <!-- Layer row -->
    <div
      class="flex items-center gap-1.5 rounded-md px-1.5 py-1.5 cursor-pointer transition-colors text-xs"
      :class="block.id === selectedId
        ? 'bg-primary text-primary-foreground'
        : 'hover:bg-accent text-foreground'"
      @click="$emit('select', block.id)"
    >
      <span
        class="layer-handle cursor-grab active:cursor-grabbing shrink-0"
        :class="block.id === selectedId ? 'text-primary-foreground/60' : 'text-muted-foreground'"
        @click.stop
      >
        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
          <path d="M7 2a1 1 0 011 1v1a1 1 0 01-2 0V3a1 1 0 011-1zm6 0a1 1 0 011 1v1a1 1 0 01-2 0V3a1 1 0 011-1zM7 8a1 1 0 011 1v1a1 1 0 01-2 0V9a1 1 0 011-1zm6 0a1 1 0 011 1v1a1 1 0 01-2 0V9a1 1 0 011-1zM7 14a1 1 0 011 1v1a1 1 0 01-2 0v-1a1 1 0 011-1zm6 0a1 1 0 011 1v1a1 1 0 01-2 0v-1a1 1 0 011-1z"/>
        </svg>
      </span>

      <span class="flex-1 truncate">{{ LABELS[block.type] ?? block.type }}</span>

      <button
        type="button"
        class="shrink-0 opacity-50 hover:opacity-100 transition-opacity"
        :class="block.id === selectedId ? 'text-primary-foreground' : 'text-muted-foreground'"
        title="Remove block"
        @click.stop="$emit('remove', block.id)"
      >
        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
    </div>

    <!-- Indented children (container blocks only) -->
    <ul v-if="block.type === 'container' && block.children?.length" class="pl-4 space-y-0.5 mt-0.5">
      <li v-for="child in block.children" :key="child.id">
        <LayerItem
          :block="child"
          :selected-id="selectedId"
          @select="$emit('select', $event)"
          @remove="$emit('remove', $event)"
        />
      </li>
    </ul>
  </div>
</template>

<script setup>
const LABELS = {
  paragraph: 'Paragraph', heading: 'Heading', image: 'Image',
  quote: 'Quote', code: 'Code', gallery: 'Gallery', video: 'Video',
  divider: 'Divider', cta: 'CTA', html: 'HTML', component: 'Component',
  container: 'Container',
}

defineProps({
  block:      { type: Object, required: true },
  selectedId: { type: String, default: null },
})

defineEmits(['select', 'remove'])
</script>
```

**Step 2: Commit**

```bash
git add resources/js/components/BlockEditor/LayerItem.vue
git commit -m "feat: add recursive LayerItem component for block layers tree"
```

---

### Task 5: Update BlockLayers.vue

**Files:**
- Modify: `resources/js/components/BlockEditor/BlockLayers.vue`

**Step 1: Replace the whole file**

Key changes:
1. Replace inline layer `<div>` loop with `<LayerItem>` component
2. Import `ContainerSettings` and `AdvancedSettings`
3. Add `container` to `LABELS` and `COMPONENT_MAP`
4. Render `<AdvancedSettings>` below the type-specific settings (always shown when block is selected)

```vue
<!-- resources/js/Components/BlockEditor/BlockLayers.vue -->
<template>
  <div class="w-64 shrink-0 border-l flex flex-col bg-sidebar overflow-hidden">

    <!-- Layers list -->
    <div class="flex flex-col shrink-0" style="max-height: 40%">
      <div class="px-3 py-2 border-b shrink-0">
        <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Layers</p>
      </div>

      <div v-if="draggableBlocks.length === 0" class="px-3 py-4 text-xs text-muted-foreground text-center">
        No blocks yet
      </div>

      <VueDraggable
        v-else
        v-model="draggableBlocks"
        tag="div"
        class="overflow-y-auto p-1.5 space-y-0.5"
        handle=".layer-handle"
        :animation="150"
      >
        <div v-for="block in draggableBlocks" :key="block.id">
          <LayerItem
            :block="block"
            :selected-id="selectedId"
            @select="$emit('select', $event)"
            @remove="$emit('remove', $event)"
          />
        </div>
      </VueDraggable>
    </div>

    <!-- Settings panel -->
    <div class="flex-1 flex flex-col border-t overflow-hidden">
      <div class="px-3 py-2 border-b shrink-0">
        <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
          {{ selectedBlock ? blockLabel(selectedBlock.type) + ' Settings' : 'Settings' }}
        </p>
      </div>

      <div class="flex-1 overflow-y-auto p-3">
        <div v-if="!selectedBlock" class="h-full flex items-center justify-center">
          <p class="text-xs text-muted-foreground text-center">Select a block<br>to edit its settings</p>
        </div>

        <div v-else-if="selectedBlock.type === 'html' && !isAdmin" class="rounded-md border border-dashed p-4 text-center">
          <p class="text-xs text-muted-foreground">HTML blocks are admin-only and cannot be edited here.</p>
        </div>

        <template v-else>
          <component
            :is="settingsComponent"
            :block="selectedBlock"
            :is-admin="isAdmin"
            :meta="meta"
            @update="$emit('update', $event)"
          />
          <AdvancedSettings
            :block="selectedBlock"
            @update="$emit('update', $event)"
          />
        </template>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { VueDraggable } from 'vue-draggable-plus'
import LayerItem         from './LayerItem.vue'
import AdvancedSettings  from './blocks/AdvancedSettings.vue'
import ContainerSettings from './blocks/ContainerSettings.vue'
import HeadingSettings   from './blocks/HeadingSettings.vue'
import ParagraphSettings from './blocks/ParagraphSettings.vue'
import ImageSettings     from './blocks/ImageSettings.vue'
import QuoteSettings     from './blocks/QuoteSettings.vue'
import CodeSettings      from './blocks/CodeSettings.vue'
import GallerySettings   from './blocks/GallerySettings.vue'
import VideoSettings     from './blocks/VideoSettings.vue'
import DividerSettings   from './blocks/DividerSettings.vue'
import CtaSettings       from './blocks/CtaSettings.vue'
import HtmlSettings      from './blocks/HtmlSettings.vue'
import ComponentSettings from './blocks/ComponentSettings.vue'

const props = defineProps({
  blocks:        { type: Array,   default: () => [] },
  selectedId:    { type: String,  default: null },
  selectedBlock: { type: Object,  default: null },
  isAdmin:       { type: Boolean, default: false },
  meta:          { type: Object,  default: () => ({}) },
})

const emit = defineEmits(['select', 'remove', 'reorder', 'update'])

const draggableBlocks = computed({
  get: () => props.blocks,
  set: (val) => emit('reorder', val),
})

const LABELS = {
  paragraph: 'Paragraph', heading: 'Heading', image: 'Image',
  quote: 'Quote', code: 'Code', gallery: 'Gallery', video: 'Video',
  divider: 'Divider', cta: 'CTA', html: 'HTML', component: 'Component',
  container: 'Container',
}

const COMPONENT_MAP = {
  paragraph: ParagraphSettings, heading: HeadingSettings, image: ImageSettings,
  quote: QuoteSettings, code: CodeSettings, gallery: GallerySettings,
  video: VideoSettings, divider: DividerSettings, cta: CtaSettings,
  html: HtmlSettings, component: ComponentSettings, container: ContainerSettings,
}

const settingsComponent = computed(() =>
  props.selectedBlock ? COMPONENT_MAP[props.selectedBlock.type] ?? null : null
)

function blockLabel(type) {
  return LABELS[type] ?? type
}
</script>
```

**Step 2: Commit**

```bash
git add resources/js/components/BlockEditor/BlockLayers.vue
git commit -m "feat: use LayerItem tree and AdvancedSettings in BlockLayers"
```

---

### Task 6: Create EditorContainerBlock.vue

**Files:**
- Create: `resources/js/components/BlockEditor/EditorContainerBlock.vue`

**Step 1: Create the file**

This renders inside `BlockCanvas` for container-type blocks. It hosts a nested `VueDraggable` with the same `group: 'canvas'` so blocks can be dragged in from the palette or moved from the root canvas. When children are reordered or a new block is dropped in, it emits `@update-children({ id, children })`. When a child is clicked, it emits `@select(childId)`.

Note: `cloneBlock` in `BlockTypePanel` runs for items dragged from the palette, so children dragged from the palette will already have full block shape (with `id`, `type`, `data`, and the four new top-level fields).

```vue
<!-- resources/js/components/BlockEditor/EditorContainerBlock.vue -->
<template>
  <div class="px-3 py-3">
    <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-2">Container</p>

    <VueDraggable
      v-model="localChildren"
      tag="div"
      class="min-h-[60px] rounded-md border-2 border-dashed border-border p-2 space-y-1.5"
      :group="{ name: 'canvas' }"
      :animation="150"
      handle=".child-drag-handle"
      ghost-class="opacity-40"
      @add="onAdd"
    >
      <div
        v-for="child in localChildren"
        :key="child.id"
        class="group relative flex items-center gap-2 rounded-md border bg-background px-2 py-1.5 cursor-pointer text-xs transition-colors"
        :class="child.id === selectedId
          ? 'border-primary ring-1 ring-primary'
          : 'border-border hover:border-muted-foreground'"
        @click.stop="$emit('select', child.id)"
      >
        <span class="child-drag-handle cursor-grab active:cursor-grabbing text-muted-foreground shrink-0" @click.stop>
          <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
            <path d="M7 2a1 1 0 011 1v1a1 1 0 01-2 0V3a1 1 0 011-1zm6 0a1 1 0 011 1v1a1 1 0 01-2 0V3a1 1 0 011-1zM7 8a1 1 0 011 1v1a1 1 0 01-2 0V9a1 1 0 011-1zm6 0a1 1 0 011 1v1a1 1 0 01-2 0V9a1 1 0 011-1zM7 14a1 1 0 011 1v1a1 1 0 01-2 0v-1a1 1 0 011-1zm6 0a1 1 0 011 1v1a1 1 0 01-2 0v-1a1 1 0 011-1z"/>
          </svg>
        </span>
        <span class="flex-1 truncate text-muted-foreground">{{ LABELS[child.type] ?? child.type }}</span>
      </div>

      <div v-if="localChildren.length === 0"
        class="text-center py-2 text-xs text-muted-foreground/60 pointer-events-none">
        Drop blocks here
      </div>
    </VueDraggable>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { VueDraggable } from 'vue-draggable-plus'

const LABELS = {
  paragraph: 'Paragraph', heading: 'Heading', image: 'Image',
  quote: 'Quote', code: 'Code', gallery: 'Gallery', video: 'Video',
  divider: 'Divider', cta: 'CTA', html: 'HTML', component: 'Component',
  container: 'Container',
}

const props = defineProps({
  block:      { type: Object, required: true },
  selectedId: { type: String, default: null },
})

const emit = defineEmits(['select', 'update-children'])

const localChildren = computed({
  get: () => props.block.children ?? [],
  set: (val) => emit('update-children', { id: props.block.id, children: val }),
})

function onAdd(evt) {
  const newChild = localChildren.value[evt.newIndex]
  if (newChild) emit('select', newChild.id)
}
</script>
```

**Step 2: Commit**

```bash
git add resources/js/components/BlockEditor/EditorContainerBlock.vue
git commit -m "feat: add EditorContainerBlock with nested sortable children"
```

---

### Task 7: Update BlockCanvas.vue

**Files:**
- Modify: `resources/js/components/BlockEditor/BlockCanvas.vue`

**Step 1: Replace the whole file**

Key changes:
1. Import `EditorContainerBlock`
2. Render `EditorContainerBlock` for container blocks, regular preview for others
3. Add `@update-children` emit and propagation
4. Add `container` to `LABELS` and `blockPreview`
5. Adjust drag handle to `top-3` (not vertically centered — containers are taller)

```vue
<!-- resources/js/Components/BlockEditor/BlockCanvas.vue -->
<template>
  <div class="flex-1 flex flex-col overflow-hidden border-r bg-background">
    <div class="px-3 py-2 border-b shrink-0">
      <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Canvas</p>
    </div>

    <div class="relative flex-1 overflow-y-auto">
      <!-- Empty state overlay -->
      <div
        v-if="draggableBlocks.length === 0"
        class="absolute inset-0 flex items-center justify-center pointer-events-none"
      >
        <div class="text-center">
          <p class="text-sm text-muted-foreground">Drag blocks from the left panel</p>
          <p class="text-xs text-muted-foreground/60 mt-1">or click a block type to add it</p>
        </div>
      </div>

      <VueDraggable
        v-model="draggableBlocks"
        tag="div"
        class="p-4 space-y-2 min-h-full"
        :group="{ name: 'canvas' }"
        :animation="150"
        handle=".block-drag-handle"
        ghost-class="opacity-40"
        @add="onAdd"
      >
        <div
          v-for="block in draggableBlocks"
          :key="block.id"
          class="group relative rounded-lg border bg-card transition-colors cursor-pointer"
          :class="block.id === selectedId
            ? 'border-primary ring-1 ring-primary'
            : 'border-border hover:border-muted-foreground'"
          @click="$emit('select', block.id)"
        >
          <!-- Drag handle -->
          <div
            class="block-drag-handle absolute left-2 top-3 cursor-grab active:cursor-grabbing text-muted-foreground opacity-0 group-hover:opacity-100 transition-opacity"
            @click.stop
          >
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
              <path d="M7 2a1 1 0 011 1v1a1 1 0 01-2 0V3a1 1 0 011-1zm6 0a1 1 0 011 1v1a1 1 0 01-2 0V3a1 1 0 011-1zM7 8a1 1 0 011 1v1a1 1 0 01-2 0V9a1 1 0 011-1zm6 0a1 1 0 011 1v1a1 1 0 01-2 0V9a1 1 0 011-1zM7 14a1 1 0 011 1v1a1 1 0 01-2 0v-1a1 1 0 011-1zm6 0a1 1 0 011 1v1a1 1 0 01-2 0v-1a1 1 0 011-1z"/>
            </svg>
          </div>

          <!-- Container block: nested sortable children -->
          <EditorContainerBlock
            v-if="block.type === 'container'"
            :block="block"
            :selected-id="selectedId"
            @select="$emit('select', $event)"
            @update-children="$emit('update-children', $event)"
          />

          <!-- Regular block: text preview -->
          <div v-else class="px-8 py-3">
            <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1">{{ blockLabel(block.type) }}</p>
            <p class="text-sm text-foreground line-clamp-2 min-h-[1.25rem]">{{ blockPreview(block) }}</p>
          </div>
        </div>
      </VueDraggable>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { VueDraggable } from 'vue-draggable-plus'
import EditorContainerBlock from './EditorContainerBlock.vue'

const props = defineProps({
  blocks:     { type: Array,  default: () => [] },
  selectedId: { type: String, default: null },
})

const emit = defineEmits(['select', 'reorder', 'update-children'])

const draggableBlocks = computed({
  get: () => props.blocks,
  set: (val) => emit('reorder', val),
})

function onAdd(evt) {
  const newBlock = draggableBlocks.value[evt.newIndex]
  if (newBlock) emit('select', newBlock.id)
}

const LABELS = {
  paragraph: 'Paragraph', heading: 'Heading', image: 'Image',
  quote: 'Quote', code: 'Code', gallery: 'Gallery', video: 'Video',
  divider: 'Divider', cta: 'CTA', html: 'HTML', container: 'Container',
}

function blockLabel(type) {
  return LABELS[type] ?? type
}

function blockPreview(block) {
  const d = block.data ?? {}
  switch (block.type) {
    case 'paragraph': return d.content   || '(empty)'
    case 'heading':   return d.text      || '(empty)'
    case 'image':     return d.caption || d.alt || d.url || '(no image)'
    case 'quote':     return d.text      || '(empty)'
    case 'code':      return d.language  ? `[${d.language}]` : '(empty)'
    case 'gallery':   return d.items?.length ? `${d.items.length} image(s)` : '(empty)'
    case 'video':     return d.url       || '(no URL)'
    case 'divider':   return '————————'
    case 'cta':       return d.headline || d.text || '(empty)'
    case 'html':      return d.content   ? '(HTML content)' : '(empty)'
    default:          return ''
  }
}
</script>
```

**Step 2: Commit**

```bash
git add resources/js/components/BlockEditor/BlockCanvas.vue
git commit -m "feat: render EditorContainerBlock in canvas for container blocks"
```

---

### Task 8: Update BlockEditor.vue

**Files:**
- Modify: `resources/js/components/BlockEditor/BlockEditor.vue`

**Step 1: Replace the whole file**

Key changes:
1. `findBlock(blocks, id)` — recursive search through `children`
2. `selectedBlock` uses `findBlock`
3. `updateBlock({ id, data, ...attrs })` — `data` merges into `block.data`, remaining attrs go top-level; recursive via `updateBlockInList`
4. `removeBlock(id)` — recursive via `removeFromList`
5. `onUpdateChildren({ id, children })` — updates a container's children array
6. Handle `@update-children` from `BlockCanvas`

```vue
<!-- resources/js/Components/BlockEditor/BlockEditor.vue -->
<template>
  <div class="flex border rounded-lg overflow-hidden bg-background" style="min-height: 500px">
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
      @select="selectBlock"
      @remove="removeBlock"
      @reorder="onReorder"
      @update="updateBlock"
    />
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import BlockTypePanel from './BlockTypePanel.vue'
import BlockCanvas    from './BlockCanvas.vue'
import BlockLayers    from './BlockLayers.vue'

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

function findBlock(blocks, id) {
  if (!id) return null
  for (const b of blocks) {
    if (b.id === id) return b
    if (b.type === 'container' && b.children?.length) {
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
    if (b.type === 'container' && b.children?.length) {
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
      if (b.type === 'container' && b.children?.length) {
        return { ...b, children: removeFromList(b.children, id) }
      }
      return b
    })
}

// ── Computed ──────────────────────────────────────────────────────────────────

const selectedBlock = computed(() =>
  findBlock(localBlocks.value, selectedBlockId.value)
)

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
    if (hasContent && !confirm('Remove this block? Its content will be lost.')) return
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
```

**Step 2: Build to verify no errors**

```bash
cd /c/Users/mariu/Herd/lambda-cms && npm run build 2>&1 | tail -30
```

Expected: successful build with no errors.

**Step 3: Commit**

```bash
git add resources/js/components/BlockEditor/BlockEditor.vue
git commit -m "feat: recursive block find/update/remove and container children in BlockEditor"
```

---

### Task 9: Create ContainerBlock.vue (public renderer)

**Files:**
- Create: `resources/js/components/Blocks/ContainerBlock.vue`

**Step 1: Create the file**

Tailwind utility classes must be written as complete strings (not interpolated like `flex-${dir}`) so Tailwind's scanner can detect and include them. Use lookup maps.

```vue
<!-- resources/js/components/Blocks/ContainerBlock.vue -->
<template>
  <div :class="containerClasses">
    <BlockRenderer :blocks="block.children ?? []" />
  </div>
</template>

<script setup>
import { computed } from 'vue'
import BlockRenderer from '@/components/BlockRenderer.vue'

const props = defineProps({ block: { type: Object, required: true } })

const DIRECTION_MAP = { row: 'flex-row', column: 'flex-col' }
const JUSTIFY_MAP   = { start: 'justify-start', center: 'justify-center', end: 'justify-end', between: 'justify-between', around: 'justify-around' }
const ALIGN_MAP     = { start: 'items-start', center: 'items-center', end: 'items-end', stretch: 'items-stretch' }
const MAX_WIDTH_MAP = { full: 'max-w-full', prose: 'max-w-prose', sm: 'max-w-sm', md: 'max-w-md', lg: 'max-w-lg', xl: 'max-w-xl', '2xl': 'max-w-2xl' }
const GAP_MAP       = { 0:'gap-0',1:'gap-1',2:'gap-2',3:'gap-3',4:'gap-4',5:'gap-5',6:'gap-6',7:'gap-7',8:'gap-8',9:'gap-9',10:'gap-10',11:'gap-11',12:'gap-12',13:'gap-13',14:'gap-14',15:'gap-15',16:'gap-16' }
const PADDING_MAP   = { 0:'p-0',1:'p-1',2:'p-2',3:'p-3',4:'p-4',5:'p-5',6:'p-6',7:'p-7',8:'p-8',9:'p-9',10:'p-10',11:'p-11',12:'p-12',13:'p-13',14:'p-14',15:'p-15',16:'p-16' }

const containerClasses = computed(() => {
  const d = props.block.data ?? {}
  return [
    'flex',
    DIRECTION_MAP[d.direction]  ?? 'flex-row',
    d.wrap ? 'flex-wrap' : 'flex-nowrap',
    GAP_MAP[d.gap]              ?? 'gap-4',
    JUSTIFY_MAP[d.justify]      ?? 'justify-start',
    ALIGN_MAP[d.align]          ?? 'items-start',
    MAX_WIDTH_MAP[d.maxWidth]   ?? 'max-w-full',
    PADDING_MAP[d.padding]      ?? 'p-4',
  ].join(' ')
})
</script>
```

**Step 2: Commit**

```bash
git add resources/js/components/Blocks/ContainerBlock.vue
git commit -m "feat: add public ContainerBlock renderer with flex layout"
```

---

### Task 10: Update BlockRenderer.vue

**Files:**
- Modify: `resources/js/components/BlockRenderer.vue`

**Step 1: Replace the whole file**

Key changes:
1. Each block gets a wrapper `<div>` with `:id`, `:class`, `:style` (font-family)
2. Scoped `<style>` tag rendered before each wrapper when `block.customCss` is set — the rule is `#block-{id} { ... }` which scopes it automatically
3. `ContainerBlock` added to `BLOCK_MAP`
4. `loadFont(family)` injects a `<link>` for Google Fonts on demand, deduped via a `Set`
5. Fonts are loaded on mount and watched for changes

Important: Vue treats `<style>` in templates as a string component (use `:is="'style'"`), or simply write it inline. We'll use `<component :is="'style'">` to avoid Vue SFC style block conflicts.

```vue
<!-- resources/js/Components/BlockRenderer.vue -->
<template>
  <div class="space-y-4">
    <template v-for="block in blocks" :key="block.id">
      <component
        v-if="block.customCss"
        :is="'style'"
      >#block-{{ block.id }} { {{ block.customCss }} }</component>
      <div
        :id="block.customId || `block-${block.id}`"
        :class="block.customClasses || undefined"
        :style="block.fontFamily ? { fontFamily: `'${block.fontFamily}', sans-serif` } : undefined"
      >
        <component
          :is="BLOCK_MAP[block.type]"
          :block="block"
        />
      </div>
    </template>
  </div>
</template>

<script setup>
import { onMounted, watch } from 'vue'
import ParagraphBlock from '@/components/Blocks/ParagraphBlock.vue'
import HeadingBlock   from '@/components/Blocks/HeadingBlock.vue'
import ImageBlock     from '@/components/Blocks/ImageBlock.vue'
import QuoteBlock     from '@/components/Blocks/QuoteBlock.vue'
import CodeBlock      from '@/components/Blocks/CodeBlock.vue'
import GalleryBlock   from '@/components/Blocks/GalleryBlock.vue'
import VideoBlock     from '@/components/Blocks/VideoBlock.vue'
import DividerBlock   from '@/components/Blocks/DividerBlock.vue'
import CtaBlock       from '@/components/Blocks/CtaBlock.vue'
import HtmlBlock      from '@/components/Blocks/HtmlBlock.vue'
import PostListBlock  from '@/components/Blocks/PostListBlock.vue'
import ContainerBlock from '@/components/Blocks/ContainerBlock.vue'

const props = defineProps({ blocks: { type: Array, default: () => [] } })

const BLOCK_MAP = {
  paragraph: ParagraphBlock,
  heading:   HeadingBlock,
  image:     ImageBlock,
  quote:     QuoteBlock,
  code:      CodeBlock,
  gallery:   GalleryBlock,
  video:     VideoBlock,
  divider:   DividerBlock,
  cta:       CtaBlock,
  html:      HtmlBlock,
  component: PostListBlock,
  container: ContainerBlock,
}

const loadedFonts = new Set()

function loadFont(family) {
  if (!family || loadedFonts.has(family)) return
  loadedFonts.add(family)
  const link = document.createElement('link')
  link.rel = 'stylesheet'
  link.href = `https://fonts.googleapis.com/css2?family=${encodeURIComponent(family)}:wght@400;600;700&display=swap`
  document.head.appendChild(link)
}

function loadFontsFromBlocks(blocks) {
  for (const block of blocks) {
    if (block.fontFamily) loadFont(block.fontFamily)
    if (block.type === 'container' && block.children?.length) {
      loadFontsFromBlocks(block.children)
    }
  }
}

onMounted(() => loadFontsFromBlocks(props.blocks))
watch(() => props.blocks, (val) => loadFontsFromBlocks(val), { deep: true })
</script>
```

**Step 2: Build to verify**

```bash
cd /c/Users/mariu/Herd/lambda-cms && npm run build 2>&1 | tail -30
```

Expected: successful build.

**Step 3: Commit**

```bash
git add resources/js/components/BlockRenderer.vue
git commit -m "feat: wrap blocks with scoped CSS, custom attrs, font loading in BlockRenderer"
```

---

### Task 11: Update PublicPageController.php

**Files:**
- Modify: `app/Http/Controllers/PublicPageController.php`

**Step 1: Make `resolveBlocks()` recursive into container children**

Replace only the `resolveBlocks` method body. Find:

```php
private function resolveBlocks(array $blocks): array
{
    return array_map(function ($block) {
        if (($block['type'] ?? '') !== 'component') {
            return $block;
        }

        return match ($block['data']['component'] ?? null) {
            'post-list' => $this->resolvePostList($block),
            default     => $block,
        };
    }, $blocks);
}
```

Replace with:

```php
private function resolveBlocks(array $blocks): array
{
    return array_map(function ($block) {
        // Recurse into container children first
        if (($block['type'] ?? '') === 'container' && !empty($block['children'])) {
            $block['children'] = $this->resolveBlocks($block['children']);
        }

        if (($block['type'] ?? '') !== 'component') {
            return $block;
        }

        return match ($block['data']['component'] ?? null) {
            'post-list' => $this->resolvePostList($block),
            default     => $block,
        };
    }, $blocks);
}
```

**Step 2: Commit**

```bash
git add app/Http/Controllers/PublicPageController.php
git commit -m "feat: resolve blocks recursively inside container children"
```

---

### Task 12: Add tests for container block resolution

**Files:**
- Modify: `tests/Feature/PageTest.php`

**Step 1: Read the test file to find the `// ── Public component block resolution` section (line ~153)**

Append two new test methods after `test_draft_posts_are_excluded_from_component_post_list` (the last existing test in the class, at line ~259). Add them before the closing `}`.

```php
    // ── Container block ───────────────────────────────────────────────────────

    public function test_container_block_children_are_preserved_on_page_load(): void
    {
        $page = Page::create([
            'user_id' => User::factory()->create()->id,
            'title'   => 'Container Test',
            'slug'    => 'container-test',
            'status'  => 'published',
            'blocks'  => [
                [
                    'id'            => 'container-1',
                    'type'          => 'container',
                    'data'          => ['direction' => 'row', 'gap' => 4, 'wrap' => true, 'justify' => 'start', 'align' => 'start', 'maxWidth' => 'full', 'padding' => 4],
                    'children'      => [
                        ['id' => 'child-1', 'type' => 'paragraph', 'data' => ['content' => 'Hello']],
                    ],
                    'customId'      => '',
                    'customClasses' => '',
                    'customCss'     => '',
                    'fontFamily'    => '',
                ],
            ],
        ]);

        $this->get("/{$page->slug}")
            ->assertOk()
            ->assertInertia(fn ($p) => $p
                ->component('Blog/Page')
                ->where('page.blocks.0.type', 'container')
                ->where('page.blocks.0.children.0.type', 'paragraph')
                ->where('page.blocks.0.children.0.data.content', 'Hello')
            );
    }

    public function test_nested_component_block_inside_container_is_resolved(): void
    {
        $post = Post::factory()->create([
            'status'       => 'published',
            'published_at' => now()->subDay(),
        ]);

        $page = Page::create([
            'user_id' => User::factory()->create()->id,
            'title'   => 'Nested Component Test',
            'slug'    => 'nested-component-test',
            'status'  => 'published',
            'blocks'  => [
                [
                    'id'       => 'container-1',
                    'type'     => 'container',
                    'data'     => ['direction' => 'row', 'gap' => 4, 'wrap' => true, 'justify' => 'start', 'align' => 'start', 'maxWidth' => 'full', 'padding' => 4],
                    'children' => [
                        [
                            'id'   => 'comp-1',
                            'type' => 'component',
                            'data' => [
                                'component'     => 'post-list',
                                'limit'         => 6,
                                'offset'        => 0,
                                'order'         => 'latest',
                                'featured_only' => false,
                                'category_ids'  => [],
                                'tag_ids'       => [],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $this->get("/{$page->slug}")
            ->assertOk()
            ->assertInertia(fn ($p) => $p
                ->component('Blog/Page')
                ->where('page.blocks.0.type', 'container')
                ->where('page.blocks.0.children.0.type', 'component')
                ->has('page.blocks.0.children.0.data.resolved.posts')
            );
    }
```

**Step 2: Run the new tests**

```bash
cd /c/Users/mariu/Herd/lambda-cms && php artisan test tests/Feature/PageTest.php --stop-on-failure
```

Expected: all tests pass (green).

**Step 3: Commit**

```bash
git add tests/Feature/PageTest.php
git commit -m "test: add container block and nested component resolution tests"
```
