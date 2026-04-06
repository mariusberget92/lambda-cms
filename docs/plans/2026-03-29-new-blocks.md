# New Block Types Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add 5 new block types (link, accordion, tabs, embed, pagination) plus icon settings on the heading block, following the exact patterns already established in the codebase.

**Architecture:** Each block consists of two files — a runtime renderer in `resources/js/components/Blocks/` and settings in `resources/js/Components/BlockEditor/blocks/`. Children-based blocks (link, accordion, tabs) also get an `EditorXxxBlock.vue` canvas wireframe component. The children architecture mirrors ContainerBlock/EditorContainerBlock. Accordion and tabs have hidden child types (`accordion-item`, `tab-item`) that only live inside their parent.

**Tech Stack:** Vue 3, Tailwind CSS 4, lucide-vue-next, @inertiajs/vue3, existing DynamicField/SelectBox/NumberInput/SpacingControl components.

---

## Task 1: Register new types in BlockTypePanel.vue

**Files:**
- Modify: `resources/js/Components/BlockEditor/BlockTypePanel.vue`

This is the central registry for the palette, default data, and cloning logic.

### Step 1: Add new imports at the top of `<script setup>`

Open the file. Find the lucide import block (lines 41–65). Add these icons to the destructured import:

```js
import {
  // ... existing imports ...
  Link,
  ChevronDown,
  Layers,
  LayoutPanelTop,
  Code2 as EmbedIcon,
  PlayCircle,
  ChevronLeft,
  ChevronRight as ChevronRightIcon,
} from 'lucide-vue-next'
```

Use `Link` for link block, `Layers` for accordion, `LayoutPanelTop` for tabs, `PlayCircle` for embed, `ChevronLeft`/`ChevronRightIcon` for pagination.

### Step 2: Add new entries to `ALL_TYPES` array

Find the `ALL_TYPES` array (around line 71). Add these entries:

**In the Content group** (after `video`):
```js
{ type: 'accordion',      label: 'Accordion', icon: Layers,           group: 'Content' },
{ type: 'accordion-item', label: 'Acc. Item', icon: ChevronDown,      group: 'Content', hiddenFromPalette: true },
{ type: 'tabs',           label: 'Tabs',       icon: LayoutPanelTop,  group: 'Content' },
{ type: 'tab-item',       label: 'Tab',        icon: LayoutPanelTop,  group: 'Content', hiddenFromPalette: true },
{ type: 'embed',          label: 'Embed',      icon: PlayCircle,      group: 'Content' },
```

**In the Interactive group** (after `component`):
```js
{ type: 'link',       label: 'Link',       icon: Link,               group: 'Interactive' },
{ type: 'pagination', label: 'Pagination', icon: ChevronRightIcon,   group: 'Interactive' },
```

### Step 3: Update `visibleGroups` filter to hide palette-hidden types

Find the `visibleGroups` computed (around line 115). Change the filter line from:
```js
const visible = ALL_TYPES.filter(t => !t.adminOnly || props.isAdmin)
```
to:
```js
const visible = ALL_TYPES.filter(t => (!t.adminOnly || props.isAdmin) && !t.hiddenFromPalette)
```

### Step 4: Add default data for new block types

Find `DEFAULT_DATA` (around line 122). Add after `search`:

```js
link: {
  url: '',
  target: '_self',
  rel: '',
  icon: { name: '', position: 'left', size: 'md', color: 'inherit' },
},
accordion: {
  defaultState: 'first-open',
  borderStyle: 'bordered',
},
'accordion-item': {
  title: 'Item title',
},
tabs: {
  alignment: 'left',
  tabStyle: 'underline',
},
'tab-item': {
  label: 'Tab',
},
embed: {
  url: '',
  caption: '',
  aspectRatio: '16/9',
  maxWidth: '',
},
pagination: {
  pageParam: 'page',
  style: 'prev-next',
  prevLabel: '← Previous',
  nextLabel: 'Next →',
  alignment: 'center',
  buttonStyle: 'outline',
},
```

### Step 5: Update `cloneBlock` to handle children + auto-populate

Find `cloneBlock` (around line 174). Replace the children check from:
```js
...(['container', 'section', 'loop', 'archive-loop'].includes(typeDef.type)
  ? { children: [] }
  : {}),
```
to:
```js
...(['container', 'section', 'loop', 'archive-loop', 'link', 'accordion', 'tabs', 'accordion-item', 'tab-item'].includes(typeDef.type)
  ? { children: [] }
  : {}),
```

Then, **after** the `return { id, type, data, ... }` but before the closing `}` of `cloneBlock`, add auto-population for accordion and tabs. Replace the function body with:

```js
function cloneBlock(typeDef) {
  const makeId = () => typeof crypto !== 'undefined' && crypto.randomUUID
    ? crypto.randomUUID()
    : Math.random().toString(36).slice(2) + Date.now().toString(36)

  const id = makeId()
  const block = {
    id,
    type: typeDef.type,
    data: { ...(DEFAULT_DATA[typeDef.type] ?? {}) },
    customId: '',
    customClasses: '',
    customCss: '',
    fontFamily: '',
    ...(['container', 'section', 'loop', 'archive-loop', 'link', 'accordion', 'tabs', 'accordion-item', 'tab-item'].includes(typeDef.type)
      ? { children: [] }
      : {}),
  }

  // Auto-populate accordion with 3 items
  if (typeDef.type === 'accordion') {
    block.children = [1, 2, 3].map(i => ({
      id: makeId(),
      type: 'accordion-item',
      data: { title: `Item ${i}` },
      customId: '', customClasses: '', customCss: '', fontFamily: '',
      children: [],
    }))
  }

  // Auto-populate tabs with 2 tab items
  if (typeDef.type === 'tabs') {
    block.children = [1, 2].map(i => ({
      id: makeId(),
      type: 'tab-item',
      data: { label: `Tab ${i}` },
      customId: '', customClasses: '', customCss: '', fontFamily: '',
      children: [],
    }))
  }

  return block
}
```

### Step 6: Build and verify

```bash
cd C:\Users\mariu\Herd\lambda-cms && npm run build
```

Expected: clean build (pre-existing chunk size warning is OK).

### Step 7: Commit

```bash
cd C:\Users\mariu\Herd\lambda-cms
git add resources/js/Components/BlockEditor/BlockTypePanel.vue
git commit -m "feat: register link, accordion, tabs, embed, pagination block types in palette"
```

---

## Task 2: Update global registries (LABELS, BLOCK_MAP, STYLE_BLOCKS)

**Files:**
- Modify: `resources/js/Components/BlockEditor/BlockCanvas.vue`
- Modify: `resources/js/Components/BlockEditor/BlockLayers.vue`
- Modify: `resources/js/components/BlockRenderer.vue`
- Modify: `resources/js/Components/BlockEditor/EditorLoopBlock.vue`

### Step 1: BlockCanvas.vue — update LABELS

Find the `LABELS` constant (around line 200). Add new entries:

```js
const LABELS = {
  paragraph: 'Paragraph', heading: 'Heading', image: 'Image',
  quote: 'Quote', code: 'Code', gallery: 'Gallery', video: 'Video',
  divider: 'Divider', cta: 'CTA', html: 'HTML', component: 'Component',
  container: 'Container', section: 'Section', spacer: 'Spacer', loop: 'Loop',
  link: 'Link', accordion: 'Accordion', 'accordion-item': 'Acc. Item',
  tabs: 'Tabs', 'tab-item': 'Tab', embed: 'Embed', pagination: 'Pagination',
}
```

### Step 2: BlockCanvas.vue — update BLOCK_MAP

Find the `BLOCK_MAP` constant (around line 186). It will be extended in Task 4–9 as each block is created. For now, leave it but note it needs additions later.

### Step 3: BlockLayers.vue — update LABELS

Find `LABELS` in BlockLayers.vue (around line 225). Add the same new entries:

```js
link: 'Link', accordion: 'Accordion', 'accordion-item': 'Accordion Item',
tabs: 'Tabs', 'tab-item': 'Tab', embed: 'Embed', pagination: 'Pagination',
```

### Step 4: BlockLayers.vue — update STYLE_BLOCKS

Find `STYLE_BLOCKS` (around line 211). Add new types that have a Style tab:

```js
const STYLE_BLOCKS = new Set([
  'container', 'section', 'spacer', 'divider', 'loop',
  'post-featured-image', 'archive-loop',
  'link', 'accordion', 'tabs', 'embed', 'pagination', 'heading',
])
```

Note: `heading` is added here since Task 5 will add a Style tab to HeadingSettings.

### Step 5: EditorLoopBlock.vue — update LABELS and NESTABLE

Find `NESTABLE` (line 108) and `LABELS` (line 111) in EditorLoopBlock.vue.

