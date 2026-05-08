<!-- resources/js/Components/BlockEditor/blocks/CounterSettings.vue -->
<template>
  <div class="space-y-3">
    <div class="flex items-center justify-between">
      <label class="text-xs font-medium text-muted-foreground">Stats</label>
      <button type="button" class="text-xs px-2 py-1 rounded-md bg-primary/20 text-primary hover:bg-primary/30 transition-colors" @click="addStat">+ Add stat</button>
    </div>

    <div v-for="(stat, i) in stats" :key="i" class="border border-white/10 rounded-lg p-3 space-y-2">
      <div class="flex items-center justify-between">
        <span class="text-xs font-semibold text-muted-foreground">Stat {{ i + 1 }}</span>
        <button type="button" class="text-xs text-destructive hover:opacity-80" @click="removeStat(i)">Remove</button>
      </div>

      <div class="grid grid-cols-2 gap-2">
        <div>
          <label class="text-xs text-muted-foreground block mb-1">Value</label>
          <input
            :value="stat.value ?? 0"
            type="number"
            class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
            @input="updateStat(i, 'value', Number($event.target.value))"
          />
        </div>
        <div>
          <label class="text-xs text-muted-foreground block mb-1">Label</label>
          <input
            :value="stat.label || ''"
            type="text"
            placeholder="Happy clients"
            class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
            @input="updateStat(i, 'label', $event.target.value)"
          />
        </div>
        <div>
          <label class="text-xs text-muted-foreground block mb-1">Prefix</label>
          <input
            :value="stat.prefix || ''"
            type="text"
            placeholder="$"
            class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
            @input="updateStat(i, 'prefix', $event.target.value)"
          />
        </div>
        <div>
          <label class="text-xs text-muted-foreground block mb-1">Suffix</label>
          <input
            :value="stat.suffix || ''"
            type="text"
            placeholder="+"
            class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
            @input="updateStat(i, 'suffix', $event.target.value)"
          />
        </div>
      </div>

      <div v-if="tab === 'style'">
        <label class="text-xs text-muted-foreground block mb-1">Number color</label>
        <ColorPicker :model-value="stat.color || ''" @update:model-value="v => updateStat(i, 'color', v)" />
      </div>
    </div>

    <div v-if="stats.length === 0" class="text-center py-6 text-xs text-muted-foreground border border-dashed border-white/10 rounded-lg">
      No stats yet. Click "+ Add stat" to start.
    </div>

    <template v-if="tab === 'style'">
      <hr class="border-white/8" />
      <div>
        <label class="text-xs font-medium text-muted-foreground block mb-1">Alignment</label>
        <div class="flex rounded-md border overflow-hidden text-xs">
          <button
            v-for="a in ['left', 'center', 'right']"
            :key="a"
            type="button"
            class="flex-1 py-1.5 capitalize transition-colors"
            :class="(block.data.alignment ?? 'center') === a ? 'bg-primary text-primary-foreground' : 'bg-background text-foreground'"
            @click="emit('update', { id: block.id, data: { alignment: a } })"
          >{{ a }}</button>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import ColorPicker from '../ColorPicker.vue'

const props = defineProps({
  block: { type: Object, required: true },
  tab:   { type: String, default: null },
})
const emit = defineEmits(['update'])

const stats = computed(() => props.block.data?.stats || [])

function addStat() {
  emit('update', { id: props.block.id, data: { stats: [...stats.value, { value: 100, label: '', prefix: '', suffix: '', color: '' }] } })
}
function removeStat(i) {
  emit('update', { id: props.block.id, data: { stats: stats.value.filter((_, idx) => idx !== i) } })
}
function updateStat(i, key, value) {
  const updated = stats.value.map((s, idx) => idx === i ? { ...s, [key]: value } : s)
  emit('update', { id: props.block.id, data: { stats: updated } })
}
</script>
