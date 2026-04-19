# Icon Picker Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add a searchable SVG icon picker (FA6 + Lucide + Tabler) to HeadingBlock and ParagraphBlock, with position/size/color/gap controls, plus fix the canvas flex-row layout bug where sibling containers smoosh together.

**Architecture:** `@iconify/vue` renders icons as inline SVGs; JSON icon data from `@iconify-json/*` packages is imported inside `IconPickerInput.vue` and registered via `addCollection()` so icons render offline in the editor. Public blocks (`HeadingBlock`, `ParagraphBlock`) only import `Icon` from `@iconify/vue` — no JSON payload needed on public pages since the collections are registered globally at app boot. `IconSettings.vue` is refactored to use the new picker; `ParagraphSettings.vue` gains an icon section; both blocks gain icon rendering logic.

**Tech Stack:** Vue 3 Composition API, `@iconify/vue`, `@iconify-json/*`, Tailwind CSS 4, lucide-vue-next.

---

### Task 0: Fix canvas layout bug — sibling containers smoosh in flex-row

**Files:**
- Modify: `resources/js/components/BlockEditor/EditorContainerBlock.vue:22-27`

**Context:** When a Container block is set to `mode: flex` with `direction: row`, its child containers render inside a `flex flex-row` droppable area. Leaf children (pills) already receive `flex-1 min-w-0`. Nestable children (nested containers, sections, loop blocks) do not — so they collapse to content width instead of sharing available space equally.

**Step 1: Apply the fix**

In `EditorContainerBlock.vue`, find the nestable child wrapper div (around line 22). Change its `:class` binding from:

```html
<div
  v-if="isNestable(child.type)"
  :id="child.customId || `block-${child.id}`"
  class="rounded-md border bg-background/50 overflow-hidden transition-colors"
  :class="child.id === selectedId
    ? 'border-primary ring-1 ring-primary'
    : 'border-border hover:border-muted-foreground'"
>
```

To:

```html
<div
  v-if="isNestable(child.type)"
  :id="child.customId || `block-${child.id}`"
  class="rounded-md border bg-background/50 overflow-hidden transition-colors"
  :class="[
    child.id === selectedId
      ? 'border-primary ring-1 ring-primary'
      : 'border-border hover:border-muted-foreground',
    isFlexRow ? 'flex-1 min-w-0' : '',
  ]"
>
```

**Step 2: Verify**

Open the block editor. Add a Container block set to `flex` + `row` direction. Drop two child containers inside it. Confirm they share available width equally instead of sitting side-by-side at minimum width.

**Step 3: Commit**

```bash
git add resources/js/components/BlockEditor/EditorContainerBlock.vue
git commit -m "fix: container children get flex-1 min-w-0 in flex-row editor canvas"
```

---

### Task 1: Install icon packages

**Step 1: Install**

```bash
cd C:\Users\mariu\Herd\lambda-cms
npm install @iconify/vue @iconify-json/fa6-solid @iconify-json/fa6-regular @iconify-json/fa6-brands @iconify-json/lucide @iconify-json/tabler
```

Expected: packages added to `package.json` and `package-lock.json`. No errors.

**Step 2: Commit**

```bash
git add package.json package-lock.json
git commit -m "chore: install @iconify/vue and icon set packages"
```

---

### Task 2: Create `IconPickerInput.vue`

**Files:**
- Create: `resources/js/components/BlockEditor/IconPickerInput.vue`

**Context:** This is the reusable icon selector used by `IconSettings.vue`. It renders as a select-like button; clicking it opens a full-screen overlay dialog with set filter tabs, a search field, and a paginated icon grid (96 per page, 8 columns). Selecting an icon closes the dialog and emits `update:modelValue` with the Iconify identifier string (e.g. `"fa6-solid:star"`). It also registers all icon collections via `addCollection` so they render offline.

**Step 1: Create the file**

