<!-- resources/js/Components/BlockEditor/blocks/EmbedSettings.vue -->
<template>
  <!-- Content tab -->
  <div v-show="!tab || tab === 'content'" class="space-y-3">
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">URL</label>
      <input
        :value="block.data.url"
        type="url"
        placeholder="YouTube, Vimeo, Maps URL..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { url: $event.target.value } })"
      />
      <p class="text-[10px] text-muted-foreground mt-1">Supports YouTube, Vimeo, Google Maps, Twitter/X, or any URL.</p>
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Caption</label>
      <input
        :value="block.data.caption"
        type="text"
        placeholder="Optional caption..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { caption: $event.target.value } })"
      />
    </div>
  </div>

  <!-- Style tab -->
  <div v-show="!tab || tab === 'style'" class="space-y-3">
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Aspect ratio</label>
      <SelectBox
        :model-value="block.data.aspectRatio ?? '16/9'"
        :data="[
          { value: '16/9',  label: '16:9 (Widescreen)' },
          { value: '4/3',   label: '4:3 (Standard)' },
          { value: '1/1',   label: '1:1 (Square)' },
          { value: '21/9',  label: '21:9 (Ultrawide)' },
          { value: '9/16',  label: '9:16 (Portrait / Short)' },
        ]"
        @update:model-value="v => emit('update', { id: block.id, data: { aspectRatio: v } })"
      />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Max width</label>
      <input
        :value="block.data.maxWidth"
        type="text"
        placeholder="e.g. 800px or 100%"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { maxWidth: $event.target.value } })"
      />
    </div>
  </div>
</template>

<script setup>
import SelectBox from '@/Components/SelectBox.vue'

const props = defineProps({
  block: { type: Object, required: true },
  tab:   { type: String, default: null },
})
const emit = defineEmits(['update'])
</script>
