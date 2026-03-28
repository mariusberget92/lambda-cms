<script setup>
defineProps({
  tab: { type: String, default: null },  // 'content' | 'style' | null (show all)
})
</script>
<template>
  <div v-show="!tab || tab === 'content'" class="p-3 text-xs text-muted-foreground">Renders the full post content. No settings.</div>
</template>
