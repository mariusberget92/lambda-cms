<template>
  <a
    :href="href"
    class="flex items-center rounded-md font-medium transition-colors"
    style="padding-top: var(--density-sidebar-py); padding-bottom: var(--density-sidebar-py); gap: var(--density-sidebar-gap); font-size: var(--density-sidebar-text)"
    :class="active
      ? 'bg-sidebar-primary/10 text-sidebar-primary font-semibold pl-[10px] border-l-2 border-sidebar-primary'
      : 'text-sidebar-foreground/70 hover:bg-sidebar-accent/60 hover:text-sidebar-foreground px-3'"
  >
    <span class="shrink-0 [&>svg]:w-full [&>svg]:h-full" style="width: var(--density-sidebar-icon); height: var(--density-sidebar-icon)">
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