Update `NESTABLE`:
```js
const NESTABLE = ['container', 'section', 'loop', 'archive-loop', 'link', 'accordion', 'tabs', 'accordion-item', 'tab-item']
```

Update `LABELS` to include the new types:
```js
const LABELS = {
  paragraph: 'Paragraph', heading: 'Heading', image: 'Image',
  quote: 'Quote', code: 'Code', gallery: 'Gallery', video: 'Video',
  divider: 'Divider', cta: 'CTA', html: 'HTML', component: 'Component',
  container: 'Container', section: 'Section', spacer: 'Spacer', loop: 'Loop',
  'archive-loop': 'Archive Loop',
  link: 'Link', accordion: 'Accordion', 'accordion-item': 'Acc. Item',
  tabs: 'Tabs', 'tab-item': 'Tab', embed: 'Embed', pagination: 'Pagination',
}
```

### Step 6: Build and verify

```bash
npm run build
```

### Step 7: Commit

```bash
cd C:\Users\mariu\Herd\lambda-cms
git add resources/js/Components/BlockEditor/BlockCanvas.vue
git add resources/js/Components/BlockEditor/BlockLayers.vue
git add resources/js/components/BlockRenderer.vue
git add resources/js/Components/BlockEditor/EditorLoopBlock.vue
git commit -m "feat: update LABELS, STYLE_BLOCKS, and NESTABLE for new block types"
```

---

## Task 3: Create IconSettings.vue shared component

**Files:**
- Create: `resources/js/Components/BlockEditor/blocks/IconSettings.vue`

### Step 1: Create the file

```vue
<!-- resources/js/Components/BlockEditor/blocks/IconSettings.vue -->
<template>
  <div class="space-y-3">
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Icon</label>
      <SelectBox
        :model-value="icon.name || ''"
        :searchable="true"
        placeholder="No icon"
        :data="[{ value: '', label: 'None' }, ...ICON_LIST.map(n => ({ value: n, label: n }))]"
        @update:model-value="v => update('name', v)"
      />
    </div>

    <template v-if="icon.name">
      <div>
        <label class="text-xs font-medium text-muted-foreground block mb-1">Position</label>
        <div class="flex gap-1">
          <button
            v-for="pos in ['left', 'right']"
            :key="pos"
            type="button"
            class="flex-1 px-2 py-1 text-xs rounded border capitalize transition-colors"
            :class="icon.position === pos
              ? 'bg-primary text-primary-foreground border-primary'
              : 'bg-background border-border hover:border-muted-foreground'"
            @click="update('position', pos)"
          >{{ pos }}</button>
        </div>
      </div>

      <div>
        <label class="text-xs font-medium text-muted-foreground block mb-1">Size</label>
        <div class="flex gap-1">
          <button
            v-for="sz in ['xs', 'sm', 'md', 'lg', 'xl']"
            :key="sz"
            type="button"
            class="flex-1 px-1 py-1 text-xs rounded border uppercase transition-colors"
            :class="icon.size === sz
              ? 'bg-primary text-primary-foreground border-primary'
              : 'bg-background border-border hover:border-muted-foreground'"
            @click="update('size', sz)"
          >{{ sz }}</button>
        </div>
      </div>

      <div>
        <label class="text-xs font-medium text-muted-foreground block mb-1">Color</label>
        <div class="flex items-center gap-2">
          <button
            type="button"
            class="px-2 py-1 text-xs rounded border transition-colors"
            :class="icon.color === 'inherit'
              ? 'bg-primary text-primary-foreground border-primary'
              : 'bg-background border-border'"
            @click="update('color', 'inherit')"
          >Inherit</button>
          <input
            type="color"
            :value="icon.color === 'inherit' ? '#000000' : icon.color"
            class="h-7 w-12 cursor-pointer rounded border border-border"
            @input="update('color', $event.target.value)"
          />
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import SelectBox from '@/Components/SelectBox.vue'

const props = defineProps({
  block: { type: Object, required: true },
})
const emit = defineEmits(['update'])

const icon = defineModel('icon', {
  get: () => props.block.data?.icon ?? { name: '', position: 'left', size: 'md', color: 'inherit' },
})

// Computed helper so template reads cleanly
import { computed } from 'vue'
const icon = computed(() => props.block.data?.icon ?? { name: '', position: 'left', size: 'md', color: 'inherit' })

function update(key, value) {
  emit('update', {
    id: props.block.id,
    data: { icon: { ...icon.value, [key]: value } },
  })
}

// Curated list of ~70 common Lucide icon names (PascalCase stripped to kebab for display)
const ICON_LIST = [
  'ArrowRight', 'ArrowLeft', 'ArrowUp', 'ArrowDown',
  'ArrowUpRight', 'ArrowDownRight', 'ExternalLink', 'Link',
  'ChevronRight', 'ChevronLeft', 'ChevronDown', 'ChevronUp',
  'Star', 'Heart', 'Bookmark', 'Share2', 'Download', 'Upload',
  'Mail', 'Phone', 'MapPin', 'Globe', 'Clock', 'Calendar',
  'User', 'Users', 'UserCircle', 'UserPlus',
  'Home', 'Building', 'Building2', 'ShoppingCart', 'ShoppingBag',
  'Search', 'Filter', 'Settings', 'Sliders', 'MoreHorizontal',
  'Plus', 'Minus', 'X', 'Check', 'CheckCircle', 'XCircle',
  'Info', 'AlertCircle', 'AlertTriangle', 'HelpCircle',
  'Zap', 'Flame', 'Shield', 'Lock', 'Unlock', 'Key',
  'Image', 'Video', 'Music', 'Play', 'Pause', 'Volume2',
  'FileText', 'File', 'Folder', 'Tag', 'Layers', 'Layout',
  'Code', 'Github', 'Twitter', 'Facebook', 'Instagram', 'Linkedin', 'Youtube',
  'Send', 'MessageCircle', 'MessageSquare', 'Bell', 'BellRing',
  'Cpu', 'Server', 'Database', 'Cloud', 'Wifi',
  'Sun', 'Moon', 'Leaf', 'Smile',
]
</script>
```

Note: The `icon` computed and `icon` defineModel conflict — remove the `defineModel` line and keep only the `computed`. Final script:

```vue
<script setup>
import { computed } from 'vue'
import SelectBox from '@/Components/SelectBox.vue'

const props = defineProps({
  block: { type: Object, required: true },
})
const emit = defineEmits(['update'])

const icon = computed(() => props.block.data?.icon ?? { name: '', position: 'left', size: 'md', color: 'inherit' })

function update(key, value) {
  emit('update', {
    id: props.block.id,
    data: { icon: { ...icon.value, [key]: value } },
  })
}

const ICON_LIST = [
  'ArrowRight', 'ArrowLeft', 'ArrowUp', 'ArrowDown',
  'ArrowUpRight', 'ArrowDownRight', 'ExternalLink', 'Link',
  'ChevronRight', 'ChevronLeft', 'ChevronDown', 'ChevronUp',
  'Star', 'Heart', 'Bookmark', 'Share2', 'Download', 'Upload',
  'Mail', 'Phone', 'MapPin', 'Globe', 'Clock', 'Calendar',
  'User', 'Users', 'UserCircle', 'UserPlus',
  'Home', 'Building', 'Building2', 'ShoppingCart', 'ShoppingBag',
  'Search', 'Filter', 'Settings', 'Sliders', 'MoreHorizontal',
  'Plus', 'Minus', 'X', 'Check', 'CheckCircle', 'XCircle',
  'Info', 'AlertCircle', 'AlertTriangle', 'HelpCircle',
  'Zap', 'Flame', 'Shield', 'Lock', 'Unlock', 'Key',
  'Image', 'Video', 'Music', 'Play', 'Pause', 'Volume2',
  'FileText', 'File', 'Folder', 'Tag', 'Layers', 'Layout',
  'Code', 'Github', 'Twitter', 'Facebook', 'Instagram', 'Linkedin', 'Youtube',
  'Send', 'MessageCircle', 'MessageSquare', 'Bell', 'BellRing',
  'Cpu', 'Server', 'Database', 'Cloud', 'Wifi',
  'Sun', 'Moon', 'Leaf', 'Smile',
]
</script>
```

### Step 2: Create the icon resolver composable

Create `resources/js/composables/useIconResolver.js`:

```js
// Resolves a Lucide icon name string to a Vue component
import * as LucideIcons from 'lucide-vue-next'
import { computed } from 'vue'

const SIZE_MAP = { xs: '12px', sm: '16px', md: '20px', lg: '24px', xl: '32px' }

export function useIconResolver(iconDataRef) {
  const iconComponent = computed(() => {
    const name = iconDataRef.value?.name
    if (!name) return null
    return LucideIcons[name] ?? null
  })

  const iconStyle = computed(() => {
    const d = iconDataRef.value ?? {}
    return {
      width:  SIZE_MAP[d.size ?? 'md'],
      height: SIZE_MAP[d.size ?? 'md'],
      color:  d.color && d.color !== 'inherit' ? d.color : undefined,
      flexShrink: 0,
    }
  })

  return { iconComponent, iconStyle }
}
```

