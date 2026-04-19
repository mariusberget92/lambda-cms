<!-- resources/js/Components/BlockEditor/blocks/IconSettings.vue -->
<template>
  <div class="space-y-3">
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Icon</label>
      <IconPickerInput
        :model-value="icon.name || null"
        @update:model-value="v => update('name', v || null)"
      />
    </div>

    <template v-if="icon.name">
      <div>
        <label class="text-xs font-medium text-muted-foreground block mb-1">Position</label>
        <div class="flex gap-1">
          <button
            v-for="[val, label] in [['prefix', 'Prefix'], ['suffix', 'Suffix']]"
            :key="val"
            type="button"
            class="flex-1 px-2 py-1 text-xs rounded border transition-colors"
            :class="(icon.position ?? 'prefix') === val
              ? 'bg-primary text-primary-foreground border-primary'
              : 'bg-background border-border hover:border-muted-foreground'"
            @click="update('position', val)"
          >{{ label }}</button>
        </div>
      </div>

      <div>
        <label class="text-xs font-medium text-muted-foreground block mb-1">Size</label>
        <input
          :value="icon.size ?? '1.25em'"
          type="text"
          placeholder="1.25em"
          class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
          @change="update('size', $event.target.value)"
        />
      </div>

      <div>
        <label class="text-xs font-medium text-muted-foreground block mb-1">Gap</label>
        <input
          :value="icon.gap ?? '0.5em'"
          type="text"
          placeholder="0.5em"
          class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
          @change="update('gap', $event.target.value)"
        />
      </div>

      <div>
        <label class="text-xs font-medium text-muted-foreground block mb-1">Color</label>
        <div class="flex items-center gap-2">
          <button
            type="button"
            class="px-2 py-1 text-xs rounded border transition-colors"
            :class="!icon.color
              ? 'bg-primary text-primary-foreground border-primary'
              : 'bg-background border-border hover:border-muted-foreground'"
            @click="update('color', null)"
          >Inherit</button>
          <ColorPicker
            :model-value="icon.color || '#000000'"
            :show-value="false"
            @update:model-value="v => update('color', v)"
          />
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import IconPickerInput from '../IconPickerInput.vue'
import ColorPicker     from '../ColorPicker.vue'

const props = defineProps({ block: { type: Object, required: true } })
const emit  = defineEmits(['update'])

const icon = computed(() => props.block.data?.icon ?? {
  name: null, position: 'prefix', size: '1.25em', color: null, gap: '0.5em',
})

function update(key, value) {
  emit('update', {
    id:   props.block.id,
    data: { icon: { ...icon.value, [key]: value } },
  })
}
</script>
