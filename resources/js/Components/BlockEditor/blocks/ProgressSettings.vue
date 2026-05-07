<!-- resources/js/Components/BlockEditor/blocks/ProgressSettings.vue -->
<template>
  <!-- Content tab -->
  <div v-show="!tab || tab === 'content'" class="space-y-3">
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Label</label>
      <input
        :value="block.data.label ?? ''"
        type="text"
        placeholder="e.g. HTML & CSS"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { label: $event.target.value } })"
      />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">
        Value <span class="text-foreground font-semibold">{{ block.data.value ?? 0 }}%</span>
      </label>
      <input
        type="range" min="0" max="100" step="1"
        :value="block.data.value ?? 0"
        class="w-full accent-primary"
        @input="emit('update', { id: block.id, data: { value: Number($event.target.value) } })"
      />
    </div>

    <div class="flex items-center gap-4">
      <label class="flex items-center gap-2 cursor-pointer">
        <EditorCheckbox :model-value="block.data.showLabel !== false" @update:model-value="v => emit('update', { id: block.id, data: { showLabel: v } })" />
        <span class="text-xs text-muted-foreground">Show label</span>
      </label>
      <label class="flex items-center gap-2 cursor-pointer">
        <EditorCheckbox :model-value="block.data.showValue !== false" @update:model-value="v => emit('update', { id: block.id, data: { showValue: v } })" />
        <span class="text-xs text-muted-foreground">Show %</span>
      </label>
    </div>
  </div>

  <!-- Style tab -->
  <div v-show="!tab || tab === 'style'" class="space-y-3">
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Bar color</label>
      <ColorPicker
        :model-value="block.data.color ?? ''"
        default="var(--primary)"
        :show-reset="true"
        @update:model-value="v => emit('update', { id: block.id, data: { color: v || null } })"
      />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Track color</label>
      <ColorPicker
        :model-value="block.data.trackColor ?? ''"
        default="var(--muted)"
        :show-reset="true"
        @update:model-value="v => emit('update', { id: block.id, data: { trackColor: v || null } })"
      />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Height</label>
      <SelectBox size="sm"
        :model-value="block.data.height ?? '8px'"
        :data="[
          { value: '4px',  label: 'Thin (4px)' },
          { value: '8px',  label: 'Normal (8px)' },
          { value: '12px', label: 'Thick (12px)' },
          { value: '20px', label: 'Bold (20px)' },
        ]"
        @update:model-value="v => emit('update', { id: block.id, data: { height: v } })"
      />
    </div>

    <div class="flex items-center gap-4">
      <label class="flex items-center gap-2 cursor-pointer">
        <EditorCheckbox :model-value="block.data.animated !== false" @update:model-value="v => emit('update', { id: block.id, data: { animated: v } })" />
        <span class="text-xs text-muted-foreground">Animate on load</span>
      </label>
      <label class="flex items-center gap-2 cursor-pointer">
        <EditorCheckbox :model-value="block.data.striped ?? false" @update:model-value="v => emit('update', { id: block.id, data: { striped: v } })" />
        <span class="text-xs text-muted-foreground">Striped</span>
      </label>
    </div>
  </div>
</template>

<script setup>
import SelectBox      from '@/Components/SelectBox.vue'
import EditorCheckbox from '../EditorCheckbox.vue'
import ColorPicker    from '../ColorPicker.vue'

const props = defineProps({
  block: { type: Object, required: true },
  tab:   { type: String, default: null },
})
const emit = defineEmits(['update'])
</script>
