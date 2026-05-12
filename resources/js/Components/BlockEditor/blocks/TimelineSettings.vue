<!-- resources/js/Components/BlockEditor/blocks/TimelineSettings.vue -->
<template>
  <!-- Content tab -->
  <div v-show="!tab || tab === 'content'" class="space-y-3">
    <div>
      <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wide block mb-2">Items</label>
      <div class="space-y-2">
        <div v-for="(item, i) in (block.data.items ?? [])" :key="i" class="rounded border border-border p-2 space-y-1.5">
          <div class="flex items-center justify-between mb-1">
            <span class="text-[10px] font-semibold text-muted-foreground uppercase">Item {{ i + 1 }}</span>
            <button type="button" class="text-muted-foreground hover:text-destructive transition-colors p-1" @click="removeItem(i)">
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
          </div>
          <input :value="item.date" type="text" placeholder="Date / Period (e.g. 2020)"
            class="w-full rounded-md border bg-background px-2 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-ring"
            @input="updateItem(i, 'date', $event.target.value)" />
          <input :value="item.title" type="text" placeholder="Title..."
            class="w-full rounded-md border bg-background px-2 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-ring"
            @input="updateItem(i, 'title', $event.target.value)" />
          <textarea :value="item.description" rows="2" placeholder="Description..."
            class="w-full rounded-md border bg-background px-2 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-ring resize-none"
            @input="updateItem(i, 'description', $event.target.value)" />
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
      <label class="text-xs font-medium text-muted-foreground block mb-1">Layout</label>
      <div class="flex rounded-md border overflow-hidden text-xs">
        <button v-for="[val, lbl] in [['vertical', 'Vertical'], ['horizontal', 'Horizontal']]" :key="val"
          type="button"
          class="flex-1 py-1.5 transition-colors"
          :class="(block.data.layout ?? 'vertical') === val ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground'"
          @click="emit('update', { id: block.id, data: { layout: val } })">
          {{ lbl }}
        </button>
      </div>
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
  const items = [...(props.block.data.items ?? []), { date: '', title: '', description: '' }]
  emit('update', { id: props.block.id, data: { items } })
}
</script>
