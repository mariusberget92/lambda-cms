<!-- resources/js/Components/BlockEditor/blocks/CtaSettings.vue -->
<template>
  <div class="space-y-3">
    <DynamicField
      label="Headline"
      field-name="headline"
      :block="block"
      :loop-fields="loopFields"
      @bind="onBind"
      @unbind="onUnbind"
    >
      <input
        :value="block.data.headline"
        type="text"
        placeholder="Bold headline..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { headline: $event.target.value } })"
      />
    </DynamicField>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Body text</label>
      <input
        :value="block.data.text"
        type="text"
        placeholder="Supporting text..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { text: $event.target.value } })"
      />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Button label</label>
      <input
        :value="block.data.button_label"
        type="text"
        placeholder="Click here"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { button_label: $event.target.value } })"
      />
    </div>

    <DynamicField
      label="Button URL"
      field-name="button_url"
      :block="block"
      :loop-fields="loopFields"
      @bind="onBind"
      @unbind="onUnbind"
    >
      <input
        :value="block.data.button_url"
        type="url"
        placeholder="https://..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { button_url: $event.target.value } })"
      />
    </DynamicField>
  </div>
</template>

<script setup>
import DynamicField from './DynamicField.vue'

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
