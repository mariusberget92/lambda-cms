<!-- resources/js/Components/BlockEditor/SpacingControl.vue -->
<!-- Box-model 4-side spacing control with link/unlink toggle -->
<script setup>
import { ref, computed } from 'vue'
import { Link2, Link2Off } from 'lucide-vue-next'
import DimensionInput from './DimensionInput.vue'

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({ top: '', right: '', bottom: '', left: '' }),
  },
  units: {
    type: Array,
    default: () => ['px', 'rem', 'em', '%', 'vh', 'vw'],
  },
  allowAuto: { type: Boolean, default: false },
})
const emit = defineEmits(['update:modelValue'])

const linked = ref(false)

const val = computed(() => ({
  top:    props.modelValue?.top    ?? '',
  right:  props.modelValue?.right  ?? '',
  bottom: props.modelValue?.bottom ?? '',
  left:   props.modelValue?.left   ?? '',
}))

function setSide(side, v) {
  if (linked.value) {
    emit('update:modelValue', { top: v, right: v, bottom: v, left: v })
  } else {
    emit('update:modelValue', { ...val.value, [side]: v })
  }
}
</script>

<template>
  <div class="space-y-1.5">
    <!-- Top -->
    <div class="flex justify-center">
      <div class="flex flex-col items-center gap-0.5 w-28">
        <span class="text-[9px] font-medium uppercase tracking-widest text-muted-foreground/70">Top</span>
        <DimensionInput
          :model-value="val.top"
          :units="units"
          :allow-auto="allowAuto"
          placeholder="0"
          @update:model-value="v => setSide('top', v)"
        />
      </div>
    </div>

    <!-- Middle row: Left | link | Right -->
    <div class="flex items-center gap-2">
      <div class="flex flex-col items-center gap-0.5 flex-1">
        <span class="text-[9px] font-medium uppercase tracking-widest text-muted-foreground/70">Left</span>
        <DimensionInput
          :model-value="val.left"
          :units="units"
          :allow-auto="allowAuto"
          placeholder="0"
          @update:model-value="v => setSide('left', v)"
        />
      </div>

      <!-- Link / Unlink toggle -->
      <button
        type="button"
        :title="linked ? 'Unlink sides' : 'Link all sides'"
        class="shrink-0 mt-3 w-7 h-7 flex items-center justify-center rounded-md border transition-colors"
        :class="linked
          ? 'border-primary bg-primary/10 text-primary'
          : 'border-border bg-background text-muted-foreground hover:border-primary hover:text-primary'"
        @click="linked = !linked"
      >
        <Link2    v-if="linked"  class="w-3.5 h-3.5" />
        <Link2Off v-else         class="w-3.5 h-3.5" />
      </button>

      <div class="flex flex-col items-center gap-0.5 flex-1">
        <span class="text-[9px] font-medium uppercase tracking-widest text-muted-foreground/70">Right</span>
        <DimensionInput
          :model-value="val.right"
          :units="units"
          :allow-auto="allowAuto"
          placeholder="0"
          @update:model-value="v => setSide('right', v)"
        />
      </div>
    </div>

    <!-- Bottom -->
    <div class="flex justify-center">
      <div class="flex flex-col items-center gap-0.5 w-28">
        <span class="text-[9px] font-medium uppercase tracking-widest text-muted-foreground/70">Bottom</span>
        <DimensionInput
          :model-value="val.bottom"
          :units="units"
          :allow-auto="allowAuto"
          placeholder="0"
          @update:model-value="v => setSide('bottom', v)"
        />
      </div>
    </div>
  </div>
</template>
