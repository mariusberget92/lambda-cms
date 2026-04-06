# Dynamic Field Bindings Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Extend block field bindings to support both loop-item data and post-context data, with human-readable grouped labels in the picker dropdown, and full coverage across Quote, Video, HTML, and CTA blocks.

**Architecture:** Binding values gain a source prefix (`loop:title`, `post:title`). `useFieldBinding` parses the prefix and injects from the right Vue provider. `loopSources.js` exports typed `{value, label}` field arrays and a new `POST_CONTEXT_FIELDS` constant. A new `availableFields` computed on `BlockEditor` merges both sources and flows down to every settings panel.

**Tech Stack:** Vue 3 Composition API, `provide`/`inject`, `loopSources.js` constants, native HTML `<select>`/`<optgroup>` for grouped picker.

**Note on testing:** No JS test framework exists. Each task ends with `php artisan test` to guard against PHP-side regressions. Visual verification in browser is the primary JS validation.

---

### Task 1: Upgrade `loopSources.js` — typed fields + POST_CONTEXT_FIELDS

**Files:**
- Modify: `resources/js/lib/loopSources.js`

**Step 1: Replace SOURCE_FIELDS with `{value, label}` objects and add POST_CONTEXT_FIELDS**

Replace the entire file with:

```js
// resources/js/lib/loopSources.js

export const SOURCES = [
  { value: 'posts',      label: 'Posts' },
  { value: 'categories', label: 'Categories' },
  { value: 'tags',       label: 'Tags' },
  { value: 'pages',      label: 'Pages' },
]

// Fields exposed per loop source — used for DynamicField binding AND ConditionSettings.
// Values are UN-prefixed (used as loop item keys at runtime).
export const SOURCE_FIELDS = {
  posts: [
    { value: 'title',              label: 'Post Title' },
    { value: 'slug',               label: 'Post Slug' },
    { value: 'excerpt',            label: 'Excerpt' },
    { value: 'body',               label: 'Body Content' },
    { value: 'featured',           label: 'Is Featured' },
    { value: 'published_at',       label: 'Published Date' },
    { value: 'author_name',        label: 'Author' },
    { value: 'featured_image_url', label: 'Featured Image' },
    { value: 'url',                label: 'Post URL' },
  ],
  categories: [
    { value: 'name',        label: 'Category Name' },
    { value: 'slug',        label: 'Category Slug' },
    { value: 'description', label: 'Description' },
    { value: 'posts_count', label: 'Post Count' },
    { value: 'url',         label: 'Category URL' },
  ],
  tags: [
    { value: 'name',        label: 'Tag Name' },
    { value: 'slug',        label: 'Tag Slug' },
    { value: 'posts_count', label: 'Post Count' },
    { value: 'url',         label: 'Tag URL' },
  ],
  pages: [
    { value: 'title',            label: 'Page Title' },
    { value: 'slug',             label: 'Page Slug' },
    { value: 'meta_description', label: 'Meta Description' },
    { value: 'url',              label: 'Page URL' },
  ],
}

// Fields available from postContext (single-post template or post with block editor).
// Values ARE pre-prefixed with 'post:' so they can be stored directly as binding values.
export const POST_CONTEXT_FIELDS = [
  { value: 'post:title',              label: 'Post Title' },
  { value: 'post:slug',               label: 'Post Slug' },
  { value: 'post:excerpt',            label: 'Excerpt' },
  { value: 'post:body',               label: 'Body Content' },
  { value: 'post:published_at',       label: 'Published Date' },
  { value: 'post:author_name',        label: 'Author Name' },
  { value: 'post:author_avatar_url',  label: 'Author Avatar' },
  { value: 'post:featured_image_url', label: 'Featured Image' },
  { value: 'post:url',                label: 'Post URL' },
]

export const SORT_FIELDS = {
  posts:      ['published_at', 'title', 'created_at'],
  categories: ['name', 'created_at', 'posts_count'],
  tags:       ['name', 'created_at', 'posts_count'],
  pages:      ['title', 'created_at', 'updated_at'],
}

export const FILTER_OPS = [
  { value: '=',         label: 'Equals' },
  { value: '!=',        label: 'Not equals' },
  { value: 'not_empty', label: 'Is not empty' },
  { value: 'empty',     label: 'Is empty' },
]
```

