<!-- resources/js/Components/BlockEditor/blocks/DynamicField.vue -->
<template>
  <div>
    <div class="flex items-center justify-between mb-1">
      <label class="text-xs font-medium text-muted-foreground">{{ label }}</label>
      <button
        v-if="loopFields.length"
        type="button"
        class="text-[10px] px-1.5 py-0.5 rounded border transition-colors"
        :class="isBound
          ? 'border-primary text-primary bg-primary/10'
          : 'border-border text-muted-foreground hover:border-primary'"
        @click="toggleBinding"
      >{{ isBound ? 'Dynamic ✓' : 'Bind' }}</button>
    </div>

    <!-- Bound: field picker replaces the static input -->
    <SelectBox
      v-if="isBound"
      :model-value="boundField"
      :data="fieldOptions"
      placeholder="Pick a field..."
      @update:model-value="v => emit('bind', fieldName, v)"
    />

    <!-- Static: whatever the parent renders in the slot -->
    <slot v-else />
  </div>
</template>

<script setup>
import { computed } from 'vue'
import SelectBox from '@/Components/SelectBox.vue'

const props = defineProps({
  label:      { type: String, required: true },
  fieldName:  { type: String, required: true },
  block:      { type: Object, required: true },
  loopFields: { type: Array,  default: () => [] },
})

const emit = defineEmits(['bind', 'unbind'])

const isBound     = computed(() => !!props.block.bindings?.[props.fieldName])
const boundField  = computed(() => props.block.bindings?.[props.fieldName] ?? null)
const fieldOptions = computed(() => props.loopFields.map(f => ({ value: f, label: f })))

function toggleBinding() {
  if (isBound.value) {
    emit('unbind', props.fieldName)
  } else {
    // Start with empty string — user picks from dropdown
    emit('bind', props.fieldName, '')
  }
}
</script>
