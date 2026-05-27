<script setup>
import { inject } from 'vue'
const props = defineProps({ block: Object })
const post = inject('postContext', null)
</script>
<template>
  <div v-if="!post" class="bg-muted/40 animate-pulse w-full aspect-video" style="border-radius: var(--blog-radius);" />
  <div v-else-if="post.featured_image_url" :style="{ maxWidth: block.data?.maxWidth ?? '100%' }">
    <img :src="post.featured_image_url" :alt="post.featured_image_alt ?? post.title"
      class="w-full object-cover" style="border-radius: var(--blog-radius);"
      :style="block.data?.aspectRatio && block.data.aspectRatio !== 'auto' ? { aspectRatio: block.data.aspectRatio } : {}" />
  </div>
  <!-- if post exists but no image: render nothing -->
</template>