### Step 3: Build and verify

```bash
npm run build
```

### Step 4: Commit

```bash
cd C:\Users\mariu\Herd\lambda-cms
git add resources/js/Components/BlockEditor/blocks/IconSettings.vue
git add resources/js/composables/useIconResolver.js
git commit -m "feat: add IconSettings component and useIconResolver composable"
```

---

## Task 4: Link block — renderer, settings, and canvas editor

**Files:**
- Create: `resources/js/components/Blocks/LinkBlock.vue`
- Create: `resources/js/Components/BlockEditor/blocks/LinkSettings.vue`
- Create: `resources/js/Components/BlockEditor/EditorLinkBlock.vue`
- Modify: `resources/js/components/BlockRenderer.vue`
- Modify: `resources/js/Components/BlockEditor/BlockCanvas.vue`

### Step 1: Create LinkBlock.vue

```vue
<!-- resources/js/Components/Blocks/LinkBlock.vue -->
<script setup>
import { computed } from 'vue'
import BlockRenderer from '@/Components/BlockRenderer.vue'
import { useFieldBinding } from '@/composables/useLoopBinding.js'
import { useIconResolver } from '@/composables/useIconResolver.js'

const props = defineProps({ block: { type: Object, required: true } })

const resolvedUrl = useFieldBinding(() => props.block, 'url')

const iconData = computed(() => props.block.data?.icon ?? {})
const { iconComponent, iconStyle } = useIconResolver(iconData)

const position = computed(() => props.block.data?.icon?.position ?? 'left')
const target   = computed(() => props.block.data?.target || '_self')
const rel      = computed(() => props.block.data?.rel || undefined)
</script>

<template>
  <a
    :href="resolvedUrl || '#'"
    :target="target"
    :rel="rel"
    class="block"
  >
    <div class="flex items-center gap-1.5">
      <component
        v-if="iconComponent && position === 'left'"
        :is="iconComponent"
        :style="iconStyle"
      />
      <div class="flex-1">
        <BlockRenderer :blocks="block.children ?? []" wrapper-class="contents" />
      </div>
      <component
        v-if="iconComponent && position === 'right'"
        :is="iconComponent"
        :style="iconStyle"
      />
    </div>
  </a>
</template>
```

### Step 2: Create LinkSettings.vue

```vue
<!-- resources/js/Components/BlockEditor/blocks/LinkSettings.vue -->
<template>
  <!-- Content tab -->
  <div v-show="!tab || tab === 'content'" class="space-y-3">
    <DynamicField
      label="URL"
      field-name="url"
      :block="block"
      :available-fields="availableFields"
      @bind="onBind"
      @unbind="onUnbind"
    >
      <input
        :value="block.data.url"
        type="url"
        placeholder="https://..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { url: $event.target.value } })"
      />
    </DynamicField>

    <div class="flex items-center gap-2">
      <input
        id="link-newtab"
        type="checkbox"
        :checked="block.data.target === '_blank'"
        class="rounded border-border accent-primary"
        @change="emit('update', { id: block.id, data: { target: $event.target.checked ? '_blank' : '_self' } })"
      />
      <label for="link-newtab" class="text-xs text-muted-foreground">Open in new tab</label>
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Rel attribute</label>
      <SelectBox
        :model-value="block.data.rel || ''"
        :data="[
          { value: '',           label: 'None' },
          { value: 'nofollow',   label: 'nofollow' },
          { value: 'noopener',   label: 'noopener' },
          { value: 'noreferrer', label: 'noreferrer' },
          { value: 'sponsored',  label: 'sponsored' },
        ]"
        @update:model-value="v => emit('update', { id: block.id, data: { rel: v } })"
      />
    </div>
  </div>

  <!-- Style tab -->
  <div v-show="!tab || tab === 'style'" class="space-y-3">
    <IconSettings :block="block" @update="emit('update', $event)" />
  </div>
</template>

<script setup>
import SelectBox   from '@/Components/SelectBox.vue'
import DynamicField from './DynamicField.vue'
import IconSettings from './IconSettings.vue'

const props = defineProps({
  block:           { type: Object, required: true },
  availableFields: { type: Array,  default: () => [] },
  tab:             { type: String, default: null },
})
const emit = defineEmits(['update'])

function onBind(fieldName, loopField) {
  emit('update', { id: props.block.id, bindings: { ...props.block.bindings, [fieldName]: loopField } })
}
function onUnbind(fieldName) {
  const b = { ...(props.block.bindings ?? {}) }
  delete b[fieldName]
  emit('update', { id: props.block.id, bindings: Object.keys(b).length ? b : undefined })
}
</script>
```

### Step 3: Create EditorLinkBlock.vue

Model this closely on `EditorContainerBlock.vue`. Read that file first, then create:

```vue
<!-- resources/js/Components/BlockEditor/EditorLinkBlock.vue -->
<template>
  <div class="border border-dashed border-white/20 rounded-lg p-2 relative min-h-[48px]">
    <span class="absolute top-1 left-1 text-[10px] text-white/40 font-semibold uppercase tracking-wider select-none flex items-center gap-1">
      <LinkIcon class="w-3 h-3" />
      {{ block.blockName || (block.data?.url ? block.data.url.slice(0, 30) : 'Link') }}
    </span>

    <VueDraggable
      v-model="localChildren"
      tag="div"
      class="pt-5 min-h-[32px] space-y-1.5"
      :group="{ name: 'canvas' }"
      :animation="150"
      handle=".child-drag-handle"
      ghost-class="opacity-40"
      @add="onAdd"
    >
      <template v-for="child in localChildren" :key="child.id">
        <div
          :id="child.customId || `block-${child.id}`"
          class="group relative flex items-center gap-2 rounded-md border bg-background/50 px-2 py-1.5 cursor-pointer text-xs transition-colors"
          :class="child.id === selectedId
            ? 'border-primary ring-1 ring-primary'
            : 'border-white/10 hover:border-white/25'"
          @click.stop="$emit('select', child.id)"
        >
          <span class="child-drag-handle cursor-grab active:cursor-grabbing text-white/30 shrink-0" @click.stop>
            <GripVertical class="w-3 h-3" />
          </span>
          <span class="text-xs truncate text-white/60">
            {{ child.blockName || LABELS[child.type] || child.type }}
          </span>
        </div>
      </template>

      <div
        v-if="localChildren.length === 0"
        class="text-center py-2 text-xs text-white/25 pointer-events-none"
      >
        Drop blocks inside this link
      </div>
    </VueDraggable>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { VueDraggable } from 'vue-draggable-plus'
import { GripVertical, Link as LinkIcon } from 'lucide-vue-next'

const LABELS = {
  paragraph: 'Paragraph', heading: 'Heading', image: 'Image',
  quote: 'Quote', code: 'Code', gallery: 'Gallery', video: 'Video',
  divider: 'Divider', cta: 'CTA', html: 'HTML',
}

const props = defineProps({
  block:      { type: Object, required: true },
  selectedId: { type: String, default: null },
})
const emit = defineEmits(['select', 'update-children'])

const _children = ref([...(props.block.children ?? [])])
watch(() => props.block.children, (v) => { _children.value = v ?? [] })

const localChildren = computed({
  get: () => _children.value,
  set: (val) => {
    _children.value = val
    emit('update-children', { id: props.block.id, children: val })
  },
})

function onAdd(evt) {
  const newChild = localChildren.value[evt.newIndex]
  if (newChild) emit('select', newChild.id)
}
</script>
```

Add the missing `import { computed } from 'vue'` — make sure `computed` is in the Vue import.

### Step 4: Register LinkBlock in BlockRenderer.vue

Add the import:
```js
import LinkBlock from '@/Components/Blocks/LinkBlock.vue'
```

Add to `BLOCK_MAP`:
```js
link: LinkBlock,
```

Also update `loadFontsFromBlocks` to recurse into link children:
```js
if (['container', 'section', 'loop', 'archive-loop', 'link', 'accordion', 'tabs'].includes(block.type) && block.children?.length) {
  loadFontsFromBlocks(block.children)
}
```

### Step 5: Register EditorLinkBlock in BlockCanvas.vue

Add import:
```js
import EditorLinkBlock from './EditorLinkBlock.vue'
```

In the template, find the `<EditorLoopBlock v-else-if="block.type === 'loop'"` block. Add after it:
```html
<EditorLinkBlock
  v-else-if="block.type === 'link'"
  :block="block"
  :selected-id="selectedId"
  @select="$emit('select', $event)"
  @update-children="$emit('update-children', $event)"
/>
```

