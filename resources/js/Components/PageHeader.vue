<template>
  <div class="flex items-start justify-between gap-4 pb-6 mb-6 border-b border-border">
    <div>
      <h1 class="text-xl font-semibold text-foreground">{{ title }}</h1>
      <p v-if="description" class="text-sm text-muted-foreground mt-1">{{ description }}</p>
    </div>
    <div v-if="$slots.actions" class="shrink-0 flex items-center gap-2">
      <slot name="actions" />
    </div>
  </div>
</template>

<script setup>
defineProps({
  title:       { type: String, required: true },
  description: { type: String, default: '' },
})
</script>
