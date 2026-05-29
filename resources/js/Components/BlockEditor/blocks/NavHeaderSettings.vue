<template>
  <div v-show="!tab || tab === 'content'" class="space-y-3">

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Logo text <span class="font-normal">(leave blank to use site name)</span></label>
      <input
        :value="block.data.logoText"
        type="text"
        placeholder="Uses site name by default"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { logoText: $event.target.value } })"
      />
    </div>

    <div class="flex items-center justify-between">
      <label class="text-xs font-medium text-muted-foreground">Show search (⌘K)</label>
      <button
        type="button"
        class="relative inline-flex h-5 w-9 rounded-full border-2 border-transparent transition-colors"
        :class="block.data.showSearch !== false ? 'bg-primary' : 'bg-muted'"
        @click="emit('update', { id: block.id, data: { showSearch: block.data.showSearch === false } })"
      >
        <span
          class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform"
          :class="block.data.showSearch !== false ? 'translate-x-4' : 'translate-x-0'"
        />
      </button>
    </div>

    <div class="flex items-center justify-between">
      <label class="text-xs font-medium text-muted-foreground">Sticky navbar</label>
      <button
        type="button"
        class="relative inline-flex h-5 w-9 rounded-full border-2 border-transparent transition-colors"
        :class="block.data.sticky !== false ? 'bg-primary' : 'bg-muted'"
        @click="emit('update', { id: block.id, data: { sticky: block.data.sticky === false } })"
      >
        <span
          class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform"
          :class="block.data.sticky !== false ? 'translate-x-4' : 'translate-x-0'"
        />
      </button>
    </div>

    <!-- Nav links -->
    <div>
      <div class="flex items-center justify-between mb-2">
        <label class="text-xs font-medium text-muted-foreground">Nav links</label>
        <button type="button" class="text-[11px] text-primary hover:underline" @click="addLink">+ Add link</button>
      </div>
      <div class="space-y-1.5">
        <div v-for="(link, i) in links" :key="i" class="flex items-center gap-1.5">
          <input
            :value="link.label"
            type="text"
            placeholder="Label"
            class="w-24 rounded border bg-background px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-ring"
            @input="updateLink(i, 'label', $event.target.value)"
          />
          <input
            :value="link.url"
            type="text"
            placeholder="URL or /path"
            class="flex-1 rounded border bg-background px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-ring"
            @input="updateLink(i, 'url', $event.target.value)"
          />
          <button type="button" class="text-muted-foreground hover:text-destructive text-xs shrink-0" @click="removeLink(i)">✕</button>
        </div>
      </div>
      <p v-if="!links.length" class="text-xs text-muted-foreground/60 italic mt-1">No links yet.</p>
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

const links = computed(() => props.block.data?.links ?? [])

function addLink() {
  emit('update', { id: props.block.id, data: { links: [...links.value, { label: '', url: '' }] } })
}
function removeLink(i) {
  emit('update', { id: props.block.id, data: { links: links.value.filter((_, j) => j !== i) } })
}
function updateLink(i, key, val) {
  emit('update', { id: props.block.id, data: { links: links.value.map((l, j) => j === i ? { ...l, [key]: val } : l) } })
}
</script>
