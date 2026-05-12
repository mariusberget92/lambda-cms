<!-- resources/js/components/Blocks/EmbedBlock.vue -->
<template>
  <figure class="my-4" :style="block.data.maxWidth ? { maxWidth: block.data.maxWidth + 'px', margin: '1rem auto' } : {}">
    <div
      v-if="safeUrl"
      class="relative overflow-hidden rounded-lg border border-border"
      :style="{ aspectRatio: block.data.aspectRatio || '16/9' }"
    >
      <iframe
        :src="safeUrl"
        class="absolute inset-0 w-full h-full"
        frameborder="0"
        allow="autoplay; encrypted-media"
        allowfullscreen
        sandbox="allow-scripts allow-same-origin allow-popups allow-forms"
      />
    </div>
    <div
      v-else
      class="flex items-center justify-center rounded-lg border-2 border-dashed border-border text-sm text-muted-foreground"
      :style="{ aspectRatio: block.data.aspectRatio || '16/9' }"
    >
      No embed URL set
    </div>
    <figcaption v-if="block.data.caption" class="mt-2 text-center text-sm text-muted-foreground">
      {{ block.data.caption }}
    </figcaption>
  </figure>
</template>
<script setup>
import { computed } from 'vue'
const props = defineProps({ block: { type: Object, required: true } })

const safeUrl = computed(() => {
  const url = props.block.data?.url
  if (!url) return null
  try {
    const u = new URL(url)
    return (u.protocol === 'http:' || u.protocol === 'https:') ? url : null
  } catch {
    return null
  }
})
</script>
