<!-- resources/js/Components/BlockEditor/BorderControl.vue -->
<template>
  <div class="space-y-3">
    <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wide block">Border</label>

    <!-- Border radius -->
    <div v-if="showRadius">
      <label class="text-xs font-medium text-muted-foreground block mb-1">Radius</label>
      <DimensionInput
        :model-value="modelValue.radius ?? ''"
        placeholder="0"
        @update:model-value="v => update('radius', v || null)"
      />
    </div>

    <!-- Border style -->
    <div v-if="showBorder">
      <label class="text-xs font-medium text-muted-foreground block mb-1">Border style</label>
      <SelectBox size="sm"
        :model-value="modelValue.style ?? 'none'"
        :data="[
          { value: 'none',   label: 'None' },
          { value: 'solid',  label: 'Solid' },
          { value: 'dashed', label: 'Dashed' },
          { value: 'dotted', label: 'Dotted' },
        ]"
        @update:model-value="v => update('style', v)"
      />
    </div>

    <template v-if="showBorder && modelValue.style && modelValue.style !== 'none'">
      <!-- Border width -->
      <div>
        <label class="text-xs font-medium text-muted-foreground block mb-1">Border width</label>
        <DimensionInput
          :model-value="modelValue.width ?? '1px'"
          placeholder="1px"
          @update:model-value="v => update('width', v || null)"
        />
      </div>

      <!-- Border color -->
      <div>
        <label class="text-xs font-medium text-muted-foreground block mb-1">Border color</label>
        <ColorPicker
          :model-value="modelValue.color"
          default="#000000"
          :show-reset="true"
          @update:model-value="v => update('color', v)"
        />
      </div>
    </template>
  </div>
</template>

<script setup>
import SelectBox    from '@/Components/SelectBox.vue'
import DimensionInput from './DimensionInput.vue'
import ColorPicker  from './ColorPicker.vue'

const props = defineProps({
  modelValue:  { type: Object,  default: () => ({}) },
  showRadius:  { type: Boolean, default: true },
  showBorder:  { type: Boolean, default: true },
})
const emit = defineEmits(['update:modelValue'])

function update(key, value) {
  emit('update:modelValue', { ...props.modelValue, [key]: value })
}
</script>
