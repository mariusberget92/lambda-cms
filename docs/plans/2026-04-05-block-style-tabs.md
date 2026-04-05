# Block Style Tabs — Consistency Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add a comprehensive Style tab to every content block that currently lacks one (Paragraph, Heading, Quote, Image, Video, Gallery, CTA, Code) using three new shared sub-components.

**Architecture:** Three new reusable controls (`TypographyControl`, `BorderControl`, `ShadowControl`) follow the existing `SpacingControl` / `DimensionInput` patterns — `modelValue` object prop + `update:modelValue` emit. Each `*Settings.vue` gets a `v-show="!tab || tab === 'style'"` div with the appropriate controls. `BlockLayers.vue`'s `STYLE_BLOCKS` set is updated last once all style content exists.

**Tech Stack:** Vue 3 `<script setup>`, Tailwind CSS 4, existing `SelectBox`, `DimensionInput`, `NumberInput`, `SpacingControl` components, `MediaPicker` (for CTA background image).

---

## Task 1: Create `TypographyControl.vue`

**Files:**
- Create: `resources/js/components/BlockEditor/TypographyControl.vue`

**Step 1: Create the file**

```vue
<!-- resources/js/components/BlockEditor/TypographyControl.vue -->
<template>
  <div class="space-y-3">
    <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wide block">Typography</label>

    <!-- Text alignment -->
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Alignment</label>
      <div class="flex rounded-md border overflow-hidden text-xs">
        <button
          v-for="align in ['left', 'center', 'right', 'justify']"
          :key="align"
          type="button"
          class="flex-1 py-1.5 transition-colors capitalize"
          :class="modelValue.textAlign === align
            ? 'bg-primary text-primary-foreground'
            : 'bg-background text-foreground'"
          @click="update('textAlign', modelValue.textAlign === align ? null : align)"
        >{{ align.slice(0, 1).toUpperCase() + align.slice(1, 4) }}</button>
      </div>
    </div>

    <!-- Color -->
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Color</label>
      <div class="flex items-center gap-2">
        <input
          type="color"
          :value="modelValue.color ?? '#ffffff'"
          class="h-8 w-14 cursor-pointer rounded border border-border"
          @input="update('color', $event.target.value)"
        />
        <span class="text-xs text-muted-foreground flex-1">{{ modelValue.color ?? 'Inherit' }}</span>
        <button
          v-if="modelValue.color"
          type="button"
          class="text-xs text-muted-foreground hover:text-foreground transition-colors"
          @click="update('color', null)"
        >Reset</button>
      </div>
    </div>

    <!-- Font size -->
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Font size</label>
      <DimensionInput
        :model-value="modelValue.fontSize ?? ''"
        placeholder="Inherit"
        @update:model-value="v => update('fontSize', v || null)"
      />
    </div>

    <!-- Font weight -->
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Font weight</label>
      <SelectBox
        :model-value="modelValue.fontWeight ?? ''"
        :data="[
          { value: '',    label: 'Inherit' },
          { value: '300', label: 'Light (300)' },
          { value: '400', label: 'Regular (400)' },
          { value: '500', label: 'Medium (500)' },
          { value: '600', label: 'Semibold (600)' },
          { value: '700', label: 'Bold (700)' },
          { value: '800', label: 'Extrabold (800)' },
        ]"
        @update:model-value="v => update('fontWeight', v || null)"
      />
    </div>

    <!-- Line height -->
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Line height</label>
      <DimensionInput
        :model-value="modelValue.lineHeight ?? ''"
        placeholder="Inherit"
        :units="['', 'px', 'rem', 'em']"
        @update:model-value="v => update('lineHeight', v || null)"
      />
    </div>
  </div>
</template>

<script setup>
import SelectBox    from '@/Components/SelectBox.vue'
import DimensionInput from './DimensionInput.vue'

const props = defineProps({
  modelValue: { type: Object, default: () => ({}) },
})
const emit = defineEmits(['update:modelValue'])

function update(key, value) {
  emit('update:modelValue', { ...props.modelValue, [key]: value })
}
</script>
```

**Step 2: Verify the file was created correctly**

Open `resources/js/components/BlockEditor/TypographyControl.vue` and confirm it has all 5 fields: textAlign, color, fontSize, fontWeight, lineHeight.

**Step 3: Commit**

```bash
git add resources/js/components/BlockEditor/TypographyControl.vue
git commit -m "feat: add TypographyControl shared block editor component"
```

---

## Task 2: Create `BorderControl.vue`

**Files:**
- Create: `resources/js/components/BlockEditor/BorderControl.vue`

**Step 1: Create the file**

