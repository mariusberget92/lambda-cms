# Block Editor Modernization Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Modernize the block editor with a forced dark theme, color-coded block palette, wireframe canvas with breadcrumb toolbar and live preview toggle, tabbed settings panel (Content / Style / Advanced), and native browser spinner suppression in NumberInput.

**Architecture:** The dark theme is self-contained — a `[data-theme="dark"]` CSS rule in `app.css` overrides all tokens inside the editor wrapper without touching any other page. The settings panel uses shadcn-vue `<Tabs>` in `BlockLayers.vue`; each `*Settings.vue` gets a `tab` prop (`'content' | 'style' | null`) and uses `v-show` to show only the relevant section. No backend changes.

**Tech Stack:** Vue 3, Tailwind CSS 4, shadcn-vue (Tabs — needs install), lucide-vue-next, existing `BlockRenderer.vue`

---

## Task 1: Fix NumberInput native spinner (caret) bug

**Files:**
- Modify: `resources/js/Components/NumberInput.vue`

### Step 1: Add webkit spin-button suppression classes

Open `NumberInput.vue`. Find the `<input>` element's `class` attribute. It currently ends with `[appearance:textfield]`. Add two more Tailwind arbitrary classes immediately after it:

```html
class="w-full rounded-md border bg-background pl-3 pr-7 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none disabled:opacity-50 disabled:cursor-not-allowed"
```

### Step 2: Verify visually

```bash
cd /c/Users/mariu/Herd/lambda-cms && npm run build
```

Open a page with a NumberInput (e.g. Container block settings, Columns field). Confirm only the ChevronUp/ChevronDown buttons are visible — no native browser arrows alongside them in Chrome or Safari.

### Step 3: Commit

```bash
cd /c/Users/mariu/Herd/lambda-cms
git add resources/js/Components/NumberInput.vue
git commit -m "fix: suppress native browser spin buttons on NumberInput in all browsers"
```

---

## Task 2: Add dark scope CSS + install Tabs component

**Files:**
- Modify: `resources/css/app.css`
- Install: shadcn-vue Tabs component

### Step 1: Add `[data-theme="dark"]` to app.css

Open `resources/css/app.css`. After the closing `}` of the `.dark { ... }` block (around line 128), add this block. It mirrors the exact same values as `.dark` so the editor always uses the app's dark palette regardless of system theme:

```css
/* Block editor forced dark scope — overrides CSS tokens inside [data-theme="dark"] */
[data-theme="dark"] {
  --background: #2e3440;
  --foreground: #d8dee9;
  --card: #3b4252;
  --card-foreground: #d8dee9;
  --popover: #3b4252;
  --popover-foreground: #d8dee9;
  --primary: #88c0d0;
  --primary-foreground: #2e3440;
  --primary-hover: #72a4b4;
  --secondary: #434c5e;
  --secondary-foreground: #d8dee9;
  --muted: #3b4252;
  --muted-foreground: #7b8898;
  --accent: #4c566a;
  --accent-foreground: #eceff4;
  --destructive: #bf616a;
  --destructive-foreground: #eceff4;
  --border: #434c5e;
  --input: #434c5e;
  --ring: #88c0d0;
  --chart-1: #88c0d0;
  --chart-2: #8fbcbb;
  --chart-3: #a3be8c;
  --chart-4: #ebcb8b;
  --chart-5: #b48ead;
  --sidebar: #2a2f3a;
  --sidebar-foreground: #d8dee9;
  --sidebar-primary: #88c0d0;
  --sidebar-primary-foreground: #2e3440;
  --sidebar-accent: #434c5e;
  --sidebar-accent-foreground: #d8dee9;
  --sidebar-border: #434c5e;
  --sidebar-ring: #88c0d0;
}

/* Canvas dot-grid background for the block editor */
.editor-canvas-bg {
  background-color: #0f1117;
  background-image: radial-gradient(circle, rgba(255,255,255,0.04) 1px, transparent 1px);
  background-size: 24px 24px;
}
```

### Step 2: Install the Tabs shadcn-vue component

