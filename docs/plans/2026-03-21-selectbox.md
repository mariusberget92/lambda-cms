# SelectBox Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Build a custom `SelectBox` Vue component (dropdown with optional search + multi-select) and replace all 18 native `<select>` elements across the app with it. Also apply Nord green `accent-color` to all native checkboxes globally.

**Architecture:** Pure frontend — one new component, global CSS tweak, then a mechanical find-and-replace of every `<select>` in the codebase. No backend changes. Uses `@vueuse/core`'s `onClickOutside`, `lucide-vue-next` icons, and Vue 3 `ref`/`computed`/`watch`.

**Tech Stack:** Vue 3, Tailwind CSS 4, `@vueuse/core` ^13.9.0, `lucide-vue-next` ^0.542.0

---

## Task 1: Nord green checkboxes + build SelectBox.vue

**Files:**
- Modify: `resources/css/app.css`
- Create: `resources/js/Components/SelectBox.vue`

### Step 1: Add Nord green `accent-color` for all native checkboxes

In `resources/css/app.css`, add this rule **after the `@theme inline { … }` block** (around line 130+):

```css
/* Nord green checkbox accent */
[type="checkbox"] {
  accent-color: var(--nord-green);
}
```

`--nord-green` is already defined as `#a3be8c` in `:root`.

### Step 2: Create `resources/js/Components/SelectBox.vue`

Create the file with this exact content:

```vue
<script setup>
import { ref, computed, watch, nextTick } from 'vue'
import { onClickOutside } from '@vueuse/core'
import { ChevronDown, X } from 'lucide-vue-next'

const props = defineProps({
  modelValue: { default: null },
  data:        { type: Array,   default: () => [] },
  multiple:    { type: Boolean, default: false },
  searchable:  { type: Boolean, default: false },
  placeholder: { type: String,  default: 'Select...' },
  disabled:    { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue'])

const open       = ref(false)
const search     = ref('')
const root       = ref(null)
const searchInput = ref(null)

onClickOutside(root, () => { open.value = false })

watch(open, (val) => {
  if (val) {
    search.value = ''
    if (props.searchable) nextTick(() => searchInput.value?.focus())
  }
})

const filteredItems = computed(() => {
  if (!props.searchable || !search.value) return props.data
  const q = search.value.toLowerCase()
  return props.data.filter(item => item.label.toLowerCase().includes(q))
})

const isSelected = (value) => {
  if (props.multiple) return Array.isArray(props.modelValue) && props.modelValue.includes(value)
  return props.modelValue === value
}

const triggerLabel = computed(() => {
  if (props.multiple) {
    const count = Array.isArray(props.modelValue) ? props.modelValue.length : 0
    return count === 0 ? null : `${count} selected`
  }
  return props.data.find(item => item.value === props.modelValue)?.label ?? null
})

const hasSelection = computed(() => {
  if (props.multiple) return Array.isArray(props.modelValue) && props.modelValue.length > 0
  return props.modelValue !== null && props.modelValue !== undefined && props.modelValue !== ''
})

const select = (value) => {
  if (props.multiple) {
    const current = Array.isArray(props.modelValue) ? [...props.modelValue] : []
    const idx = current.indexOf(value)
    if (idx === -1) current.push(value)
    else current.splice(idx, 1)
    emit('update:modelValue', current)
  } else {
    emit('update:modelValue', value)
    open.value = false
  }
}

const clear = (e) => {
  e.stopPropagation()
  emit('update:modelValue', props.multiple ? [] : null)
}

const toggle = () => {
  if (!props.disabled) open.value = !open.value
}
</script>

<template>
  <div ref="root" class="relative" @keydown.escape="open = false">
    <!-- Trigger -->
    <button
      type="button"
      :disabled="disabled"
      class="w-full flex items-center justify-between rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring disabled:opacity-50 disabled:cursor-not-allowed"
      :class="open ? 'ring-2 ring-ring border-ring' : ''"
      @click="toggle"
    >
      <span :class="triggerLabel ? 'text-foreground' : 'text-muted-foreground'">
        {{ triggerLabel ?? placeholder }}
      </span>
      <span class="flex items-center gap-1 ml-2 shrink-0">
        <span
          v-if="hasSelection"
          class="text-muted-foreground hover:text-foreground"
          role="button"
          @click="clear"
        >
          <X class="w-3.5 h-3.5" />
        </span>
        <ChevronDown
          class="w-4 h-4 text-muted-foreground transition-transform duration-150"
          :class="{ 'rotate-180': open }"
        />
      </span>
    </button>

    <!-- Dropdown panel -->
    <div
      v-show="open"
      class="absolute z-50 mt-1 w-full rounded-md border bg-background shadow-md"
    >
      <!-- Search input -->
      <div v-if="searchable" class="p-2 border-b">
        <input
          ref="searchInput"
          v-model="search"
          type="text"
          placeholder="Search..."
          class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        />
      </div>

      <!-- Item list -->
      <ul class="max-h-60 overflow-y-auto py-1">
        <li
          v-if="filteredItems.length === 0"
          class="px-3 py-2 text-sm text-muted-foreground"
        >
          No results
        </li>
        <li
          v-for="item in filteredItems"
          :key="item.value"
          class="flex items-center gap-2 px-3 py-2 text-sm cursor-pointer select-none"
          :class="isSelected(item.value) && !multiple
            ? 'bg-primary text-primary-foreground'
            : 'hover:bg-accent hover:text-accent-foreground'"
          @click="select(item.value)"
        >
          <input
            v-if="multiple"
            type="checkbox"
            :checked="isSelected(item.value)"
            class="shrink-0"
            readonly
            @click.stop
          />
          {{ item.label }}
        </li>
      </ul>
    </div>
  </div>
</template>
```