**Step 2: Verify LoopSettings still works**

`LoopSettings.vue` uses `SOURCE_FIELDS` for `filterableFields`:
```js
const filterableFields = computed(() =>
  (SOURCE_FIELDS[source.value] ?? []).map(f => ({ value: f, label: f }))
)
```
This must change to:
```js
const filterableFields = computed(() => SOURCE_FIELDS[source.value] ?? [])
```
(Items are already `{value, label}` — no mapping needed.)

Also update `sortFieldOptions` — it currently uses `SORT_FIELDS` which is still a string array, so no change needed there. But confirm `filterableFields` and `sortFieldOptions` still render correctly.

**Step 3: Run tests**

```bash
php artisan test
```
Expected: all 381 tests pass.

**Step 4: Commit**

```bash
git add resources/js/lib/loopSources.js resources/js/components/BlockEditor/blocks/LoopSettings.vue
git commit -m "feat: loopSources — typed {value,label} fields + POST_CONTEXT_FIELDS"
```

---

### Task 2: Update `useLoopBinding.js` — prefix-aware resolution

**Files:**
- Modify: `resources/js/composables/useLoopBinding.js`

**Step 1: Rewrite with prefix parsing**

Replace the entire file:

```js
// resources/js/composables/useLoopBinding.js
import { inject, computed } from 'vue'

/**
 * Resolve a block field, preferring a dynamic binding over the static data value.
 *
 * Binding value formats:
 *   'loop:title'        → loopItem.value.title
 *   'post:title'        → postContext.title
 *   'post:author_name'  → postContext.author?.name  (flattened nested field)
 *   'title'             → legacy — treated as 'loop:title'
 *
 * Falls back to block.data[fieldName] when no binding, or when the provider
 * is not available in the current component tree.
 */
export function useFieldBinding(getBlock, fieldName) {
  const loopItem    = inject('loopItem',    null)
  const postContext = inject('postContext', null)

  return computed(() => {
    const block    = getBlock()
    const binding  = block?.bindings?.[fieldName]
    const fallback = block?.data?.[fieldName]

    if (!binding) return fallback

    const colon = binding.indexOf(':')

    // Legacy: no prefix → treat as loop binding
    if (colon === -1) {
      return loopItem?.value?.[binding] ?? fallback
    }

    const source = binding.slice(0, colon)
    const field  = binding.slice(colon + 1)

    if (source === 'loop') {
      return loopItem?.value?.[field] ?? fallback
    }

    if (source === 'post') {
      return resolvePostField(postContext, field) ?? fallback
    }

    return fallback
  })
}

/**
 * Resolve a field from the postContext object.
 * Handles flattened keys that map to nested paths on the context object.
 */
function resolvePostField(postContext, field) {
  const ctx = postContext  // postContext is a plain object (not a ref) from TemplatePage.provide
  if (!ctx) return undefined

  // Nested field mappings
  const nested = {
    author_name:       c => c.author?.name,
    author_avatar_url: c => c.author?.avatar_url,
  }

  if (nested[field]) return nested[field](ctx)
  return ctx[field]
}
```

**Step 2: Run tests**

```bash
php artisan test
```
Expected: all pass.

**Step 3: Commit**

```bash
git add resources/js/composables/useLoopBinding.js
git commit -m "feat: useFieldBinding — prefix-aware resolution (loop: and post: sources)"
```

---

### Task 3: Update `BlockEditor.vue` — add `contextFields` prop + `availableFields` computed

**Files:**
- Modify: `resources/js/components/BlockEditor/BlockEditor.vue`

**Step 1: Add `contextFields` prop and `availableFields` computed**