### Step 6: Register LinkSettings in BlockLayers.vue

Find `settingsComponent` computed (the switch/map that selects a settings component by block type). Add:
```js
link: LinkSettings,
```
And import at the top:
```js
import LinkSettings from './blocks/LinkSettings.vue'
```

### Step 7: Build and verify

```bash
npm run build
```

Open the block editor. Drag a Link block onto the canvas — it should appear as a dashed bordered box labelled "Link". Drop a heading inside it. Select the Link block and check settings: Content tab shows URL/target/rel, Style tab shows icon picker.

### Step 8: Commit

```bash
cd C:\Users\mariu\Herd\lambda-cms
git add resources/js/components/Blocks/LinkBlock.vue
git add resources/js/Components/BlockEditor/blocks/LinkSettings.vue
git add resources/js/Components/BlockEditor/EditorLinkBlock.vue
git add resources/js/components/BlockRenderer.vue
git add resources/js/Components/BlockEditor/BlockCanvas.vue
git add resources/js/Components/BlockEditor/BlockLayers.vue
git commit -m "feat: add Link block with dynamic URL binding and icon settings"
```

---

## Task 5: Add icon settings to Heading block

**Files:**
- Modify: `resources/js/components/Blocks/HeadingBlock.vue`
- Modify: `resources/js/Components/BlockEditor/blocks/HeadingSettings.vue`

### Step 1: Update HeadingBlock.vue to render icons

```vue
<!-- resources/js/Components/Blocks/HeadingBlock.vue -->
<template>
  <component :is="'h' + (block.data.level ?? 2)" class="font-bold leading-tight flex items-center gap-1.5">
    <component
      v-if="iconComponent && position === 'left'"
      :is="iconComponent"
      :style="iconStyle"
    />
    {{ resolvedText }}
    <component
      v-if="iconComponent && position === 'right'"
      :is="iconComponent"
      :style="iconStyle"
    />
  </component>
</template>

<script setup>
import { computed } from 'vue'
import { useFieldBinding } from '@/composables/useLoopBinding.js'
import { useIconResolver } from '@/composables/useIconResolver.js'

const props = defineProps({ block: { type: Object, required: true } })
const resolvedText = useFieldBinding(() => props.block, 'text')
const iconData = computed(() => props.block.data?.icon ?? {})
const { iconComponent, iconStyle } = useIconResolver(iconData)
const position = computed(() => props.block.data?.icon?.position ?? 'left')
</script>
```

### Step 2: Add Style tab to HeadingSettings.vue

```vue
<!-- resources/js/Components/BlockEditor/blocks/HeadingSettings.vue -->
<template>
  <!-- Content tab -->
  <div v-show="!tab || tab === 'content'" class="space-y-3">
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Level</label>
      <SelectBox
        :model-value="block.data.level"
        :data="[1,2,3,4,5,6].map(n => ({ value: n, label: `H${n}` }))"
        @update:model-value="v => emit('update', { id: block.id, data: { level: Number(v) } })"
      />
    </div>

    <DynamicField
      label="Text"
      field-name="text"
      :block="block"
      :available-fields="availableFields"
      @bind="onBind"
      @unbind="onUnbind"
    >
      <input
        :value="block.data.text"
        type="text"
        placeholder="Heading text..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { text: $event.target.value } })"
      />
    </DynamicField>
  </div>

  <!-- Style tab -->
  <div v-show="!tab || tab === 'style'" class="space-y-3">
    <IconSettings :block="block" @update="emit('update', $event)" />
  </div>
</template>

<script setup>
import SelectBox    from '@/Components/SelectBox.vue'
import DynamicField from './DynamicField.vue'
import IconSettings from './IconSettings.vue'

const props = defineProps({
  block:           { type: Object, required: true },
  availableFields: { type: Array,  default: () => [] },
  tab:             { type: String, default: null },
})
const emit = defineEmits(['update'])

function onBind(fieldName, loopField) {
  emit('update', { id: props.block.id, bindings: { ...props.block.bindings, [fieldName]: loopField } })
}
function onUnbind(fieldName) {
  const b = { ...(props.block.bindings ?? {}) }
  delete b[fieldName]
  emit('update', { id: props.block.id, bindings: Object.keys(b).length ? b : undefined })
}
</script>
```

### Step 3: Build and verify

```bash
npm run build
```

Select a Heading block in the editor. The Style tab should now show the icon picker. Set an icon — it should appear next to the heading text in both the canvas wireframe and live preview.

### Step 4: Commit

```bash
cd C:\Users\mariu\Herd\lambda-cms
git add resources/js/components/Blocks/HeadingBlock.vue
git add resources/js/Components/BlockEditor/blocks/HeadingSettings.vue
git commit -m "feat: add icon settings to Heading block"
```

---

## Task 6: Accordion block

**Files:**
- Create: `resources/js/components/Blocks/AccordionBlock.vue`
- Create: `resources/js/Components/BlockEditor/blocks/AccordionSettings.vue`
- Create: `resources/js/Components/BlockEditor/blocks/AccordionItemSettings.vue`
- Create: `resources/js/Components/BlockEditor/EditorAccordionBlock.vue`
- Modify: `resources/js/components/BlockRenderer.vue`
- Modify: `resources/js/Components/BlockEditor/BlockCanvas.vue`
- Modify: `resources/js/Components/BlockEditor/BlockLayers.vue`

### Step 1: Create AccordionBlock.vue

```vue
<!-- resources/js/Components/Blocks/AccordionBlock.vue -->
<script setup>
import { ref, computed } from 'vue'
import { ChevronDown } from 'lucide-vue-next'
import BlockRenderer from '@/Components/BlockRenderer.vue'

const props = defineProps({ block: { type: Object, required: true } })

const defaultState = computed(() => props.block.data?.defaultState ?? 'first-open')
const borderStyle  = computed(() => props.block.data?.borderStyle  ?? 'bordered')

// Build initial open state from defaultState setting
const openItems = ref(
  Object.fromEntries(
    (props.block.children ?? []).map((child, i) => [
      child.id,
      defaultState.value === 'all-open' ||
      (defaultState.value === 'first-open' && i === 0),
    ])
  )
)

function toggle(id) {
  openItems.value = { ...openItems.value, [id]: !openItems.value[id] }
}

const wrapperClass = computed(() => ({
  'divide-y divide-border':             borderStyle.value === 'bordered',
  'border border-border rounded-lg divide-y divide-border': borderStyle.value === 'bordered',
  'space-y-2':                          borderStyle.value === 'separated',
}))
</script>

<template>
  <div :class="wrapperClass">
    <div
      v-for="item in block.children"
      :key="item.id"
      :class="{ 'border border-border rounded-lg overflow-hidden': borderStyle === 'separated' }"
    >
      <button
        type="button"
        class="w-full flex justify-between items-center px-4 py-3 text-left font-medium hover:bg-muted/50 transition-colors"
        @click="toggle(item.id)"
      >
        <span>{{ item.data?.title || 'Item' }}</span>
        <ChevronDown
          class="w-4 h-4 shrink-0 transition-transform duration-200"
          :class="{ 'rotate-180': openItems[item.id] }"
        />
      </button>
      <div v-show="openItems[item.id]" class="px-4 pb-4">
        <BlockRenderer :blocks="item.children ?? []" />
      </div>
    </div>
  </div>
</template>
```

### Step 2: Create AccordionSettings.vue

```vue
<!-- resources/js/Components/BlockEditor/blocks/AccordionSettings.vue -->
<template>
  <div v-show="!tab || tab === 'style'" class="space-y-3">
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Default state</label>
      <SelectBox
        :model-value="block.data.defaultState ?? 'first-open'"
        :data="[
          { value: 'first-open',   label: 'First item open' },
          { value: 'all-collapsed', label: 'All collapsed' },
          { value: 'all-open',     label: 'All open' },
        ]"
        @update:model-value="v => emit('update', { id: block.id, data: { defaultState: v } })"
      />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Border style</label>
      <SelectBox
        :model-value="block.data.borderStyle ?? 'bordered'"
        :data="[
          { value: 'bordered',   label: 'Bordered (dividers)' },
          { value: 'separated',  label: 'Separated (cards)' },
          { value: 'borderless', label: 'Borderless' },
        ]"
        @update:model-value="v => emit('update', { id: block.id, data: { borderStyle: v } })"
      />
    </div>
  </div>
</template>

<script setup>
import SelectBox from '@/Components/SelectBox.vue'

const props = defineProps({
  block: { type: Object, required: true },
  tab:   { type: String, default: null },
})
const emit = defineEmits(['update'])
</script>
```

### Step 3: Create AccordionItemSettings.vue

