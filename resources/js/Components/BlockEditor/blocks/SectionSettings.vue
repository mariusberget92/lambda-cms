<script setup>
import { computed } from 'vue'
import SelectBox from '@/Components/SelectBox.vue'
import SpacingControl from '../SpacingControl.vue'

const props = defineProps({ block: { type: Object, required: true } })
const emit   = defineEmits(['update'])

const d = computed(() => props.block.data ?? {})

function update(key, value) {
  emit('update', { id: props.block.id, data: { [key]: value } })
}

function updateNested(key, subKey, value) {
  emit('update', { id: props.block.id, data: { [key]: { ...(d.value[key] ?? {}), [subKey]: value } } })
}
</script>

<template>
  <div class="space-y-4">

    <!-- Background -->
    <div class="space-y-2">
      <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wide block">Background</label>

      <div class="flex gap-1 flex-wrap">
        <button type="button" v-for="opt in ['none','color','image','gradient']" :key="opt"
          class="px-2 py-1 text-xs rounded border transition-colors"
          :class="d.bgType === opt ? 'bg-primary text-primary-foreground border-primary' : 'bg-background border-border'"
          @click="update('bgType', opt)">
          {{ opt.charAt(0).toUpperCase() + opt.slice(1) }}
        </button>
      </div>

      <!-- Color picker -->
      <div v-if="d.bgType === 'color'" class="flex items-center gap-2">
        <input type="color" :value="d.bgColor ?? '#ffffff'"
          @input="update('bgColor', $event.target.value)"
          class="h-8 w-16 cursor-pointer rounded border border-border" />
        <span class="text-xs text-muted-foreground">{{ d.bgColor ?? '#ffffff' }}</span>
      </div>

      <!-- Image picker -->
      <div v-if="d.bgType === 'image'" class="space-y-2">
        <input type="url" :value="d.bgImage?.url ?? ''"
          @input="updateNested('bgImage', 'url', $event.target.value)"
          placeholder="https://..."
          class="w-full rounded border border-border bg-background px-2 py-1 text-xs" />
        <SelectBox
          :model-value="d.bgImage?.position ?? 'center'"
          :data="[
            { value: 'center',       label: 'Center' },
            { value: 'top',          label: 'Top' },
            { value: 'bottom',       label: 'Bottom' },
            { value: 'left center',  label: 'Left' },
            { value: 'right center', label: 'Right' },
          ]"
          @update:model-value="v => updateNested('bgImage', 'position', v)"
        />
        <SelectBox
          :model-value="d.bgImage?.size ?? 'cover'"
          :data="[
            { value: 'cover',   label: 'Cover' },
            { value: 'contain', label: 'Contain' },
            { value: 'auto',    label: 'Auto' },
          ]"
          @update:model-value="v => updateNested('bgImage', 'size', v)"
        />
      </div>

      <!-- Gradient picker -->
      <div v-if="d.bgType === 'gradient'" class="space-y-2">
        <div class="flex gap-2 items-center">
          <div>
            <label class="text-[10px] text-muted-foreground">From</label>
            <input type="color" :value="d.bgGradient?.from ?? '#3b4252'"
              @input="updateNested('bgGradient', 'from', $event.target.value)"
              class="block h-8 w-12 cursor-pointer rounded border border-border" />
          </div>
          <div>
            <label class="text-[10px] text-muted-foreground">To</label>
            <input type="color" :value="d.bgGradient?.to ?? '#4c566a'"
              @input="updateNested('bgGradient', 'to', $event.target.value)"
              class="block h-8 w-12 cursor-pointer rounded border border-border" />
          </div>
        </div>
        <SelectBox
          :model-value="d.bgGradient?.direction ?? 'to-r'"
          :data="[
            { value: 'to-r',  label: 'Left to right' },
            { value: 'to-l',  label: 'Right to left' },
            { value: 'to-b',  label: 'Top to bottom' },
            { value: 'to-t',  label: 'Bottom to top' },
            { value: 'to-br', label: 'Top-left to bottom-right' },
            { value: 'to-bl', label: 'Top-right to bottom-left' },
          ]"
          @update:model-value="v => updateNested('bgGradient', 'direction', v)"
        />
      </div>
    </div>

    <!-- Width -->
    <div class="space-y-2">
      <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wide block">Width</label>
      <div class="flex items-center gap-2">
        <input type="checkbox" id="section-fullwidth"
          :checked="d.fullWidth"
          @change="update('fullWidth', $event.target.checked)"
          class="rounded border-border accent-nord-green" />
        <label for="section-fullwidth" class="text-xs text-muted-foreground">Full width (no inner max-width)</label>
      </div>
      <div v-if="!d.fullWidth">
        <label class="text-xs text-muted-foreground block mb-1">Inner max width</label>
        <SelectBox
          :model-value="d.innerMaxWidth ?? 'xl'"
          :data="[
            { value: 'sm',   label: 'SM (24rem)' },
            { value: 'md',   label: 'MD (28rem)' },
            { value: 'lg',   label: 'LG (32rem)' },
            { value: 'xl',   label: 'XL (36rem)' },
            { value: '2xl',  label: '2XL (42rem)' },
            { value: 'full', label: 'Full' },
          ]"
          @update:model-value="v => update('innerMaxWidth', v)"
        />
      </div>
    </div>

    <!-- Spacing -->
    <div class="space-y-2">
      <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wide block">Padding</label>
      <SpacingControl
        :model-value="typeof d.padding === 'object' ? d.padding : {}"
        @update:model-value="v => update('padding', v)"
      />
    </div>

    <!-- Min height -->
    <div class="space-y-1">
      <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wide block">Min Height</label>
      <SelectBox
        :model-value="d.minHeight ?? 'auto'"
        :data="[
          { value: 'auto',   label: 'Auto' },
          { value: 'screen', label: 'Full screen (100vh)' },
          { value: '1/2',    label: 'Half screen (50vh)' },
        ]"
        @update:model-value="v => update('minHeight', v)"
      />
    </div>

  </div>
</template>