```vue
<!-- resources/js/components/BlockEditor/IconPickerInput.vue -->
<template>
  <div>
    <!-- Trigger -->
    <button
      type="button"
      class="w-full flex items-center gap-2 rounded-md border bg-background px-2 py-1.5 text-sm text-left hover:border-muted-foreground transition-colors"
      @click="open = true"
    >
      <Icon v-if="modelValue" :icon="modelValue" class="w-4 h-4 shrink-0" />
      <span class="flex-1 truncate" :class="modelValue ? 'text-foreground' : 'text-muted-foreground'">
        {{ modelValue ? modelValue.split(':')[1] : 'Select icon…' }}
      </span>
      <ChevronDown class="w-3 h-3 text-muted-foreground shrink-0" />
    </button>

    <button
      v-if="modelValue"
      type="button"
      class="mt-1 text-[10px] text-muted-foreground hover:text-foreground transition-colors"
      @click="$emit('update:modelValue', null)"
    >Clear</button>

    <!-- Dialog -->
    <Teleport to="body">
      <div
        v-if="open"
        class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/60"
        @click.self="open = false"
      >
        <div class="bg-background border border-border rounded-xl shadow-2xl w-[680px] max-h-[85vh] flex flex-col p-4 gap-3">

          <!-- Header -->
          <div class="flex items-center justify-between shrink-0">
            <h3 class="text-sm font-semibold">Select Icon</h3>
            <button type="button" class="text-muted-foreground hover:text-foreground" @click="open = false">
              <X class="w-4 h-4" />
            </button>
          </div>

          <!-- Set tabs -->
          <div class="flex gap-1 flex-wrap shrink-0">
            <button
              v-for="s in SETS"
              :key="s.id"
              type="button"
              class="px-2 py-0.5 text-xs rounded border transition-colors"
              :class="activeSet === s.id
                ? 'bg-primary text-primary-foreground border-primary'
                : 'bg-background border-border hover:border-muted-foreground'"
              @click="activeSet = s.id; page = 0"
            >
              {{ s.label }}
              <span class="opacity-60 ml-0.5">({{ s.id === 'all' ? FLAT_ALL.length : (ALL_ICONS[s.id]?.length ?? 0) }})</span>
            </button>
          </div>

          <!-- Search -->
          <input
            v-model="search"
            type="text"
            placeholder="Search icons…"
            class="shrink-0 w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
            @input="page = 0"
          />

          <!-- Grid -->
          <div class="flex-1 overflow-y-auto min-h-0">
            <div class="grid grid-cols-8 gap-1">
              <button
                v-for="ic in pagedIcons"
                :key="ic"
                type="button"
                class="flex flex-col items-center gap-0.5 p-1.5 rounded hover:bg-muted transition-colors"
                :class="ic === modelValue ? 'bg-primary/15 ring-1 ring-primary rounded' : ''"
                :title="ic"
                @click="select(ic)"
              >
                <Icon :icon="ic" class="w-6 h-6 shrink-0" />
                <span class="text-[9px] text-muted-foreground truncate w-full text-center leading-none">
                  {{ ic.split(':')[1] }}
                </span>
              </button>
            </div>

            <p v-if="filteredIcons.length === 0" class="text-center text-sm text-muted-foreground py-8">
              No icons found for "{{ search }}"
            </p>
          </div>

          <!-- Pagination -->
          <div v-if="totalPages > 1" class="flex items-center justify-between text-xs text-muted-foreground shrink-0 pt-1 border-t border-border">
            <button
              type="button"
              class="px-2 py-1 rounded border hover:bg-muted disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
              :disabled="page === 0"
              @click="page--"
            >← Prev</button>
            <span>{{ page + 1 }} / {{ totalPages }}</span>
            <button
              type="button"
              class="px-2 py-1 rounded border hover:bg-muted disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
              :disabled="page >= totalPages - 1"
              @click="page++"
            >Next →</button>
          </div>

        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Icon, addCollection } from '@iconify/vue'
import { ChevronDown, X } from 'lucide-vue-next'

import fa6SolidData   from '@iconify-json/fa6-solid/icons.json'
import fa6RegularData from '@iconify-json/fa6-regular/icons.json'
import fa6BrandsData  from '@iconify-json/fa6-brands/icons.json'
import lucideData     from '@iconify-json/lucide/icons.json'
import tablerData     from '@iconify-json/tabler/icons.json'

// Register all sets — icons render offline in the editor; public pages use Iconify API fallback
addCollection(fa6SolidData)
addCollection(fa6RegularData)
addCollection(fa6BrandsData)
addCollection(lucideData)
addCollection(tablerData)

function buildList(data) {
  return Object.keys(data.icons).map(n => `${data.prefix}:${n}`)
}

const ALL_ICONS = {
  'fa6-solid':   buildList(fa6SolidData),
  'fa6-regular': buildList(fa6RegularData),
  'fa6-brands':  buildList(fa6BrandsData),
  'lucide':      buildList(lucideData),
  'tabler':      buildList(tablerData),
}

const FLAT_ALL = Object.values(ALL_ICONS).flat()

const SETS = [
  { id: 'all',         label: 'All' },
  { id: 'fa6-solid',   label: 'FA Solid' },
  { id: 'fa6-regular', label: 'FA Regular' },
  { id: 'fa6-brands',  label: 'FA Brands' },
  { id: 'lucide',      label: 'Lucide' },
  { id: 'tabler',      label: 'Tabler' },
]

const PAGE_SIZE = 96  // 8 columns × 12 rows

const props = defineProps({ modelValue: { type: String, default: null } })
const emit  = defineEmits(['update:modelValue'])

const open      = ref(false)
const activeSet = ref('all')
const search    = ref('')
const page      = ref(0)

const sourceIcons = computed(() =>
  activeSet.value === 'all' ? FLAT_ALL : (ALL_ICONS[activeSet.value] ?? [])
)

const filteredIcons = computed(() => {
  const q = search.value.trim().toLowerCase()
  if (!q) return sourceIcons.value
  return sourceIcons.value.filter(ic => ic.split(':')[1].includes(q))
})

const totalPages  = computed(() => Math.max(1, Math.ceil(filteredIcons.value.length / PAGE_SIZE)))
const pagedIcons  = computed(() => {
  const start = page.value * PAGE_SIZE
  return filteredIcons.value.slice(start, start + PAGE_SIZE)
})

function select(ic) {
  emit('update:modelValue', ic)
  open.value = false
}
</script>
```

