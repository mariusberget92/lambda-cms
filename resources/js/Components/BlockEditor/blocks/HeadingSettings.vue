<!-- resources/js/Components/BlockEditor/blocks/HeadingSettings.vue -->
<template>
  <!-- Content tab -->
  <div v-show="!tab || tab === 'content'" class="space-y-3">
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Level</label>
      <SelectBox
        :model-value="block.data.level"
        :data="[1,2,3,4,5,6].map(n => ({ value: n, label: `H${n}` }))"
        @update:model-value="v => emit('update', { id: block.id, data: { level: Number(v) } })"
      />
    </div>

    <DynamicField
      label="Text"
      field-name="text"
      :block="block"
      :available-fields="availableFields"
      @bind="onBind"
      @unbind="onUnbind"
    >
      <input
        :value="block.data.text"
        type="text"
        placeholder="Heading text..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { text: $event.target.value } })"
      />
    </DynamicField>
  </div>

  <!-- Style tab -->
  <div v-show="!tab || tab === 'style'" class="space-y-3">
    <IconSettings :block="block" @update="emit('update', $event)" />
  </div>
</template>

<script setup>
import SelectBox    from '@/Components/SelectBox.vue'
import DynamicField from './DynamicField.vue'
import IconSettings from './IconSettings.vue'

const props = defineProps({
  block:           { type: Object, required: true },
  availableFields: { type: Array,  default: () => [] },
  tab:             { type: String, default: null },
})
const emit = defineEmits(['update'])

function onBind(fieldName, loopField) {
  emit('update', { id: props.block.id, bindings: { ...props.block.bindings, [fieldName]: loopField } })
}
function onUnbind(fieldName) {
  const b = { ...(props.block.bindings ?? {}) }
  delete b[fieldName]
  emit('update', { id: props.block.id, bindings: Object.keys(b).length ? b : undefined })
}
</script>
