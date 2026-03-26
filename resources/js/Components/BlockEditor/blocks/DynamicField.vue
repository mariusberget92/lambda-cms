<!-- resources/js/Components/BlockEditor/blocks/DynamicField.vue -->
<template>
  <div>
    <div class="flex items-center justify-between mb-1">
      <label class="text-xs font-medium text-muted-foreground">{{ label }}</label>
      <button
        v-if="availableFields.length"
        type="button"
        class="text-[10px] px-1.5 py-0.5 rounded border transition-colors"
        :class="isBound
          ? 'border-primary text-primary bg-primary/10'
          : 'border-border text-muted-foreground hover:border-primary'"
        @click="toggleBinding"
      >{{ isBound ? 'Dynamic ✓' : 'Bind' }}</button>
    </div>

    <!-- Bound: grouped native select replaces the static input -->
    <select
      v-if="isBound"
      :value="boundField"
      class="w-full rounded-md border border-border bg-background px-2 py-1.5 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring"
      @change="e => emit('bind', fieldName, e.target.value)"
    >
      <option value="" disabled>Pick a field…</option>
      <template v-for="group in groups" :key="group.label">
        <optgroup v-if="group.label" :label="group.label">
          <option v-for="f in group.items" :key="f.value" :value="f.value">{{ f.label }}</option>
        </optgroup>
      </template>
    </select>

    <!-- Static: whatever the parent renders in the slot -->
    <slot v-else />
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  label:           { type: String, required: true },
  fieldName:       { type: String, required: true },
  block:           { type: Object, required: true },
  availableFields: { type: Array,  default: () => [] },
})

const emit = defineEmits(['bind', 'unbind'])

const isBound    = computed(() => !!props.block.bindings?.[props.fieldName])
const boundField = computed(() => props.block.bindings?.[props.fieldName] ?? '')

// Group availableFields by their .group property.
const groups = computed(() => {
  const map = new Map()
  for (const f of props.availableFields) {
    const key = f.group ?? ''
    if (!map.has(key)) map.set(key, { label: key, items: [] })
    map.get(key).items.push(f)
  }
  return [...map.values()]
})

function toggleBinding() {
  if (isBound.value) {
    emit('unbind', props.fieldName)
  } else {
    emit('bind', props.fieldName, '')
  }
}
</script>