```bash
cd /c/Users/mariu/Herd/lambda-cms
npx shadcn-vue@latest add tabs
```

This generates `resources/js/components/ui/tabs/index.ts` (and related files). Accept all prompts.

### Step 3: Build to confirm no errors

```bash
npm run build
```

Expected: clean build (pre-existing chunk size warning is fine).

### Step 4: Commit

```bash
git add resources/css/app.css resources/js/components/ui/tabs/
git commit -m "feat: add dark scope CSS for block editor and install Tabs component"
```

---

## Task 3: BlockEditor.vue — force dark wrapper

**Files:**
- Modify: `resources/js/components/BlockEditor/BlockEditor.vue`

### Step 1: Add `data-theme="dark"` to the root div

Find the root `<div>` in `BlockEditor.vue`'s template (line 3):

```html
<div class="flex border rounded-xl overflow-hidden bg-background" style="min-height: 500px; max-height: calc(100vh - 220px)">
```

Replace with:

```html
<div
  data-theme="dark"
  class="flex border border-white/10 rounded-xl overflow-hidden bg-background"
  style="min-height: 500px; max-height: calc(100vh - 220px)"
>
```

### Step 2: Update the remove-block confirmation modal

The modal currently uses generic classes. Update its card to match dark chrome. Find the modal card div (around line 44):

```html
<div class="relative bg-card border rounded-xl shadow-xl w-full max-w-sm p-6">
```

Change to:

```html
<div class="relative bg-card border border-white/10 rounded-xl shadow-xl w-full max-w-sm p-6">
```

### Step 3: Build and verify

```bash
npm run build
```

Open a page with the block editor. The entire editor should now appear in dark mode (Nord dark palette) regardless of whether the app is in light or dark mode.

### Step 4: Commit

```bash
git add resources/js/components/BlockEditor/BlockEditor.vue
git commit -m "feat: force block editor into dark mode via data-theme scope"
```

---

## Task 4: BlockTypePanel.vue redesign

**Files:**
- Modify: `resources/js/components/BlockEditor/BlockTypePanel.vue`

### Step 1: Replace the template

Replace the entire `<template>` block with the following. Key changes: pill group headers, taller tiles (`py-4`), bigger icons (`w-5 h-5`), glass-style tile backgrounds, color-coded icons per group.

```html
<template>
  <div class="w-48 shrink-0 border-r border-white/8 flex flex-col bg-sidebar">
    <div class="px-3 py-2 border-b border-white/8 shrink-0 bg-black/20">
      <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Add Block</p>
    </div>

    <div class="flex-1 overflow-y-auto scrollbar-hidden">
      <template v-for="group in visibleGroups" :key="group.name">
        <div class="px-3 pt-3 pb-1 flex items-center">
          <span class="inline-flex bg-white/5 rounded-full px-2 py-0.5 text-[9px] font-semibold uppercase tracking-widest text-white/40">
            {{ group.name }}
          </span>
        </div>
        <VueDraggable
          :model-value="group.types"
          tag="div"
          class="px-2 pb-2 grid grid-cols-2 gap-1.5 content-start"
          :group="{ name: 'canvas', pull: 'clone', put: false }"
          :sort="false"
          :clone="cloneBlock"
          :animation="150"
        >
          <div
            v-for="btype in group.types"
            :key="btype.type"
            class="flex flex-col items-center justify-center gap-1.5 rounded-lg border bg-white/5 border-white/10 px-1 py-4 cursor-grab active:cursor-grabbing hover:bg-white/10 hover:border-white/20 active:border-primary/60 active:bg-primary/10 transition-colors select-none"
          >
            <component :is="btype.icon" class="w-5 h-5 shrink-0" :class="GROUP_COLORS[btype.group]" />
            <span class="text-[11px] leading-none text-center text-muted-foreground">{{ btype.label }}</span>
          </div>
        </VueDraggable>
      </template>
    </div>
  </div>
</template>
```

### Step 2: Add `GROUP_COLORS` to the script

In `<script setup>`, add this constant right after the `ALL_TYPES` array:

