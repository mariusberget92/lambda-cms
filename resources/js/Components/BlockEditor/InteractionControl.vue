<!-- resources/js/Components/BlockEditor/InteractionControl.vue -->
<template>
  <div class="space-y-3">

    <!-- Hover animation -->
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Hover animation</label>
      <SelectBox size="sm"
        :model-value="modelValue.hoverAnimation ?? ''"
        :data="ANIMATIONS"
        @update:model-value="v => update('hoverAnimation', v || null)"
      />
    </div>

    <!-- Speed modifier (only when an animation is chosen) -->
    <div v-if="modelValue.hoverAnimation">
      <label class="text-xs font-medium text-muted-foreground block mb-1">Speed</label>
      <SelectBox size="sm"
        :model-value="modelValue.hoverAnimationSpeed ?? ''"
        :data="[
          { value: '',        label: 'Normal (~1s)' },
          { value: 'faster',  label: 'Faster (500ms)' },
          { value: 'fast',    label: 'Fast (800ms)' },
          { value: 'slow',    label: 'Slow (2s)' },
          { value: 'slower',  label: 'Slower (3s)' },
        ]"
        @update:model-value="v => update('hoverAnimationSpeed', v || null)"
      />
    </div>

    <!-- Hover background -->
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Hover background</label>
      <ColorPicker
        :model-value="modelValue.hoverBgColor ?? ''"
        default="#f0f0f0"
        :show-reset="true"
        @update:model-value="v => update('hoverBgColor', v || null)"
      />
    </div>

    <!-- Hover text color -->
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Hover text color</label>
      <ColorPicker
        :model-value="modelValue.hoverTextColor ?? ''"
        default="#000000"
        :show-reset="true"
        @update:model-value="v => update('hoverTextColor', v || null)"
      />
    </div>

    <!-- Hover scale -->
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Hover scale</label>
      <SelectBox size="sm"
        :model-value="modelValue.hoverScale ?? ''"
        :data="[
          { value: '',     label: 'None' },
          { value: '1.05', label: 'Scale up 5%' },
          { value: '1.1',  label: 'Scale up 10%' },
          { value: '1.15', label: 'Scale up 15%' },
          { value: '0.95', label: 'Scale down 5%' },
        ]"
        @update:model-value="v => update('hoverScale', v || null)"
      />
    </div>

  </div>
</template>

<script setup>
import SelectBox  from '@/Components/SelectBox.vue'
import ColorPicker from './ColorPicker.vue'

const props = defineProps({
  modelValue: { type: Object, default: () => ({}) },
})
const emit = defineEmits(['update:modelValue'])

function update(key, value) {
  emit('update:modelValue', { ...props.modelValue, [key]: value })
}

const ANIMATIONS = [
  { value: '',           label: 'None' },
  { value: 'bounce',     label: 'Bounce' },
  { value: 'flash',      label: 'Flash' },
  { value: 'pulse',      label: 'Pulse' },
  { value: 'rubberBand', label: 'Rubber Band' },
  { value: 'shakeX',     label: 'Shake X' },
  { value: 'shakeY',     label: 'Shake Y' },
  { value: 'headShake',  label: 'Head Shake' },
  { value: 'swing',      label: 'Swing' },
  { value: 'tada',       label: 'Tada' },
  { value: 'wobble',     label: 'Wobble' },
  { value: 'jello',      label: 'Jello' },
  { value: 'heartBeat',  label: 'Heart Beat' },
  { value: 'zoomIn',     label: 'Zoom In' },
  { value: 'fadeIn',     label: 'Fade In' },
  { value: 'fadeInUp',   label: 'Fade In Up' },
  { value: 'flip',       label: 'Flip' },
]
</script>
