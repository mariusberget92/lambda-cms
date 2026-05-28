<template>
  <div v-show="!tab || tab === 'content'" class="space-y-3">
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Eyebrow <span class="font-normal">(mono, top-left)</span></label>
      <input
        :value="block.data.eyebrow"
        type="text"
        placeholder="// engineering-notes"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { eyebrow: $event.target.value } })"
      />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Accent word <span class="font-normal">(mono, top-right)</span></label>
      <input
        :value="block.data.accentWord"
        type="text"
        placeholder="v1.0.0"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { accentWord: $event.target.value } })"
      />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">
        Title
        <span class="font-normal text-muted-foreground/70"> — wrap a word in ||pipes|| for mono accent</span>
      </label>
      <input
        :value="block.data.title"
        type="text"
        placeholder="Engineering notes from a ||runtime||"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { title: $event.target.value } })"
      />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Subtitle</label>
      <textarea
        :value="block.data.subtitle"
        rows="2"
        placeholder="Modern tooling, architecture, and the craft of building at scale."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring resize-none"
        @input="emit('update', { id: block.id, data: { subtitle: $event.target.value } })"
      />
    </div>

    <!-- Stat rows -->
    <div>
      <div class="flex items-center justify-between mb-1.5">
        <label class="text-xs font-medium text-muted-foreground">Stats</label>
        <button
          type="button"
          class="text-[11px] text-primary hover:underline"
          @click="addStat"
        >+ Add stat</button>
      </div>
      <div class="space-y-1.5">
        <div
          v-for="(stat, idx) in stats"
          :key="idx"
          class="flex items-center gap-2"
        >
          <input
            :value="stat.value"
            type="text"
            placeholder="2.4K"
            class="w-16 rounded-md border bg-background px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-ring font-mono"
            @input="updateStat(idx, 'value', $event.target.value)"
          />
          <input
            :value="stat.label"
            type="text"
            placeholder="articles"
            class="flex-1 rounded-md border bg-background px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
            @input="updateStat(idx, 'label', $event.target.value)"
          />
          <button
            type="button"
            class="text-muted-foreground hover:text-destructive transition-colors text-xs"
            @click="removeStat(idx)"
          >✕</button>
        </div>
      </div>
      <p v-if="!stats.length" class="text-xs text-muted-foreground/60 italic mt-1">No stats — add some above.</p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  block: { type: Object, required: true },
  tab:   { type: String, default: null },
})
const emit = defineEmits(['update'])

const stats = computed(() => props.block.data?.stats ?? [])

function addStat() {
  emit('update', { id: props.block.id, data: { stats: [...stats.value, { value: '', label: '' }] } })
}
function removeStat(idx) {
  emit('update', { id: props.block.id, data: { stats: stats.value.filter((_, i) => i !== idx) } })
}
function updateStat(idx, key, val) {
  const updated = stats.value.map((s, i) => i === idx ? { ...s, [key]: val } : s)
  emit('update', { id: props.block.id, data: { stats: updated } })
}
</script>
