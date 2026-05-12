<!-- resources/js/Components/BlockEditor/blocks/HeroSettings.vue -->
<template>
  <!-- Content tab -->
  <div v-show="!tab || tab === 'content'" class="space-y-3">
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Eyebrow <span class="opacity-60">(optional)</span></label>
      <input :value="block.data.eyebrow" type="text" placeholder="INTRODUCING"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { eyebrow: $event.target.value } })" />
    </div>

    <DynamicField label="Headline" field-name="headline" :block="block" :available-fields="availableFields" @bind="onBind" @unbind="onUnbind">
      <input :value="block.data.headline" type="text" placeholder="Bold headline..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { headline: $event.target.value } })" />
    </DynamicField>

    <DynamicField label="Subtext" field-name="subtext" :block="block" :available-fields="availableFields" @bind="onBind" @unbind="onUnbind">
      <textarea :value="block.data.subtext" rows="3" placeholder="Supporting description..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring resize-none"
        @input="emit('update', { id: block.id, data: { subtext: $event.target.value } })" />
    </DynamicField>

    <div>
      <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wide block mb-2">Buttons</label>
      <div class="space-y-2">
        <div
          v-for="(btn, i) in (block.data.buttons ?? [])"
          :key="i"
          class="rounded-md border border-border p-2 space-y-1.5"
        >
          <div class="flex items-center justify-between mb-1">
            <span class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wide">Button {{ i + 1 }}</span>
            <button type="button" class="text-muted-foreground hover:text-destructive transition-colors" @click="removeButton(i)">
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
          </div>
          <input :value="btn.label" type="text" placeholder="Button label"
            class="w-full rounded-md border bg-background px-2 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-ring"
            @input="updateButton(i, 'label', $event.target.value)" />
          <input :value="btn.url" type="text" placeholder="https://..."
            class="w-full rounded-md border bg-background px-2 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-ring"
            @input="updateButton(i, 'url', $event.target.value)" />
          <div class="flex gap-1">
            <button v-for="v in ['filled', 'outline', 'ghost']" :key="v"
              type="button"
              class="flex-1 py-1 text-[10px] rounded border transition-colors capitalize"
              :class="(btn.variant ?? 'filled') === v ? 'bg-primary text-primary-foreground border-primary' : 'border-border bg-background hover:border-muted-foreground'"
              @click="updateButton(i, 'variant', v)">
              {{ v }}
            </button>
          </div>
        </div>
      </div>
      <button type="button"
        class="mt-2 w-full py-1.5 text-xs rounded border border-dashed border-border text-muted-foreground hover:border-muted-foreground hover:text-foreground transition-colors"
        @click="addButton">
        + Add button
      </button>
    </div>
  </div>

  <!-- Style tab -->
  <div v-show="!tab || tab === 'style'" class="space-y-4">
    <div>
      <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wide block mb-2">Alignment</label>
      <div class="flex rounded-md border overflow-hidden text-xs">
        <button v-for="al in ['left', 'center', 'right']" :key="al"
          type="button"
          class="flex-1 py-1.5 transition-colors capitalize"
          :class="(block.data.align ?? 'center') === al ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground'"
          @click="emit('update', { id: block.id, data: { align: al } })">
          {{ al }}
        </button>
      </div>
    </div>

    <div>
      <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wide block mb-2">Size</label>
      <div class="flex rounded-md border overflow-hidden text-xs">
        <button v-for="[val, lbl] in [['sm', 'Small'], ['md', 'Medium'], ['lg', 'Large']]" :key="val"
          type="button"
          class="flex-1 py-1.5 transition-colors"
          :class="(block.data.size ?? 'md') === val ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground'"
          @click="emit('update', { id: block.id, data: { size: val } })">
          {{ lbl }}
        </button>
      </div>
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Min height</label>
      <input :value="block.data.minHeight ?? ''" type="text" placeholder="60vh"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @change="emit('update', { id: block.id, data: { minHeight: $event.target.value || null } })" />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Vertical padding</label>
      <input :value="block.data.paddingY ?? ''" type="text" placeholder="5rem"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @change="emit('update', { id: block.id, data: { paddingY: $event.target.value || null } })" />
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
      <label class="text-xs font-medium text-muted-foreground block mb-1">Background image URL</label>
      <input :value="block.data.bgImage ?? ''" type="text" placeholder="https://..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @change="emit('update', { id: block.id, data: { bgImage: $event.target.value || null } })" />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Heading level</label>
      <SelectBox size="sm"
        :model-value="block.data.headingLevel ?? 1"
        :data="[1,2,3,4].map(n => ({ value: n, label: `H${n}` }))"
        @update:model-value="v => emit('update', { id: block.id, data: { headingLevel: Number(v) } })" />
    </div>
  </div>
</template>

<script setup>
import DynamicField from './DynamicField.vue'
import ColorPicker  from '../ColorPicker.vue'
import SelectBox    from '@/Components/SelectBox.vue'

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

function addButton() {
  const buttons = [...(props.block.data.buttons ?? []), { label: 'Click here', url: '#', variant: 'filled', target: '_self' }]
  emit('update', { id: props.block.id, data: { buttons } })
}

function removeButton(i) {
  const buttons = [...(props.block.data.buttons ?? [])]
  buttons.splice(i, 1)
  emit('update', { id: props.block.id, data: { buttons } })
}

function updateButton(i, key, value) {
  const buttons = [...(props.block.data.buttons ?? [])]
  buttons[i] = { ...buttons[i], [key]: value }
  emit('update', { id: props.block.id, data: { buttons } })
}
</script>
