<!-- resources/js/Components/Blocks/MapBlock.vue -->
<template>
  <div
    v-if="block.data?.embedUrl"
    class="overflow-hidden"
    :style="{ borderRadius: block.data?.borderRadius ?? '0.5rem' }"
  >
    <iframe
      :src="block.data.embedUrl"
      :style="{ width: '100%', height: block.data?.height ?? '400px', border: 0 }"
      allowfullscreen
      loading="lazy"
      referrerpolicy="no-referrer-when-downgrade"
      title="Map"
    />
  </div>
  <div
    v-else
    class="flex flex-col items-center justify-center gap-2 rounded-lg border border-dashed text-muted-foreground/40"
    :style="{ height: block.data?.height ?? '400px' }"
  >
    <Icon icon="lucide:map-pin" style="font-size: 2rem" aria-hidden="true" />
    <p class="text-sm">No map embed URL set</p>
  </div>
</template>

<script setup>
import { Icon } from '@iconify/vue'
defineProps({ block: { type: Object, required: true } })
</script>
