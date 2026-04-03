<template>
  <nav :class="wrapClass">
    <a
      v-for="(link, i) in links"
      :key="i"
      :href="link.url || '#'"
      :target="link.newTab ? '_blank' : undefined"
      :rel="link.newTab ? 'noopener noreferrer' : undefined"
      :class="linkClass"
    >{{ link.label }}</a>
  </nav>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({ block: Object })

const links = computed(() => props.block?.data?.links ?? [])
const navStyle = computed(() => props.block?.data?.style ?? 'horizontal')
const alignment = computed(() => props.block?.data?.alignment ?? 'left')

const wrapClass = computed(() => {
  const align = { left: 'justify-start', center: 'justify-center', right: 'justify-end' }[alignment.value] ?? 'justify-start'
  if (navStyle.value === 'vertical') return `flex flex-col gap-1 items-${alignment.value === 'center' ? 'center' : alignment.value === 'right' ? 'end' : 'start'}`
  return `flex flex-wrap gap-2 ${align}`
})

const linkClass = computed(() => {
  if (navStyle.value === 'pills') return 'rounded-full bg-primary/10 text-primary px-3 py-1 text-sm font-medium hover:bg-primary/20 transition-colors'
  if (navStyle.value === 'minimal') return 'text-sm text-muted-foreground hover:text-foreground transition-colors'
  return 'text-sm font-medium hover:text-primary transition-colors'
})
</script>
