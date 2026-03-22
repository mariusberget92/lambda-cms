<!-- resources/js/Components/BlockEditor/blocks/ImageSettings.vue -->
<template>
  <div class="space-y-3">
    <!-- URL via DynamicField — media picker shown only in static mode -->
    <DynamicField
      label="Image URL"
      field-name="url"
      :block="block"
      :loop-fields="loopFields"
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

    <DynamicField
      label="Alt text"
      field-name="alt"
      :block="block"
      :loop-fields="loopFields"
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
import { ref } from 'vue'
import MediaPicker  from '@/Components/MediaPicker.vue'
import DynamicField from './DynamicField.vue'

const props = defineProps({
  block:      { type: Object, required: true },
  loopFields: { type: Array,  default: () => [] },
})
const emit = defineEmits(['update'])

const showPicker = ref(false)

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
