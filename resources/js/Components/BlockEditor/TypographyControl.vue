<!-- resources/js/Components/BlockEditor/TypographyControl.vue -->
<template>
  <div class="space-y-3">
    <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wide block">Typography</label>

    <!-- Text alignment -->
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Alignment</label>
      <div class="flex rounded-md border overflow-hidden text-xs">
        <button
          v-for="align in ['left', 'center', 'right', 'justify']"
          :key="align"
          type="button"
          class="flex-1 py-1.5 transition-colors capitalize"
          :class="modelValue.textAlign === align
            ? 'bg-primary text-primary-foreground'
            : 'bg-background text-foreground'"
          @click="update('textAlign', modelValue.textAlign === align ? null : align)"
        >{{ align.slice(0, 1).toUpperCase() + align.slice(1, 4) }}</button>
      </div>
    </div>

    <!-- Color -->
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Color</label>
      <ColorPicker
        :model-value="modelValue.color"
        default="#ffffff"
        :show-reset="true"
        @update:model-value="v => update('color', v)"
      />
    </div>

    <!-- Font size -->
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Font size</label>
      <DimensionInput
        :model-value="modelValue.fontSize ?? ''"
        placeholder="Inherit"
        @update:model-value="v => update('fontSize', v || null)"
      />
    </div>

    <!-- Font weight -->
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Font weight</label>
      <SelectBox size="sm"
        :model-value="modelValue.fontWeight ?? ''"
        :data="[
          { value: '',    label: 'Inherit' },
          { value: '300', label: 'Light (300)' },
          { value: '400', label: 'Regular (400)' },
          { value: '500', label: 'Medium (500)' },
          { value: '600', label: 'Semibold (600)' },
          { value: '700', label: 'Bold (700)' },
          { value: '800', label: 'Extrabold (800)' },
        ]"
        @update:model-value="v => update('fontWeight', v || null)"
      />
    </div>

    <!-- Line height -->
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Line height</label>
      <DimensionInput
        :model-value="modelValue.lineHeight ?? ''"
        placeholder="Inherit"
        :units="['', 'px', 'rem', 'em']"
        @update:model-value="v => update('lineHeight', v || null)"
      />
    </div>
  </div>
</template>

<script setup>
import SelectBox    from '@/Components/SelectBox.vue'
import DimensionInput from './DimensionInput.vue'
import ColorPicker  from './ColorPicker.vue'

const props = defineProps({
  modelValue: { type: Object, default: () => ({}) },
})
const emit = defineEmits(['update:modelValue'])

function update(key, value) {
  emit('update:modelValue', { ...props.modelValue, [key]: value })
}
</script>