```vue
<!-- resources/js/Components/BlockEditor/blocks/AccordionItemSettings.vue -->
<template>
  <div v-show="!tab || tab === 'content'" class="space-y-3">
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Title</label>
      <input
        :value="block.data.title"
        type="text"
        placeholder="Item title..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { title: $event.target.value } })"
      />
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  block: { type: Object, required: true },
  tab:   { type: String, default: null },
})
const emit = defineEmits(['update'])
</script>
```

### Step 4: Create EditorAccordionBlock.vue

```vue
<!-- resources/js/Components/BlockEditor/EditorAccordionBlock.vue -->
<template>
  <div class="border border-dashed border-white/20 rounded-lg overflow-hidden">
    <!-- Header -->
    <div class="px-3 py-1.5 border-b border-white/8 bg-white/3 flex items-center justify-between">
      <span class="text-[10px] font-semibold uppercase tracking-wider text-white/40">
        {{ block.blockName || 'Accordion' }} ({{ block.children?.length ?? 0 }} items)
      </span>
    </div>

    <!-- Items -->
    <div class="divide-y divide-white/8">
      <div
        v-for="(item, idx) in localChildren"
        :key="item.id"
        class="group"
      >
        <!-- Item header row -->
        <div
          class="flex items-center gap-2 px-3 py-2 cursor-pointer transition-colors"
          :class="item.id === selectedId
            ? 'bg-primary/10 border-l-2 border-primary'
            : 'hover:bg-white/5'"
          @click.stop="$emit('select', item.id)"
        >
          <GripVertical class="w-3 h-3 text-white/25 shrink-0 cursor-grab" />
          <span class="flex-1 text-xs text-white/60 truncate">
            {{ item.data?.title || `Item ${idx + 1}` }}
          </span>
          <ChevronDown class="w-3 h-3 text-white/25 shrink-0" />
        </div>

        <!-- Item children drop zone -->
        <VueDraggable
          v-model="itemChildren[item.id]"
          tag="div"
          class="pl-6 pr-2 pb-1 min-h-[28px] space-y-1"
          :group="{ name: 'canvas' }"
          :animation="150"
          ghost-class="opacity-40"
          @change="onChildrenChange(item.id)"
        >
          <div
            v-for="child in itemChildren[item.id]"
            :key="child.id"
            class="flex items-center gap-1.5 rounded border border-white/10 bg-background/40 px-2 py-1 text-xs cursor-pointer text-white/50 transition-colors hover:border-white/25"
            :class="child.id === selectedId ? 'border-primary ring-1 ring-primary' : ''"
            @click.stop="$emit('select', child.id)"
          >
            <GripVertical class="w-3 h-3 text-white/20 shrink-0" />
            {{ child.blockName || LABELS[child.type] || child.type }}
          </div>
          <div
            v-if="!itemChildren[item.id]?.length"
            class="text-[10px] text-white/20 py-1 pl-1 pointer-events-none"
          >
            Drop blocks here
          </div>
        </VueDraggable>
      </div>
    </div>

    <!-- Add Item button -->
    <button
      type="button"
      class="w-full px-3 py-2 text-xs text-white/40 hover:text-white/60 hover:bg-white/5 transition-colors flex items-center gap-1.5 border-t border-white/8"
      @click.stop="addItem"
    >
      <Plus class="w-3 h-3" /> Add Item
    </button>
  </div>
</template>

<script setup>
import { ref, watch, reactive } from 'vue'
import { VueDraggable } from 'vue-draggable-plus'
import { GripVertical, ChevronDown, Plus } from 'lucide-vue-next'

const LABELS = {
  paragraph: 'Paragraph', heading: 'Heading', image: 'Image',
  quote: 'Quote', code: 'Code', gallery: 'Gallery', video: 'Video',
  divider: 'Divider', cta: 'CTA', html: 'HTML', link: 'Link',
}

const props = defineProps({
  block:      { type: Object, required: true },
  selectedId: { type: String, default: null },
})
const emit = defineEmits(['select', 'update-children'])

// Local copy of top-level children (accordion items)
const _children = ref([...(props.block.children ?? [])])
watch(() => props.block.children, (v) => { _children.value = v ?? [] })

const localChildren = computed({
  get: () => _children.value,
  set: (val) => {
    _children.value = val
    syncToParent()
  },
})

// Per-item children (the blocks inside each accordion-item)
const itemChildren = reactive(
  Object.fromEntries((props.block.children ?? []).map(c => [c.id, [...(c.children ?? [])]]))
)

watch(() => props.block.children, (items) => {
  ;(items ?? []).forEach(c => {
    if (!itemChildren[c.id]) itemChildren[c.id] = [...(c.children ?? [])]
  })
})

function syncToParent() {
  const updated = _children.value.map(item => ({
    ...item,
    children: itemChildren[item.id] ?? [],
  }))
  emit('update-children', { id: props.block.id, children: updated })
}

function onChildrenChange(itemId) {
  syncToParent()
}

function addItem() {
  const id = crypto.randomUUID()
  const newItem = {
    id,
    type: 'accordion-item',
    data: { title: `Item ${_children.value.length + 1}` },
    customId: '', customClasses: '', customCss: '', fontFamily: '',
    children: [],
  }
  itemChildren[id] = []
  _children.value = [..._children.value, newItem]
  syncToParent()
  emit('select', id)
}

import { computed } from 'vue'
</script>
```

Note: Move the `import { computed } from 'vue'` to the top of the script block with the other imports.

### Step 5: Register in BlockRenderer.vue

```js
import AccordionBlock from '@/Components/Blocks/AccordionBlock.vue'
```

In `BLOCK_MAP`:
```js
accordion:       AccordionBlock,
'accordion-item': AccordionBlock, // accordion-item is never rendered standalone; fallback to parent
```

Actually `accordion-item` is never rendered by `BlockRenderer` standalone — it's only rendered inside `AccordionBlock`. So no need to register it. Leave `accordion-item` out of BLOCK_MAP. Just add:
```js
accordion: AccordionBlock,
```

### Step 6: Register in BlockCanvas.vue

Add import:
```js
import EditorAccordionBlock from './EditorAccordionBlock.vue'
```

In template, after the `EditorLinkBlock` block:
```html
<EditorAccordionBlock
  v-else-if="block.type === 'accordion'"
  :block="block"
  :selected-id="selectedId"
  @select="$emit('select', $event)"
  @update-children="$emit('update-children', $event)"
/>
```

### Step 7: Register AccordionSettings and AccordionItemSettings in BlockLayers.vue

Find the settings component map/switch. Add:
```js
import AccordionSettings     from './blocks/AccordionSettings.vue'
import AccordionItemSettings from './blocks/AccordionItemSettings.vue'
```

Add to the map:
```js
accordion:       AccordionSettings,
'accordion-item': AccordionItemSettings,
```

### Step 8: Build and verify

```bash
npm run build
```

Drag Accordion onto canvas — should show 3 pre-populated items. Click an item row to select it — settings panel shows the title field. Click "Add Item" — a 4th item appears. In live preview, accordion items should collapse/expand on click.

### Step 9: Commit

```bash
cd C:\Users\mariu\Herd\lambda-cms
git add resources/js/components/Blocks/AccordionBlock.vue
git add resources/js/Components/BlockEditor/blocks/AccordionSettings.vue
git add resources/js/Components/BlockEditor/blocks/AccordionItemSettings.vue
git add resources/js/Components/BlockEditor/EditorAccordionBlock.vue
git add resources/js/components/BlockRenderer.vue
git add resources/js/Components/BlockEditor/BlockCanvas.vue
git add resources/js/Components/BlockEditor/BlockLayers.vue
git commit -m "feat: add Accordion block with collapsible children items"
```

---

## Task 7: Tabs block

**Files:**
- Create: `resources/js/components/Blocks/TabsBlock.vue`
- Create: `resources/js/Components/BlockEditor/blocks/TabsSettings.vue`
- Create: `resources/js/Components/BlockEditor/blocks/TabItemSettings.vue`
- Create: `resources/js/Components/BlockEditor/EditorTabsBlock.vue`
- Modify: `resources/js/components/BlockRenderer.vue`
- Modify: `resources/js/Components/BlockEditor/BlockCanvas.vue`
- Modify: `resources/js/Components/BlockEditor/BlockLayers.vue`

### Step 1: Create TabsBlock.vue

