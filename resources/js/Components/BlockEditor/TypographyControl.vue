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

    <!-- Letter spacing -->
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Letter spacing</label>
      <SelectBox size="sm"
        :model-value="modelValue.letterSpacing ?? ''"
        :data="[
          { value: '',        label: 'Inherit' },
          { value: '-0.05em', label: 'Tighter' },
          { value: '0em',     label: 'Normal' },
          { value: '0.025em', label: 'Wide' },
          { value: '0.05em',  label: 'Wider' },
          { value: '0.1em',   label: 'Widest' },
        ]"
        @update:model-value="v => update('letterSpacing', v || null)"
      />
    </div>

    <!-- Text decoration -->
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Text decoration</label>
      <div class="flex rounded-md border overflow-hidden text-xs">
        <button
          v-for="opt in [{ value: 'none', label: 'None' }, { value: 'underline', label: 'Underline' }, { value: 'line-through', label: 'Strike' }]"
          :key="opt.value"
          type="button"
          class="flex-1 py-1.5 transition-colors"
          :class="(modelValue.textDecoration ?? 'none') === opt.value
            ? 'bg-primary text-primary-foreground'
            : 'bg-background text-foreground'"
          @click="update('textDecoration', opt.value === 'none' ? null : opt.value)"
        >{{ opt.label }}</button>
      </div>
    </div>

    <!-- Text transform -->
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Transform</label>
      <div class="flex rounded-md border overflow-hidden text-xs">
        <button
          v-for="opt in [{ value: 'none', label: 'None' }, { value: 'uppercase', label: 'AA' }, { value: 'lowercase', label: 'aa' }, { value: 'capitalize', label: 'Aa' }]"
          :key="opt.value"
          type="button"
          class="flex-1 py-1.5 transition-colors"
          :class="(modelValue.textTransform ?? 'none') === opt.value
            ? 'bg-primary text-primary-foreground'
            : 'bg-background text-foreground'"
          @click="update('textTransform', opt.value === 'none' ? null : opt.value)"
        >{{ opt.label }}</button>
      </div>
    </div>

    <!-- Text shadow -->
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Text shadow</label>
      <div class="space-y-2">
        <ColorPicker
          :model-value="modelValue.textShadow?.color ?? ''"
          default="#000000"
          :show-reset="true"
          @update:model-value="v => updateShadow('color', v)"
        />
        <div class="grid grid-cols-3 gap-1">
          <div>
            <label class="text-[10px] text-muted-foreground block mb-0.5">X (px)</label>
            <input type="number" :value="modelValue.textShadow?.x ?? 0"
              class="w-full rounded border bg-background px-2 py-1 text-xs"
              @input="updateShadow('x', Number($event.target.value))" />
          </div>
          <div>
            <label class="text-[10px] text-muted-foreground block mb-0.5">Y (px)</label>
            <input type="number" :value="modelValue.textShadow?.y ?? 0"
              class="w-full rounded border bg-background px-2 py-1 text-xs"
              @input="updateShadow('y', Number($event.target.value))" />
          </div>
          <div>
            <label class="text-[10px] text-muted-foreground block mb-0.5">Blur</label>
            <input type="number" :value="modelValue.textShadow?.blur ?? 0" min="0"
              class="w-full rounded border bg-background px-2 py-1 text-xs"
              @input="updateShadow('blur', Number($event.target.value))" />
          </div>
        </div>
      </div>
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

function updateShadow(key, value) {
  emit('update:modelValue', {
    ...props.modelValue,
    textShadow: { ...(props.modelValue.textShadow ?? {}), [key]: value },
  })
}
</script>
