<script setup>
import DimensionInput from '../DimensionInput.vue'

const props = defineProps({ block: { type: Object, required: true } })
const emit   = defineEmits(['update'])

function getBreakpoint(bp) {
  const val = props.block.data?.height
  if (typeof val === 'object' && val !== null) return String(val[bp] ?? '')
  if (bp === 'default') return String(val ?? '')
  return ''
}

function setBreakpoint(bp, value) {
  const current = props.block.data?.height
  const base = (typeof current === 'object' && current !== null)
    ? { ...current }
    : { default: typeof current === 'string' ? current : '2rem' }
  emit('update', {
    id: props.block.id,
    data: { height: { ...base, [bp]: value } },
  })
}
</script>

<template>
  <div class="space-y-2">
    <label class="text-xs font-medium text-muted-foreground block">Height</label>
    <div class="grid grid-cols-3 gap-1.5">
      <div v-for="bp in ['default', 'sm', 'lg']" :key="bp">
        <span class="text-[10px] text-muted-foreground block mb-1 text-center">
          {{ bp === 'default' ? 'Mobile' : bp === 'sm' ? 'SM' : 'LG' }}
        </span>
        <DimensionInput
          :model-value="getBreakpoint(bp)"
          placeholder="–"
          @update:model-value="v => setBreakpoint(bp, v)"
        />
      </div>
    </div>
  </div>
</template>