```js
const GROUP_COLORS = {
  'Content':     'text-[var(--chart-1)]',
  'Layout':      'text-[var(--chart-2)]',
  'Interactive': 'text-[var(--chart-3)]',
  'Developer':   'text-[var(--chart-4)]',
  'Post':        'text-[var(--chart-5)]',
  'Archive':     'text-[var(--chart-1)]',
}
```

### Step 3: Build and verify

```bash
npm run build
```

Open the block editor. The left panel should show pill-style group headers, taller tiles with larger icons, and each group's icons should be a distinct color.

### Step 4: Commit

```bash
git add resources/js/components/BlockEditor/BlockTypePanel.vue
git commit -m "feat: redesign block type palette with pill headers and color-coded icons"
```

---

## Task 5: BlockCanvas.vue — dark background and wireframe card styles

**Files:**
- Modify: `resources/js/components/BlockEditor/BlockCanvas.vue`

### Step 1: Update the outer container

Find the outer container div (line 3):
```html
<div class="flex-1 flex flex-col overflow-hidden border-r bg-background">
```
Replace with:
```html
<div class="flex-1 flex flex-col overflow-hidden border-r border-white/8">
```

### Step 2: Add the canvas toolbar

Find the existing `<div class="px-3 py-2 border-b shrink-0">` header (lines 4–6). Replace it with:

```html
<!-- Toolbar: breadcrumb + live preview toggle -->
<div class="h-9 border-b border-white/8 bg-black/20 px-3 flex items-center justify-between shrink-0">
  <!-- Breadcrumb -->
  <div class="flex items-center gap-1 text-xs text-white/40 overflow-hidden">
    <template v-if="selectedPath.length">
      <span
        v-for="(crumb, i) in selectedPath"
        :key="crumb.id"
        class="flex items-center gap-1 shrink-0"
      >
        <ChevronRight v-if="i > 0" class="w-3 h-3 text-white/20" />
        <button
          type="button"
          class="hover:text-white/70 transition-colors truncate max-w-[80px]"
          @click="$emit('select', crumb.id)"
        >
          {{ crumb.label }}
        </button>
      </span>
    </template>
    <span v-else class="text-white/25 italic">No block selected</span>
  </div>

  <!-- Live preview toggle -->
  <button
    type="button"
    class="flex items-center gap-1.5 text-xs px-2 py-1 rounded-md border transition-colors"
    :class="previewMode
      ? 'bg-primary/20 border-primary/40 text-primary'
      : 'bg-white/8 border-white/12 text-white/60 hover:text-white/80 hover:bg-white/12'"
    @click="previewMode = !previewMode"
  >
    <Eye class="w-3.5 h-3.5" />
    <span>Preview</span>
  </button>
</div>
```

### Step 3: Replace the scroll area content

Find `<div class="relative flex-1 overflow-y-auto scrollbar-hidden">` and its contents. Replace the entire scroll area with:

