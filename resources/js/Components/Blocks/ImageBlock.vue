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
    <div v-else-if="!resolvedUrl" class="img-placeholder">
      Image not available
    </div>
    <figcaption v-if="block.data.caption" class="img-caption">
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

<style scoped>
.img-placeholder {
  width: 100%;
  height: 8rem;
  border-radius: var(--blog-radius);
  border: 2px dashed var(--line-strong);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--soft);
  font-size: 0.875rem;
}
.img-caption { margin-top: 0.5rem; text-align: center; font-size: 0.875rem; color: var(--soft); }
</style>
