<!-- resources/js/Components/Blocks/GalleryBlock.vue -->
<template>
  <div v-if="block.data.items?.length" class="grid gap-2 my-4"
    :class="block.data.items.length === 1 ? 'grid-cols-1' : block.data.items.length === 2 ? 'grid-cols-2' : 'grid-cols-3'"
  >
    <div
      v-for="item in block.data.items"
      :key="item.media_id"
      class="rounded-lg overflow-hidden aspect-square"
    >
      <img
        :src="item.url"
        :alt="item.alt || ''"
        class="w-full h-full object-cover"
        @error="e => e.target.parentElement.classList.add('bg-muted')"
      />
    </div>
  </div>
  <div v-else class="h-24 rounded-lg border-2 border-dashed border-border flex items-center justify-center text-muted-foreground text-sm my-4">
    Empty gallery
  </div>
</template>
<script setup>
defineProps({ block: { type: Object, required: true } })
</script>
