<!-- resources/js/Components/BlockEditor/blocks/HtmlSettings.vue -->
<template>
  <!-- Defensive self-guard: disabled for non-admins even if rendered directly -->
  <div v-if="!isAdmin" class="rounded-md border border-dashed p-4 text-center">
    <p class="text-xs text-muted-foreground">HTML blocks are admin-only.</p>
  </div>
  <div v-else>
    <label class="text-xs font-medium text-muted-foreground block mb-1">Raw HTML</label>
    <textarea
      :value="block.data.content"
      rows="12"
      placeholder="<div>...</div>"
      class="w-full rounded-md border bg-background px-2 py-1.5 text-xs font-mono focus:outline-none focus:ring-2 focus:ring-ring resize-y"
      @input="emit('update', { id: block.id, data: { content: $event.target.value } })"
    />
    <p class="mt-1 text-xs text-muted-foreground">&#x26A0; Admin only &mdash; rendered as-is in the page.</p>
  </div>
</template>

<script setup>
defineProps({
  block:   { type: Object,  required: true },
  isAdmin: { type: Boolean, default: false },
})
const emit = defineEmits(['update'])
</script>