```vue
<!-- resources/js/components/BlockEditor/BorderControl.vue -->
<template>
  <div class="space-y-3">
    <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wide block">Border</label>

    <!-- Border radius -->
    <div v-if="showRadius">
      <label class="text-xs font-medium text-muted-foreground block mb-1">Radius</label>
      <DimensionInput
        :model-value="modelValue.radius ?? ''"
        placeholder="0"
        @update:model-value="v => update('radius', v || null)"
      />
    </div>

    <!-- Border style -->
    <div v-if="showBorder">
      <label class="text-xs font-medium text-muted-foreground block mb-1">Border style</label>
      <SelectBox
        :model-value="modelValue.style ?? 'none'"
        :data="[
          { value: 'none',   label: 'None' },
          { value: 'solid',  label: 'Solid' },
          { value: 'dashed', label: 'Dashed' },
          { value: 'dotted', label: 'Dotted' },
        ]"
        @update:model-value="v => update('style', v)"
      />
    </div>

    <template v-if="showBorder && modelValue.style && modelValue.style !== 'none'">
      <!-- Border width -->
      <div>
        <label class="text-xs font-medium text-muted-foreground block mb-1">Border width</label>
        <DimensionInput
          :model-value="modelValue.width ?? '1px'"
          placeholder="1px"
          @update:model-value="v => update('width', v || '1px')"
        />
      </div>

      <!-- Border color -->
      <div>
        <label class="text-xs font-medium text-muted-foreground block mb-1">Border color</label>
        <div class="flex items-center gap-2">
          <input
            type="color"
            :value="modelValue.color ?? '#000000'"
            class="h-8 w-14 cursor-pointer rounded border border-border"
            @input="update('color', $event.target.value)"
          />
          <span class="text-xs text-muted-foreground">{{ modelValue.color ?? '#000000' }}</span>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import SelectBox    from '@/Components/SelectBox.vue'
import DimensionInput from './DimensionInput.vue'

const props = defineProps({
  modelValue:  { type: Object,  default: () => ({}) },
  showRadius:  { type: Boolean, default: true },
  showBorder:  { type: Boolean, default: true },
})
const emit = defineEmits(['update:modelValue'])

function update(key, value) {
  emit('update:modelValue', { ...props.modelValue, [key]: value })
}
</script>
```

**Step 2: Commit**

```bash
git add resources/js/components/BlockEditor/BorderControl.vue
git commit -m "feat: add BorderControl shared block editor component"
```

---

## Task 3: Create `ShadowControl.vue`

**Files:**
- Create: `resources/js/components/BlockEditor/ShadowControl.vue`

**Step 1: Create the file**

```vue
<!-- resources/js/components/BlockEditor/ShadowControl.vue -->
<template>
  <div class="space-y-2">
    <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wide block">Shadow</label>

    <!-- Preset pills -->
    <div class="flex gap-1 flex-wrap">
      <button
        v-for="preset in PRESETS"
        :key="preset.label"
        type="button"
        class="px-2 py-1 text-xs rounded border transition-colors"
        :class="isActive(preset) ? 'bg-primary text-primary-foreground border-primary' : 'bg-background border-border'"
        @click="select(preset)"
      >{{ preset.label }}</button>
    </div>

    <!-- Custom value input -->
    <div v-if="isCustom">
      <input
        type="text"
        :value="modelValue"
        placeholder="0px 4px 6px rgba(0,0,0,0.1)"
        class="w-full rounded border border-border bg-background px-2 py-1 text-xs"
        @input="emit('update:modelValue', $event.target.value)"
      />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const PRESETS = [
  { label: 'None',   value: '' },
  { label: 'SM',     value: '0 1px 2px rgba(0,0,0,0.05)' },
  { label: 'MD',     value: '0 4px 6px rgba(0,0,0,0.1)' },
  { label: 'LG',     value: '0 10px 15px rgba(0,0,0,0.15)' },
  { label: 'XL',     value: '0 20px 25px rgba(0,0,0,0.2)' },
  { label: 'Custom', value: '__custom__' },
]

const props = defineProps({
  modelValue: { type: String, default: '' },
})
const emit = defineEmits(['update:modelValue'])

const isCustom = computed(() => {
  if (!props.modelValue) return false
  return !PRESETS.slice(0, -1).some(p => p.value === props.modelValue)
})

function isActive(preset) {
  if (preset.value === '__custom__') return isCustom.value
  return props.modelValue === preset.value
}

function select(preset) {
  if (preset.value === '__custom__') {
    // Keep current value (shows the custom input), emit nothing
    return
  }
  emit('update:modelValue', preset.value)
}
</script>
```

**Step 2: Commit**

```bash
git add resources/js/components/BlockEditor/ShadowControl.vue
git commit -m "feat: add ShadowControl shared block editor component"
```

---

## Task 4: Add Style tab to `ParagraphSettings.vue`

**Files:**
- Modify: `resources/js/components/BlockEditor/blocks/ParagraphSettings.vue`

**Step 1: Replace the file contents**

Data stored in `block.data.typography` (object) and `block.data.bgColor` (string|null).

