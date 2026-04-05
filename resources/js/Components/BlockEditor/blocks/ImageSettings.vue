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
    // Open the picker — don't clear url yet, user may cancel
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
