<!-- resources/js/Components/BlockEditor/blocks/TocSettings.vue -->
<template>
  <div class="space-y-3">
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Title</label>
      <input :value="block.data.title" type="text" placeholder="Table of Contents"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { title: $event.target.value } })" />
    </div>

    <div class="flex items-center gap-2">
      <input :id="`ordered-${block.id}`" type="checkbox" :checked="block.data.ordered"
        class="rounded"
        @change="emit('update', { id: block.id, data: { ordered: $event.target.checked } })" />
      <label :for="`ordered-${block.id}`" class="text-xs text-muted-foreground cursor-pointer">Numbered list</label>
    </div>

    <div>
      <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wide block mb-2">Items</label>
      <div class="space-y-1.5">
        <div v-for="(item, i) in (block.data.items ?? [])" :key="i" class="flex items-center gap-1.5">
          <SelectBox size="sm" :model-value="item.level ?? 1"
            :data="[1,2,3].map(n => ({ value: n, label: `H${n}` }))" class="w-14 shrink-0"
            @update:model-value="v => updateItem(i, 'level', Number(v))" />
          <input :value="item.label" type="text" placeholder="Section title..."
            class="flex-1 rounded-md border bg-background px-2 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-ring"
            @input="updateItem(i, 'label', $event.target.value)" />
          <input :value="item.anchor" type="text" placeholder="#anchor"
            class="w-24 rounded-md border bg-background px-2 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-ring"
            @input="updateItem(i, 'anchor', $event.target.value)" />
          <button type="button" class="text-muted-foreground hover:text-destructive transition-colors p-1 shrink-0" @click="removeItem(i)">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>
      </div>
      <button type="button"
        class="mt-2 w-full py-1.5 text-xs rounded border border-dashed border-border text-muted-foreground hover:border-muted-foreground hover:text-foreground transition-colors"
        @click="addItem">+ Add item</button>
    </div>
  </div>
</template>

<script setup>
import SelectBox from '@/Components/SelectBox.vue'

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
  const items = [...(props.block.data.items ?? []), { label: '', anchor: '', level: 1 }]
  emit('update', { id: props.block.id, data: { items } })
}
</script>
