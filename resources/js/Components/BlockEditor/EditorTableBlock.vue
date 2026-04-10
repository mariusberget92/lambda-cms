<template>
  <div class="px-3 py-2 text-xs text-white/40">Table (editor coming soon)</div>
</template>
<script setup>
defineProps({ block: { type: Object, required: true }, selectedId: { type: String, default: null } })
defineEmits(['select', 'update'])
</script>
