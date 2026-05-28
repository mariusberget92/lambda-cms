<script setup>
import { inject, computed } from 'vue'
const props = defineProps({ block: Object })
const post = inject('postContext', null)

const isHero      = computed(() => props.block.data?.variant === 'hero')
const aspectRatio = computed(() => props.block.data?.aspectRatio ?? (isHero.value ? '21/9' : '16/9'))
</script>

<template>
  <!-- Hero variant: edge-to-edge, cinematic, gradient fade to --bg -->
  <div v-if="isHero" class="feat-hero">
    <div v-if="!post" class="feat-hero__skel" :style="{ aspectRatio: aspectRatio }" />
    <div v-else-if="post.featured_image_url" class="feat-hero__wrap" :style="{ aspectRatio: aspectRatio }">
      <img
        :src="post.featured_image_url"
        :alt="post.featured_image_alt ?? post.title"
        class="feat-hero__img"
      />
      <div class="feat-hero__grad" />
    </div>
  </div>

  <!-- Default variant: constrained image with blog radius -->
  <template v-else>
    <div v-if="!post" class="feat-default__skel" :style="{ aspectRatio: aspectRatio }" />
    <div v-else-if="post.featured_image_url" :style="{ maxWidth: block.data?.maxWidth ?? '100%' }">
      <img
        :src="post.featured_image_url"
        :alt="post.featured_image_alt ?? post.title"
        class="feat-default__img"
        :style="{ aspectRatio: aspectRatio }"
      />
    </div>
  </template>
</template>

<style scoped>
/* ── Hero variant ── */
.feat-hero {
  width: 100%;
  overflow: hidden;
}
.feat-hero__skel {
  width: 100%;
  background: var(--line-strong);
  animation: pulse 1.5s ease-in-out infinite;
}
.feat-hero__wrap {
  position: relative;
  width: 100%;
  overflow: hidden;
}
.feat-hero__img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}
.feat-hero__grad {
  position: absolute;
  inset: 0;
  background: linear-gradient(to bottom, transparent 55%, var(--bg) 100%);
  pointer-events: none;
}

/* ── Default variant ── */
.feat-default__skel {
  width: 100%;
  background: var(--line-strong);
  border-radius: var(--blog-radius);
  animation: pulse 1.5s ease-in-out infinite;
}
.feat-default__img {
  width: 100%;
  object-fit: cover;
  display: block;
  border-radius: var(--blog-radius);
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}
</style>
