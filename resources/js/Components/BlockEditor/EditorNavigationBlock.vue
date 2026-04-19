<template>
  <div class="px-3 py-2">
    <div v-if="links.length" class="flex flex-wrap gap-2 text-xs text-muted-foreground">
      <span v-for="(link, i) in links" :key="i" class="underline">{{ link.label || '(no label)' }}</span>
    </div>
    <div v-else class="text-xs text-muted-foreground italic">No links yet — edit in settings</div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
const props = defineProps({ block: Object })
const links = computed(() => props.block?.data?.links ?? [])
</script>