```vue
<!-- resources/js/Components/Blocks/TabsBlock.vue -->
<script setup>
import { ref, computed } from 'vue'
import BlockRenderer from '@/Components/BlockRenderer.vue'

const props = defineProps({ block: { type: Object, required: true } })

const activeTab = ref(0)
const alignment = computed(() => props.block.data?.alignment ?? 'left')
const tabStyle  = computed(() => props.block.data?.tabStyle   ?? 'underline')

const tabBarClass = computed(() => ({
  'flex gap-0 border-b':   tabStyle.value === 'underline',
  'flex gap-1 mb-4':       tabStyle.value === 'pills',
  'flex gap-1 mb-4':       tabStyle.value === 'buttons',
  'justify-start':         alignment.value === 'left',
  'justify-center':        alignment.value === 'center',
  'justify-end':           alignment.value === 'right',
}))

function tabClass(i) {
  const active = activeTab.value === i
  if (tabStyle.value === 'underline') {
    return active
      ? 'px-4 py-2 text-sm font-medium border-b-2 border-primary text-primary -mb-px'
      : 'px-4 py-2 text-sm font-medium text-muted-foreground hover:text-foreground border-b-2 border-transparent -mb-px transition-colors'
  }
  if (tabStyle.value === 'pills') {
    return active
      ? 'px-3 py-1.5 text-sm font-medium rounded-full bg-primary text-primary-foreground'
      : 'px-3 py-1.5 text-sm font-medium rounded-full text-muted-foreground hover:bg-muted transition-colors'
  }
  // buttons
  return active
    ? 'px-3 py-1.5 text-sm font-medium rounded border border-primary bg-primary text-primary-foreground'
    : 'px-3 py-1.5 text-sm font-medium rounded border border-border text-muted-foreground hover:border-muted-foreground transition-colors'
}
</script>

<template>
  <div>
    <div :class="tabBarClass">
      <button
        v-for="(tab, i) in block.children"
        :key="tab.id"
        type="button"
        :class="tabClass(i)"
        @click="activeTab = i"
      >
        {{ tab.data?.label || `Tab ${i + 1}` }}
      </button>
    </div>

    <div class="pt-4">
      <div
        v-for="(tab, i) in block.children"
        v-show="activeTab === i"
        :key="tab.id"
      >
        <BlockRenderer :blocks="tab.children ?? []" />
      </div>
    </div>
  </div>
</template>
```

### Step 2: Create TabsSettings.vue

```vue
<!-- resources/js/Components/BlockEditor/blocks/TabsSettings.vue -->
<template>
  <div v-show="!tab || tab === 'style'" class="space-y-3">
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Tab style</label>
      <SelectBox
        :model-value="block.data.tabStyle ?? 'underline'"
        :data="[
          { value: 'underline', label: 'Underline' },
          { value: 'pills',     label: 'Pills' },
          { value: 'buttons',   label: 'Buttons' },
        ]"
        @update:model-value="v => emit('update', { id: block.id, data: { tabStyle: v } })"
      />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Alignment</label>
      <div class="flex gap-1">
        <button
          v-for="al in ['left', 'center', 'right']"
          :key="al"
          type="button"
          class="flex-1 px-2 py-1 text-xs rounded border capitalize transition-colors"
          :class="(block.data.alignment ?? 'left') === al
            ? 'bg-primary text-primary-foreground border-primary'
            : 'bg-background border-border hover:border-muted-foreground'"
          @click="emit('update', { id: block.id, data: { alignment: al } })"
        >{{ al }}</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import SelectBox from '@/Components/SelectBox.vue'

const props = defineProps({
  block: { type: Object, required: true },
  tab:   { type: String, default: null },
})
const emit = defineEmits(['update'])
</script>
```

### Step 3: Create TabItemSettings.vue

```vue
<!-- resources/js/Components/BlockEditor/blocks/TabItemSettings.vue -->
<template>
  <div v-show="!tab || tab === 'content'" class="space-y-3">
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Label</label>
      <input
        :value="block.data.label"
        type="text"
        placeholder="Tab label..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { label: $event.target.value } })"
      />
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  block: { type: Object, required: true },
  tab:   { type: String, default: null },
})
const emit = defineEmits(['update'])
</script>
```

### Step 4: Create EditorTabsBlock.vue

Model this on `EditorAccordionBlock.vue` — same pattern but shows a tab bar with labels:

```vue
<!-- resources/js/Components/BlockEditor/EditorTabsBlock.vue -->
<template>
  <div class="border border-dashed border-white/20 rounded-lg overflow-hidden">
    <!-- Mock tab bar -->
    <div class="flex items-center gap-0 border-b border-white/8 bg-white/3 overflow-x-auto">
      <button
        v-for="(item, i) in localChildren"
        :key="item.id"
        type="button"
        class="px-3 py-2 text-xs whitespace-nowrap border-b-2 transition-colors"
        :class="activeIdx === i
          ? 'border-primary text-primary'
          : 'border-transparent text-white/40 hover:text-white/60'"
        @click.stop="activeIdx = i; $emit('select', item.id)"
      >
        {{ item.data?.label || `Tab ${i + 1}` }}
      </button>

      <!-- Add Tab button -->
      <button
        type="button"
        class="px-2 py-2 text-white/30 hover:text-white/60 transition-colors ml-1"
        @click.stop="addTab"
        title="Add tab"
      >
        <Plus class="w-3 h-3" />
      </button>
    </div>

    <!-- Active tab children drop zone -->
    <div v-if="localChildren[activeIdx]">
      <VueDraggable
        v-model="itemChildren[localChildren[activeIdx].id]"
        tag="div"
        class="p-2 min-h-[40px] space-y-1"
        :group="{ name: 'canvas' }"
        :animation="150"
        ghost-class="opacity-40"
        @change="onChildrenChange(localChildren[activeIdx].id)"
      >
        <div
          v-for="child in itemChildren[localChildren[activeIdx].id]"
          :key="child.id"
          class="flex items-center gap-1.5 rounded border border-white/10 bg-background/40 px-2 py-1 text-xs cursor-pointer text-white/50 transition-colors hover:border-white/25"
          :class="child.id === selectedId ? 'border-primary ring-1 ring-primary' : ''"
          @click.stop="$emit('select', child.id)"
        >
          <GripVertical class="w-3 h-3 text-white/20 shrink-0" />
          {{ child.blockName || LABELS[child.type] || child.type }}
        </div>
        <div
          v-if="!itemChildren[localChildren[activeIdx].id]?.length"
          class="text-[10px] text-white/20 py-1 pl-1 pointer-events-none"
        >
          Drop blocks into this tab
        </div>
      </VueDraggable>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, reactive, computed } from 'vue'
import { VueDraggable } from 'vue-draggable-plus'
import { GripVertical, Plus } from 'lucide-vue-next'

const LABELS = {
  paragraph: 'Paragraph', heading: 'Heading', image: 'Image',
  quote: 'Quote', code: 'Code', gallery: 'Gallery', video: 'Video',
  divider: 'Divider', cta: 'CTA', html: 'HTML', link: 'Link',
}

const props = defineProps({
  block:      { type: Object, required: true },
  selectedId: { type: String, default: null },
})
const emit = defineEmits(['select', 'update-children'])

const activeIdx = ref(0)

const _children = ref([...(props.block.children ?? [])])
watch(() => props.block.children, (v) => { _children.value = v ?? [] })

const localChildren = computed({
  get: () => _children.value,
  set: (val) => {
    _children.value = val
    syncToParent()
  },
})

const itemChildren = reactive(
  Object.fromEntries((props.block.children ?? []).map(c => [c.id, [...(c.children ?? [])]]))
)

watch(() => props.block.children, (items) => {
  ;(items ?? []).forEach(c => {
    if (!itemChildren[c.id]) itemChildren[c.id] = [...(c.children ?? [])]
  })
})

function syncToParent() {
  const updated = _children.value.map(item => ({
    ...item,
    children: itemChildren[item.id] ?? [],
  }))
  emit('update-children', { id: props.block.id, children: updated })
}

function onChildrenChange() {
  syncToParent()
}

function addTab() {
  const id = crypto.randomUUID()
  const newTab = {
    id,
    type: 'tab-item',
    data: { label: `Tab ${_children.value.length + 1}` },
    customId: '', customClasses: '', customCss: '', fontFamily: '',
    children: [],
  }
  itemChildren[id] = []
  _children.value = [..._children.value, newTab]
  activeIdx.value = _children.value.length - 1
  syncToParent()
  emit('select', id)
}
</script>
```

### Step 5: Register in BlockRenderer.vue

```js
import TabsBlock from '@/Components/Blocks/TabsBlock.vue'
```

In `BLOCK_MAP`:
```js
tabs: TabsBlock,
```

### Step 6: Register in BlockCanvas.vue

```js
import EditorTabsBlock from './EditorTabsBlock.vue'
```

In template:
```html
<EditorTabsBlock
  v-else-if="block.type === 'tabs'"
  :block="block"
  :selected-id="selectedId"
  @select="$emit('select', $event)"
  @update-children="$emit('update-children', $event)"
/>
```

### Step 7: Register in BlockLayers.vue

```js
import TabsSettings     from './blocks/TabsSettings.vue'
import TabItemSettings  from './blocks/TabItemSettings.vue'
```

