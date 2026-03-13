<script setup>
defineProps({
  loading: { type: Boolean, default: false },
  empty:   { type: Boolean, default: false },
})
</script>

<template>
  <div class="rounded-lg border overflow-hidden">
    <table class="w-full text-sm">
      <thead class="bg-muted/50 border-b border-border">
        <tr>
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
            <td colspan="100" class="px-4 py-12 text-center text-sm text-muted-foreground">
              <slot name="empty">No results found.</slot>
            </td>
          </tr>
        </template>
        <template v-else>
          <slot name="rows" />
        </template>
      </tbody>
    </table>
  </div>
</template>

<style scoped>
:deep(thead th) {
  padding: 0.75rem 1rem;
  font-size: 0.75rem;
  line-height: 1rem;
  font-weight: 500;
  color: var(--muted-foreground);
}
:deep(tbody td) {
  padding: 0.75rem 1rem;
}
</style>
