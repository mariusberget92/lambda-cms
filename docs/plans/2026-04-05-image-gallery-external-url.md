# Image & Gallery External URL Support — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add a Library / URL mode toggle to the image block and gallery block so editors can use external image URLs instead of (or instead of uploading to) the media library.

**Architecture:** Pure frontend change — no data model or backend changes. Both blocks already store `url` and `media_id`. Library mode sets both; URL mode sets `url` and nulls `media_id`. A two-pill toggle (derived from block data, not stored separately) controls which input is visible.

**Tech Stack:** Vue 3 `<script setup>`, Tailwind CSS 4, existing `MediaPicker` component.

---

## Task 1: Update `ImageSettings.vue` — add Library / URL toggle

**Files:**
- Modify: `resources/js/components/BlockEditor/blocks/ImageSettings.vue`

The toggle is **derived**, not stored: if `block.data.media_id` is set → Library active; else → URL active. There is no new prop or emit needed beyond what already exists.

**Step 1: Replace the file**

```vue
<!-- resources/js/components/BlockEditor/blocks/ImageSettings.vue -->
<template>
  <!-- Content fields -->
  <div v-show="!tab || tab === 'content'" class="space-y-3">

    <!-- Source toggle -->
    <div class="flex gap-1 p-0.5 rounded-md bg-muted w-fit">
      <button
        type="button"
        class="px-2.5 py-1 rounded text-xs font-medium transition-colors"
        :class="mode === 'library' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'"
        @click="switchMode('library')"
      >Library</button>
      <button
        type="button"
        class="px-2.5 py-1 rounded text-xs font-medium transition-colors"
        :class="mode === 'url' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'"
        @click="switchMode('url')"
      >URL</button>
    </div>

    <!-- Library mode -->
    <DynamicField
      v-if="mode === 'library'"
      label="Image"
      field-name="url"
      :block="block"
      :available-fields="availableFields"
      @bind="onBind"
      @unbind="onUnbind"
    >
      <div>
        <div v-if="block.data.url" class="rounded-md overflow-hidden border mb-2">
          <img :src="block.data.url" :alt="block.data.alt" class="w-full object-cover max-h-32" />
        </div>
        <button
          type="button"
          class="w-full rounded-md border border-dashed px-3 py-2 text-xs text-muted-foreground hover:border-primary hover:text-primary transition-colors"
          @click="showPicker = true"
        >
          {{ block.data.url ? 'Change image' : 'Select image' }}
        </button>
        <MediaPicker v-model="showPicker" @select="onMediaSelect" />
      </div>
    </DynamicField>

    <!-- URL mode -->
    <div v-else>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Image URL</label>
      <div v-if="block.data.url" class="rounded-md overflow-hidden border mb-2">
        <img :src="block.data.url" :alt="block.data.alt" class="w-full object-cover max-h-32" />
      </div>
      <input
        :value="block.data.url"
        type="text"
        placeholder="https://example.com/image.jpg"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { url: $event.target.value, media_id: null } })"
      />
    </div>

    <!-- Alt text (both modes) -->
    <DynamicField
      label="Alt text"
      field-name="alt"
      :block="block"
      :available-fields="availableFields"
      @bind="onBind"
      @unbind="onUnbind"
    >
      <input
        :value="block.data.alt"
        type="text"
        placeholder="Describe the image..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { alt: $event.target.value } })"
      />
    </DynamicField>

    <!-- Caption (both modes) -->
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Caption (optional)</label>
      <input
        :value="block.data.caption"
        type="text"
        placeholder="Caption..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { caption: $event.target.value } })"
      />
    </div>

  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import MediaPicker  from '@/Components/MediaPicker.vue'
import DynamicField from './DynamicField.vue'

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
    // Just open the picker — don't clear url yet, user may cancel
    showPicker.value = true
  } else {
    // Clear media_id, keep any existing url so the preview stays
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

**Step 2: Verify**

Open a page in the block editor, add an Image block. Confirm:
- Default state with no image → URL tab active (no `media_id`)
- Click Library tab → MediaPicker opens; selecting an image switches to Library tab and shows thumbnail
- Click URL tab → clears `media_id`, shows URL input
- Paste a URL → preview appears below the input
- Alt and caption fields present in both modes

**Step 3: Commit**

```bash
git add resources/js/components/BlockEditor/blocks/ImageSettings.vue
git commit -m "feat: add Library/URL toggle to image block"
```

---

## Task 2: Update `GallerySettings.vue` — add Library / URL toggle to add-form

**Files:**
- Modify: `resources/js/components/BlockEditor/blocks/GallerySettings.vue`

Replace the single "+ Add image" button with an always-visible inline add-form containing the same Library / URL toggle. `addMode` is a local `ref('library')`.

**Step 1: Replace the file**

```vue
<!-- resources/js/components/BlockEditor/blocks/GallerySettings.vue -->
<template>
  <!-- Content fields -->
  <div v-show="!tab || tab === 'content'" class="space-y-3">

    <!-- Existing items -->
    <div v-if="block.data.items?.length" class="grid grid-cols-3 gap-1">
      <div
        v-for="(item, i) in block.data.items"
        :key="i"
        class="relative group rounded overflow-hidden border aspect-square"
      >
        <img :src="item.url" :alt="item.alt" class="w-full h-full object-cover" />
        <button
          type="button"
          class="absolute top-0.5 right-0.5 w-5 h-5 rounded-full bg-destructive text-destructive-foreground text-xs flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity"
          @click="removeItem(i)"
        >&#x2715;</button>
      </div>
    </div>
    <p v-else class="text-xs text-muted-foreground text-center py-2">No images yet</p>

    <!-- Add-form -->
    <div class="rounded-md border border-dashed p-2 space-y-2">

      <!-- Mode toggle -->
      <div class="flex gap-1 p-0.5 rounded-md bg-muted w-fit">
        <button
          type="button"
          class="px-2.5 py-1 rounded text-xs font-medium transition-colors"
          :class="addMode === 'library' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'"
          @click="addMode = 'library'"
        >Library</button>
        <button
          type="button"
          class="px-2.5 py-1 rounded text-xs font-medium transition-colors"
          :class="addMode === 'url' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'"
          @click="addMode = 'url'"
        >URL</button>
      </div>

      <!-- Library mode -->
      <button
        v-if="addMode === 'library'"
        type="button"
        class="w-full rounded-md px-3 py-2 text-xs text-muted-foreground hover:text-primary transition-colors text-left"
        @click="showPicker = true"
      >+ Add from library</button>

      <!-- URL mode -->
      <template v-else>
        <input
          v-model="urlInput"
          type="text"
          placeholder="https://example.com/image.jpg"
          class="w-full rounded-md border bg-background px-2 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-ring"
        />
        <input
          v-model="altInput"
          type="text"
          placeholder="Alt text (optional)"
          class="w-full rounded-md border bg-background px-2 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-ring"
        />
        <button
          type="button"
          :disabled="!urlInput.trim()"
          class="w-full rounded-md px-3 py-1.5 text-xs font-medium bg-primary text-primary-foreground disabled:opacity-40 transition-colors"
          @click="addByUrl"
        >Add image</button>
      </template>

    </div>

    <MediaPicker v-model="showPicker" @select="onMediaSelect" />
  </div>
</template>

<script setup>
import { ref } from 'vue'
import MediaPicker from '@/Components/MediaPicker.vue'

const props = defineProps({
  block: { type: Object, required: true },
  tab:   { type: String, default: null },
})
const emit = defineEmits(['update'])

const showPicker = ref(false)
const addMode    = ref('library')
const urlInput   = ref('')
const altInput   = ref('')

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

**Step 2: Verify**

Add a Gallery block. Confirm:
- Default add-form shows Library tab with "+ Add from library" button
- Clicking Library → MediaPicker opens; selecting adds thumbnail to grid
- Switching to URL tab shows URL + alt inputs + disabled "Add image" button
- Typing a URL enables the button; clicking it appends thumbnail to grid and clears inputs
- Remove (×) button still works on all items regardless of source

**Step 3: Commit**

```bash
git add resources/js/components/BlockEditor/blocks/GallerySettings.vue
git commit -m "feat: add Library/URL toggle to gallery block add-form"
```

---

## Task 3: Build

**Step 1: Run the build**

```bash
npm run build
```

Expected: clean build, no new errors.

**Step 2: Commit if build artefacts are tracked**

`public/build` is gitignored in this project — no commit needed.