In the `<script setup>` section, add the import and new prop + computed. The key changes:

1. Add import of `SOURCES`:
```js
import { SOURCE_FIELDS, SOURCES } from '@/lib/loopSources.js'
```

2. Add `contextFields` prop to `defineProps`:
```js
contextFields: { type: Array, default: () => [] },
```

3. Add `availableFields` computed after the existing `loopFields` computed:
```js
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
    const source     = loopAncestor.value?.data?.source ?? 'posts'
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
```

4. Pass `availableFields` to `<BlockLayers>` (alongside existing `loop-fields`):
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

**Step 2: Run tests**

```bash
php artisan test
```

**Step 3: Commit**

```bash
git add resources/js/components/BlockEditor/BlockEditor.vue
git commit -m "feat: BlockEditor — contextFields prop + availableFields computed"
```

---

### Task 4: Update `BlockLayers.vue` — thread `availableFields` to settings

**Files:**
- Modify: `resources/js/components/BlockEditor/BlockLayers.vue`

**Step 1: Add `availableFields` prop**

In `defineProps`, add:
```js
availableFields: { type: Array, default: () => [] },
```

**Step 2: Pass `availableFields` to the settings `<component>`**

Change the dynamic settings component binding from:
```html
<component
  :is="settingsComponent"
  :block="selectedBlock"
  :is-admin="isAdmin"
  :meta="meta"
  :loop-fields="loopFields"
  @update="$emit('update', $event)"
/>
```
to:
```html
<component
  :is="settingsComponent"
  :block="selectedBlock"
  :is-admin="isAdmin"
  :meta="meta"
  :loop-fields="loopFields"
  :available-fields="availableFields"
  @update="$emit('update', $event)"
/>
```

Note: `ConditionSettings` keeps `:loop-fields="loopFields"` — conditions evaluate against the loop item so they must use un-prefixed loop field keys.

**Step 3: Run tests + commit**

```bash
php artisan test
git add resources/js/components/BlockEditor/BlockLayers.vue
git commit -m "feat: BlockLayers — thread availableFields prop to settings components"
```

---

### Task 5: Update `DynamicField.vue` — grouped native select

**Files:**
- Modify: `resources/js/components/BlockEditor/blocks/DynamicField.vue`

**Step 1: Replace with grouped native `<select>`**

Replace the entire file:

```vue
<!-- resources/js/Components/BlockEditor/blocks/DynamicField.vue -->
<template>
  <div>
    <div class="flex items-center justify-between mb-1">
      <label class="text-xs font-medium text-muted-foreground">{{ label }}</label>
      <button
        v-if="availableFields.length"
        type="button"
        class="text-[10px] px-1.5 py-0.5 rounded border transition-colors"
        :class="isBound
          ? 'border-primary text-primary bg-primary/10'
          : 'border-border text-muted-foreground hover:border-primary'"
        @click="toggleBinding"
      >{{ isBound ? 'Dynamic ✓' : 'Bind' }}</button>
    </div>

    <!-- Bound: grouped native select replaces the static input -->
    <select
      v-if="isBound"
      :value="boundField"
      class="w-full rounded-md border border-border bg-background px-2 py-1.5 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring"
      @change="e => emit('bind', fieldName, e.target.value)"
    >
      <option value="" disabled>Pick a field…</option>
      <template v-for="group in groups" :key="group.label">
        <optgroup v-if="group.label" :label="group.label">
          <option v-for="f in group.items" :key="f.value" :value="f.value">{{ f.label }}</option>
        </optgroup>
      </template>
    </select>

    <!-- Static: whatever the parent renders in the slot -->
    <slot v-else />
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  label:          { type: String, required: true },
  fieldName:      { type: String, required: true },
  block:          { type: Object, required: true },
  availableFields: { type: Array,  default: () => [] },
})

const emit = defineEmits(['bind', 'unbind'])

const isBound    = computed(() => !!props.block.bindings?.[props.fieldName])
const boundField = computed(() => props.block.bindings?.[props.fieldName] ?? '')

// Group availableFields by their .group property.
// Falls back to a single unnamed group when no group is set.
const groups = computed(() => {
  const map = new Map()
  for (const f of props.availableFields) {
    const key = f.group ?? ''
    if (!map.has(key)) map.set(key, { label: key, items: [] })
    map.get(key).items.push(f)
  }
  return [...map.values()]
})

function toggleBinding() {
  if (isBound.value) {
    emit('unbind', props.fieldName)
  } else {
    emit('bind', props.fieldName, '')
  }
}
</script>
```

