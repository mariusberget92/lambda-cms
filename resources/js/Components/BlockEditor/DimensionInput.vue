<!-- resources/js/Components/BlockEditor/DimensionInput.vue -->
<!-- Parses / emits a CSS dimension string like "16px", "1.5rem", "50%", "auto" -->
<script setup>
import { computed } from 'vue'

const props = defineProps({
  modelValue:  { type: String, default: '' },
  placeholder: { type: String, default: '0' },
  units:       { type: Array,  default: () => ['px', 'rem', 'em', '%', 'vh', 'vw'] },
  allowAuto:   { type: Boolean, default: false },
})
const emit = defineEmits(['update:modelValue'])

const parsed = computed(() => {
  const val = String(props.modelValue ?? '')
  if (val === 'auto') return { num: '', unit: 'auto' }
  const m = val.match(/^(-?[\d.]+)\s*([a-z%]*)$/i)
  if (m) {
    const unit = m[2] || 'px'
    return { num: m[1], unit: props.units.includes(unit) ? unit : (props.allowAuto && unit === 'auto' ? 'auto' : props.units[0]) }
  }
  return { num: '', unit: props.units[0] ?? 'px' }
})

function emitVal(num, unit) {
  if (unit === 'auto') { emit('update:modelValue', 'auto'); return }
  emit('update:modelValue', num === '' ? '' : `${num}${unit}`)
}

function onNum(e)  { emitVal(e.target.value, parsed.value.unit) }
function onUnit(e) { emitVal(parsed.value.num, e.target.value)  }
</script>

<template>
  <div class="flex rounded-md border border-border overflow-hidden bg-background text-xs h-7">
    <input
      type="text"
      :value="parsed.num"
      :placeholder="placeholder"
      :disabled="parsed.unit === 'auto'"
      class="min-w-0 w-full bg-transparent px-2 text-xs disabled:text-muted-foreground/50 focus:outline-none"
      @input="onNum"
    />
    <select
      :value="parsed.unit"
      class="shrink-0 border-l border-border bg-muted/50 px-1 text-[10px] text-muted-foreground focus:outline-none cursor-pointer"
      @change="onUnit"
    >
      <option v-if="allowAuto" value="auto">auto</option>
      <option v-for="u in units" :key="u" :value="u">{{ u }}</option>
    </select>
  </div>
</template>
