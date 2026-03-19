<!-- resources/js/Components/Blocks/ImageBlock.vue -->
<template>
  <figure class="my-4">
    <img
      v-if="block.data.url"
      :src="block.data.url"
      :alt="block.data.alt || ''"
      class="w-full rounded-lg object-cover"
      @error="onError"
    />
    <div
      v-else
      class="w-full h-32 rounded-lg border-2 border-dashed border-border flex items-center justify-center text-muted-foreground text-sm"
    >
      Image not available
    </div>
    <figcaption v-if="block.data.caption" class="mt-2 text-center text-sm text-muted-foreground">
      {{ block.data.caption }}
    </figcaption>
  </figure>
</template>
<script setup>
defineProps({ block: { type: Object, required: true } })
function onError(e) { e.target.style.display = 'none' }
</script>