**Step 2: Run tests + commit**

```bash
php artisan test
git add resources/js/components/BlockEditor/blocks/DynamicField.vue
git commit -m "feat: DynamicField — grouped native select, rename loopFields → availableFields"
```

---

### Task 6: Update `ConditionSettings.vue` — handle `{value, label}` loop fields

**Files:**
- Modify: `resources/js/components/BlockEditor/blocks/ConditionSettings.vue`

**Step 1: Fix `fieldOptions` computed**

`loopFields` items are now `{value, label}` objects. The current mapping creates duplicates. Replace:
```js
const fieldOptions = computed(() => props.loopFields.map(f => ({ value: f, label: f })))
```
with:
```js
const fieldOptions = computed(() => props.loopFields)
```

**Step 2: Run tests + commit**

```bash
php artisan test
git add resources/js/components/BlockEditor/blocks/ConditionSettings.vue
git commit -m "fix: ConditionSettings — fieldOptions from typed {value,label} loop fields"
```

---

### Task 7: Update existing settings files — rename prop

These files all accept `loopFields` and pass it to `DynamicField`. Rename the prop to `availableFields` in each.

**Files:**
- Modify: `resources/js/components/BlockEditor/blocks/HeadingSettings.vue`
- Modify: `resources/js/components/BlockEditor/blocks/ParagraphSettings.vue`
- Modify: `resources/js/components/BlockEditor/blocks/ImageSettings.vue`
- Modify: `resources/js/components/BlockEditor/blocks/CtaSettings.vue`

**Step 1: In each file, rename `loopFields` → `availableFields`**

The pattern is the same in all four. Find every occurrence of `loopFields` in `defineProps` and in the template and rename to `availableFields`. Example for `HeadingSettings.vue`:

```js
// Before
const props = defineProps({
  block:      { type: Object, required: true },
  loopFields: { type: Array,  default: () => [] },
})
```
```js
// After
const props = defineProps({
  block:           { type: Object, required: true },
  availableFields: { type: Array,  default: () => [] },
})
```

Template before:
```html
:loop-fields="loopFields"
```
Template after:
```html
:available-fields="availableFields"
```

Apply this same rename to all four files.

**Step 2: In `CtaSettings.vue`, also add DynamicField for `text` and `button_label`**

The CTA currently binds `headline` and `button_url`. Add the two missing fields. In the template, add after the existing headline DynamicField:

```html
<DynamicField
  label="Body text"
  field-name="text"
  :block="block"
  :available-fields="availableFields"
  @bind="onBind"
  @unbind="onUnbind"
>
  <input
    :value="block.data.text"
    type="text"
    placeholder="Supporting text..."
    class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
    @input="emit('update', { id: block.id, data: { text: $event.target.value } })"
  />
</DynamicField>
```

And wrap `button_label` similarly:
```html
<DynamicField
  label="Button label"
  field-name="button_label"
  :block="block"
  :available-fields="availableFields"
  @bind="onBind"
  @unbind="onUnbind"
>
  <input
    :value="block.data.button_label"
    type="text"
    placeholder="Click here"
    class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
    @input="emit('update', { id: block.id, data: { button_label: $event.target.value } })"
  />
</DynamicField>
```

Remove the previous non-wrapped `button_label` div.

**Step 3: Run tests + commit**

