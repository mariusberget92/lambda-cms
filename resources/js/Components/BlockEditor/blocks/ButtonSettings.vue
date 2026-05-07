<!-- resources/js/Components/BlockEditor/blocks/ButtonSettings.vue -->
<template>
  <!-- Content tab -->
  <div v-show="!tab || tab === 'content'" class="space-y-3">
    <DynamicField
      label="Label"
      field-name="text"
      :block="block"
      :available-fields="availableFields"
      @bind="onBind"
      @unbind="onUnbind"
    >
      <input
        :value="block.data.text"
        type="text"
        placeholder="Button label…"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { text: $event.target.value } })"
      />
    </DynamicField>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">URL</label>
      <input
        :value="block.data.href ?? ''"
        type="text"
        placeholder="https://…"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { href: $event.target.value } })"
      />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Open in</label>
      <div class="flex rounded-md border overflow-hidden text-xs">
        <button
          v-for="[val, label] in [['_self', 'Same tab'], ['_blank', 'New tab']]"
          :key="val"
          type="button"
          class="flex-1 py-1.5 transition-colors"
          :class="(block.data.target ?? '_self') === val ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground'"
          @click="emit('update', { id: block.id, data: { target: val } })"
        >{{ label }}</button>
      </div>
    </div>
  </div>

  <!-- Style tab -->
  <div v-show="!tab || tab === 'style'" class="space-y-3">
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Variant</label>
      <div class="flex rounded-md border overflow-hidden text-xs">
        <button
          v-for="v in ['solid', 'outline', 'ghost', 'link']"
          :key="v"
          type="button"
          class="flex-1 py-1.5 transition-colors capitalize"
          :class="(block.data.variant ?? 'solid') === v ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground'"
          @click="emit('update', { id: block.id, data: { variant: v } })"
        >{{ v }}</button>
      </div>
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Size</label>
      <div class="flex rounded-md border overflow-hidden text-xs">
        <button
          v-for="[val, label] in [['sm', 'SM'], ['md', 'MD'], ['lg', 'LG']]"
          :key="val"
          type="button"
          class="flex-1 py-1.5 transition-colors"
          :class="(block.data.size ?? 'md') === val ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground'"
          @click="emit('update', { id: block.id, data: { size: val } })"
        >{{ label }}</button>
      </div>
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Alignment</label>
      <div class="flex rounded-md border overflow-hidden text-xs">
        <button
          v-for="al in ['left', 'center', 'right']"
          :key="al"
          type="button"
          class="flex-1 py-1.5 transition-colors capitalize"
          :class="(block.data.alignment ?? 'left') === al ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground'"
          @click="emit('update', { id: block.id, data: { alignment: al } })"
        >{{ al }}</button>
      </div>
    </div>

    <label class="flex items-center gap-2 cursor-pointer">
      <EditorCheckbox :model-value="block.data.fullWidth ?? false" @update:model-value="v => emit('update', { id: block.id, data: { fullWidth: v } })" />
      <span class="text-xs text-muted-foreground">Full width</span>
    </label>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Border radius</label>
      <SelectBox size="sm"
        :model-value="block.data.borderRadius ?? '0.375rem'"
        :data="[
          { value: '0',        label: 'None' },
          { value: '0.25rem',  label: 'Small' },
          { value: '0.375rem', label: 'Medium' },
          { value: '0.5rem',   label: 'Large' },
          { value: '0.75rem',  label: 'Extra large' },
          { value: '9999px',   label: 'Pill / full' },
        ]"
        @update:model-value="v => emit('update', { id: block.id, data: { borderRadius: v } })"
      />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Background / accent color</label>
      <ColorPicker
        :model-value="block.data.bgColor ?? ''"
        default="var(--primary)"
        :show-reset="true"
        @update:model-value="v => emit('update', { id: block.id, data: { bgColor: v || null } })"
      />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Text color</label>
      <ColorPicker
        :model-value="block.data.textColor ?? ''"
        default="#ffffff"
        :show-reset="true"
        @update:model-value="v => emit('update', { id: block.id, data: { textColor: v || null } })"
      />
    </div>

    <div v-if="(block.data.variant ?? 'solid') === 'outline'">
      <label class="text-xs font-medium text-muted-foreground block mb-1">Border color</label>
      <ColorPicker
        :model-value="block.data.borderColor ?? ''"
        default="var(--primary)"
        :show-reset="true"
        @update:model-value="v => emit('update', { id: block.id, data: { borderColor: v || null } })"
      />
    </div>

    <IconSettings :block="block" @update="emit('update', $event)" />
  </div>
</template>

<script setup>
import SelectBox     from '@/Components/SelectBox.vue'
import DynamicField  from './DynamicField.vue'
import IconSettings  from './IconSettings.vue'
import EditorCheckbox from '../EditorCheckbox.vue'
import ColorPicker   from '../ColorPicker.vue'

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
