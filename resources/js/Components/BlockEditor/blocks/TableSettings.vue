<template>
  <div class="text-xs text-muted-foreground p-2">Table settings coming soon</div>
</template>
<script setup>
defineProps({ block: { type: Object, required: true }, tab: { type: String, default: null } })
defineEmits(['update'])
</script>
