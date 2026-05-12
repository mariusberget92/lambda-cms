<!-- resources/js/Components/BlockEditor/blocks/MapSettings.vue -->
<template>
  <div class="space-y-3">
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Embed URL <span class="text-destructive">*</span></label>
      <textarea :value="block.data.embedUrl" rows="3" placeholder="Paste a Google Maps or other map embed URL..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring resize-none"
        @input="emit('update', { id: block.id, data: { embedUrl: $event.target.value } })" />
      <p class="text-[10px] text-muted-foreground mt-1">In Google Maps: Share → Embed a map → copy the src="..." URL.</p>
    </div>
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Height</label>
      <input :value="block.data.height ?? '400px'" type="text" placeholder="400px"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @change="emit('update', { id: block.id, data: { height: $event.target.value || '400px' } })" />
    </div>
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Border radius</label>
      <input :value="block.data.borderRadius ?? '0.5rem'" type="text" placeholder="0.5rem"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @change="emit('update', { id: block.id, data: { borderRadius: $event.target.value || '0.5rem' } })" />
    </div>
  </div>
</template>

<script setup>
const props = defineProps({ block: { type: Object, required: true }, tab: { type: String, default: null } })
const emit = defineEmits(['update'])
</script>