```vue
<!-- resources/js/Components/BlockEditor/blocks/ParagraphSettings.vue -->
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

    <div>
      <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wide block mb-2">Background</label>
      <div class="flex items-center gap-2">
        <input
          type="color"
          :value="block.data.bgColor ?? '#ffffff'"
          class="h-8 w-14 cursor-pointer rounded border border-border"
          @input="emit('update', { id: block.id, data: { bgColor: $event.target.value } })"
        />
        <span class="text-xs text-muted-foreground flex-1">{{ block.data.bgColor ?? 'None' }}</span>
        <button
          v-if="block.data.bgColor"
          type="button"
          class="text-xs text-muted-foreground hover:text-foreground transition-colors"
          @click="emit('update', { id: block.id, data: { bgColor: null } })"
        >Remove</button>
      </div>
    </div>

  </div>
</template>

<script setup>
import TiptapEditor    from '@/Components/TiptapEditor.vue'
import DynamicField    from './DynamicField.vue'
import TypographyControl from '../TypographyControl.vue'

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

**Step 2: Commit**

```bash
git add resources/js/components/BlockEditor/blocks/ParagraphSettings.vue
git commit -m "feat: add Style tab to Paragraph block"
```

---

## Task 5: Enhance Style tab in `HeadingSettings.vue`

**Files:**
- Modify: `resources/js/components/BlockEditor/blocks/HeadingSettings.vue`

**Step 1: Replace the file contents**

The existing Style tab only has `IconSettings`. Prepend `TypographyControl` above it. Data stored in `block.data.typography` (object).

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
  <div v-show="!tab || tab === 'style'" class="space-y-4">

    <TypographyControl
      :model-value="block.data.typography ?? {}"
      @update:model-value="v => emit('update', { id: block.id, data: { typography: v } })"
    />

    <IconSettings :block="block" @update="emit('update', $event)" />

  </div>
</template>

<script setup>
import SelectBox       from '@/Components/SelectBox.vue'
import DynamicField    from './DynamicField.vue'
import IconSettings    from './IconSettings.vue'
import TypographyControl from '../TypographyControl.vue'

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

**Step 2: Commit**

```bash
git add resources/js/components/BlockEditor/blocks/HeadingSettings.vue
git commit -m "feat: add TypographyControl to Heading Style tab"
```

---

## Task 6: Add Style tab to `QuoteSettings.vue`

**Files:**
- Modify: `resources/js/components/BlockEditor/blocks/QuoteSettings.vue`

**Step 1: Replace the file contents**

Data: `block.data.typography` (object), `block.data.bgColor` (string|null), `block.data.accentBar` (`{ style: 'none'|'left'|'top', color: string, width: string }`).

```vue
<!-- resources/js/Components/BlockEditor/blocks/QuoteSettings.vue -->
<template>
  <!-- Content fields -->
  <div v-show="!tab || tab === 'content'" class="space-y-3">
    <DynamicField
      label="Quote text"
      field-name="text"
      :block="block"
      :available-fields="availableFields"
      @bind="onBind"
      @unbind="onUnbind"
    >
      <textarea
        :value="block.data.text"
        rows="4"
        placeholder="The quote..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring resize-none"
        @input="emit('update', { id: block.id, data: { text: $event.target.value } })"
      />
    </DynamicField>

    <DynamicField
      label="Attribution"
      field-name="attribution"
      :block="block"
      :available-fields="availableFields"
      @bind="onBind"
      @unbind="onUnbind"
    >
      <input
        :value="block.data.attribution"
        type="text"
        placeholder="— Author name"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { attribution: $event.target.value } })"
      />
    </DynamicField>
  </div>

  <!-- Style fields -->
  <div v-show="!tab || tab === 'style'" class="space-y-4">

    <TypographyControl
      :model-value="block.data.typography ?? {}"
      @update:model-value="v => emit('update', { id: block.id, data: { typography: v } })"
    />

    <!-- Accent bar -->
    <div class="space-y-2">
      <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wide block">Accent bar</label>
      <SelectBox
        :model-value="block.data.accentBar?.style ?? 'left'"
        :data="[
          { value: 'none',  label: 'None' },
          { value: 'left',  label: 'Left border' },
          { value: 'top',   label: 'Top border' },
        ]"
        @update:model-value="v => updateNested('accentBar', 'style', v)"
      />
      <template v-if="(block.data.accentBar?.style ?? 'left') !== 'none'">
        <div class="flex items-center gap-2">
          <input
            type="color"
            :value="block.data.accentBar?.color ?? '#5e81ac'"
            class="h-8 w-14 cursor-pointer rounded border border-border"
            @input="updateNested('accentBar', 'color', $event.target.value)"
          />
          <span class="text-xs text-muted-foreground">Color</span>
        </div>
        <DimensionInput
          :model-value="block.data.accentBar?.width ?? '4px'"
          placeholder="4px"
          @update:model-value="v => updateNested('accentBar', 'width', v || '4px')"
        />
      </template>
    </div>

    <!-- Background color -->
    <div>
      <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wide block mb-2">Background</label>
      <div class="flex items-center gap-2">
        <input
          type="color"
          :value="block.data.bgColor ?? '#ffffff'"
          class="h-8 w-14 cursor-pointer rounded border border-border"
          @input="emit('update', { id: block.id, data: { bgColor: $event.target.value } })"
        />
        <span class="text-xs text-muted-foreground flex-1">{{ block.data.bgColor ?? 'None' }}</span>
        <button
          v-if="block.data.bgColor"
          type="button"
          class="text-xs text-muted-foreground hover:text-foreground transition-colors"
          @click="emit('update', { id: block.id, data: { bgColor: null } })"
        >Remove</button>
      </div>
    </div>

  </div>
</template>

<script setup>
import DynamicField    from './DynamicField.vue'
import TypographyControl from '../TypographyControl.vue'
import DimensionInput  from '../DimensionInput.vue'
import SelectBox       from '@/Components/SelectBox.vue'

const props = defineProps({
  block:           { type: Object, required: true },
  availableFields: { type: Array,  default: () => [] },
  tab:             { type: String, default: null },
})
const emit = defineEmits(['update'])