```html
<div class="relative flex-1 overflow-y-auto scrollbar-hidden">

  <!-- Live preview mode -->
  <div v-if="previewMode" class="p-6 bg-white min-h-full">
    <BlockRenderer :blocks="blocks" />
  </div>

  <!-- Wireframe canvas mode -->
  <template v-else>
    <!-- Empty state -->
    <div
      v-if="draggableBlocks.length === 0"
      class="absolute inset-0 flex items-center justify-center pointer-events-none"
    >
      <div class="text-center">
        <LayoutTemplate class="w-8 h-8 mx-auto mb-3 text-white/20" />
        <p class="text-sm text-white/40">Drag a block from the left to get started</p>
        <p class="text-xs text-white/25 mt-1">or click a block type to add it</p>
      </div>
    </div>

    <VueDraggable
      v-model="draggableBlocks"
      tag="div"
      class="p-4 space-y-2 min-h-full editor-canvas-bg"
      :group="{ name: 'canvas' }"
      :animation="150"
      handle=".block-drag-handle"
      ghost-class="opacity-40"
      @add="onAdd"
    >
      <div
        v-for="block in draggableBlocks"
        :key="block.id"
        :id="block.customId || `block-${block.id}`"
        class="group flex items-stretch rounded-lg border transition-colors cursor-pointer"
        :class="block.id === selectedId
          ? 'border-primary ring-1 ring-primary bg-primary/8'
          : 'border-white/10 bg-white/4 hover:border-white/20'"
        @click="$emit('select', block.id)"
      >
        <!-- Custom CSS injection -->
        <component v-if="block.customCss" :is="'style'">
          #{{ block.customId ? CSS.escape(block.customId) : 'block-' + block.id }} { {{ sanitizeCss(block.customCss) }} }
        </component>

        <!-- Drag handle -->
        <div
          class="block-drag-handle shrink-0 w-7 flex items-center justify-center border-r border-transparent group-hover:border-white/8 cursor-grab active:cursor-grabbing text-white/20 group-hover:text-white/40 transition-colors"
          @click.stop
        >
          <GripVertical class="w-3.5 h-3.5" />
        </div>

        <!-- Content area -->
        <div class="flex-1 min-w-0">

          <EditorContainerBlock
            v-if="block.type === 'container'"
            :block="block"
            :selected-id="selectedId"
            @select="$emit('select', $event)"
            @update-children="$emit('update-children', $event)"
          />
          <EditorSectionBlock
            v-else-if="block.type === 'section'"
            :block="block"
            :selected-id="selectedId"
            @select="$emit('select', $event)"
            @update-children="$emit('update-children', $event)"
          />
          <EditorLoopBlock
            v-else-if="block.type === 'loop'"
            :block="block"
            :selected-id="selectedId"
            @select="$emit('select', $event)"
            @update-children="$emit('update-children', $event)"
          />

          <!-- Spacer -->
          <div v-else-if="block.type === 'spacer'" class="flex flex-col">
            <div class="px-3 py-1.5 border-b border-white/8 bg-white/3 flex items-center">
              <span class="text-[10px] font-semibold uppercase tracking-wider text-white/40">
                {{ block.blockName || 'Spacer' }}
              </span>
            </div>
            <div class="px-3 py-2">
              <div
                class="w-full flex items-center justify-center bg-white/5 border border-dashed border-white/15 rounded text-xs text-white/40 select-none"
                :style="{ height: `${(block.data?.height?.default ?? 8) * 4}px` }"
              >
                h-{{ block.data?.height?.default ?? 8 }}
              </div>
            </div>
          </div>

          <!-- Regular block -->
          <div v-else class="flex flex-col min-h-[2.5rem]">
            <div class="px-3 py-1.5 border-b border-white/8 bg-white/3 flex items-center gap-2">
              <span class="text-[10px] font-semibold uppercase tracking-wider text-white/40">
                {{ block.blockName || LABELS[block.type] || block.type }}
              </span>
              <span v-if="block.blockName" class="text-[10px] text-white/25 uppercase tracking-wider">
                {{ LABELS[block.type] || block.type }}
              </span>
            </div>
            <div class="px-3 py-2 opacity-60">
              <span v-if="isEmptyBlock(block)" class="text-xs text-white/30 italic">empty</span>
              <component
                v-else
                :is="BLOCK_MAP[block.type]"
                :block="block"
                class="pointer-events-none"
              />
            </div>
          </div>

        </div>
      </div>
    </VueDraggable>
  </template>
</div>
```

### Step 4: Update the script

In `<script setup>`, add new imports at the top:

```js
import { computed, ref, watch } from 'vue'
import { VueDraggable }  from 'vue-draggable-plus'
import { GripVertical, Eye, ChevronRight, LayoutTemplate } from 'lucide-vue-next'
import BlockRenderer from '@/components/BlockRenderer.vue'
```

Add new props:

```js
const props = defineProps({
  blocks:     { type: Array,  default: () => [] },
  selectedId: { type: String, default: null },
})
```

Add new refs and computed after the existing `draggableBlocks` computed:

