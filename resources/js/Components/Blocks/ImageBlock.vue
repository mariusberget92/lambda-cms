<!-- resources/js/Components/Blocks/ImageBlock.vue -->
<template>
  <figure>
    <img
      v-if="resolvedUrl"
      :src="resolvedUrl"
      :alt="resolvedAlt || ''"
      class="w-full object-cover"
      :style="[
        block.data?.maxHeight ? { maxHeight: block.data.maxHeight, height: block.data.maxHeight } : {},
        block.data?.aspectRatio && block.data.aspectRatio !== 'auto' ? { aspectRatio: block.data.aspectRatio } : {}
      ]"
      @error="onError"
    />
    <div
      v-else-if="!resolvedUrl"
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
import { useFieldBinding } from '@/composables/useLoopBinding.js'
const props = defineProps({ block: { type: Object, required: true } })
const resolvedUrl = useFieldBinding(() => props.block, 'url')
const resolvedAlt = useFieldBinding(() => props.block, 'alt')
function onError(e) { e.target.style.display = 'none' }
</script>
