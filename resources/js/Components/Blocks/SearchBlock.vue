<script setup>
const props = defineProps({ block: Object })
const currentQ = typeof window !== 'undefined'
  ? new URLSearchParams(window.location.search).get('q') ?? ''
  : ''
</script>

<template>
  <form method="GET" action="/search" class="flex gap-2">
    <input
      type="text"
      name="q"
      :placeholder="block.data?.placeholder ?? 'Search…'"
      :value="currentQ"
      class="flex-1 rounded-md border border-border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary/40"
    />
    <button type="submit"
      class="px-4 py-2 bg-primary text-primary-foreground text-sm rounded-md hover:bg-primary/90 transition-colors">
      {{ block.data?.buttonLabel ?? 'Search' }}
    </button>
  </form>
</template>