### Step 3: Build and verify

```bash
cd C:\Users\mariu\Herd\lambda-cms
npm run build
```

Expected: `✓ built in X.XXs` with no errors.

### Step 4: Commit

```bash
git add resources/css/app.css resources/js/Components/SelectBox.vue
git commit -m "feat: add SelectBox component + Nord green checkbox accent"
```

---

## Task 2: Replace all native `<select>` elements with SelectBox

**Files to modify (all):**
- `resources/js/Pages/Posts/Index.vue`
- `resources/js/Pages/Settings/Index.vue`
- `resources/js/Pages/Users/Form.vue`
- `resources/js/Pages/Navigation/Index.vue`
- `resources/js/Pages/Media/Index.vue`
- `resources/js/Components/MediaPicker.vue`
- `resources/js/Components/BlockEditor/blocks/HeadingSettings.vue`
- `resources/js/Components/BlockEditor/blocks/CodeSettings.vue`
- `resources/js/Components/BlockEditor/blocks/AdvancedSettings.vue`
- `resources/js/Components/BlockEditor/blocks/ContainerSettings.vue`
- `resources/js/Components/BlockEditor/blocks/ComponentSettings.vue`

### General replacement rules

**In every file:**
1. Import SelectBox: `import SelectBox from '@/Components/SelectBox.vue'`
2. Replace `<select v-model="x" …><option value="v">Label</option>…</select>` with `<SelectBox v-model="x" :data="[…]" />`
3. For selects using `:value` + `@change`: replace with `v-model` on SelectBox
4. Convert all static `<option>` lists to inline `{ value, label }` arrays
5. Convert all dynamic arrays (strings) to `{ value: item, label: item }` via computed or inline map

**Static options pattern:**
```html
<!-- Before -->
<select v-model="x">
  <option value="foo">Foo label</option>
  <option value="bar">Bar label</option>
</select>

<!-- After -->
<SelectBox
  v-model="x"
  :data="[
    { value: 'foo', label: 'Foo label' },
    { value: 'bar', label: 'Bar label' },
  ]"
/>
```

**`:value` + `@change` pattern (BlockEditor):**
```html
<!-- Before -->
<select :value="block.data.level" @change="e => update('level', +e.target.value)">…</select>

<!-- After -->
<SelectBox
  :model-value="block.data.level"
  :data="[…]"
  @update:model-value="v => update('level', v)"
/>
```

Note: BlockEditor selects use `@change` handlers like `e => update('key', e.target.value)`. With SelectBox replace with `@update:model-value="v => update('key', v)"` (no need to parse `e.target.value` — SelectBox emits the value directly).

### Step 1: Replace in `Pages/Posts/Index.vue`

**Purpose:** Status filter (static options, v-model, no label)

Add import. Replace the `<select v-model="statusFilter" …>` with:

```html
<SelectBox
  v-model="statusFilter"
  :data="[
    { value: '',          label: 'All statuses' },
    { value: 'published', label: 'Published' },
    { value: 'scheduled', label: 'Scheduled' },
    { value: 'draft',     label: 'Draft' },
  ]"
  placeholder="All statuses"
/>
```

> Check the existing `v-model` binding name — it is `statusFilter`. Check how filter clears (the existing `''` value maps to "all"). The `placeholder` is not shown because a value is always set, but include it for completeness.

### Step 2: Replace in `Pages/Settings/Index.vue`

Three selects. Add import once at top of `<script setup>`.

**Timezone (searchable, dynamic — array of strings):**