```bash
php artisan test
git add resources/js/components/BlockEditor/blocks/HeadingSettings.vue \
        resources/js/components/BlockEditor/blocks/ParagraphSettings.vue \
        resources/js/components/BlockEditor/blocks/ImageSettings.vue \
        resources/js/components/BlockEditor/blocks/CtaSettings.vue
git commit -m "feat: settings — rename loopFields → availableFields; CTA adds text+button_label bindings"
```

---

### Task 8: Add DynamicField to Quote, Video, HTML settings

**Files:**
- Modify: `resources/js/components/BlockEditor/blocks/QuoteSettings.vue`
- Modify: `resources/js/components/BlockEditor/blocks/VideoSettings.vue`
- Modify: `resources/js/components/BlockEditor/blocks/HtmlSettings.vue`

**Step 1: Rewrite `QuoteSettings.vue`**

```vue
<!-- resources/js/Components/BlockEditor/blocks/QuoteSettings.vue -->
<template>
  <div class="space-y-3">
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
</template>

<script setup>
import DynamicField from './DynamicField.vue'

const props = defineProps({
  block:           { type: Object, required: true },
  availableFields: { type: Array,  default: () => [] },
})
const emit = defineEmits(['update'])

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

**Step 2: Update `VideoSettings.vue`**

Add `availableFields` prop and wrap the URL field with DynamicField. In `defineProps`:
```js
const props = defineProps({
  block:           { type: Object, required: true },
  availableFields: { type: Array,  default: () => [] },
})
```

Add `onBind`/`onUnbind` helpers (same pattern as QuoteSettings above).

Wrap the URL input in the template:
```html
<DynamicField
  label="YouTube or Vimeo URL"
  field-name="url"
  :block="block"
  :available-fields="availableFields"
  @bind="onBind"
  @unbind="onUnbind"
>
  <div>
    <input
      :value="block.data.url"
      type="url"
      placeholder="https://www.youtube.com/watch?v=..."
      class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
      :class="{ 'border-destructive': urlError }"
      @input="onUrlInput"
    />
    <p v-if="urlError" class="mt-1 text-xs text-destructive">{{ urlError }}</p>
  </div>
</DynamicField>
```

Add `import DynamicField from './DynamicField.vue'` to the script.

**Step 3: Update `HtmlSettings.vue`**

Add `availableFields` prop and wrap the textarea with DynamicField (admin-only guard stays):

```vue
<!-- resources/js/Components/BlockEditor/blocks/HtmlSettings.vue -->
<template>
  <div v-if="!isAdmin" class="rounded-md border border-dashed p-4 text-center">
    <p class="text-xs text-muted-foreground">HTML blocks are admin-only.</p>
  </div>
  <div v-else class="space-y-2">
    <DynamicField
      label="Raw HTML"
      field-name="content"
      :block="block"
      :available-fields="availableFields"
      @bind="onBind"
      @unbind="onUnbind"
    >
      <textarea
        :value="block.data.content"
        rows="12"
        placeholder="<div>...</div>"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-xs font-mono focus:outline-none focus:ring-2 focus:ring-ring resize-y"
        @input="emit('update', { id: block.id, data: { content: $event.target.value } })"
      />
    </DynamicField>
    <p class="text-xs text-muted-foreground">&#x26A0; Admin only — rendered as-is in the page.</p>
  </div>
</template>

<script setup>
import DynamicField from './DynamicField.vue'

const props = defineProps({
  block:           { type: Object,  required: true },
  isAdmin:         { type: Boolean, default: false },
  availableFields: { type: Array,   default: () => [] },
})
const emit = defineEmits(['update'])

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

**Step 4: Run tests + commit**

```bash
php artisan test
git add resources/js/components/BlockEditor/blocks/QuoteSettings.vue \
        resources/js/components/BlockEditor/blocks/VideoSettings.vue \
        resources/js/components/BlockEditor/blocks/HtmlSettings.vue
git commit -m "feat: QuoteSettings, VideoSettings, HtmlSettings — DynamicField binding support"
```

---

