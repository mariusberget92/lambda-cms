<!-- resources/js/Components/BlockEditor/blocks/AudioSettings.vue -->
<template>
  <div class="space-y-3">
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Audio file URL <span class="text-destructive">*</span></label>
      <input :value="block.data.src" type="text" placeholder="https://.../audio.mp3"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { src: $event.target.value } })" />
    </div>
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Title <span class="opacity-60">(optional)</span></label>
      <input :value="block.data.title" type="text" placeholder="Track title..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { title: $event.target.value } })" />
    </div>
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Artist</label>
      <input :value="block.data.artist" type="text" placeholder="Artist name..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { artist: $event.target.value } })" />
    </div>
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Cover image URL</label>
      <input :value="block.data.coverUrl" type="text" placeholder="https://..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { coverUrl: $event.target.value } })" />
    </div>
  </div>
</template>

<script setup>
const props = defineProps({ block: { type: Object, required: true }, tab: { type: String, default: null } })
const emit = defineEmits(['update'])
</script>