```js
// Live preview toggle
const previewMode = ref(false)

// Breadcrumb: find the path from root to selectedId
function findPath(blocks, targetId, path = []) {
  for (const b of blocks) {
    const crumb = { id: b.id, label: b.blockName || LABELS[b.type] || b.type }
    const next = [...path, crumb]
    if (b.id === targetId) return next
    if (b.children?.length) {
      const found = findPath(b.children, targetId, next)
      if (found) return found
    }
  }
  return null
}

const selectedPath = computed(() => {
  if (!props.selectedId) return []
  return findPath(props.blocks, props.selectedId) ?? []
})
```

Update `defineEmits` to include `'select'`:

```js
const emit = defineEmits(['select', 'reorder', 'update-children'])
```

### Step 5: Build and verify

```bash
npm run build
```

Open the block editor:
- Canvas should show the dark dot-grid background
- Blocks should appear as subtle dark wireframe cards
- Selected block should have a primary-color ring with a slight blue tint
- The toolbar should show a breadcrumb (click a block to see the path) and a "Preview" button
- Clicking "Preview" should show the blocks rendered in a white `bg-white` container exactly as the frontend would

### Step 6: Commit

```bash
git add resources/js/components/BlockEditor/BlockCanvas.vue
git commit -m "feat: dark wireframe canvas with dot-grid, breadcrumb toolbar, and live preview toggle"
```

---

## Task 6: BlockLayers.vue — inject Tabs into settings panel

**Files:**
- Modify: `resources/js/components/BlockEditor/BlockLayers.vue`

### Step 1: Import Tabs

At the top of `<script setup>`, add the Tabs import:

```js
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/components/ui/tabs'
```

### Step 2: Add tab state and constants to script

After the existing imports, add:

```js
const settingsTab = ref('content')

// Reset tab to content whenever the selected block changes
watch(() => props.selectedId, () => {
  settingsTab.value = DEFAULT_TAB[props.selectedBlock?.type] ?? 'content'
})

// Blocks that have a Style tab
const STYLE_BLOCKS = new Set([
  'paragraph', 'heading', 'image', 'quote', 'gallery', 'video',
  'cta', 'container', 'section', 'spacer', 'divider', 'loop',
  'component', 'post-featured-image', 'archive-loop',
])

// Blocks where Style should be the default tab
const DEFAULT_TAB = {
  divider: 'style',
  spacer:  'style',
}
```

### Step 3: Replace the settings panel HTML

Find the entire settings panel section (from `<!-- Settings panel -->` to `</div></div>` near the end of the template). Replace it with:

```html
<!-- Settings panel -->
<div class="flex-1 flex flex-col border-t border-white/8 overflow-hidden">

  <div v-if="!selectedBlock" class="flex-1 flex items-center justify-center">
    <p class="text-xs text-muted-foreground text-center">Select a block<br>to edit its settings</p>
  </div>

  <div v-else-if="selectedBlock.type === 'html' && !isAdmin" class="p-4">
    <div class="rounded-md border border-dashed p-4 text-center">
      <p class="text-xs text-muted-foreground">HTML blocks are admin-only and cannot be edited here.</p>
    </div>
  </div>

  <template v-else>
    <Tabs v-model="settingsTab" class="flex flex-col flex-1 overflow-hidden">
      <TabsList class="shrink-0 w-full rounded-none border-b border-white/8 bg-black/20 px-2 h-9 gap-0 justify-start">
        <TabsTrigger value="content" class="text-xs h-full rounded-none border-b-2 border-transparent data-[state=active]:border-primary data-[state=active]:bg-transparent data-[state=active]:text-foreground px-3">
          Content
        </TabsTrigger>
        <TabsTrigger
          v-if="STYLE_BLOCKS.has(selectedBlock.type)"
          value="style"
          class="text-xs h-full rounded-none border-b-2 border-transparent data-[state=active]:border-primary data-[state=active]:bg-transparent data-[state=active]:text-foreground px-3"
        >
          Style
        </TabsTrigger>
        <TabsTrigger value="advanced" class="text-xs h-full rounded-none border-b-2 border-transparent data-[state=active]:border-primary data-[state=active]:bg-transparent data-[state=active]:text-foreground px-3">
          Advanced
        </TabsTrigger>
      </TabsList>

      <div class="flex-1 overflow-y-auto scrollbar-hidden">
        <div class="p-3">

          <!-- Content + Style tabs: delegate to block's settings component -->
          <template v-if="settingsTab === 'content' || settingsTab === 'style'">
            <component
              :is="settingsComponent"
              :block="selectedBlock"
              :tab="settingsTab"
              :is-admin="isAdmin"
              :meta="meta"
              :loop-fields="loopFields"
              :available-fields="availableFields"
              @update="$emit('update', $event)"
            />
          </template>

          <!-- Advanced tab -->
          <template v-else-if="settingsTab === 'advanced'">
            <AdvancedSettings
              :block="selectedBlock"
              @update="$emit('update', $event)"
            />
            <ConditionSettings
              v-if="loopFields.length"
              :block="selectedBlock"
              :loop-fields="loopFields"
              @update="$emit('update', $event)"
            />
          </template>

        </div>
      </div>
    </Tabs>
  </template>

</div>
```

