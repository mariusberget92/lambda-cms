<template><div></div></template>
<script setup>
defineProps({ blocks: Array, selectedId: String, isAdmin: Boolean })
defineEmits(['select', 'add', 'remove', 'reorder'])
</script>
