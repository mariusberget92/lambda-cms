<template>
  <figure class="embed-block" :style="block.data.maxWidth ? { maxWidth: block.data.maxWidth + 'px', margin: '1rem auto' } : {}">
    <div
      v-if="block.data.url"
      class="embed-block__frame"
      :style="{ aspectRatio: block.data.aspectRatio || '16/9' }"
    >
      <iframe
        :src="block.data.url"
        class="absolute inset-0 w-full h-full"
        frameborder="0"
        allow="autoplay; encrypted-media"
        allowfullscreen
        sandbox="allow-scripts allow-same-origin allow-popups allow-forms"
      />
    </div>
    <div
      v-else
      class="embed-block__empty"
      :style="{ aspectRatio: block.data.aspectRatio || '16/9' }"
    >
      No embed URL set
    </div>
    <figcaption v-if="block.data.caption" class="embed-block__caption">
      {{ block.data.caption }}
    </figcaption>
  </figure>
</template>

<script setup>
defineProps({ block: { type: Object, required: true } })
</script>

<style scoped>
.embed-block { margin: 1rem 0; }
.embed-block__frame {
  position: relative;
  overflow: hidden;
  border-radius: var(--blog-radius);
  border: 1px solid var(--line-strong);
}
.embed-block__empty {
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: var(--blog-radius);
  border: 2px dashed var(--line-strong);
  font-size: 0.875rem;
  color: var(--soft);
}
.embed-block__caption { margin-top: 0.5rem; text-align: center; font-size: 0.875rem; color: var(--soft); }
</style>