### Task 9: Update block renderers — add `useFieldBinding`

**Files:**
- Modify: `resources/js/components/Blocks/QuoteBlock.vue`
- Modify: `resources/js/components/Blocks/VideoBlock.vue`
- Modify: `resources/js/components/Blocks/HtmlBlock.vue`
- Modify: `resources/js/components/Blocks/CtaBlock.vue`

**Step 1: Rewrite `QuoteBlock.vue`**

```vue
<!-- resources/js/Components/Blocks/QuoteBlock.vue -->
<template>
  <blockquote class="border-l-4 border-primary pl-4 my-4 italic">
    <p class="text-lg">{{ resolvedText }}</p>
    <cite v-if="resolvedAttribution" class="block mt-2 text-sm text-muted-foreground not-italic">
      — {{ resolvedAttribution }}
    </cite>
  </blockquote>
</template>
<script setup>
import { useFieldBinding } from '@/composables/useLoopBinding.js'
const props = defineProps({ block: { type: Object, required: true } })
const resolvedText        = useFieldBinding(() => props.block, 'text')
const resolvedAttribution = useFieldBinding(() => props.block, 'attribution')
</script>
```

**Step 2: Rewrite `VideoBlock.vue`**

The `url` field is processed through the embed URL computed. Pass the resolved URL into it:

```vue
<!-- resources/js/Components/Blocks/VideoBlock.vue -->
<template>
  <figure class="my-4">
    <div v-if="embedUrl" class="relative aspect-video rounded-lg overflow-hidden border border-border">
      <iframe
        :src="embedUrl"
        class="absolute inset-0 w-full h-full"
        frameborder="0"
        allow="autoplay; encrypted-media"
        allowfullscreen
      />
    </div>
    <div v-else class="aspect-video rounded-lg border-2 border-dashed border-border flex items-center justify-center text-muted-foreground text-sm">
      {{ resolvedUrl ? 'Invalid video URL' : 'No video URL set' }}
    </div>
    <figcaption v-if="block.data.caption" class="mt-2 text-center text-sm text-muted-foreground">
      {{ block.data.caption }}
    </figcaption>
  </figure>
</template>
<script setup>
import { computed } from 'vue'
import { useFieldBinding } from '@/composables/useLoopBinding.js'
const props = defineProps({ block: { type: Object, required: true } })

const resolvedUrl = useFieldBinding(() => props.block, 'url')

const embedUrl = computed(() => {
  const url = resolvedUrl.value ?? ''
  const yt = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\s]+)/)
  if (yt) return `https://www.youtube.com/embed/${yt[1]}`
  const vm = url.match(/vimeo\.com\/(\d+)/)
  if (vm) return `https://player.vimeo.com/video/${vm[1]}`
  return null
})
</script>
```

**Step 3: Rewrite `HtmlBlock.vue`**

```vue
<!-- resources/js/Components/Blocks/HtmlBlock.vue -->
<template>
  <div v-if="resolvedContent" v-html="resolvedContent" />
</template>
<script setup>
import { useFieldBinding } from '@/composables/useLoopBinding.js'
const props = defineProps({ block: { type: Object, required: true } })
const resolvedContent = useFieldBinding(() => props.block, 'content')
</script>
```

**Step 4: Update `CtaBlock.vue` — add `resolvedText` and `resolvedButtonLabel`**

The CTA already has `resolvedHeadline` and `resolvedButtonUrl`. Add the two new fields:

```vue
<!-- resources/js/Components/Blocks/CtaBlock.vue -->
<template>
  <div class="my-4 rounded-lg border bg-card p-6 text-center">
    <h3 v-if="resolvedHeadline" class="text-xl font-bold mb-2">{{ resolvedHeadline }}</h3>
    <p v-if="resolvedText" class="text-muted-foreground mb-4">{{ resolvedText }}</p>
    <a
      v-if="resolvedButtonUrl"
      :href="resolvedButtonUrl"
      class="inline-flex items-center rounded-md bg-primary px-5 py-2 text-sm font-medium text-primary-foreground hover:bg-[var(--primary-hover)] transition-colors"
    >
      {{ resolvedButtonLabel || 'Learn more' }}
    </a>
  </div>