The timezones are defined as a local `const timezones = […]` array of strings. Create a computed:
```js
const timezoneOptions = computed(() =>
  timezones.map(tz => ({ value: tz, label: tz }))
)
```

Replace the `<select v-model="localeForm['locale.timezone']" …>` with:
```html
<SelectBox
  v-model="localeForm['locale.timezone']"
  :data="timezoneOptions"
  searchable
/>
```

**Mail Driver (static):**
```html
<SelectBox
  v-model="mailForm['mail.driver']"
  :data="[
    { value: 'smtp',    label: 'SMTP' },
    { value: 'log',     label: 'Log (development)' },
    { value: 'mailgun', label: 'Mailgun' },
  ]"
/>
```

**Mail Encryption (static):**
```html
<SelectBox
  v-model="mailForm['mail.encryption']"
  :data="[
    { value: 'tls',  label: 'TLS' },
    { value: 'ssl',  label: 'SSL' },
    { value: 'none', label: 'None' },
  ]"
/>
```

> Remove the `:class="{ 'border-destructive': form.errors.x }"` from the old `<select>` — SelectBox handles its own border. If you need to show error state, that's a future enhancement; for now just remove it.

### Step 3: Replace in `Pages/Users/Form.vue`

**Role (dynamic — `roles` prop is array of strings, has `disabled` prop):**

```js
// Add computed in <script setup>:
const roleOptions = computed(() =>
  props.roles.map(r => ({ value: r, label: r }))
)
```

Replace:
```html
<SelectBox
  v-model="form.role"
  :data="roleOptions"
  :disabled="isLastAdmin"
/>
```

> Keep the error message `<p v-if="form.errors.role" …>` below as-is.

### Step 4: Replace in `Pages/Navigation/Index.vue`

**Page selector (dynamic, no persistent v-model — fires once to add page):**

The existing select uses `@change="onPageSelect"` and the handler reads `e.target.value`. With SelectBox:

```js
// Replace the handler — SelectBox emits the value directly, no event object:
function onPageSelect(pageId) {
  if (!pageId) return
  const page = props.pages.find(p => p.id === pageId)
  // … rest of handler unchanged, but use pageId instead of +e.target.value …
}
```

```js
// Add computed:
const pageOptions = computed(() =>
  props.pages.map(p => ({ value: p.id, label: p.title }))
)
```

Replace the `<select>` with:
```html
<SelectBox
  :model-value="null"
  :data="pageOptions"
  placeholder="Select a page…"
  @update:model-value="onPageSelect"
/>
```

> `:model-value="null"` keeps it uncontrolled (no persistent selection — each pick triggers the add action and the SelectBox resets).

### Step 5: Replace in `Pages/Media/Index.vue`

**Media type filter (static, v-model):**

```html
<SelectBox
  v-model="filters.type"
  :data="[
    { value: '',          label: 'All types' },
    { value: 'image',     label: 'Images' },
    { value: 'document',  label: 'Documents' },
    { value: 'video',     label: 'Video' },
    { value: 'audio',     label: 'Audio' },
  ]"
  placeholder="All types"
/>
```

> Check the existing option values (they may be `image`, `document`, etc. — match exactly).

### Step 6: Replace in `Components/MediaPicker.vue`

Same options as Media/Index.vue (same filter):

```html
<SelectBox
  v-model="filters.type"
  :data="[
    { value: '',          label: 'All types' },
    { value: 'image',     label: 'Images' },
    { value: 'document',  label: 'Documents' },
    { value: 'video',     label: 'Video' },
    { value: 'audio',     label: 'Audio' },
  ]"
  placeholder="All types"
/>
```

### Step 7: Replace in `Components/BlockEditor/blocks/HeadingSettings.vue`

**Heading level (dynamic 1–6, uses `:value` + `@change`):**

```html
<SelectBox
  :model-value="block.data.level"
  :data="[1,2,3,4,5,6].map(n => ({ value: n, label: `H${n}` }))"
  @update:model-value="v => update('level', v)"
/>
```

> Read the file first to confirm the exact `update()` call signature and the block data property name.

### Step 8: Replace in `Components/BlockEditor/blocks/CodeSettings.vue`

**Code language (static, uses `:value` + `@change`):**

Read the file first to get exact option values. Replacement pattern:

```html
<SelectBox
  :model-value="block.data.language"
  :data="[
    { value: 'javascript',  label: 'JavaScript' },
    { value: 'typescript',  label: 'TypeScript' },
    { value: 'php',         label: 'PHP' },
    { value: 'python',      label: 'Python' },
    { value: 'html',        label: 'HTML' },
    { value: 'css',         label: 'CSS' },
    { value: 'bash',        label: 'Bash' },
    { value: 'json',        label: 'JSON' },
    { value: 'sql',         label: 'SQL' },
    { value: 'plaintext',   label: 'Plain text' },
  ]"
  @update:model-value="v => update('language', v)"
/>
```