In the settings map:
```js
tabs:      TabsSettings,
'tab-item': TabItemSettings,
```

### Step 8: Build and verify

```bash
npm run build
```

Drag a Tabs block — should show 2 pre-populated tabs. Click tab labels to switch active tab. Drop blocks into each tab. In live preview, tabs should switch panels.

### Step 9: Commit

```bash
cd C:\Users\mariu\Herd\lambda-cms
git add resources/js/components/Blocks/TabsBlock.vue
git add resources/js/Components/BlockEditor/blocks/TabsSettings.vue
git add resources/js/Components/BlockEditor/blocks/TabItemSettings.vue
git add resources/js/Components/BlockEditor/EditorTabsBlock.vue
git add resources/js/components/BlockRenderer.vue
git add resources/js/Components/BlockEditor/BlockCanvas.vue
git add resources/js/Components/BlockEditor/BlockLayers.vue
git commit -m "feat: add Tabs block with per-tab child blocks"
```

---

## Task 8: Embed block

**Files:**
- Create: `resources/js/components/Blocks/EmbedBlock.vue`
- Create: `resources/js/Components/BlockEditor/blocks/EmbedSettings.vue`
- Modify: `resources/js/components/BlockRenderer.vue`
- Modify: `resources/js/Components/BlockEditor/BlockCanvas.vue` (BLOCK_MAP + isEmptyBlock)
- Modify: `resources/js/Components/BlockEditor/BlockLayers.vue` (settings map)

### Step 1: Create EmbedBlock.vue

```vue
<!-- resources/js/Components/Blocks/EmbedBlock.vue -->
<script setup>
import { computed } from 'vue'

const props = defineProps({ block: { type: Object, required: true } })

const data = computed(() => props.block.data ?? {})

function extractYouTubeId(url) {
  const m = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/)
  return m ? m[1] : null
}

function extractVimeoId(url) {
  const m = url.match(/vimeo\.com\/(\d+)/)
  return m ? m[1] : null
}

const embedInfo = computed(() => {
  const url = data.value.url ?? ''
  if (!url) return { type: 'empty' }

  const ytId = extractYouTubeId(url)
  if (ytId) return { type: 'iframe', src: `https://www.youtube.com/embed/${ytId}` }

  const vimeoId = extractVimeoId(url)
  if (vimeoId) return { type: 'iframe', src: `https://player.vimeo.com/video/${vimeoId}` }

  if (url.includes('maps.google.com') || url.includes('goo.gl/maps') || url.includes('google.com/maps')) {
    return { type: 'iframe', src: url }
  }

  if (url.includes('twitter.com/') || url.includes('x.com/')) {
    return { type: 'twitter', url }
  }

  // Generic iframe fallback
  return { type: 'iframe', src: url }
})
</script>

<template>
  <figure :style="data.maxWidth ? { maxWidth: data.maxWidth } : undefined">
    <div
      v-if="embedInfo.type === 'empty'"
      class="flex items-center justify-center rounded border border-dashed border-border bg-muted h-40 text-sm text-muted-foreground"
    >
      No URL set
    </div>
    <div
      v-else
      :style="{ aspectRatio: data.aspectRatio || '16/9', position: 'relative', overflow: 'hidden' }"
    >
      <iframe
        v-if="embedInfo.type === 'iframe'"
        :src="embedInfo.src"
        style="position:absolute;inset:0;width:100%;height:100%;"
        allowfullscreen
        loading="lazy"
        frameborder="0"
      />
      <div
        v-else-if="embedInfo.type === 'twitter'"
        class="p-4 border border-border rounded text-sm text-muted-foreground"
      >
        Twitter/X embed — renders only on public frontend.<br />
        <a :href="embedInfo.url" target="_blank" class="underline text-xs">{{ embedInfo.url }}</a>
      </div>
    </div>
    <figcaption v-if="data.caption" class="mt-2 text-sm text-muted-foreground text-center">
      {{ data.caption }}
    </figcaption>
  </figure>
</template>
```

### Step 2: Create EmbedSettings.vue

```vue
<!-- resources/js/Components/BlockEditor/blocks/EmbedSettings.vue -->
<template>
  <!-- Content tab -->
  <div v-show="!tab || tab === 'content'" class="space-y-3">
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">URL</label>
      <input
        :value="block.data.url"
        type="url"
        placeholder="YouTube, Vimeo, Maps URL..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { url: $event.target.value } })"
      />
      <p class="text-[10px] text-muted-foreground mt-1">Supports YouTube, Vimeo, Google Maps, Twitter/X, or any URL.</p>
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Caption</label>
      <input
        :value="block.data.caption"
        type="text"
        placeholder="Optional caption..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { caption: $event.target.value } })"
      />
    </div>
  </div>

  <!-- Style tab -->
  <div v-show="!tab || tab === 'style'" class="space-y-3">
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Aspect ratio</label>
      <SelectBox
        :model-value="block.data.aspectRatio ?? '16/9'"
        :data="[
          { value: '16/9',  label: '16:9 (Widescreen)' },
          { value: '4/3',   label: '4:3 (Standard)' },
          { value: '1/1',   label: '1:1 (Square)' },
          { value: '21/9',  label: '21:9 (Ultrawide)' },
          { value: '9/16',  label: '9:16 (Portrait / Short)' },
        ]"
        @update:model-value="v => emit('update', { id: block.id, data: { aspectRatio: v } })"
      />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Max width</label>
      <input
        :value="block.data.maxWidth"
        type="text"
        placeholder="e.g. 800px or 100%"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { maxWidth: $event.target.value } })"
      />
    </div>
  </div>
</template>

<script setup>
import SelectBox from '@/Components/SelectBox.vue'

const props = defineProps({
  block: { type: Object, required: true },
  tab:   { type: String, default: null },
})
const emit = defineEmits(['update'])
</script>
```

### Step 3: Register in BlockRenderer.vue

```js
import EmbedBlock from '@/Components/Blocks/EmbedBlock.vue'
```

```js
embed: EmbedBlock,
```

### Step 4: Update BlockCanvas.vue

Add `EmbedBlock` to BLOCK_MAP (it renders in wireframe via the regular `<component :is="BLOCK_MAP[block.type]">` path):
```js
import EmbedBlock from '@/Components/Blocks/EmbedBlock.vue'
```

```js
embed: EmbedBlock,
```

Add to `isEmptyBlock`:
```js
case 'embed': return !d.url
```

### Step 5: Register EmbedSettings in BlockLayers.vue

```js
import EmbedSettings from './blocks/EmbedSettings.vue'
```

```js
embed: EmbedSettings,
```

### Step 6: Build and verify

```bash
npm run build
```

Drag an Embed block. Paste a YouTube URL in the Content tab. Canvas wireframe should show the iframe preview. Style tab shows aspect ratio and max-width controls.

### Step 7: Commit

```bash
cd C:\Users\mariu\Herd\lambda-cms
git add resources/js/components/Blocks/EmbedBlock.vue
git add resources/js/Components/BlockEditor/blocks/EmbedSettings.vue
git add resources/js/components/BlockRenderer.vue
git add resources/js/Components/BlockEditor/BlockCanvas.vue
git add resources/js/Components/BlockEditor/BlockLayers.vue
git commit -m "feat: add Embed block for YouTube, Vimeo, Maps, and generic iframes"
```

---

## Task 9: Pagination block

**Files:**
- Create: `resources/js/components/Blocks/PaginationBlock.vue`
- Create: `resources/js/Components/BlockEditor/blocks/PaginationSettings.vue`
- Modify: `resources/js/components/BlockRenderer.vue`
- Modify: `resources/js/Components/BlockEditor/BlockCanvas.vue`
- Modify: `resources/js/Components/BlockEditor/BlockLayers.vue`

### Step 1: Create PaginationBlock.vue

```vue
<!-- resources/js/Components/Blocks/PaginationBlock.vue -->
<script setup>
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({ block: { type: Object, required: true } })

const data = computed(() => props.block.data ?? {})
const pageParam   = computed(() => data.value.pageParam   || 'page')
const style       = computed(() => data.value.style       || 'prev-next')
const prevLabel   = computed(() => data.value.prevLabel   || '← Previous')
const nextLabel   = computed(() => data.value.nextLabel   || 'Next →')
const alignment   = computed(() => data.value.alignment   || 'center')
const buttonStyle = computed(() => data.value.buttonStyle || 'outline')

const currentPage = computed(() => {
  try {
    const url = new URL(window.location.href)
    return parseInt(url.searchParams.get(pageParam.value) || '1', 10)
  } catch {
    return 1
  }
})

function goToPage(p) {
  if (p < 1) return
  try {
    const url = new URL(window.location.href)
    url.searchParams.set(pageParam.value, p)
    router.visit(url.pathname + url.search, { preserveState: true, preserveScroll: true })
  } catch { /* SSR guard */ }
}