</template>
<script setup>
import { useFieldBinding } from '@/composables/useLoopBinding.js'
const props = defineProps({ block: { type: Object, required: true } })
const resolvedHeadline    = useFieldBinding(() => props.block, 'headline')
const resolvedText        = useFieldBinding(() => props.block, 'text')
const resolvedButtonUrl   = useFieldBinding(() => props.block, 'button_url')
const resolvedButtonLabel = useFieldBinding(() => props.block, 'button_label')
</script>
```

**Step 5: Run tests + commit**

```bash
php artisan test
git add resources/js/components/Blocks/QuoteBlock.vue \
        resources/js/components/Blocks/VideoBlock.vue \
        resources/js/components/Blocks/HtmlBlock.vue \
        resources/js/components/Blocks/CtaBlock.vue
git commit -m "feat: QuoteBlock, VideoBlock, HtmlBlock, CtaBlock — useFieldBinding for all bindable fields"
```

---

### Task 10: Pass `contextFields` from Template editor pages

**Files:**
- Modify: `resources/js/Pages/Templates/Edit.vue`
- Modify: `resources/js/Pages/Templates/Create.vue`

**Step 1: Import `POST_CONTEXT_FIELDS` in both files**

At the top of the `<script setup>` in each file, add:
```js
import { POST_CONTEXT_FIELDS } from '@/lib/loopSources.js'
```

**Step 2: Pass `contextFields` to `<BlockEditor>` conditionally**

In `Templates/Edit.vue`, find the `<BlockEditor>` usage:
```html
<BlockEditor
  :template-type="template.type"
  :model-value="form.blocks"
  :is-admin="authUser?.role === 'administrator'"
  @update:model-value="form.blocks = $event"
/>
```

Replace with:
```html
<BlockEditor
  :model-value="form.blocks"
  :is-admin="authUser?.role === 'administrator'"
  :context-fields="template.type === 'single-post' ? POST_CONTEXT_FIELDS : []"
  @update:model-value="form.blocks = $event"
/>
```

Note: `:template-type` was never consumed by BlockEditor, so it can be removed.

Apply the same change in `Templates/Create.vue` — using `form.type` instead of `template.type`:
```html
<BlockEditor
  :model-value="form.blocks"
  :is-admin="authUser?.role === 'administrator'"
  :context-fields="form.type === 'single-post' ? POST_CONTEXT_FIELDS : []"
  @update:model-value="form.blocks = $event"
/>
```

**Step 3: Run tests + build**

```bash
php artisan test
npm run build
```
Expected: all tests pass, build succeeds with no errors.

**Step 4: Commit**

```bash
git add resources/js/Pages/Templates/Edit.vue \
        resources/js/Pages/Templates/Create.vue
git commit -m "feat: Templates editor — pass POST_CONTEXT_FIELDS for single-post templates"
```

---

## Summary of Commits

1. `feat: loopSources — typed {value,label} fields + POST_CONTEXT_FIELDS`
2. `feat: useFieldBinding — prefix-aware resolution (loop: and post: sources)`
3. `feat: BlockEditor — contextFields prop + availableFields computed`
4. `feat: BlockLayers — thread availableFields prop to settings components`
5. `feat: DynamicField — grouped native select, rename loopFields → availableFields`
6. `fix: ConditionSettings — fieldOptions from typed {value,label} loop fields`
7. `feat: settings — rename loopFields → availableFields; CTA adds text+button_label bindings`
8. `feat: QuoteSettings, VideoSettings, HtmlSettings — DynamicField binding support`
9. `feat: QuoteBlock, VideoBlock, HtmlBlock, CtaBlock — useFieldBinding for all bindable fields`
10. `feat: Templates editor — pass POST_CONTEXT_FIELDS for single-post templates`