### Step 4: Update the Layers panel header to match dark chrome

Find the layers header div (around line 7):
```html
<div class="px-3 py-2 border-b shrink-0 flex items-center justify-between">
  <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Layers</p>
```
Replace with:
```html
<div class="px-3 py-2 border-b border-white/8 shrink-0 flex items-center justify-between bg-black/20">
  <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Layers</p>
```

### Step 5: Build and verify

```bash
npm run build
```

Open the block editor, select a block. The settings area should now show a tabbed interface with Content / Style (if applicable) / Advanced tabs. Advanced tab should show the existing AdvancedSettings form.

### Step 6: Commit

```bash
git add resources/js/components/BlockEditor/BlockLayers.vue
git commit -m "feat: add Content/Style/Advanced tabs to block settings panel"
```

---

## Task 7: Settings split — Content blocks

Split the `tab` prop into each content-type settings component. Each component gets a `tab` prop; content fields are wrapped in `v-show="!tab || tab === 'content'"`, style fields in `v-show="!tab || tab === 'style'"`.

**Files:**
- Modify: `resources/js/components/BlockEditor/blocks/ParagraphSettings.vue`
- Modify: `resources/js/components/BlockEditor/blocks/HeadingSettings.vue`
- Modify: `resources/js/components/BlockEditor/blocks/ImageSettings.vue`
- Modify: `resources/js/components/BlockEditor/blocks/QuoteSettings.vue`
- Modify: `resources/js/components/BlockEditor/blocks/GallerySettings.vue`
- Modify: `resources/js/components/BlockEditor/blocks/VideoSettings.vue`
- Modify: `resources/js/components/BlockEditor/blocks/CtaSettings.vue`
- Modify: `resources/js/components/BlockEditor/blocks/CodeSettings.vue`
- Modify: `resources/js/components/BlockEditor/blocks/HtmlSettings.vue`

### Step 1: Add `tab` prop to each component

In each component's `defineProps`, add:
```js
tab: { type: String, default: null },  // 'content' | 'style' | null (show all)
```

### Step 2: Apply the Content / Style tab split per component

Read each file carefully and wrap its existing fields into two `<div v-show>` sections according to this mapping:

**ParagraphSettings.vue**
- Content: TipTap / content field, dynamic field binding
- Style: text alignment, font size (if present)

**HeadingSettings.vue**
- Content: text field, heading level (h1–h6), tag selector
- Style: text alignment, font size (if present)

**ImageSettings.vue**
- Content: media picker, alt text, caption, URL field
- Style: max-width, aspect ratio, object-fit (if present)

**QuoteSettings.vue**
- Content: quote text, attribution
- Style: alignment (if present)

**GallerySettings.vue**
- Content: gallery items list (add/remove images)
- Style: columns, gap, aspect ratio

**VideoSettings.vue**
- Content: URL, caption
- Style: aspect ratio, max-width (if present)

**CtaSettings.vue**
- Content: headline, body text, button label, button URL
- Style: alignment, button variant/style (if present)

**CodeSettings.vue** — NO style tab (all fields stay in content, no style section needed)

**HtmlSettings.vue** — NO style tab (all fields stay in content, no style section needed)