function updateNested(key, subKey, value) {
  const current = props.block.data[key] ?? {}
  emit('update', { id: props.block.id, data: { [key]: { ...current, [subKey]: value } } })
}
function onBind(fieldName, value) {
  emit('update', { id: props.block.id, bindings: { ...props.block.bindings, [fieldName]: value } })
}
function onUnbind(fieldName) {
  const b = { ...(props.block.bindings ?? {}) }
  delete b[fieldName]
  emit('update', { id: props.block.id, bindings: Object.keys(b).length ? b : undefined })
}
</script>
```

**Step 2: Commit**

```bash
git add resources/js/components/BlockEditor/blocks/QuoteSettings.vue
git commit -m "feat: add Style tab to Quote block"
```

---

## Task 7: Add Style tab to `ImageSettings.vue`

**Files:**
- Modify: `resources/js/components/BlockEditor/blocks/ImageSettings.vue`

**Step 1: Add Style section and imports**

Add after the closing `</div>` of the content section (before `</template>`):

```vue
  <!-- Style fields -->
  <div v-show="!tab || tab === 'style'" class="space-y-4">

    <!-- Max width + alignment -->
    <div class="space-y-2">
      <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wide block">Layout</label>
      <div>
        <label class="text-xs font-medium text-muted-foreground block mb-1">Max width</label>
        <DimensionInput
          :model-value="block.data.maxWidth ?? ''"
          placeholder="100%"
          :allow-auto="true"
          @update:model-value="v => emit('update', { id: block.id, data: { maxWidth: v || null } })"
        />
      </div>
      <div>
        <label class="text-xs font-medium text-muted-foreground block mb-1">Alignment</label>
        <div class="flex rounded-md border overflow-hidden text-xs">
          <button
            v-for="align in ['left', 'center', 'right']"
            :key="align"
            type="button"
            class="flex-1 py-1.5 transition-colors capitalize"
            :class="block.data.alignment === align
              ? 'bg-primary text-primary-foreground'
              : 'bg-background text-foreground'"
            @click="emit('update', { id: block.id, data: { alignment: block.data.alignment === align ? null : align } })"
          >{{ align.charAt(0).toUpperCase() + align.slice(1) }}</button>
        </div>
      </div>
    </div>

    <BorderControl
      :model-value="block.data.border ?? {}"
      @update:model-value="v => emit('update', { id: block.id, data: { border: v } })"
    />

    <ShadowControl
      :model-value="block.data.shadow ?? ''"
      @update:model-value="v => emit('update', { id: block.id, data: { shadow: v } })"
    />

  </div>
```

**Step 2: Add imports to `<script setup>`**

Replace the existing `<script setup>` block:

```vue
<script setup>
import { ref, computed } from 'vue'
import MediaPicker    from '@/Components/MediaPicker.vue'
import DynamicField   from './DynamicField.vue'
import DimensionInput from '../DimensionInput.vue'
import BorderControl  from '../BorderControl.vue'
import ShadowControl  from '../ShadowControl.vue'

const props = defineProps({
  block:           { type: Object, required: true },
  availableFields: { type: Array,  default: () => [] },
  tab:             { type: String, default: null },
})
const emit = defineEmits(['update'])

const showPicker = ref(false)

const mode = computed(() => props.block.data.media_id ? 'library' : 'url')

function switchMode(newMode) {
  if (newMode === 'library') {
    showPicker.value = true
  } else {
    emit('update', { id: props.block.id, data: { media_id: null } })
  }
}

function onMediaSelect(media) {
  showPicker.value = false
  emit('update', { id: props.block.id, data: { media_id: media.id, url: media.url, alt: media.alt ?? '' } })
}
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

**Step 3: Commit**

```bash
git add resources/js/components/BlockEditor/blocks/ImageSettings.vue
git commit -m "feat: add Style tab to Image block"
```

---

## Task 8: Add Style tab to `VideoSettings.vue`

**Files:**
- Modify: `resources/js/components/BlockEditor/blocks/VideoSettings.vue`

**Step 1: Add Style section before `</template>`**

```vue
  <!-- Style fields -->
  <div v-show="!tab || tab === 'style'" class="space-y-4">

    <div class="space-y-2">
      <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wide block">Layout</label>
      <div>
        <label class="text-xs font-medium text-muted-foreground block mb-1">Max width</label>
        <DimensionInput
          :model-value="block.data.maxWidth ?? ''"
          placeholder="100%"
          :allow-auto="true"
          @update:model-value="v => emit('update', { id: block.id, data: { maxWidth: v || null } })"
        />
      </div>
      <div>
        <label class="text-xs font-medium text-muted-foreground block mb-1">Alignment</label>
        <div class="flex rounded-md border overflow-hidden text-xs">
          <button
            v-for="align in ['left', 'center', 'right']"
            :key="align"
            type="button"
            class="flex-1 py-1.5 transition-colors"
            :class="block.data.alignment === align
              ? 'bg-primary text-primary-foreground'
              : 'bg-background text-foreground'"
            @click="emit('update', { id: block.id, data: { alignment: block.data.alignment === align ? null : align } })"
          >{{ align.charAt(0).toUpperCase() + align.slice(1) }}</button>
        </div>
      </div>
    </div>

    <div>
      <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wide block mb-2">Aspect ratio</label>
      <div class="flex gap-1 flex-wrap">
        <button
          v-for="ratio in ['16/9', '4/3', '1/1', '9/16']"
          :key="ratio"
          type="button"
          class="px-2 py-1 text-xs rounded border transition-colors"
          :class="block.data.aspectRatio === ratio
            ? 'bg-primary text-primary-foreground border-primary'
            : 'bg-background border-border'"
          @click="emit('update', { id: block.id, data: { aspectRatio: block.data.aspectRatio === ratio ? null : ratio } })"
        >{{ ratio }}</button>
      </div>
    </div>

    <div>
      <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wide block mb-2">Border radius</label>
      <DimensionInput
        :model-value="block.data.borderRadius ?? ''"
        placeholder="0"
        @update:model-value="v => emit('update', { id: block.id, data: { borderRadius: v || null } })"
      />
    </div>

  </div>
```

