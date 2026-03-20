<!-- resources/js/Components/Blocks/CtaBlock.vue -->
<template>
  <div class="my-4 rounded-lg border bg-card p-6 text-center">
    <h3 v-if="block.data.headline" class="text-xl font-bold mb-2">{{ block.data.headline }}</h3>
    <p v-if="block.data.text" class="text-muted-foreground mb-4">{{ block.data.text }}</p>
    <a
      v-if="block.data.button_url"
      :href="block.data.button_url"
      class="inline-flex items-center rounded-md bg-primary px-5 py-2 text-sm font-medium text-primary-foreground hover:bg-primary-hover transition-colors"
    >
      {{ block.data.button_label || 'Learn more' }}
    </a>
  </div>
</template>
<script setup>
defineProps({ block: { type: Object, required: true } })
</script>
