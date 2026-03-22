<!-- resources/js/Components/BlockEditor/blocks/ParagraphSettings.vue -->
<template>
  <div>
    <DynamicField
      label="Content"
      field-name="content"
      :block="block"
      :loop-fields="loopFields"
      @bind="onBind"
      @unbind="onUnbind"
    >
      <TiptapEditor
        :model-value="block.data.content"
        @update:model-value="emit('update', { id: block.id, data: { content: $event } })"
      />
    </DynamicField>
  </div>
</template>

<script setup>
import TiptapEditor from '@/Components/TiptapEditor.vue'
import DynamicField  from './DynamicField.vue'

const props = defineProps({
  block:      { type: Object, required: true },
  loopFields: { type: Array,  default: () => [] },
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