**Step 2: Verify**

No automated test for UI. Manually verify in the next task when it's wired into IconSettings.

**Step 3: Commit**

```bash
git add resources/js/components/BlockEditor/IconPickerInput.vue
git commit -m "feat: add IconPickerInput dialog with FA6 + Lucide + Tabler sets"
```

---

### Task 3: Refactor `IconSettings.vue`

**Files:**
- Modify: `resources/js/components/BlockEditor/blocks/IconSettings.vue`

**Context:** Replace the old Lucide-only SelectBox with `IconPickerInput`. Change the position labels from `left/right` to `prefix/suffix`. Replace the size toggle buttons with a text input (CSS value). Add a gap text input. Update color to use `null` for inherit instead of the string `'inherit'`. The `emit` shape stays identical — `{ id, data: { icon: {...} } }`.

**Step 1: Replace the entire file**

```vue
<!-- resources/js/components/BlockEditor/blocks/IconSettings.vue -->
<template>
  <div class="space-y-3">
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Icon</label>
      <IconPickerInput
        :model-value="icon.name || null"
        @update:model-value="v => update('name', v || null)"
      />
    </div>

    <template v-if="icon.name">
      <div>
        <label class="text-xs font-medium text-muted-foreground block mb-1">Position</label>
        <div class="flex gap-1">
          <button
            v-for="[val, label] in [['prefix', 'Prefix'], ['suffix', 'Suffix']]"
            :key="val"
            type="button"
            class="flex-1 px-2 py-1 text-xs rounded border transition-colors"
            :class="(icon.position ?? 'prefix') === val
              ? 'bg-primary text-primary-foreground border-primary'
              : 'bg-background border-border hover:border-muted-foreground'"
            @click="update('position', val)"
          >{{ label }}</button>
        </div>
      </div>

      <div>
        <label class="text-xs font-medium text-muted-foreground block mb-1">Size</label>
        <input
          :value="icon.size ?? '1.25em'"
          type="text"
          placeholder="1.25em"
          class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
          @change="update('size', $event.target.value)"
        />
      </div>

      <div>
        <label class="text-xs font-medium text-muted-foreground block mb-1">Gap</label>
        <input
          :value="icon.gap ?? '0.5em'"
          type="text"
          placeholder="0.5em"
          class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
          @change="update('gap', $event.target.value)"
        />
      </div>

      <div>
        <label class="text-xs font-medium text-muted-foreground block mb-1">Color</label>
        <div class="flex items-center gap-2">
          <button
            type="button"
            class="px-2 py-1 text-xs rounded border transition-colors"
            :class="!icon.color
              ? 'bg-primary text-primary-foreground border-primary'
              : 'bg-background border-border hover:border-muted-foreground'"
            @click="update('color', null)"
          >Inherit</button>
          <ColorPicker
            :model-value="icon.color || '#000000'"
            :show-value="false"
            @update:model-value="v => update('color', v)"
          />
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import IconPickerInput from '../IconPickerInput.vue'
import ColorPicker     from '../ColorPicker.vue'

const props = defineProps({ block: { type: Object, required: true } })
const emit  = defineEmits(['update'])

const icon = computed(() => props.block.data?.icon ?? {
  name: null, position: 'prefix', size: '1.25em', color: null, gap: '0.5em',
})

function update(key, value) {
  emit('update', {
    id:   props.block.id,
    data: { icon: { ...icon.value, [key]: value } },
  })
}
</script>
```

