<template>
  <div class="divider-wrap">
    <hr v-if="block.data.style === 'line'" class="divider-line" />
    <div v-else-if="block.data.style === 'dots'" class="divider-dots">
      <span>·</span><span>·</span><span>·</span>
    </div>
    <div v-else class="divider-space" />
  </div>
</template>

<script setup>
defineProps({ block: { type: Object, required: true } })
</script>

<style scoped>
.divider-wrap { margin-top: 1.5rem; margin-bottom: 1.5rem; display: flex; align-items: center; justify-content: center; }
.divider-line { width: 100%; border: none; border-top: 1px solid var(--line-strong); }
.divider-dots { display: flex; gap: 0.5rem; color: var(--soft); }
.divider-space { height: 2rem; }
</style>
