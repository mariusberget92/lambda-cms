<template>
  <figure class="video-block">
    <div v-if="embedUrl" class="video-block__frame">
      <iframe
        :src="embedUrl"
        class="absolute inset-0 w-full h-full"
        frameborder="0"
        allow="autoplay; encrypted-media"
        allowfullscreen
      />
    </div>
    <div v-else class="video-block__empty">
      {{ resolvedUrl ? 'Invalid video URL' : 'No video URL set' }}
    </div>
    <figcaption v-if="block.data.caption" class="video-block__caption">
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

<style scoped>
.video-block { margin: 1rem 0; }
.video-block__frame {
  position: relative;
  aspect-ratio: 16/9;
  border-radius: var(--blog-radius);
  overflow: hidden;
  border: 1px solid var(--line-strong);
}
.video-block__empty {
  aspect-ratio: 16/9;
  border-radius: var(--blog-radius);
  border: 2px dashed var(--line-strong);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--soft);
  font-size: 0.875rem;
}
.video-block__caption { margin-top: 0.5rem; text-align: center; font-size: 0.875rem; color: var(--soft); }
</style>
