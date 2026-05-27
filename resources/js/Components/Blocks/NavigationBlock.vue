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

const links    = computed(() => props.block?.data?.links ?? [])
const navStyle = computed(() => props.block?.data?.style ?? 'horizontal')
const alignment = computed(() => props.block?.data?.alignment ?? 'left')

const wrapClass = computed(() => {
  const align = { left: 'justify-start', center: 'justify-center', right: 'justify-end' }[alignment.value] ?? 'justify-start'
  if (navStyle.value === 'vertical') {
    const ai = alignment.value === 'center' ? 'items-center' : alignment.value === 'right' ? 'items-end' : 'items-start'
    return `flex flex-col gap-1 ${ai}`
  }
  return `flex flex-wrap gap-2 ${align}`
})

const linkClass = computed(() => {
  if (navStyle.value === 'pills')   return 'nav-link nav-link--pill'
  if (navStyle.value === 'minimal') return 'nav-link nav-link--minimal'
  return 'nav-link nav-link--default'
})
</script>

<style scoped>
.nav-link { font-size: 0.875rem; text-decoration: none; transition: color 150ms; }

.nav-link--default { font-weight: 500; color: var(--soft); }
.nav-link--default:hover { color: var(--ink); }

.nav-link--minimal { color: var(--soft); }
.nav-link--minimal:hover { color: var(--ink); }

.nav-link--pill {
  font-weight: 500;
  color: var(--accent);
  background: transparent;
  border: 1px solid var(--line-strong);
  border-radius: 9999px;
  padding: 0.25rem 0.75rem;
}
.nav-link--pill:hover {
  background: var(--accent);
  color: var(--accent-ink);
  border-color: var(--accent);
}
</style>
