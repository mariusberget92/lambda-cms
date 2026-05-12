<!-- resources/js/Components/BlockEditor/blocks/ButtonSettings.vue -->
<template>
  <!-- Content tab -->
  <div v-show="!tab || tab === 'content'" class="space-y-3">
    <DynamicField label="Label" field-name="label" :block="block" :available-fields="availableFields" @bind="onBind" @unbind="onUnbind">
      <input :value="block.data.label" type="text" placeholder="Click here"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { label: $event.target.value } })" />
    </DynamicField>

    <DynamicField label="URL" field-name="url" :block="block" :available-fields="availableFields" @bind="onBind" @unbind="onUnbind">
      <input :value="block.data.url" type="text" placeholder="https://..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { url: $event.target.value } })" />
    </DynamicField>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Open in</label>
      <div class="flex rounded-md border overflow-hidden text-xs">
        <button v-for="[val, lbl] in [['_self', 'Same tab'], ['_blank', 'New tab']]" :key="val"
          type="button"
          class="flex-1 py-1.5 transition-colors"
          :class="(block.data.target ?? '_self') === val ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground'"
          @click="emit('update', { id: block.id, data: { target: val } })">
          {{ lbl }}
        </button>
      </div>
    </div>
  </div>

  <!-- Style tab -->
  <div v-show="!tab || tab === 'style'" class="space-y-4">
    <div>
      <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wide block mb-2">Variant</label>
      <div class="flex rounded-md border overflow-hidden text-xs">
        <button v-for="v in ['filled', 'outline', 'ghost']" :key="v"
          type="button"
          class="flex-1 py-1.5 transition-colors capitalize"
          :class="(block.data.variant ?? 'filled') === v ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground'"
          @click="emit('update', { id: block.id, data: { variant: v } })">
          {{ v }}
        </button>
      </div>
    </div>

    <div>
      <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wide block mb-2">Size</label>
      <div class="flex rounded-md border overflow-hidden text-xs">
        <button v-for="[val, lbl] in [['sm', 'S'], ['md', 'M'], ['lg', 'L']]" :key="val"
          type="button"
          class="flex-1 py-1.5 transition-colors"
          :class="(block.data.size ?? 'md') === val ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground'"
          @click="emit('update', { id: block.id, data: { size: val } })">
          {{ lbl }}
        </button>
      </div>
    </div>

    <div>
      <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wide block mb-2">Alignment</label>
      <div class="flex rounded-md border overflow-hidden text-xs">
        <button v-for="al in ['left', 'center', 'right']" :key="al"
          type="button"
          class="flex-1 py-1.5 transition-colors capitalize"
          :class="(block.data.align ?? 'left') === al ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground'"
          @click="emit('update', { id: block.id, data: { align: al } })">
          {{ al }}
        </button>
      </div>
    </div>

    <hr class="border-border" />

    <div class="flex gap-4">
      <div class="flex-1">
        <label class="text-xs font-medium text-muted-foreground block mb-1">Bg color</label>
        <ColorPicker :model-value="block.data.bgColor" :show-reset="true"
          @update:model-value="v => emit('update', { id: block.id, data: { bgColor: v || null } })" />
      </div>
      <div class="flex-1">
        <label class="text-xs font-medium text-muted-foreground block mb-1">Text color</label>
        <ColorPicker :model-value="block.data.textColor" :show-reset="true"
          @update:model-value="v => emit('update', { id: block.id, data: { textColor: v || null } })" />
      </div>
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Border radius</label>
      <DimensionInput :model-value="block.data.borderRadius ?? ''"
        placeholder="0.375rem"
        @update:model-value="v => emit('update', { id: block.id, data: { borderRadius: v || null } })" />
    </div>

    <hr class="border-border" />

    <div>
      <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wide block mb-2">Icon</label>
      <SharedIconSettings :block="block" @update="emit('update', $event)" />
    </div>
  </div>
</template>

<script setup>
import DynamicField      from './DynamicField.vue'
import ColorPicker       from '../ColorPicker.vue'
import DimensionInput    from '../DimensionInput.vue'
import SharedIconSettings from './IconSettings.vue'

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
