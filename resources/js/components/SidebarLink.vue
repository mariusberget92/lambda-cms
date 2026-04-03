<template>
  <a
    :href="href"
    class="flex items-center gap-3 py-2 rounded-md text-sm font-medium transition-colors"
    :class="active
      ? 'bg-sidebar-primary/10 text-sidebar-primary font-semibold pl-[10px] border-l-2 border-sidebar-primary'
      : 'text-sidebar-foreground/70 hover:bg-sidebar-accent/60 hover:text-sidebar-foreground px-3'"
  >
    <span class="shrink-0">
      <slot name="icon" />
    </span>
    <slot />
  </a>
</template>

<script setup>
defineProps({
  href:   { type: String, required: true },
  active: { type: Boolean, default: false },
})
</script>
