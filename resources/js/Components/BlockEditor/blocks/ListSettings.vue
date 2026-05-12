<!-- resources/js/Components/BlockEditor/blocks/ListSettings.vue -->
<template>
  <div class="space-y-4">
    <!-- Style -->
    <div>
      <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wide block mb-2">List style</label>
      <div class="grid grid-cols-3 gap-1.5">
        <button
          v-for="[val, lbl, preview] in STYLES"
          :key="val"
          type="button"
          class="py-2 px-1 text-xs rounded border transition-colors text-center"
          :class="(block.data.style ?? 'bullet') === val
            ? 'border-primary bg-primary/10 text-primary font-medium'
            : 'border-border bg-background hover:border-muted-foreground text-muted-foreground'"
          @click="emit('update', { id: block.id, data: { style: val } })"
        >
          <div class="text-base mb-0.5">{{ preview }}</div>
          <div>{{ lbl }}</div>
        </button>
      </div>
    </div>

    <!-- Spacing -->
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Item spacing</label>
      <div class="flex rounded-md border overflow-hidden text-xs">
        <button v-for="[val, lbl] in [['compact', 'Compact'], ['normal', 'Normal'], ['loose', 'Loose']]" :key="val"
          type="button"
          class="flex-1 py-1.5 transition-colors"
          :class="(block.data.spacing ?? 'normal') === val ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground'"
          @click="emit('update', { id: block.id, data: { spacing: val } })">
          {{ lbl }}
        </button>
      </div>
    </div>

    <!-- Items -->
    <div>
      <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wide block mb-2">Items</label>
      <div class="space-y-1.5">
        <div
          v-for="(item, i) in (block.data.items ?? [])"
          :key="i"
          class="flex items-center gap-1.5"
        >
          <input
            :value="item"
            type="text"
            placeholder="List item..."
            class="flex-1 rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
            @input="updateItem(i, $event.target.value)"
          />
          <button type="button"
            class="p-1 text-muted-foreground hover:text-destructive transition-colors rounded"
            @click="removeItem(i)"
            title="Remove item">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>
      </div>
      <button type="button"
        class="mt-2 w-full py-1.5 text-xs rounded border border-dashed border-border text-muted-foreground hover:border-muted-foreground hover:text-foreground transition-colors"
        @click="addItem">
        + Add item
      </button>
    </div>
  </div>
</template>

<script setup>
const STYLES = [
  ['bullet',   'Bullet',   '•'],
  ['numbered', 'Numbered', '1.'],
  ['check',    'Check',    '✓'],
  ['arrow',    'Arrow',    '→'],
  ['x',        'X mark',  '✕'],
  ['none',     'None',     '—'],
]

const props = defineProps({
  block: { type: Object, required: true },
  tab:   { type: String, default: null },
})
const emit = defineEmits(['update'])

function updateItem(i, value) {
  const items = [...(props.block.data.items ?? [])]
  items[i] = value
  emit('update', { id: props.block.id, data: { items } })
}

function removeItem(i) {
  const items = [...(props.block.data.items ?? [])]
  items.splice(i, 1)
  emit('update', { id: props.block.id, data: { items } })
}

function addItem() {
  const items = [...(props.block.data.items ?? []), '']
  emit('update', { id: props.block.id, data: { items } })
}
</script>
