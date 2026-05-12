<!-- resources/js/Components/Blocks/MapBlock.vue -->
<template>
  <div
    v-if="safeUrl"
    class="overflow-hidden"
    :style="{ borderRadius: block.data?.borderRadius ?? '0.5rem' }"
  >
    <iframe
      :src="safeUrl"
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
import { computed } from 'vue'
import { Icon } from '@iconify/vue'
const props = defineProps({ block: { type: Object, required: true } })

const safeUrl = computed(() => {
  const url = props.block.data?.embedUrl
  if (!url) return null
  try {
    const u = new URL(url)
    return (u.protocol === 'http:' || u.protocol === 'https:') ? url : null
  } catch {
    return null
  }
})
</script>
