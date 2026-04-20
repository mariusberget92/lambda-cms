<!-- resources/js/Components/BlockEditor/blocks/ParagraphSettings.vue -->
<template>
  <!-- Content fields -->
  <div v-show="!tab || tab === 'content'" class="space-y-3">
    <DynamicField
      label="Content"
      field-name="content"
      :block="block"
      :available-fields="availableFields"
      @bind="onBind"
      @unbind="onUnbind"
    >
      <TiptapEditor
        :model-value="block.data.content"
        :dark="true"
        @update:model-value="emit('update', { id: block.id, data: { content: $event } })"
      />
    </DynamicField>
  </div>

  <!-- Style fields -->
  <div v-show="!tab || tab === 'style'" class="space-y-4">

    <IconSettings :block="block" @update="emit('update', $event)" />

    <hr class="border-border" />

    <TypographyControl
      :model-value="block.data.typography ?? {}"
      @update:model-value="v => emit('update', { id: block.id, data: { typography: v } })"
    />

  </div>
</template>

<script setup>
import TiptapEditor      from '@/Components/TiptapEditor.vue'
import DynamicField      from './DynamicField.vue'
import TypographyControl from '../TypographyControl.vue'
import IconSettings      from './IconSettings.vue'

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
