<!-- resources/js/Components/BlockEditor/BorderControl.vue -->
<template>
  <div class="space-y-3">
    <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wide block">Border</label>

    <!-- Border radius — 4 corners with link toggle -->
    <div v-if="showRadius">
      <label class="text-xs font-medium text-muted-foreground block mb-2">Radius</label>
      <div class="space-y-1.5">
        <!-- Top row: TL / TR -->
        <div class="flex gap-2">
          <div class="flex-1 flex flex-col items-center gap-0.5">
            <span class="text-[9px] font-medium uppercase tracking-widest text-muted-foreground/70">↖ TL</span>
            <DimensionInput
              :model-value="radiusLinked ? (modelValue.radiusTL ?? '') : (modelValue.radiusTL ?? '')"
              placeholder="0"
              @update:model-value="v => setRadius('TL', v)"
            />
          </div>
          <div class="flex-1 flex flex-col items-center gap-0.5">
            <span class="text-[9px] font-medium uppercase tracking-widest text-muted-foreground/70">TR ↗</span>
            <DimensionInput
              :model-value="modelValue.radiusTR ?? ''"
              placeholder="0"
              @update:model-value="v => setRadius('TR', v)"
            />
          </div>
        </div>
        <!-- Link toggle -->
        <div class="flex justify-center">
          <button
            type="button"
            :title="radiusLinked ? 'Unlink corners' : 'Link all corners'"
            class="flex items-center gap-1 px-2 py-0.5 rounded-md border text-[10px] transition-colors"
            :class="radiusLinked
              ? 'border-primary bg-primary/10 text-primary'
              : 'border-border bg-background text-muted-foreground hover:border-primary hover:text-primary'"
            @click="radiusLinked = !radiusLinked"
          >
            <Link2    v-if="radiusLinked"  class="w-3 h-3" />
            <Link2Off v-else               class="w-3 h-3" />
            {{ radiusLinked ? 'Linked' : 'Link' }}
          </button>
        </div>
        <!-- Bottom row: BL / BR -->
        <div class="flex gap-2">
          <div class="flex-1 flex flex-col items-center gap-0.5">
            <span class="text-[9px] font-medium uppercase tracking-widest text-muted-foreground/70">↙ BL</span>
            <DimensionInput
              :model-value="modelValue.radiusBL ?? ''"
              placeholder="0"
              @update:model-value="v => setRadius('BL', v)"
            />
          </div>
          <div class="flex-1 flex flex-col items-center gap-0.5">
            <span class="text-[9px] font-medium uppercase tracking-widest text-muted-foreground/70">BR ↘</span>
            <DimensionInput
              :model-value="modelValue.radiusBR ?? ''"
              placeholder="0"
              @update:model-value="v => setRadius('BR', v)"
            />
          </div>
        </div>
      </div>
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
import { ref } from 'vue'
import { Link2, Link2Off } from 'lucide-vue-next'
import SelectBox    from '@/Components/SelectBox.vue'
import DimensionInput from './DimensionInput.vue'
import ColorPicker  from './ColorPicker.vue'

const props = defineProps({
  modelValue:  { type: Object,  default: () => ({}) },
  showRadius:  { type: Boolean, default: true },
  showBorder:  { type: Boolean, default: true },
})
const emit = defineEmits(['update:modelValue'])

const radiusLinked = ref(false)

function update(key, value) {
  emit('update:modelValue', { ...props.modelValue, [key]: value })
}

function setRadius(corner, value) {
  if (radiusLinked.value) {
    emit('update:modelValue', {
      ...props.modelValue,
      radiusTL: value,
      radiusTR: value,
      radiusBL: value,
      radiusBR: value,
    })
  } else {
    emit('update:modelValue', { ...props.modelValue, [`radius${corner}`]: value || null })
  }
}
</script>
