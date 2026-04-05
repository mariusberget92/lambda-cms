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
          class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        />
        <input
          v-model="altInput"
          type="text"
          placeholder="Alt text (optional)"
          class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        />
        <button
          type="button"
          :disabled="!urlInput.trim()"
          class="w-full rounded-md px-3 py-1.5 text-xs font-medium bg-primary text-primary-foreground disabled:opacity-40 transition-colors"
          @click="addByUrl"
        >Add image</button>
      </template>

    </div>

    <MediaPicker v-model="showPicker" :dark="true" @select="onMediaSelect" />
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
