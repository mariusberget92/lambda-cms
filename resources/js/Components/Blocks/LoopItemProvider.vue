<!-- resources/js/Components/Blocks/LoopItemProvider.vue -->
<script setup>
import { provide, toRef } from 'vue'
const props = defineProps({ item: { type: Object, required: true } })
// Provide as a ref so watchers on loopItem.value in children react to item changes
provide('loopItem', toRef(props, 'item'))
</script>
<template><slot /></template>
