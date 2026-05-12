<!-- resources/js/Components/BlockEditor/blocks/StatsSettings.vue -->
<template>
  <!-- Content tab -->
  <div v-show="!tab || tab === 'content'" class="space-y-3">
    <div>
      <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wide block mb-2">Stats</label>
      <div class="space-y-2">
        <div v-for="(item, i) in (block.data.items ?? [])" :key="i" class="rounded border border-border p-2 space-y-1.5">
          <div class="flex items-center gap-1.5">
            <input :value="item.value" type="text" placeholder="Value (e.g. 99)"
              class="flex-1 rounded-md border bg-background px-2 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-ring"
              @input="updateItem(i, 'value', $event.target.value)" />
            <button type="button" class="text-muted-foreground hover:text-destructive transition-colors p-1 shrink-0" @click="removeItem(i)">
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
          </div>
          <div class="flex gap-1.5">
            <input :value="item.prefix" type="text" placeholder="Prefix"
              class="w-16 rounded-md border bg-background px-2 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-ring"
              @input="updateItem(i, 'prefix', $event.target.value)" />
            <input :value="item.suffix" type="text" placeholder="Suffix"
              class="w-16 rounded-md border bg-background px-2 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-ring"
              @input="updateItem(i, 'suffix', $event.target.value)" />
            <input :value="item.label" type="text" placeholder="Label"
              class="flex-1 rounded-md border bg-background px-2 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-ring"
              @input="updateItem(i, 'label', $event.target.value)" />
          </div>
          <input :value="item.description" type="text" placeholder="Description (optional)"
            class="w-full rounded-md border bg-background px-2 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-ring"
            @input="updateItem(i, 'description', $event.target.value)" />
        </div>
      </div>
      <button type="button"
        class="mt-2 w-full py-1.5 text-xs rounded border border-dashed border-border text-muted-foreground hover:border-muted-foreground hover:text-foreground transition-colors"
        @click="addItem">+ Add stat</button>
    </div>
  </div>

  <!-- Style tab -->
  <div v-show="!tab || tab === 'style'" class="space-y-3">
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Layout</label>
      <div class="flex rounded-md border overflow-hidden text-xs">
        <button v-for="[val, lbl] in [['row', 'Row'], ['grid', 'Grid']]" :key="val"
          type="button"
          class="flex-1 py-1.5 transition-colors"
          :class="(block.data.layout ?? 'row') === val ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground'"
          @click="emit('update', { id: block.id, data: { layout: val } })">
          {{ lbl }}
        </button>
      </div>
    </div>
    <div v-if="(block.data.layout ?? 'row') === 'grid'">
      <label class="text-xs font-medium text-muted-foreground block mb-1">Columns</label>
      <SelectBox size="sm"
        :model-value="block.data.columns ?? 3"
        :data="[2,3,4].map(n => ({ value: n, label: `${n} columns` }))"
        @update:model-value="v => emit('update', { id: block.id, data: { columns: Number(v) } })" />
    </div>
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Alignment</label>
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
      <label class="text-xs font-medium text-muted-foreground block mb-1">Value font size</label>
      <input :value="block.data.valueSize ?? '2.5rem'" type="text" placeholder="2.5rem"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @change="emit('update', { id: block.id, data: { valueSize: $event.target.value || '2.5rem' } })" />
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
  const items = [...(props.block.data.items ?? []), { value: '0', label: '', prefix: '', suffix: '', description: '' }]
  emit('update', { id: props.block.id, data: { items } })
}
</script>