**Step 2: Add imports to `<script setup>`**

Replace the existing `<script setup>`:

```vue
<script setup>
import { ref, computed } from 'vue'
import DynamicField   from './DynamicField.vue'
import DimensionInput from '../DimensionInput.vue'

const props = defineProps({
  block:           { type: Object, required: true },
  availableFields: { type: Array,  default: () => [] },
  tab:             { type: String, default: null },
})
const emit = defineEmits(['update'])

const urlError = ref('')

const embedUrl = computed(() => {
  const url = props.block.data.url ?? ''
  if (!url) return null
  const ytMatch = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\s]+)/)
  if (ytMatch) return `https://www.youtube.com/embed/${ytMatch[1]}`
  const vmMatch = url.match(/vimeo\.com\/(\d+)/)
  if (vmMatch) return `https://player.vimeo.com/video/${vmMatch[1]}`
  return null
})

function onUrlInput(e) {
  const url = e.target.value
  const isYt = /(?:youtube\.com|youtu\.be)/.test(url)
  const isVm = /vimeo\.com/.test(url)
  urlError.value = url && !isYt && !isVm ? 'Must be a YouTube or Vimeo URL' : ''
  emit('update', { id: props.block.id, data: { url } })
}

function onBind(fieldName, value) {
  emit('update', { id: props.block.id, bindings: { ...props.block.bindings, [fieldName]: value } })
}
function onUnbind(fieldName) {
  const b = { ...(props.block.bindings ?? {}) }
  delete b[fieldName]
  emit('update', { id: props.block.id, bindings: Object.keys(b).length ? b : undefined })
}
</script>
```

**Step 3: Commit**

```bash
git add resources/js/components/BlockEditor/blocks/VideoSettings.vue
git commit -m "feat: add Style tab to Video block"
```

---

## Task 9: Add Style tab to `GallerySettings.vue`

**Files:**
- Modify: `resources/js/components/BlockEditor/blocks/GallerySettings.vue`

**Step 1: Add Style section before `</template>`**

```vue
  <!-- Style fields -->
  <div v-show="!tab || tab === 'style'" class="space-y-4">

    <!-- Columns (responsive) -->
    <div>
      <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wide block mb-2">Columns</label>
      <div class="grid grid-cols-3 gap-1">
        <div v-for="bp in ['default', 'sm', 'lg']" :key="bp">
          <span class="text-[10px] text-muted-foreground block mb-0.5 text-center">
            {{ bp === 'default' ? 'Mobile' : bp === 'sm' ? 'SM' : 'LG' }}
          </span>
          <NumberInput
            :model-value="getColumns(bp)"
            :min="1"
            :max="6"
            @update:model-value="v => setColumns(bp, v || null)"
          />
        </div>
      </div>
    </div>

    <!-- Gap -->
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Gap</label>
      <DimensionInput
        :model-value="block.data.gap ?? ''"
        placeholder="0"
        @update:model-value="v => emit('update', { id: block.id, data: { gap: v || null } })"
      />
    </div>

    <!-- Image aspect ratio -->
    <div>
      <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wide block mb-2">Image aspect ratio</label>
      <div class="flex gap-1 flex-wrap">
        <button
          v-for="ratio in ['auto', 'square', 'landscape', 'portrait']"
          :key="ratio"
          type="button"
          class="px-2 py-1 text-xs rounded border transition-colors capitalize"
          :class="(block.data.imageAspect ?? 'auto') === ratio
            ? 'bg-primary text-primary-foreground border-primary'
            : 'bg-background border-border'"
          @click="emit('update', { id: block.id, data: { imageAspect: ratio } })"
        >{{ ratio }}</button>
      </div>
    </div>

    <!-- Border radius -->
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Image radius</label>
      <DimensionInput
        :model-value="block.data.borderRadius ?? ''"
        placeholder="0"
        @update:model-value="v => emit('update', { id: block.id, data: { borderRadius: v || null } })"
      />
    </div>

  </div>
```

**Step 2: Add imports and helpers to `<script setup>`**

Replace the existing `<script setup>`:

```vue
<script setup>
import { ref } from 'vue'
import MediaPicker    from '@/Components/MediaPicker.vue'
import NumberInput    from '@/Components/NumberInput.vue'
import DimensionInput from '../DimensionInput.vue'

const props = defineProps({
  block: { type: Object, required: true },
  tab:   { type: String, default: null },
})
const emit = defineEmits(['update'])

const showPicker = ref(false)
const addMode    = ref('library')
const urlInput   = ref('')
const altInput   = ref('')

function getColumns(bp) {
  const val = props.block.data.columns
  if (typeof val === 'object' && val !== null) return val[bp] ?? null
  if (bp === 'default') return val
  return null
}

function setColumns(bp, value) {
  const current = props.block.data.columns
  const base = (typeof current === 'object' && current !== null) ? { ...current } : { default: current }
  emit('update', { id: props.block.id, data: { columns: { ...base, [bp]: value } } })
}

function onMediaSelect(media) {
  showPicker.value = false
  const items = [...(props.block.data.items ?? []), { media_id: media.id, url: media.url, alt: media.alt ?? '' }]
  emit('update', { id: props.block.id, data: { items } })
}

function addByUrl() {
  if (!urlInput.value.trim()) return
  const items = [...(props.block.data.items ?? []), { media_id: null, url: urlInput.value.trim(), alt: altInput.value.trim() }]
  emit('update', { id: props.block.id, data: { items } })
  urlInput.value = ''
  altInput.value = ''
}

