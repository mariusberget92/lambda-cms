<!-- resources/js/Components/BlockEditor/blocks/VideoSettings.vue -->
<template>
  <!-- Content fields -->
  <div v-show="!tab || tab === 'content'" class="space-y-3">
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

    <!-- Embedded preview (read-only) -->
    <div v-if="embedUrl" class="rounded-md overflow-hidden border aspect-video">
      <iframe
        :src="embedUrl"
        class="w-full h-full"
        frameborder="0"
        allow="autoplay; encrypted-media"
        sandbox="allow-scripts allow-same-origin allow-presentation"
        allowfullscreen
      />
    </div>

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
import DynamicField from './DynamicField.vue'

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
