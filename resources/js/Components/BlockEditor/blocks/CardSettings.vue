<!-- resources/js/Components/BlockEditor/blocks/CardSettings.vue -->
<template>
  <!-- Content tab -->
  <div v-show="!tab || tab === 'content'" class="space-y-3">
    <DynamicField label="Image URL" field-name="imageUrl" :block="block" :available-fields="availableFields" @bind="onBind" @unbind="onUnbind">
      <input :value="block.data.imageUrl" type="text" placeholder="https://..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { imageUrl: $event.target.value } })" />
    </DynamicField>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Image alt text</label>
      <input :value="block.data.imageAlt" type="text" placeholder="Describe the image..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { imageAlt: $event.target.value } })" />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Image aspect ratio</label>
      <SelectBox size="sm"
        :model-value="block.data.imageAspect ?? '16/9'"
        :data="[
          { value: '16/9', label: '16:9 (Landscape)' },
          { value: '4/3',  label: '4:3 (Standard)' },
          { value: '1/1',  label: '1:1 (Square)' },
          { value: '3/4',  label: '3:4 (Portrait)' },
          { value: '21/9', label: '21:9 (Ultra-wide)' },
        ]"
        @update:model-value="v => emit('update', { id: block.id, data: { imageAspect: v } })" />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Label / Eyebrow <span class="opacity-60">(optional)</span></label>
      <input :value="block.data.label" type="text" placeholder="Category..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { label: $event.target.value } })" />
    </div>

    <DynamicField label="Title" field-name="title" :block="block" :available-fields="availableFields" @bind="onBind" @unbind="onUnbind">
      <input :value="block.data.title" type="text" placeholder="Card title..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { title: $event.target.value } })" />
    </DynamicField>

    <DynamicField label="Text" field-name="text" :block="block" :available-fields="availableFields" @bind="onBind" @unbind="onUnbind">
      <textarea :value="block.data.text" rows="3" placeholder="Card description..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring resize-none"
        @input="emit('update', { id: block.id, data: { text: $event.target.value } })" />
    </DynamicField>

    <DynamicField label="Button label" field-name="buttonLabel" :block="block" :available-fields="availableFields" @bind="onBind" @unbind="onUnbind">
      <input :value="block.data.buttonLabel" type="text" placeholder="Read more"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { buttonLabel: $event.target.value } })" />
    </DynamicField>

    <DynamicField label="Button URL" field-name="buttonUrl" :block="block" :available-fields="availableFields" @bind="onBind" @unbind="onUnbind">
      <input :value="block.data.buttonUrl" type="text" placeholder="https://..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { buttonUrl: $event.target.value } })" />
    </DynamicField>
  </div>

  <!-- Style tab -->
  <div v-show="!tab || tab === 'style'" class="space-y-4">
    <div>
      <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wide block mb-2">Card style</label>
      <div class="flex rounded-md border overflow-hidden text-xs">
        <button v-for="[val, lbl] in [['bordered', 'Bordered'], ['shadowed', 'Shadowed'], ['flat', 'Flat']]" :key="val"
          type="button"
          class="flex-1 py-1.5 transition-colors"
          :class="(block.data.variant ?? 'bordered') === val ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground'"
          @click="emit('update', { id: block.id, data: { variant: val } })">
          {{ lbl }}
        </button>
      </div>
    </div>

    <div>
      <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wide block mb-2">Button style</label>
      <div class="flex rounded-md border overflow-hidden text-xs">
        <button v-for="[val, lbl] in [['filled', 'Filled'], ['outline', 'Outline'], ['ghost', 'Ghost']]" :key="val"
          type="button"
          class="flex-1 py-1.5 transition-colors"
          :class="(block.data.buttonVariant ?? 'filled') === val ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground'"
          @click="emit('update', { id: block.id, data: { buttonVariant: val } })">
          {{ lbl }}
        </button>
      </div>
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Heading level</label>
      <SelectBox size="sm"
        :model-value="block.data.headingLevel ?? 3"
        :data="[2,3,4,5,6].map(n => ({ value: n, label: `H${n}` }))"
        @update:model-value="v => emit('update', { id: block.id, data: { headingLevel: Number(v) } })" />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Background color</label>
      <ColorPicker :model-value="block.data.bgColor" :show-reset="true"
        @update:model-value="v => emit('update', { id: block.id, data: { bgColor: v || null } })" />
    </div>
  </div>
</template>

<script setup>
import DynamicField from './DynamicField.vue'
import SelectBox    from '@/Components/SelectBox.vue'
import ColorPicker  from '../ColorPicker.vue'

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