function removeItem(index) {
  const items = props.block.data.items.filter((_, i) => i !== index)
  emit('update', { id: props.block.id, data: { items } })
}
</script>
```

**Step 3: Commit**

```bash
git add resources/js/components/BlockEditor/blocks/GallerySettings.vue
git commit -m "feat: add Style tab to Gallery block"
```

---

## Task 10: Add Style tab to `CtaSettings.vue`

**Files:**
- Modify: `resources/js/components/BlockEditor/blocks/CtaSettings.vue`

**Step 1: Replace the entire file**

The Style tab reuses the same background pattern from `ContainerSettings.vue` (color/image/gradient). Data: `block.data.bgType`, `block.data.bgColor`, `block.data.bgImage`, `block.data.bgGradient`, `block.data.textAlign`, `block.data.headlineColor`, `block.data.textColor`, `block.data.button` (object), `block.data.padding`.

```vue
<!-- resources/js/Components/BlockEditor/blocks/CtaSettings.vue -->
<template>
  <!-- Content fields -->
  <div v-show="!tab || tab === 'content'" class="space-y-3">
    <DynamicField label="Headline" field-name="headline" :block="block" :available-fields="availableFields" @bind="onBind" @unbind="onUnbind">
      <input :value="block.data.headline" type="text" placeholder="Bold headline..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { headline: $event.target.value } })" />
    </DynamicField>

    <DynamicField label="Body text" field-name="text" :block="block" :available-fields="availableFields" @bind="onBind" @unbind="onUnbind">
      <input :value="block.data.text" type="text" placeholder="Supporting text..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { text: $event.target.value } })" />
    </DynamicField>

    <DynamicField label="Button label" field-name="button_label" :block="block" :available-fields="availableFields" @bind="onBind" @unbind="onUnbind">
      <input :value="block.data.button_label" type="text" placeholder="Click here"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { button_label: $event.target.value } })" />
    </DynamicField>

    <DynamicField label="Button URL" field-name="button_url" :block="block" :available-fields="availableFields" @bind="onBind" @unbind="onUnbind">
      <input :value="block.data.button_url" type="url" placeholder="https://..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { button_url: $event.target.value } })" />
    </DynamicField>
  </div>

  <!-- Style fields -->
  <div v-show="!tab || tab === 'style'" class="space-y-4">

    <!-- Background (same pattern as ContainerSettings) -->
    <div class="space-y-2">
      <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wide block">Background</label>
      <div class="flex gap-1 flex-wrap">
        <button v-for="opt in ['none','color','image','gradient']" :key="opt" type="button"
          class="px-2 py-1 text-xs rounded border transition-colors"
          :class="block.data.bgType === opt ? 'bg-primary text-primary-foreground border-primary' : 'bg-background border-border'"
          @click="update('bgType', opt)">
          {{ opt.charAt(0).toUpperCase() + opt.slice(1) }}
        </button>
      </div>

      <div v-if="block.data.bgType === 'color'" class="flex items-center gap-2">
        <input type="color" :value="block.data.bgColor ?? '#ffffff'"
          @input="update('bgColor', $event.target.value)"
          class="h-8 w-14 cursor-pointer rounded border border-border" />
        <span class="text-xs text-muted-foreground">{{ block.data.bgColor ?? '#ffffff' }}</span>
      </div>

      <div v-if="block.data.bgType === 'image'" class="space-y-2">
        <div class="flex gap-1 p-0.5 rounded-md bg-muted w-fit">
          <button type="button" class="px-2.5 py-1 rounded text-xs font-medium transition-colors"
            :class="bgImageMode === 'library' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'"
            @click="bgImageMode = 'library'">Library</button>
          <button type="button" class="px-2.5 py-1 rounded text-xs font-medium transition-colors"
            :class="bgImageMode === 'url' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'"
            @click="bgImageMode = 'url'">URL</button>
        </div>
        <div v-if="bgImageMode === 'library'">
          <div v-if="block.data.bgImage?.url" class="rounded overflow-hidden border mb-2 max-h-24">
            <img :src="block.data.bgImage.url" class="w-full h-full object-cover" />
          </div>
          <button type="button"
            class="w-full rounded border border-dashed px-3 py-2 text-xs text-muted-foreground hover:border-primary hover:text-primary transition-colors"
            @click="showBgPicker = true">
            {{ block.data.bgImage?.url ? 'Change image' : 'Select image' }}
          </button>
          <MediaPicker v-model="showBgPicker" :dark="true" @select="onBgMediaSelect" />
        </div>
        <input v-else type="url" :value="block.data.bgImage?.url ?? ''"
          @input="updateNested('bgImage', 'url', $event.target.value)"
          placeholder="https://..."
          class="w-full rounded border border-border bg-background px-2 py-1 text-xs" />
        <SelectBox :model-value="block.data.bgImage?.position ?? 'center'"
          :data="[{ value:'center',label:'Center'},{ value:'top',label:'Top'},{ value:'bottom',label:'Bottom'},{ value:'left center',label:'Left'},{ value:'right center',label:'Right'}]"
          @update:model-value="v => updateNested('bgImage','position',v)" />
        <SelectBox :model-value="block.data.bgImage?.size ?? 'cover'"
          :data="[{ value:'cover',label:'Cover'},{ value:'contain',label:'Contain'},{ value:'auto',label:'Auto'}]"
          @update:model-value="v => updateNested('bgImage','size',v)" />
      </div>

      <div v-if="block.data.bgType === 'gradient'" class="space-y-2">
        <div class="flex gap-2 items-center">
          <div>
            <label class="text-[10px] text-muted-foreground">From</label>
            <input type="color" :value="block.data.bgGradient?.from ?? '#3b4252'"
              @input="updateNested('bgGradient','from',$event.target.value)"
              class="block h-8 w-12 cursor-pointer rounded border border-border" />
          </div>
          <div>
            <label class="text-[10px] text-muted-foreground">To</label>
            <input type="color" :value="block.data.bgGradient?.to ?? '#4c566a'"
              @input="updateNested('bgGradient','to',$event.target.value)"
              class="block h-8 w-12 cursor-pointer rounded border border-border" />
          </div>
        </div>
        <SelectBox :model-value="block.data.bgGradient?.direction ?? 'to-r'"
          :data="[{ value:'to-r',label:'Left → Right'},{ value:'to-l',label:'Right → Left'},{ value:'to-b',label:'Top → Bottom'},{ value:'to-t',label:'Bottom → Top'}]"
          @update:model-value="v => updateNested('bgGradient','direction',v)" />
      </div>
    </div>

    <!-- Text alignment -->
    <div>
      <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wide block mb-2">Text alignment</label>
      <div class="flex rounded-md border overflow-hidden text-xs">
        <button v-for="align in ['left','center','right']" :key="align" type="button"
          class="flex-1 py-1.5 transition-colors capitalize"
          :class="block.data.textAlign === align ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground'"
          @click="update('textAlign', block.data.textAlign === align ? null : align)">
          {{ align.charAt(0).toUpperCase() + align.slice(1) }}
        </button>
      </div>
    </div>

    <!-- Headline color -->
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Headline color</label>
      <div class="flex items-center gap-2">
        <input type="color" :value="block.data.headlineColor ?? '#ffffff'"
          @input="update('headlineColor', $event.target.value)"
          class="h-8 w-14 cursor-pointer rounded border border-border" />
        <span class="text-xs text-muted-foreground flex-1">{{ block.data.headlineColor ?? 'Inherit' }}</span>
        <button v-if="block.data.headlineColor" type="button"
          class="text-xs text-muted-foreground hover:text-foreground"
          @click="update('headlineColor', null)">Reset</button>
      </div>
    </div>

    <!-- Body text color -->
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Body text color</label>
      <div class="flex items-center gap-2">
        <input type="color" :value="block.data.textColor ?? '#ffffff'"
          @input="update('textColor', $event.target.value)"
          class="h-8 w-14 cursor-pointer rounded border border-border" />
        <span class="text-xs text-muted-foreground flex-1">{{ block.data.textColor ?? 'Inherit' }}</span>
        <button v-if="block.data.textColor" type="button"
          class="text-xs text-muted-foreground hover:text-foreground"
          @click="update('textColor', null)">Reset</button>
      </div>
    </div>

    <!-- Button style -->
    <div class="space-y-2">
      <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wide block">Button style</label>
      <div class="flex rounded-md border overflow-hidden text-xs">
        <button v-for="variant in ['filled', 'outline']" :key="variant" type="button"
          class="flex-1 py-1.5 transition-colors capitalize"
          :class="(block.data.button?.variant ?? 'filled') === variant ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground'"
          @click="updateNested('button', 'variant', variant)">
          {{ variant.charAt(0).toUpperCase() + variant.slice(1) }}
        </button>
      </div>
      <div class="flex gap-2">
        <div class="flex-1">
          <label class="text-[10px] text-muted-foreground block mb-1">Bg color</label>
          <input type="color" :value="block.data.button?.bgColor ?? '#5e81ac'"
            @input="updateNested('button', 'bgColor', $event.target.value)"
            class="h-7 w-full cursor-pointer rounded border border-border" />
        </div>
        <div class="flex-1">
          <label class="text-[10px] text-muted-foreground block mb-1">Text color</label>
          <input type="color" :value="block.data.button?.textColor ?? '#eceff4'"
            @input="updateNested('button', 'textColor', $event.target.value)"
            class="h-7 w-full cursor-pointer rounded border border-border" />
        </div>
      </div>
      <div>
        <label class="text-xs font-medium text-muted-foreground block mb-1">Button radius</label>
        <DimensionInput :model-value="block.data.button?.radius ?? ''"
          placeholder="0"
          @update:model-value="v => updateNested('button', 'radius', v || null)" />
      </div>
    </div>

    <!-- Padding -->
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Padding</label>
      <SpacingControl
        :model-value="typeof block.data.padding === 'object' ? block.data.padding : {}"
        @update:model-value="v => update('padding', v)"
      />
    </div>

  </div>