const wrapperClass = computed(() => [
  'flex items-center gap-2',
  alignment.value === 'center' ? 'justify-center' :
  alignment.value === 'right'  ? 'justify-end' : 'justify-start',
])

function btnClass(disabled = false) {
  const base = 'px-4 py-2 text-sm rounded transition-colors'
  const style = buttonStyle.value
  if (style === 'outline') return `${base} border border-border hover:border-muted-foreground ${disabled ? 'opacity-40 cursor-not-allowed' : ''}`
  if (style === 'solid')   return `${base} bg-primary text-primary-foreground hover:bg-primary/90 ${disabled ? 'opacity-40 cursor-not-allowed' : ''}`
  // ghost
  return `${base} hover:bg-muted ${disabled ? 'opacity-40 cursor-not-allowed' : ''}`
}
</script>

<template>
  <div :class="wrapperClass">
    <button
      type="button"
      :class="btnClass(currentPage <= 1)"
      :disabled="currentPage <= 1"
      @click="goToPage(currentPage - 1)"
    >
      {{ prevLabel }}
    </button>

    <span v-if="style === 'numbered'" class="text-sm text-muted-foreground px-2">
      Page {{ currentPage }}
    </span>

    <button
      type="button"
      :class="btnClass()"
      @click="goToPage(currentPage + 1)"
    >
      {{ nextLabel }}
    </button>
  </div>
</template>
```

### Step 2: Create PaginationSettings.vue

```vue
<!-- resources/js/Components/BlockEditor/blocks/PaginationSettings.vue -->
<template>
  <!-- Content tab -->
  <div v-show="!tab || tab === 'content'" class="space-y-3">
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Page URL param</label>
      <input
        :value="block.data.pageParam ?? 'page'"
        type="text"
        placeholder="page"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { pageParam: $event.target.value } })"
      />
      <p class="text-[10px] text-muted-foreground mt-1">Must match the URL param key set in the paired Loop block's filter.</p>
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Style</label>
      <SelectBox
        :model-value="block.data.style ?? 'prev-next'"
        :data="[
          { value: 'prev-next', label: 'Prev / Next only' },
          { value: 'numbered',  label: 'Numbered (shows current page)' },
        ]"
        @update:model-value="v => emit('update', { id: block.id, data: { style: v } })"
      />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Previous label</label>
      <input
        :value="block.data.prevLabel ?? '← Previous'"
        type="text"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { prevLabel: $event.target.value } })"
      />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Next label</label>
      <input
        :value="block.data.nextLabel ?? 'Next →'"
        type="text"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { nextLabel: $event.target.value } })"
      />
    </div>
  </div>

  <!-- Style tab -->
  <div v-show="!tab || tab === 'style'" class="space-y-3">
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Alignment</label>
      <div class="flex gap-1">
        <button
          v-for="al in ['left', 'center', 'right']"
          :key="al"
          type="button"
          class="flex-1 px-2 py-1 text-xs rounded border capitalize transition-colors"
          :class="(block.data.alignment ?? 'center') === al
            ? 'bg-primary text-primary-foreground border-primary'
            : 'bg-background border-border hover:border-muted-foreground'"
          @click="emit('update', { id: block.id, data: { alignment: al } })"
        >{{ al }}</button>
      </div>
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Button style</label>
      <SelectBox
        :model-value="block.data.buttonStyle ?? 'outline'"
        :data="[
          { value: 'outline', label: 'Outline' },
          { value: 'ghost',   label: 'Ghost' },
          { value: 'solid',   label: 'Solid' },
        ]"
        @update:model-value="v => emit('update', { id: block.id, data: { buttonStyle: v } })"
      />
    </div>
  </div>
</template>

<script setup>
import SelectBox from '@/Components/SelectBox.vue'

const props = defineProps({
  block: { type: Object, required: true },
  tab:   { type: String, default: null },
})
const emit = defineEmits(['update'])
</script>
```

### Step 3: Register in BlockRenderer.vue

```js
import PaginationBlock from '@/Components/Blocks/PaginationBlock.vue'
```

```js
pagination: PaginationBlock,
```

### Step 4: Register in BlockCanvas.vue (BLOCK_MAP + isEmptyBlock)

```js
import PaginationBlock from '@/Components/Blocks/PaginationBlock.vue'
```

```js
pagination: PaginationBlock,
```

`isEmptyBlock` does not need a case for pagination (returns `false` — it always has content).

### Step 5: Register PaginationSettings in BlockLayers.vue

```js
import PaginationSettings from './blocks/PaginationSettings.vue'
```

```js
pagination: PaginationSettings,
```

### Step 6: Build and verify

```bash
npm run build
```

Drag a Pagination block. It should render prev/next buttons. In live preview, clicking next should append `?page=2` to the URL.

### Step 7: Commit

```bash
cd C:\Users\mariu\Herd\lambda-cms
git add resources/js/components/Blocks/PaginationBlock.vue
git add resources/js/Components/BlockEditor/blocks/PaginationSettings.vue
git add resources/js/components/BlockRenderer.vue
git add resources/js/Components/BlockEditor/BlockCanvas.vue
git add resources/js/Components/BlockEditor/BlockLayers.vue
git commit -m "feat: add Pagination block for Loop block page navigation"
```

---

## Task 10: Wire canvas editor for nested blocks + final build

**Files:**
- Modify: `resources/js/Components/BlockEditor/EditorLoopBlock.vue`

### Step 1: Add EditorLinkBlock, EditorAccordionBlock, EditorTabsBlock to EditorLoopBlock

Open `EditorLoopBlock.vue`. Find `defineAsyncComponent` imports (line 105). Add:

```js
const EditorLinkBlock      = defineAsyncComponent(() => import('./EditorLinkBlock.vue'))
const EditorAccordionBlock = defineAsyncComponent(() => import('./EditorAccordionBlock.vue'))
const EditorTabsBlock      = defineAsyncComponent(() => import('./EditorTabsBlock.vue'))
```

In the template, find the chain of `v-if` checks for container/section/loop. Add:

```html
<EditorLinkBlock
  v-else-if="child.type === 'link'"
  :block="child"
  :selected-id="selectedId"
  @select="$emit('select', $event)"
  @update-children="$emit('update-children', $event)"
/>
<EditorAccordionBlock
  v-else-if="child.type === 'accordion'"
  :block="child"
  :selected-id="selectedId"
  @select="$emit('select', $event)"
  @update-children="$emit('update-children', $event)"
/>
<EditorTabsBlock
  v-else-if="child.type === 'tabs'"
  :block="child"
  :selected-id="selectedId"
  @select="$emit('select', $event)"
  @update-children="$emit('update-children', $event)"
/>
```

### Step 2: Final build

```bash
cd C:\Users\mariu\Herd\lambda-cms && npm run build
```

Expected: clean build with pre-existing chunk size warning only.

### Step 3: Run tests

```bash
php artisan test
```

Expected: all tests pass (no backend changes were made).

### Step 4: Commit

```bash
cd C:\Users\mariu\Herd\lambda-cms
git add resources/js/Components/BlockEditor/EditorLoopBlock.vue
git commit -m "feat: wire link, accordion, and tabs as nestable editor blocks inside loops"
```

### Step 5: Push

```bash
git push origin master
```

---

## Summary of all new files

**Runtime blocks (resources/js/components/Blocks/):**
- `LinkBlock.vue`
- `AccordionBlock.vue`
- `TabsBlock.vue`
- `EmbedBlock.vue`
- `PaginationBlock.vue`

**Settings (resources/js/Components/BlockEditor/blocks/):**
- `IconSettings.vue` (shared)
- `LinkSettings.vue`
- `AccordionSettings.vue`
- `AccordionItemSettings.vue`
- `TabsSettings.vue`
- `TabItemSettings.vue`
- `EmbedSettings.vue`
- `PaginationSettings.vue`

**Canvas editors (resources/js/Components/BlockEditor/):**
- `EditorLinkBlock.vue`
- `EditorAccordionBlock.vue`
- `EditorTabsBlock.vue`

**Composables (resources/js/composables/):**
- `useIconResolver.js`

**Modified files:**
- `BlockTypePanel.vue` — ALL_TYPES, DEFAULT_DATA, cloneBlock
- `BlockRenderer.vue` — BLOCK_MAP, loadFontsFromBlocks
- `BlockCanvas.vue` — LABELS, BLOCK_MAP, isEmptyBlock, template v-else-if chain
- `BlockLayers.vue` — LABELS, STYLE_BLOCKS, settings component map
- `EditorLoopBlock.vue` — LABELS, NESTABLE, template async components
- `HeadingBlock.vue` — icon rendering
- `HeadingSettings.vue` — Style tab with IconSettings