### Pattern to apply to each component:

```html
<template>
  <!-- Content tab fields -->
  <div v-show="!tab || tab === 'content'" class="space-y-3">
    <!-- ... existing content fields ... -->
  </div>

  <!-- Style tab fields (only for blocks that have style fields) -->
  <div v-show="!tab || tab === 'style'" class="space-y-3">
    <!-- ... existing style/layout fields ... -->
  </div>
</template>
```

For components with NO style fields (Code, HTML), simply keep all fields in the content div and add no style div.

### Step 3: Build and verify

```bash
npm run build
```

Select a Paragraph block — Content tab shows text editor, Style tab (if any) shows alignment. Select a Code block — only Content tab available (Style tab hidden by BlockLayers.vue since `code` is not in `STYLE_BLOCKS`).

### Step 4: Commit

```bash
git add resources/js/components/BlockEditor/blocks/ParagraphSettings.vue \
        resources/js/components/BlockEditor/blocks/HeadingSettings.vue \
        resources/js/components/BlockEditor/blocks/ImageSettings.vue \
        resources/js/components/BlockEditor/blocks/QuoteSettings.vue \
        resources/js/components/BlockEditor/blocks/GallerySettings.vue \
        resources/js/components/BlockEditor/blocks/VideoSettings.vue \
        resources/js/components/BlockEditor/blocks/CtaSettings.vue \
        resources/js/components/BlockEditor/blocks/CodeSettings.vue \
        resources/js/components/BlockEditor/blocks/HtmlSettings.vue
git commit -m "feat: split content block settings into Content/Style tabs"
```

---

## Task 8: Settings split — Layout blocks

**Files:**
- Modify: `resources/js/components/BlockEditor/blocks/ContainerSettings.vue`
- Modify: `resources/js/components/BlockEditor/blocks/SectionSettings.vue`
- Modify: `resources/js/components/BlockEditor/blocks/SpacerSettings.vue`
- Modify: `resources/js/components/BlockEditor/blocks/DividerSettings.vue`

### Step 1: Add `tab` prop to each component (same as Task 7)

### Step 2: Apply the Content / Style tab split

**ContainerSettings.vue**
- Content: Mode toggle (Flex / Grid)
- Style: direction, wrap, justify, align (flex), columns (grid), gap, max-width, padding

**SectionSettings.vue**
- Content: _(nothing — section has no content fields, only layout/visual)_ → treat entire form as style
- Style: bg type, bg color, bg image, bg gradient, full-width, inner max-width, padding, min-height

Since SectionSettings has no content fields, wrap everything in the style div. The `DEFAULT_TAB` in BlockLayers.vue already sets `section` to default to... actually section isn't in `DEFAULT_TAB`. Add `'section': 'style'` to `DEFAULT_TAB` in BlockLayers.vue.

**SpacerSettings.vue**
- Content: _(nothing)_
- Style: height (responsive breakpoints) — entire form goes in style div
  _(DEFAULT_TAB already has `spacer: 'style'`)_

**DividerSettings.vue**
- Content: _(nothing — divider is purely visual)_
- Style: style (line/dots/none), color, thickness — entire form goes in style div
  _(DEFAULT_TAB already has `divider: 'style'`)_

### Step 3: Update DEFAULT_TAB in BlockLayers.vue

Also add `section` to `DEFAULT_TAB`:

```js
const DEFAULT_TAB = {
  divider: 'style',
  spacer:  'style',
  section: 'style',
}
```

### Step 4: Build and verify

```bash
npm run build
```

Select a Container block — Content tab shows only the Flex/Grid mode toggle; Style tab shows all the layout fields. Select a Section block — defaults directly to the Style tab.

### Step 5: Commit

```bash
git add resources/js/components/BlockEditor/blocks/ContainerSettings.vue \
        resources/js/components/BlockEditor/blocks/SectionSettings.vue \
        resources/js/components/BlockEditor/blocks/SpacerSettings.vue \
        resources/js/components/BlockEditor/blocks/DividerSettings.vue \
        resources/js/components/BlockEditor/BlockLayers.vue
git commit -m "feat: split layout block settings into Content/Style tabs"
```

