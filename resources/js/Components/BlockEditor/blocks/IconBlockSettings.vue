<!-- resources/js/Components/BlockEditor/blocks/IconBlockSettings.vue -->
<template>
  <!-- Content tab -->
  <div v-show="!tab || tab === 'content'" class="space-y-3">
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Icon</label>
      <IconPickerInput
        :model-value="block.data.name ?? null"
        @update:model-value="v => emit('update', { id: block.id, data: { name: v || null } })"
      />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Alignment</label>
      <div class="flex rounded-md border overflow-hidden text-xs">
        <button v-for="al in ['left', 'center', 'right']" :key="al"
          type="button"
          class="flex-1 py-1.5 transition-colors capitalize"
          :class="(block.data.align ?? 'center') === al ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground'"
          @click="emit('update', { id: block.id, data: { align: al } })">
          {{ al }}
        </button>
      </div>
    </div>
  </div>

  <!-- Style tab -->
  <div v-show="!tab || tab === 'style'" class="space-y-3">
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Size</label>
      <input :value="block.data.size ?? '2.5rem'" type="text" placeholder="2.5rem"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @change="emit('update', { id: block.id, data: { size: $event.target.value || '2.5rem' } })" />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Color</label>
      <ColorPicker :model-value="block.data.color" :show-reset="true"
        @update:model-value="v => emit('update', { id: block.id, data: { color: v || null } })" />
    </div>
  </div>
</template>

<script setup>
import IconPickerInput from '../IconPickerInput.vue'
import ColorPicker     from '../ColorPicker.vue'

const props = defineProps({
  block: { type: Object, required: true },
  tab:   { type: String, default: null },
})
const emit = defineEmits(['update'])
</script>
