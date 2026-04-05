<!-- resources/js/Components/ScrollArea.vue -->
<!-- Thin wrapper around OverlayScrollbars with Nord-palette styling -->
<template>
  <OverlayScrollbarsComponent
    :element="element"
    :options="options"
    :class="$attrs.class"
    :style="$attrs.style"
    defer
  >
    <slot />
  </OverlayScrollbarsComponent>
</template>

<script setup>
import { OverlayScrollbarsComponent } from 'overlayscrollbars-vue'

defineOptions({ inheritAttrs: false })

const props = defineProps({
  element: { type: String, default: 'div' },
})

const options = {
  scrollbars: {
    theme:      'os-theme-nord',
    autoHide:   'scroll',
    autoHideDelay: 800,
    clickScroll: true,
  },
}
</script>
