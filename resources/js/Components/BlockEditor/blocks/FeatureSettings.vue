<!-- resources/js/Components/BlockEditor/blocks/FeatureSettings.vue -->
<template>
  <!-- Content tab -->
  <div v-show="!tab || tab === 'content'" class="space-y-3">
    <DynamicField label="Title" field-name="title" :block="block" :available-fields="availableFields" @bind="onBind" @unbind="onUnbind">
      <input :value="block.data.title" type="text" placeholder="Feature title..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { title: $event.target.value } })" />
    </DynamicField>

    <DynamicField label="Description" field-name="text" :block="block" :available-fields="availableFields" @bind="onBind" @unbind="onUnbind">
      <textarea :value="block.data.text" rows="3" placeholder="Feature description..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring resize-none"
        @input="emit('update', { id: block.id, data: { text: $event.target.value } })" />
    </DynamicField>
  </div>

  <!-- Style tab -->
  <div v-show="!tab || tab === 'style'" class="space-y-4">
    <div>
      <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wide block mb-2">Layout</label>
      <div class="flex rounded-md border overflow-hidden text-xs">
        <button v-for="[val, lbl] in [['vertical', 'Vertical'], ['horizontal', 'Horizontal']]" :key="val"
          type="button"
          class="flex-1 py-1.5 transition-colors"
          :class="(block.data.layout ?? 'vertical') === val ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground'"
          @click="emit('update', { id: block.id, data: { layout: val } })">
          {{ lbl }}
        </button>
      </div>
    </div>

    <div>
      <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wide block mb-2">Icon</label>
      <IconPickerInput :model-value="block.data.icon ?? null"
        @update:model-value="v => emit('update', { id: block.id, data: { icon: v || null } })" />
    </div>

    <div class="flex gap-3">
      <div class="flex-1">
        <label class="text-xs font-medium text-muted-foreground block mb-1">Icon size</label>
        <input :value="block.data.iconSize ?? '1.75rem'" type="text" placeholder="1.75rem"
          class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
          @change="emit('update', { id: block.id, data: { iconSize: $event.target.value || '1.75rem' } })" />
      </div>
      <div class="flex-1">
        <label class="text-xs font-medium text-muted-foreground block mb-1">Icon color</label>
        <ColorPicker :model-value="block.data.iconColor" :show-reset="true"
          @update:model-value="v => emit('update', { id: block.id, data: { iconColor: v || null } })" />
      </div>
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Icon background</label>
      <ColorPicker :model-value="block.data.iconBgColor" :show-reset="true"
        @update:model-value="v => emit('update', { id: block.id, data: { iconBgColor: v || null } })" />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Heading level</label>
      <SelectBox size="sm"
        :model-value="block.data.headingLevel ?? 3"
        :data="[2,3,4,5,6].map(n => ({ value: n, label: `H${n}` }))"
        @update:model-value="v => emit('update', { id: block.id, data: { headingLevel: Number(v) } })" />
    </div>
  </div>
</template>

<script setup>
import DynamicField   from './DynamicField.vue'
import IconPickerInput from '../IconPickerInput.vue'
import ColorPicker    from '../ColorPicker.vue'
import SelectBox      from '@/Components/SelectBox.vue'

const props = defineProps({
  block:           { type: Object, required: true },
  availableFields: { type: Array,  default: () => [] },
  tab:             { type: String, default: null },
})
const emit = defineEmits(['update'])

function onBind(fieldName, value) {
  emit('update', { id: props.block.id, bindings: { ...props.block.bindings, [fieldName]: value } })
}
function onUnbind(fieldName) {
  const b = { ...(props.block.bindings ?? {}) }
  delete b[fieldName]
  emit('update', { id: props.block.id, bindings: Object.keys(b).length ? b : undefined })
}
</script>
