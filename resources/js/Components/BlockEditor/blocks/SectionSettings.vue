<script setup>
import { computed } from 'vue'
import SelectBox   from '@/Components/SelectBox.vue'
import SpacingControl from '../SpacingControl.vue'

const props = defineProps({
  block: { type: Object, required: true },
  tab: { type: String, default: null },  // 'content' | 'style' | null (show all)
})
const emit   = defineEmits(['update'])

const d = computed(() => props.block.data ?? {})

function update(key, value) {
  emit('update', { id: props.block.id, data: { [key]: value } })
}


</script>

<template>
  <div>

    <!-- Style tab fields -->
    <div v-show="!tab || tab === 'style'" class="space-y-4">

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
          <SelectBox size="sm"
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
        <SelectBox size="sm"
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

  </div>
</template>
