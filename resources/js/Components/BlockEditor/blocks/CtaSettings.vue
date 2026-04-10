<!-- resources/js/Components/BlockEditor/blocks/CtaSettings.vue -->
<template>
  <!-- Content fields -->
  <div v-show="!tab || tab === 'content'" class="space-y-3">
    <DynamicField label="Headline" field-name="headline" :block="block" :available-fields="availableFields" @bind="onBind" @unbind="onUnbind">
      <input :value="block.data.headline" type="text" placeholder="Bold headline..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { headline: $event.target.value } })" />
    </DynamicField>

    <DynamicField label="Body text" field-name="text" :block="block" :available-fields="availableFields" @bind="onBind" @unbind="onUnbind">
      <input :value="block.data.text" type="text" placeholder="Supporting text..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { text: $event.target.value } })" />
    </DynamicField>

    <DynamicField label="Button label" field-name="button_label" :block="block" :available-fields="availableFields" @bind="onBind" @unbind="onUnbind">
      <input :value="block.data.button_label" type="text" placeholder="Click here"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { button_label: $event.target.value } })" />
    </DynamicField>

    <DynamicField label="Button URL" field-name="button_url" :block="block" :available-fields="availableFields" @bind="onBind" @unbind="onUnbind">
      <input :value="block.data.button_url" type="url" placeholder="https://..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { button_url: $event.target.value } })" />
    </DynamicField>
  </div>

  <!-- Style fields -->
  <div v-show="!tab || tab === 'style'" class="space-y-4">

    <!-- Text alignment -->
    <div>
      <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wide block mb-2">Text alignment</label>
      <div class="flex rounded-md border overflow-hidden text-xs">
        <button v-for="align in ['left','center','right']" :key="align" type="button"
          class="flex-1 py-1.5 transition-colors capitalize"
          :class="block.data.textAlign === align ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground'"
          @click="update('textAlign', block.data.textAlign === align ? null : align)">
          {{ align.charAt(0).toUpperCase() + align.slice(1) }}
        </button>
      </div>
    </div>

    <!-- Headline color -->
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Headline color</label>
      <ColorPicker
        :model-value="block.data.headlineColor"
        default="#ffffff"
        :show-reset="true"
        @update:model-value="v => update('headlineColor', v)"
      />
    </div>

    <!-- Body text color -->
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Body text color</label>
      <ColorPicker
        :model-value="block.data.textColor"
        default="#ffffff"
        :show-reset="true"
        @update:model-value="v => update('textColor', v)"
      />
    </div>

    <!-- Button style -->
    <div class="space-y-2">
      <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wide block">Button style</label>
      <div class="flex rounded-md border overflow-hidden text-xs">
        <button v-for="variant in ['filled', 'outline']" :key="variant" type="button"
          class="flex-1 py-1.5 transition-colors capitalize"
          :class="(block.data.button?.variant ?? 'filled') === variant ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground'"
          @click="updateNested('button', 'variant', variant)">
          {{ variant.charAt(0).toUpperCase() + variant.slice(1) }}
        </button>
      </div>
      <div class="flex gap-4">
        <div>
          <label class="text-[10px] text-muted-foreground block mb-1">Bg color</label>
          <ColorPicker
            :model-value="block.data.button?.bgColor ?? '#5e81ac'"
            default="#5e81ac"
            :show-value="false"
            @update:model-value="v => updateNested('button', 'bgColor', v)"
          />
        </div>
        <div>
          <label class="text-[10px] text-muted-foreground block mb-1">Text color</label>
          <ColorPicker
            :model-value="block.data.button?.textColor ?? '#eceff4'"
            default="#eceff4"
            :show-value="false"
            @update:model-value="v => updateNested('button', 'textColor', v)"
          />
        </div>
      </div>
      <div>
        <label class="text-xs font-medium text-muted-foreground block mb-1">Button radius</label>
        <DimensionInput :model-value="block.data.button?.radius ?? ''"
          placeholder="0"
          @update:model-value="v => updateNested('button', 'radius', v || null)" />
      </div>
    </div>

    <!-- Padding -->
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Padding</label>
      <SpacingControl
        :model-value="typeof block.data.padding === 'object' ? block.data.padding : {}"
        @update:model-value="v => update('padding', v)"
      />
    </div>

  </div>
</template>

<script setup>
import DynamicField  from './DynamicField.vue'
import DimensionInput from '../DimensionInput.vue'
import SpacingControl from '../SpacingControl.vue'
import ColorPicker   from '../ColorPicker.vue'

const props = defineProps({
  block:           { type: Object, required: true },
  availableFields: { type: Array,  default: () => [] },
  tab:             { type: String, default: null },
})
const emit = defineEmits(['update'])


function update(key, value) {
  emit('update', { id: props.block.id, data: { [key]: value } })
}
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