</template>

<script setup>
import { ref } from 'vue'
import DynamicField  from './DynamicField.vue'
import SelectBox     from '@/Components/SelectBox.vue'
import DimensionInput from '../DimensionInput.vue'
import SpacingControl from '../SpacingControl.vue'
import MediaPicker   from '@/Components/MediaPicker.vue'

const props = defineProps({
  block:           { type: Object, required: true },
  availableFields: { type: Array,  default: () => [] },
  tab:             { type: String, default: null },
})
const emit = defineEmits(['update'])

const bgImageMode  = ref('library')
const showBgPicker = ref(false)

function update(key, value) {
  emit('update', { id: props.block.id, data: { [key]: value } })
}
function updateNested(key, subKey, value) {
  const current = props.block.data[key] ?? {}
  emit('update', { id: props.block.id, data: { [key]: { ...current, [subKey]: value } } })
}
function onBgMediaSelect(media) {
  showBgPicker.value = false
  updateNested('bgImage', 'url', media.url)
}
function onBind(fieldName, value) {
  emit('update', { id: props.block.id, bindings: { ...props.block.bindings, [fieldName]: value } })
}
function onUnbind(fieldName) {
  const b = { ...(props.block.bindings ?? {}) }
  delete b[fieldName]
  emit('update', { id: props.block.id, bindings: Object.keys(b).length ? b : undefined })
}
</script>
```

**Step 2: Commit**

```bash
git add resources/js/components/BlockEditor/blocks/CtaSettings.vue
git commit -m "feat: add Style tab to CTA block with full background + button controls"
```

---

## Task 11: Add Style tab to `CodeSettings.vue`

**Files:**
- Modify: `resources/js/components/BlockEditor/blocks/CodeSettings.vue`

**Step 1: Replace the file contents**

```vue
<!-- resources/js/Components/BlockEditor/blocks/CodeSettings.vue -->
<template>
  <!-- Content fields -->
  <div v-show="!tab || tab === 'content'" class="space-y-3">
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Language</label>
      <SelectBox
        :model-value="block.data.language"
        :data="[
          { value: 'javascript', label: 'JavaScript' },
          { value: 'typescript', label: 'TypeScript' },
          { value: 'php',        label: 'PHP' },
          { value: 'python',     label: 'Python' },
          { value: 'html',       label: 'HTML' },
          { value: 'css',        label: 'CSS' },
          { value: 'bash',       label: 'Bash' },
          { value: 'json',       label: 'JSON' },
          { value: 'sql',        label: 'SQL' },
          { value: 'plaintext',  label: 'Plain text' },
        ]"
        @update:model-value="v => emit('update', { id: block.id, data: { language: v } })"
      />
    </div>
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Code</label>
      <textarea
        :value="block.data.code"
        rows="8"
        placeholder="Paste code here..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-xs font-mono focus:outline-none focus:ring-2 focus:ring-ring resize-y"
        @input="emit('update', { id: block.id, data: { code: $event.target.value } })"
      />
    </div>
  </div>

  <!-- Style fields -->
  <div v-show="!tab || tab === 'style'" class="space-y-3">

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Max height (scroll beyond)</label>
      <DimensionInput
        :model-value="block.data.maxHeight ?? ''"
        placeholder="None"
        :allow-auto="false"
        @update:model-value="v => emit('update', { id: block.id, data: { maxHeight: v || null } })"
      />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Border radius</label>
      <DimensionInput
        :model-value="block.data.borderRadius ?? ''"
        placeholder="0"
        @update:model-value="v => emit('update', { id: block.id, data: { borderRadius: v || null } })"
      />
    </div>

  </div>