> Read the file to confirm the exact `value` strings and `update()` call. Match existing values exactly.

### Step 9: Replace in `Components/BlockEditor/blocks/AdvancedSettings.vue`

**Font family (dynamic from `FONTS` constant, uses `:value` + `@change`):**

Read the file to see the FONTS constant structure. If it's an array of strings:
```js
// The data prop — inline map:
:data="FONTS.map(f => ({ value: f, label: f }))"
```

If FONTS is `[{ value, label }]` already, pass directly: `:data="FONTS"`.

```html
<SelectBox
  :model-value="block.data.fontFamily"
  :data="FONTS.map(f => ({ value: f, label: f }))"
  @update:model-value="v => update('fontFamily', v)"
/>
```

> Read the file first to confirm FONTS shape and the exact property name on `block.data`.

### Step 10: Replace all 4 selects in `Components/BlockEditor/blocks/ContainerSettings.vue`

Read the file first to confirm all option values and `update()` calls. Then replace:

**Direction:**
```html
<SelectBox
  :model-value="block.data.direction"
  :data="[
    { value: 'row',    label: 'Row (horizontal)' },
    { value: 'column', label: 'Column (vertical)' },
  ]"
  @update:model-value="v => update('direction', v)"
/>
```

**Justify content:**
```html
<SelectBox
  :model-value="block.data.justify"
  :data="[
    { value: 'start',         label: 'Start' },
    { value: 'center',        label: 'Center' },
    { value: 'end',           label: 'End' },
    { value: 'space-between', label: 'Space between' },
    { value: 'space-around',  label: 'Space around' },
  ]"
  @update:model-value="v => update('justify', v)"
/>
```

**Align items:**
```html
<SelectBox
  :model-value="block.data.align"
  :data="[
    { value: 'start',   label: 'Start' },
    { value: 'center',  label: 'Center' },
    { value: 'end',     label: 'End' },
    { value: 'stretch', label: 'Stretch' },
  ]"
  @update:model-value="v => update('align', v)"
/>
```

**Max width:**
```html
<SelectBox
  :model-value="block.data.maxWidth"
  :data="[
    { value: 'full',   label: 'Full' },
    { value: 'prose',  label: 'Prose (65ch)' },
    { value: 'sm',     label: 'SM (24rem)' },
    { value: 'md',     label: 'MD (28rem)' },
    { value: 'lg',     label: 'LG (32rem)' },
    { value: 'xl',     label: 'XL (36rem)' },
    { value: '2xl',    label: '2XL (42rem)' },
  ]"
  @update:model-value="v => update('maxWidth', v)"
/>
```

> Read the actual file — confirm the exact `block.data` property names and option values before replacing. The plan shows guesses; the actual names take precedence.

### Step 11: Replace both selects in `Components/BlockEditor/blocks/ComponentSettings.vue`

Read the file first. Then:

**Component type:**
```html
<SelectBox
  :model-value="block.data.component"
  :data="[{ value: 'post-list', label: 'Post List' }]"
  @update:model-value="v => update('component', v)"
/>
```

**Post order:**
```html
<SelectBox
  :model-value="block.data.order"
  :data="[
    { value: 'latest',       label: 'Latest first' },
    { value: 'oldest',       label: 'Oldest first' },
    { value: 'alphabetical', label: 'Alphabetical' },
  ]"
  @update:model-value="v => update('order', v)"
/>
```

> Read the file — confirm property names and option values.

### Step 12: Build and verify

```bash
cd C:\Users\mariu\Herd\lambda-cms
npm run build
```

Expected: `✓ built in X.XXs` with no errors. If there are errors, they will be TypeScript/Vue compiler errors indicating wrong prop names or import paths — fix them before committing.

### Step 13: Commit

```bash
git add resources/js/Pages/Posts/Index.vue \
        resources/js/Pages/Settings/Index.vue \
        resources/js/Pages/Users/Form.vue \
        resources/js/Pages/Navigation/Index.vue \
        resources/js/Pages/Media/Index.vue \
        resources/js/Components/MediaPicker.vue \
        resources/js/Components/BlockEditor/blocks/HeadingSettings.vue \
        resources/js/Components/BlockEditor/blocks/CodeSettings.vue \
        resources/js/Components/BlockEditor/blocks/AdvancedSettings.vue \
        resources/js/Components/BlockEditor/blocks/ContainerSettings.vue \
        resources/js/Components/BlockEditor/blocks/ComponentSettings.vue
git commit -m "feat: replace native selects with SelectBox throughout app"
```
