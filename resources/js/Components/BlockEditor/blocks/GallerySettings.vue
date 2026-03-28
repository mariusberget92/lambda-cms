<!-- resources/js/Components/BlockEditor/blocks/GallerySettings.vue -->
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

    <button
      type="button"
      class="w-full rounded-md border border-dashed px-3 py-2 text-xs text-muted-foreground hover:border-primary hover:text-primary transition-colors"
      @click="showPicker = true"
    >
      + Add image
    </button>

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
const emit  = defineEmits(['update'])

const showPicker = ref(false)

function onMediaSelect(media) {
  showPicker.value = false
  const items = [...(props.block.data.items ?? []), { media_id: media.id, url: media.url, alt: media.alt ?? '' }]
  emit('update', { id: props.block.id, data: { items } })
}

function removeItem(index) {
  const items = props.block.data.items.filter((_, i) => i !== index)
  emit('update', { id: props.block.id, data: { items } })
}
</script>
