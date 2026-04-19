<script setup>
defineProps({
  loading: { type: Boolean, default: false },
  empty:   { type: Boolean, default: false },
})
</script>

<template>
  <div class="rounded-xl border bg-card overflow-hidden" style="box-shadow: var(--shadow-sm)">
    <table class="w-full text-sm" :aria-busy="loading">
      <thead>
        <tr class="border-b border-border bg-muted/40">
          <slot name="headers" />
        </tr>
      </thead>
      <tbody class="divide-y divide-border">
        <template v-if="loading">
          <tr v-for="i in 5" :key="i">
            <td colspan="100" class="px-4 py-3">
              <div class="h-4 bg-muted animate-pulse rounded" />
            </td>
          </tr>
        </template>
        <template v-else-if="empty">
          <tr>
            <td colspan="100" class="px-4 py-16 text-center">
              <slot name="empty">
                <p class="text-sm text-muted-foreground">No results found.</p>
              </slot>
            </td>
          </tr>
        </template>
        <template v-else>
          <slot name="rows" />
        </template>
      </tbody>
    </table>
    <div v-if="$slots.footer" class="border-t border-border px-4 py-3 bg-muted/20">
      <slot name="footer" />
    </div>
  </div>
</template>

<style scoped>
:deep(thead th) {
  padding: 0.625rem 1rem;
  font-size: 0.7rem;
  font-weight: 600;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  color: var(--muted-foreground);
}
:deep(tbody td) {
  padding: 0.875rem 1rem;
}
:deep(tbody tr) {
  transition: background-color 0.15s;
}
:deep(tbody tr:hover) {
  background-color: color-mix(in srgb, var(--muted) 30%, transparent);
}
</style>
