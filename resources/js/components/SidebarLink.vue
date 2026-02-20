<template>
  <a
    :href="href"
    class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition-colors"
    :class="active
      ? 'bg-sidebar-accent text-sidebar-accent-foreground'
      : 'text-sidebar-foreground/70 hover:bg-sidebar-accent/50 hover:text-sidebar-foreground'"
  >
    <span class="shrink-0">
      <slot name="icon" />
    </span>
    <slot />
  </a>
</template>

<script setup>
defineProps({
  href: {
    type: String,
    required: true,
  },
  active: {
    type: Boolean,
    default: false,
  },
});
</script>
