<!-- resources/js/Components/BlockEditor/blocks/LinkSettings.vue -->
<template>
  <!-- Content tab -->
  <div v-show="!tab || tab === 'content'" class="space-y-3">
    <DynamicField
      label="URL"
      field-name="url"
      :block="block"
      :available-fields="availableFields"
      @bind="onBind"
      @unbind="onUnbind"
    >
      <input
        :value="block.data.url"
        type="url"
        placeholder="https://..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { url: $event.target.value } })"
      />
    </DynamicField>

    <div class="flex items-center gap-2">
      <input
        id="link-newtab"
        type="checkbox"
        :checked="block.data.target === '_blank'"
        class="rounded border-border accent-primary"
        @change="emit('update', { id: block.id, data: { target: $event.target.checked ? '_blank' : '_self' } })"
      />
      <label for="link-newtab" class="text-xs text-muted-foreground">Open in new tab</label>
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Rel attribute</label>
      <SelectBox
        :model-value="block.data.rel || ''"
        :data="[
          { value: '',           label: 'None' },
          { value: 'nofollow',   label: 'nofollow' },
          { value: 'noopener',   label: 'noopener' },
          { value: 'noreferrer', label: 'noreferrer' },
          { value: 'sponsored',  label: 'sponsored' },
        ]"
        @update:model-value="v => emit('update', { id: block.id, data: { rel: v } })"
      />
    </div>
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
