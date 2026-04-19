<!-- resources/js/Components/BlockEditor/blocks/QuoteSettings.vue -->
<template>
  <!-- Content fields -->
  <div v-show="!tab || tab === 'content'" class="space-y-3">
    <DynamicField
      label="Quote text"
      field-name="text"
      :block="block"
      :available-fields="availableFields"
      @bind="onBind"
      @unbind="onUnbind"
    >
      <textarea
        :value="block.data.text"
        rows="4"
        placeholder="The quote..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring resize-none"
        @input="emit('update', { id: block.id, data: { text: $event.target.value } })"
      />
    </DynamicField>

    <DynamicField
      label="Attribution"
      field-name="attribution"
      :block="block"
      :available-fields="availableFields"
      @bind="onBind"
      @unbind="onUnbind"
    >
      <input
        :value="block.data.attribution"
        type="text"
        placeholder="— Author name"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { attribution: $event.target.value } })"
      />
    </DynamicField>
  </div>

  <!-- Style fields -->
  <div v-show="!tab || tab === 'style'" class="space-y-4">

    <TypographyControl
      :model-value="block.data.typography ?? {}"
      @update:model-value="v => emit('update', { id: block.id, data: { typography: v } })"
    />

    <!-- Accent bar -->
    <div class="space-y-2">
      <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wide block">Accent bar</label>
      <SelectBox size="sm"
        :model-value="block.data.accentBar?.style ?? 'left'"
        :data="[
          { value: 'none',  label: 'None' },
          { value: 'left',  label: 'Left border' },
          { value: 'top',   label: 'Top border' },
        ]"
        @update:model-value="v => updateNested('accentBar', 'style', v)"
      />
      <template v-if="(block.data.accentBar?.style ?? 'left') !== 'none'">
        <div>
          <label class="text-xs font-medium text-muted-foreground block mb-1">Color</label>
          <ColorPicker
            :model-value="block.data.accentBar?.color ?? '#5e81ac'"
            default="#5e81ac"
            @update:model-value="v => updateNested('accentBar', 'color', v)"
          />
        </div>
        <DimensionInput
          :model-value="block.data.accentBar?.width ?? '4px'"
          placeholder="4px"
          @update:model-value="v => updateNested('accentBar', 'width', v || '4px')"
        />
      </template>
    </div>

  </div>
</template>

<script setup>
import DynamicField    from './DynamicField.vue'
import TypographyControl from '../TypographyControl.vue'
import DimensionInput  from '../DimensionInput.vue'
import SelectBox       from '@/Components/SelectBox.vue'
import ColorPicker     from '../ColorPicker.vue'

const props = defineProps({
  block:           { type: Object, required: true },
  availableFields: { type: Array,  default: () => [] },
  tab:             { type: String, default: null },
})
const emit = defineEmits(['update'])

function updateNested(key, subKey, value) {
  const current = props.block.data[key] ?? {}
  emit('update', { id: props.block.id, data: { [key]: { ...current, [subKey]: value } } })
}
function onBind(fieldName, value) {
  emit('update', { id: props.block.id, bindings: { ...props.block.bindings, [fieldName]: value } })
}
function onUnbind(fieldName) {
  const b = { ...(props.block.bindings ?? {}) }
  delete b[fieldName]
  emit('update', { id: props.block.id, bindings: Object.keys(b).length ? b : undefined })
}
</script>
