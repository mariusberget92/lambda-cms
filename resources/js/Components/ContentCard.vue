<template>
  <div
    class="rounded-xl border bg-card overflow-hidden"
    style="box-shadow: var(--shadow-sm)"
  >
    <div v-if="title || $slots.actions" class="flex items-center justify-between px-6 py-4 border-b border-border">
      <div>
        <h3 class="text-sm font-semibold text-foreground">{{ title }}</h3>
        <p v-if="description" class="text-xs text-muted-foreground mt-0.5">{{ description }}</p>
      </div>
      <div v-if="$slots.actions" class="shrink-0 flex items-center gap-2">
        <slot name="actions" />
      </div>
    </div>
    <div class="px-6 py-5">
      <slot />
    </div>
    <div v-if="$slots.footer" class="px-6 py-4 border-t border-border bg-muted/20 flex items-center justify-end gap-3">
      <slot name="footer" />
    </div>
  </div>
</template>

<script setup>
defineProps({
  title:       { type: String, default: '' },
  description: { type: String, default: '' },
})
</script>