---

## Task 9: Settings split — Dynamic and Post/Archive blocks

**Files:**
- Modify: `resources/js/components/BlockEditor/blocks/LoopSettings.vue`
- Modify: `resources/js/components/BlockEditor/blocks/ComponentSettings.vue`
- Modify: `resources/js/components/BlockEditor/blocks/PostFeaturedImageSettings.vue`
- Modify: `resources/js/components/BlockEditor/blocks/PostTitleSettings.vue`
- Modify: `resources/js/components/BlockEditor/blocks/PostBodySettings.vue`
- Modify: `resources/js/components/BlockEditor/blocks/PostMetaSettings.vue`
- Modify: `resources/js/components/BlockEditor/blocks/PostAuthorSettings.vue`
- Modify: `resources/js/components/BlockEditor/blocks/PostTaxonomySettings.vue`
- Modify: `resources/js/components/BlockEditor/blocks/ArchiveTitleSettings.vue`
- Modify: `resources/js/components/BlockEditor/blocks/SearchSettings.vue`

### Step 1: Add `tab` prop to each component

### Step 2: Apply the Content / Style tab split

**LoopSettings.vue**
- Content: source, filters, sort, limit, offset
- Style: columns, gap

**ComponentSettings.vue**
- Content: component type, limit, offset, order, filters (category/tag)
- Style: _(none — stays all in content)_

**PostFeaturedImageSettings.vue**
- Content: _(none — no content data)_
- Style: max-width, aspect ratio

**PostTitleSettings.vue**
- Content: tag selector (h1/h2/etc.)
- Style: _(none)_

**PostBodySettings.vue** — NO split (no settings at all or minimal)

**PostMetaSettings.vue**
- Content: date toggle, author toggle, readTime toggle
- Style: _(none)_

**PostAuthorSettings.vue**
- Content: showAvatar toggle
- Style: _(none)_

**PostTaxonomySettings.vue**
- Content: showCategories toggle, showTags toggle
- Style: _(none)_

**ArchiveTitleSettings.vue**
- Content: tag selector
- Style: _(none)_

**SearchSettings.vue**
- Content: placeholder text, button label, scope
- Style: _(none)_

For components with no style fields, put all content in the content div only.

### Step 3: Build and verify

```bash
npm run build
```

Select a Loop block — Content tab shows source/filters/sort/limit; Style tab shows columns/gap. Select PostFeaturedImage — defaults to Style tab (only style fields exist).

Add `'post-featured-image': 'style'` and `'archive-loop': 'style'` to `DEFAULT_TAB` in `BlockLayers.vue` for blocks where all fields are style-only.

### Step 4: Commit

```bash
git add resources/js/components/BlockEditor/blocks/LoopSettings.vue \
        resources/js/components/BlockEditor/blocks/ComponentSettings.vue \
        resources/js/components/BlockEditor/blocks/PostFeaturedImageSettings.vue \
        resources/js/components/BlockEditor/blocks/PostTitleSettings.vue \
        resources/js/components/BlockEditor/blocks/PostBodySettings.vue \
        resources/js/components/BlockEditor/blocks/PostMetaSettings.vue \
        resources/js/components/BlockEditor/blocks/PostAuthorSettings.vue \
        resources/js/components/BlockEditor/blocks/PostTaxonomySettings.vue \
        resources/js/components/BlockEditor/blocks/ArchiveTitleSettings.vue \
        resources/js/components/BlockEditor/blocks/SearchSettings.vue \
        resources/js/components/BlockEditor/BlockLayers.vue
git commit -m "feat: split dynamic and post/archive block settings into Content/Style tabs"
```

---

## Task 10: Final build and push

### Step 1: Run the full test suite

```bash
cd /c/Users/mariu/Herd/lambda-cms && php artisan test
```

Expected: all tests pass (the editor changes are purely frontend).

### Step 2: Production build

```bash
npm run build
```

Expected: clean build. Pre-existing chunk size warning is fine.

### Step 3: Push

```bash
git push origin master
```
