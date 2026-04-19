<!-- resources/js/Components/BlockEditor/blocks/HtmlSettings.vue -->
<template>
  <!-- Defensive self-guard: shows admin-only message for non-admins even if rendered directly -->
  <div v-if="!isAdmin" class="rounded-md border border-dashed p-4 text-center">
    <p class="text-xs text-muted-foreground">HTML blocks are admin-only.</p>
  </div>
  <!-- Content fields -->
  <div v-else v-show="!tab || tab === 'content'" class="space-y-2">
    <DynamicField
      label="Raw HTML"
      field-name="content"
      :block="block"
      :available-fields="availableFields"
      @bind="onBind"
      @unbind="onUnbind"
    >
      <textarea
        :value="block.data.content"
        rows="12"
        placeholder="<div>...</div>"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-xs font-mono focus:outline-none focus:ring-2 focus:ring-ring resize-y"
        @input="emit('update', { id: block.id, data: { content: $event.target.value } })"
      />
    </DynamicField>
    <p class="text-xs text-muted-foreground">&#x26A0; Admin only &mdash; rendered as-is in the page.</p>
  </div>
</template>

<script setup>
import DynamicField from './DynamicField.vue'

const props = defineProps({
  block:           { type: Object,  required: true },
  isAdmin:         { type: Boolean, default: false },
  availableFields: { type: Array,   default: () => [] },
  tab:             { type: String,  default: null },
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
