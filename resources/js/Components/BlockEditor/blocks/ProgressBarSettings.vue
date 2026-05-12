<!-- resources/js/Components/BlockEditor/blocks/ProgressBarSettings.vue -->
<template>
  <!-- Content tab -->
  <div v-show="!tab || tab === 'content'" class="space-y-3">
    <div>
      <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wide block mb-2">Items</label>
      <div class="space-y-2">
        <div v-for="(item, i) in (block.data.items ?? [])" :key="i" class="rounded border border-border p-2 space-y-1.5">
          <div class="flex items-center gap-1.5">
            <input :value="item.label" type="text" placeholder="Label..."
              class="flex-1 rounded-md border bg-background px-2 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-ring"
              @input="updateItem(i, 'label', $event.target.value)" />
            <button type="button" class="text-muted-foreground hover:text-destructive transition-colors p-1 shrink-0" @click="removeItem(i)">
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
          </div>
          <div class="flex items-center gap-2">
            <label class="text-[10px] text-muted-foreground w-8 shrink-0">{{ item.value ?? 0 }}%</label>
            <input :value="item.value ?? 0" type="range" min="0" max="100" step="1"
              class="flex-1 h-1 accent-primary"
              @input="updateItem(i, 'value', Number($event.target.value))" />
          </div>
          <div class="flex items-center gap-2">
            <label class="text-[10px] text-muted-foreground shrink-0">Color</label>
            <ColorPicker :model-value="item.color" :show-reset="true" :show-value="false"
              @update:model-value="v => updateItem(i, 'color', v || null)" />
          </div>
        </div>
      </div>
      <button type="button"
        class="mt-2 w-full py-1.5 text-xs rounded border border-dashed border-border text-muted-foreground hover:border-muted-foreground hover:text-foreground transition-colors"
        @click="addItem">+ Add item</button>
    </div>
  </div>

  <!-- Style tab -->
  <div v-show="!tab || tab === 'style'" class="space-y-3">
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Bar height</label>
      <input :value="block.data.height ?? '0.5rem'" type="text" placeholder="0.5rem"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @change="emit('update', { id: block.id, data: { height: $event.target.value || '0.5rem' } })" />
    </div>
    <div class="flex items-center gap-2">
      <input :id="`show-val-${block.id}`" type="checkbox" :checked="block.data.showValues !== false"
        class="rounded"
        @change="emit('update', { id: block.id, data: { showValues: $event.target.checked } })" />
      <label :for="`show-val-${block.id}`" class="text-xs text-muted-foreground cursor-pointer">Show percentage values</label>
    </div>
  </div>
</template>

<script setup>
import ColorPicker from '../ColorPicker.vue'

const props = defineProps({ block: { type: Object, required: true }, tab: { type: String, default: null } })
const emit = defineEmits(['update'])

function updateItem(i, key, value) {
  const items = [...(props.block.data.items ?? [])]
  items[i] = { ...items[i], [key]: value }
  emit('update', { id: props.block.id, data: { items } })
}
function removeItem(i) {
  const items = [...(props.block.data.items ?? [])]
  items.splice(i, 1)
  emit('update', { id: props.block.id, data: { items } })
}
function addItem() {
  const items = [...(props.block.data.items ?? []), { label: '', value: 50, color: null }]
  emit('update', { id: props.block.id, data: { items } })
}
</script>
