<!-- resources/js/components/BlockEditor/blocks/ContainerSettings.vue -->
<template>
  <div class="space-y-3">

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Direction</label>
      <SelectBox
        :model-value="block.data.direction"
        :data="[
          { value: 'row',    label: 'Row (horizontal)' },
          { value: 'column', label: 'Column (vertical)' },
        ]"
        @update:model-value="v => update('direction', v)"
      />
    </div>

    <div class="flex items-center gap-2">
      <input type="checkbox" :checked="block.data.wrap" @change="update('wrap', $event.target.checked)"
        id="container-wrap" class="rounded border-border accent-nord-green" />
      <label for="container-wrap" class="text-xs font-medium text-muted-foreground">Wrap items</label>
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Gap: {{ block.data.gap }}</label>
      <input type="range" min="0" max="16" :value="block.data.gap"
        @input="update('gap', parseInt($event.target.value))"
        class="w-full" />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Justify content</label>
      <SelectBox
        :model-value="block.data.justify"
        :data="[
          { value: 'start',   label: 'Start' },
          { value: 'center',  label: 'Center' },
          { value: 'end',     label: 'End' },
          { value: 'between', label: 'Space between' },
          { value: 'around',  label: 'Space around' },
        ]"
        @update:model-value="v => update('justify', v)"
      />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Align items</label>
      <SelectBox
        :model-value="block.data.align"
        :data="[
          { value: 'start',   label: 'Start' },
          { value: 'center',  label: 'Center' },
          { value: 'end',     label: 'End' },
          { value: 'stretch', label: 'Stretch' },
        ]"
        @update:model-value="v => update('align', v)"
      />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Max width</label>
      <SelectBox
        :model-value="block.data.maxWidth"
        :data="[
          { value: 'full',  label: 'Full' },
          { value: 'prose', label: 'Prose (65ch)' },
          { value: 'sm',    label: 'SM (24rem)' },
          { value: 'md',    label: 'MD (28rem)' },
          { value: 'lg',    label: 'LG (32rem)' },
          { value: 'xl',    label: 'XL (36rem)' },
          { value: '2xl',   label: '2XL (42rem)' },
        ]"
        @update:model-value="v => update('maxWidth', v)"
      />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Padding: {{ block.data.padding }}</label>
      <input type="range" min="0" max="16" :value="block.data.padding"
        @input="update('padding', parseInt($event.target.value))"
        class="w-full" />
    </div>

  </div>
</template>

<script setup>
import SelectBox from '@/Components/SelectBox.vue'

const props = defineProps({ block: { type: Object, required: true } })
const emit = defineEmits(['update'])

function update(key, value) {
  emit('update', { id: props.block.id, data: { [key]: value } })
}
</script>
