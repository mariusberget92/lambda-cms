<script setup>
const props = defineProps({ block: { type: Object, required: true } })
const emit   = defineEmits(['update'])

function getBreakpoint(bp) {
  const val = props.block.data?.height
  if (typeof val === 'object' && val !== null) return val[bp] ?? ''
  if (bp === 'default') return val ?? ''
  return ''
}

function setBreakpoint(bp, value) {
  const current = props.block.data?.height
  const base = (typeof current === 'object' && current !== null)
    ? { ...current }
    : { default: current ?? 8 }
  const parsed = parseInt(value)
  emit('update', {
    id: props.block.id,
    data: { height: { ...base, [bp]: isNaN(parsed) ? null : parsed } },
  })
}
</script>

<template>
  <div class="space-y-2">
    <label class="text-xs font-medium text-muted-foreground block">Height (Tailwind spacing)</label>
    <div class="grid grid-cols-3 gap-1">
      <div v-for="bp in ['default', 'sm', 'lg']" :key="bp">
        <span class="text-[10px] text-muted-foreground block mb-0.5 text-center">
          {{ bp === 'default' ? 'Mobile' : bp === 'sm' ? 'SM' : 'LG' }}
        </span>
        <input
          type="number" min="1" max="64"
          :value="getBreakpoint(bp)"
          placeholder="–"
          class="w-full rounded border border-border bg-background px-1.5 py-1 text-xs text-center"
          @change="e => setBreakpoint(bp, e.target.value)"
        />
      </div>
    </div>
    <p class="text-[10px] text-muted-foreground">1 unit = 0.25rem (4px). Range: 1–64.</p>
  </div>
</template>
