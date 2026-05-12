<!-- resources/js/Components/BlockEditor/blocks/BreadcrumbSettings.vue -->
<template>
  <div class="space-y-4">
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Separator</label>
      <div class="flex rounded-md border overflow-hidden text-xs">
        <button v-for="[val, lbl] in [['chevron', '› Chevron'], ['slash', '/ Slash'], ['dot', '· Dot']]" :key="val"
          type="button"
          class="flex-1 py-1.5 transition-colors"
          :class="(block.data.separator ?? 'chevron') === val ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground'"
          @click="emit('update', { id: block.id, data: { separator: val } })">
          {{ lbl }}
        </button>
      </div>
    </div>

    <div>
      <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wide block mb-2">Items</label>
      <div class="space-y-1.5">
        <div v-for="(item, i) in (block.data.items ?? [])" :key="i" class="flex items-center gap-1.5">
          <input :value="item.label" type="text" placeholder="Label"
            class="flex-1 rounded-md border bg-background px-2 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-ring"
            @input="updateItem(i, 'label', $event.target.value)" />
          <input :value="item.url" type="text" placeholder="URL (blank = current)"
            class="flex-1 rounded-md border bg-background px-2 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-ring"
            @input="updateItem(i, 'url', $event.target.value)" />
          <button type="button" class="p-1 text-muted-foreground hover:text-destructive transition-colors shrink-0" @click="removeItem(i)">
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
  const items = [...(props.block.data.items ?? []), { label: '', url: '' }]
  emit('update', { id: props.block.id, data: { items } })
}
</script>