**Step 2: Verify**

Open the block editor. Click a Heading block. Go to Style tab. Confirm the icon section shows the picker button ("Select icon…"). Click it — the dialog should open with tabs, search, and a grid of icons. Select one — it should appear in the trigger button. Position/Size/Gap/Color controls should appear below. Clearing should reset to "Select icon…".

**Step 3: Commit**

```bash
git add resources/js/components/BlockEditor/blocks/IconSettings.vue
git commit -m "feat: refactor IconSettings to use IconPickerInput with new data shape"
```

---

### Task 4: Update `HeadingBlock.vue` — render icon prefix/suffix

**Files:**
- Modify: `resources/js/Components/Blocks/HeadingBlock.vue`

**Context:** When `block.data.icon.name` is set, wrap heading content in a flex container so the icon and text sit side-by-side. When no icon, rendering is identical to today (no wrapper, no extra DOM). Uses `@iconify/vue`'s `Icon` component which resolves icons via the globally registered collections (registered by `IconPickerInput` in the editor) or the Iconify API on the public frontend.

**Step 1: Replace the entire file**

```vue
<!-- resources/js/Components/Blocks/HeadingBlock.vue -->
<template>
  <component
    :is="'h' + block.data.level"
    class="font-bold leading-tight"
    :style="hasIcon ? { display: 'flex', alignItems: 'center', gap: icon.gap ?? '0.5em' } : undefined"
  >
    <Icon
      v-if="hasIcon && (icon.position ?? 'prefix') !== 'suffix'"
      :icon="icon.name"
      :style="iconStyle"
      aria-hidden="true"
    />
    <span>{{ resolvedText }}</span>
    <Icon
      v-if="hasIcon && icon.position === 'suffix'"
      :icon="icon.name"
      :style="iconStyle"
      aria-hidden="true"
    />
  </component>
</template>

<script setup>
import { computed } from 'vue'
import { Icon } from '@iconify/vue'
import { useFieldBinding } from '@/composables/useLoopBinding.js'

const props = defineProps({ block: { type: Object, required: true } })

const resolvedText = useFieldBinding(() => props.block, 'text')

const icon    = computed(() => props.block.data?.icon ?? null)
const hasIcon = computed(() => !!(icon.value?.name))

const iconStyle = computed(() => {
  if (!icon.value) return {}
  const s = {}
  if (icon.value.size)  s.fontSize = icon.value.size
  if (icon.value.color) s.color    = icon.value.color
  return s
})
</script>
```

**Step 2: Verify**

On a public page (or block editor preview), add a Heading block, set an icon via settings, and confirm:
- Prefix position: icon appears to the left of heading text
- Suffix position: icon appears to the right
- Size "2em" makes the icon larger than text
- Color "#ff0000" makes the icon red; "Inherit" uses the heading's text color
- Heading with no icon renders exactly as before (no wrapper span, no flex)

**Step 3: Commit**

```bash
git add resources/js/Components/Blocks/HeadingBlock.vue
git commit -m "feat: HeadingBlock renders icon prefix/suffix via @iconify/vue"
```

---

### Task 5: Update `ParagraphBlock.vue` — render icon prefix/suffix

**Files:**
- Modify: `resources/js/Components/Blocks/ParagraphBlock.vue`

**Context:** Same pattern as HeadingBlock. The paragraph content (`v-html`) becomes a sibling of the icon inside a flex container. `mt-[0.2em]` on the icon keeps it visually aligned with the first line of text (works for single and multi-line paragraphs).

