<!-- resources/js/Components/Blocks/VideoBlock.vue -->
<template>
  <figure class="my-4">
    <div v-if="embedUrl" class="relative aspect-video rounded-lg overflow-hidden border border-border">
      <iframe
        :src="embedUrl"
        class="absolute inset-0 w-full h-full"
        frameborder="0"
        allow="autoplay; encrypted-media"
        allowfullscreen
      />
    </div>
    <div v-else class="aspect-video rounded-lg border-2 border-dashed border-border flex items-center justify-center text-muted-foreground text-sm">
      {{ resolvedUrl ? 'Invalid video URL' : 'No video URL set' }}
    </div>
    <figcaption v-if="block.data.caption" class="mt-2 text-center text-sm text-muted-foreground">
      {{ block.data.caption }}
    </figcaption>
  </figure>
</template>
<script setup>
import { computed } from 'vue'
import { useFieldBinding } from '@/composables/useLoopBinding.js'
const props = defineProps({ block: { type: Object, required: true } })

const resolvedUrl = useFieldBinding(() => props.block, 'url')

const embedUrl = computed(() => {
  const url = resolvedUrl.value ?? ''
  const yt = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\s]+)/)
  if (yt) return `https://www.youtube.com/embed/${yt[1]}`
  const vm = url.match(/vimeo\.com\/(\d+)/)
  if (vm) return `https://player.vimeo.com/video/${vm[1]}`
  return null
})
</script>
