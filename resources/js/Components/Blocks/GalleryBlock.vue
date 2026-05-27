<template>
  <div v-if="block.data.items?.length" class="grid gap-2 my-4"
    :class="block.data.items.length === 1 ? 'grid-cols-1' : block.data.items.length === 2 ? 'grid-cols-2' : 'grid-cols-3'"
  >
    <div
      v-for="item in block.data.items"
      :key="item.media_id"
      class="gallery-item aspect-square"
    >
      <img
        :src="item.url"
        :alt="item.alt || ''"
        class="w-full h-full object-cover"
        @error="e => e.target.parentElement.classList.add('gallery-item--error')"
      />
    </div>
  </div>
  <div v-else class="gallery-empty">Empty gallery</div>
</template>

<script setup>
defineProps({ block: { type: Object, required: true } })
</script>

<style scoped>
.gallery-item {
  overflow: hidden;
  border-radius: var(--blog-radius);
  background: var(--bg);
}
.gallery-item--error { background: var(--bg); }
.gallery-empty {
  height: 6rem;
  border-radius: var(--blog-radius);
  border: 2px dashed var(--line-strong);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--soft);
  font-size: 0.875rem;
  margin: 1rem 0;
}
</style>
