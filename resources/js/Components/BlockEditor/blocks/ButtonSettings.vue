<!-- resources/js/Components/BlockEditor/blocks/ButtonSettings.vue -->
<template>
  <!-- Content tab -->
  <div v-show="!tab || tab === 'content'" class="space-y-3">
    <DynamicField label="Label" field-name="label" :block="block" :available-fields="availableFields" @bind="onBind" @unbind="onUnbind">
      <input
        :value="block.data.label"
        type="text"
        placeholder="Button text"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { label: $event.target.value } })"
      />
    </DynamicField>

    <DynamicField label="URL" field-name="url" :block="block" :available-fields="availableFields" @bind="onBind" @unbind="onUnbind">
      <input
        :value="block.data.url"
        type="url"
        placeholder="https://..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { url: $event.target.value } })"
      />
    </DynamicField>

    <div class="flex items-center gap-2">
      <EditorCheckbox
        :model-value="block.data.target === '_blank'"
        @update:model-value="v => emit('update', { id: block.id, data: { target: v ? '_blank' : '_self' } })"
      />
      <label class="text-xs text-muted-foreground">Open in new tab</label>
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Rel attribute</label>
      <SelectBox size="sm"
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
  <div v-show="!tab || tab === 'style'" class="space-y-4">
    <!-- Variant -->
    <div>
      <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wide block mb-2">Variant</label>
      <div class="flex rounded-md border overflow-hidden text-xs">
        <button v-for="v in ['filled','outline','ghost']" :key="v" type="button"
          class="flex-1 py-1.5 transition-colors capitalize"
          :class="(block.data.variant ?? 'filled') === v ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground'"
          @click="emit('update', { id: block.id, data: { variant: v } })"
        >{{ v }}</button>
      </div>
    </div>

    <!-- Size -->
    <div>
      <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wide block mb-2">Size</label>
      <div class="flex rounded-md border overflow-hidden text-xs">
        <button v-for="s in ['sm','md','lg']" :key="s" type="button"
          class="flex-1 py-1.5 transition-colors uppercase"
          :class="(block.data.size ?? 'md') === s ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground'"
          @click="emit('update', { id: block.id, data: { size: s } })"
        >{{ s }}</button>
      </div>
    </div>

    <!-- Alignment -->
    <div>
      <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wide block mb-2">Alignment</label>
      <div class="flex rounded-md border overflow-hidden text-xs">
        <button v-for="a in ['left','center','right']" :key="a" type="button"
          class="flex-1 py-1.5 transition-colors capitalize"
          :class="(block.data.alignment ?? 'left') === a ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground'"
          @click="emit('update', { id: block.id, data: { alignment: a } })"
        >{{ a }}</button>
      </div>
    </div>

    <!-- Full width -->
    <div class="flex items-center gap-2">
      <EditorCheckbox
        :model-value="block.data.fullWidth ?? false"
        @update:model-value="v => emit('update', { id: block.id, data: { fullWidth: v } })"
      />
      <label class="text-xs text-muted-foreground">Full width</label>
    </div>

    <!-- Colors -->
    <div class="space-y-2">
      <div>
        <label class="text-xs font-medium text-muted-foreground block mb-1">Background / accent color</label>
        <ColorPicker
          :model-value="block.data.bgColor"
          :show-reset="true"
          @update:model-value="v => emit('update', { id: block.id, data: { bgColor: v } })"
        />
      </div>
      <div>
        <label class="text-xs font-medium text-muted-foreground block mb-1">Text color</label>
        <ColorPicker
          :model-value="block.data.textColor"
          :show-reset="true"
          @update:model-value="v => emit('update', { id: block.id, data: { textColor: v } })"
        />
      </div>
    </div>

    <!-- Border radius -->
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Border radius</label>
      <DimensionInput
        :model-value="block.data.radius ?? ''"
        placeholder="0.375rem"
        @update:model-value="v => emit('update', { id: block.id, data: { radius: v || null } })"
      />
    </div>

    <!-- Icon -->
    <IconSettings :block="block" @update="emit('update', $event)" />
  </div>
</template>

<script setup>
import SelectBox      from '@/Components/SelectBox.vue'
import DynamicField   from './DynamicField.vue'
import IconSettings   from './IconSettings.vue'
import ColorPicker    from '../ColorPicker.vue'
import DimensionInput from '../DimensionInput.vue'
import EditorCheckbox from '../EditorCheckbox.vue'

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