**Step 1: Replace the entire file**

```vue
<!-- resources/js/Components/Blocks/ParagraphBlock.vue -->
<template>
  <div
    :style="hasIcon ? { display: 'flex', alignItems: 'flex-start', gap: icon.gap ?? '0.5em' } : undefined"
  >
    <Icon
      v-if="hasIcon && (icon.position ?? 'prefix') !== 'suffix'"
      :icon="icon.name"
      :style="iconStyle"
      class="shrink-0 mt-[0.2em]"
      aria-hidden="true"
    />
    <div class="prose prose-sm max-w-none dark:prose-invert" v-html="resolvedContent" />
    <Icon
      v-if="hasIcon && icon.position === 'suffix'"
      :icon="icon.name"
      :style="iconStyle"
      class="shrink-0 mt-[0.2em]"
      aria-hidden="true"
    />
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Icon } from '@iconify/vue'
import { useFieldBinding } from '@/composables/useLoopBinding.js'

const props = defineProps({ block: { type: Object, required: true } })

const resolvedContent = useFieldBinding(() => props.block, 'content')

const icon    = computed(() => props.block.data?.icon ?? null)
const hasIcon = computed(() => !!(icon.value?.name))

const iconStyle = computed(() => {
  if (!icon.value) return {}
  const s = {}
  if (icon.value.size)  s.fontSize = icon.value.size
  if (icon.value.color) s.color    = icon.value.color
  return s
})
</script>
```

**Step 2: Verify**

Add a Paragraph block in the editor, set an icon, confirm icon appears left/right of paragraph text. Verify no icon → renders identically to before (single `<div class="prose ...">` wrapper, no outer div).

**Step 3: Commit**

```bash
git add resources/js/Components/Blocks/ParagraphBlock.vue
git commit -m "feat: ParagraphBlock renders icon prefix/suffix via @iconify/vue"
```

---

### Task 6: Add icon settings to `ParagraphSettings.vue`

**Files:**
- Modify: `resources/js/components/BlockEditor/blocks/ParagraphSettings.vue`

**Context:** `HeadingSettings` already includes `<IconSettings>` in its style tab. `ParagraphSettings` has a style tab with only `TypographyControl`. Add `IconSettings` after `TypographyControl`, plus import it.

**Step 1: Replace the entire file**

```vue
<!-- resources/js/components/BlockEditor/blocks/ParagraphSettings.vue -->
<template>
  <!-- Content fields -->
  <div v-show="!tab || tab === 'content'" class="space-y-3">
    <DynamicField
      label="Content"
      field-name="content"
      :block="block"
      :available-fields="availableFields"
      @bind="onBind"
      @unbind="onUnbind"
    >
      <TiptapEditor
        :model-value="block.data.content"
        :dark="true"
        @update:model-value="emit('update', { id: block.id, data: { content: $event } })"
      />
    </DynamicField>
  </div>

  <!-- Style fields -->
  <div v-show="!tab || tab === 'style'" class="space-y-4">

    <TypographyControl
      :model-value="block.data.typography ?? {}"
      @update:model-value="v => emit('update', { id: block.id, data: { typography: v } })"
    />

    <IconSettings :block="block" @update="emit('update', $event)" />

  </div>
</template>

<script setup>
import TiptapEditor    from '@/Components/TiptapEditor.vue'
import DynamicField    from './DynamicField.vue'
import TypographyControl from '../TypographyControl.vue'
import IconSettings    from './IconSettings.vue'

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

**Step 2: Verify**

Click a Paragraph block in the editor. Open Style tab. Confirm "Icon" section appears below Typography. Select an icon — it should render in the paragraph canvas preview.

**Step 3: Commit**

```bash
git add resources/js/components/BlockEditor/blocks/ParagraphSettings.vue
git commit -m "feat: add icon settings to ParagraphSettings style tab"
```

---

### Task 7: Production build

**Step 1: Build**

```bash
cd C:\Users\mariu\Herd\lambda-cms
npm run build
```

Expected: `✓ built` with no errors. Chunk size warning is expected (pre-existing + icon JSON adds ~500 KB gzipped). If there are actual **errors** (not warnings), fix them before continuing.

**Step 2: Commit build artifacts if any, then push**

```bash
git push
```