</template>

<script setup>
import SelectBox    from '@/Components/SelectBox.vue'
import DimensionInput from '../DimensionInput.vue'

defineProps({
  block: { type: Object, required: true },
  tab:   { type: String, default: null },
})
const emit = defineEmits(['update'])
</script>
```

**Step 2: Commit**

```bash
git add resources/js/components/BlockEditor/blocks/CodeSettings.vue
git commit -m "feat: add Style tab to Code block"
```

---

## Task 12: Register new blocks in `STYLE_BLOCKS` — `BlockLayers.vue`

**Files:**
- Modify: `resources/js/components/BlockEditor/BlockLayers.vue:220-224`

**Step 1: Update STYLE_BLOCKS**

Find the `STYLE_BLOCKS` constant (around line 220) and add the new block types:

```js
const STYLE_BLOCKS = new Set([
  'container', 'section', 'spacer', 'divider', 'loop',
  'post-featured-image', 'archive-loop',
  'link', 'accordion', 'tabs', 'embed', 'pagination', 'heading',
  // Content blocks with style tabs
  'paragraph', 'image', 'gallery', 'video', 'quote', 'cta', 'code',
])
```

**Step 2: Commit**

```bash
git add resources/js/components/BlockEditor/BlockLayers.vue
git commit -m "feat: register paragraph/image/gallery/video/quote/cta/code in STYLE_BLOCKS"
```

---

## Task 13: Build and verify

**Step 1: Run the Vite build**

```bash
npm run build 2>&1 | tail -20
```

Expected: No errors. If there are import errors, check that the component paths match exactly (note: shared controls are in `resources/js/components/BlockEditor/`, not the `blocks/` subdirectory — import them with `../TypographyControl.vue` etc from inside `blocks/`).

**Step 2: Verify in browser**

- Open the page editor (`/pages/{id}/edit`)
- Select a **Paragraph** block → confirm Content and Style tabs appear
- Style tab should show: Typography section (alignment, color, size, weight, line-height) + Background color
- Select a **Heading** block → Style tab should show: Typography + IconSettings
- Select a **Quote** block → Style tab: Typography + Accent bar + Background
- Select an **Image** block → Style tab: Layout (max-width, alignment) + Border + Shadow
- Select a **Video** block → Style tab: Layout + Aspect ratio + Border radius
- Select a **Gallery** block → Style tab: Columns (responsive) + Gap + Image aspect + Image radius
- Select a **CTA** block → Style tab: Background (color/image/gradient) + Text align + Colors + Button style + Padding
- Select a **Code** block → Style tab: Max height + Border radius

**Step 3: Final commit**

```bash
git add -A
git commit -m "chore: block style tabs — build verified"
git push
```
