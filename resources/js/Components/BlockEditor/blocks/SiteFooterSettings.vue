<template>
  <div v-show="!tab || tab === 'content'" class="space-y-4">

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Tagline</label>
      <input
        :value="block.data.tagline"
        type="text"
        placeholder="A short description…"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { tagline: $event.target.value } })"
      />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Copyright</label>
      <input
        :value="block.data.copyright"
        type="text"
        placeholder="© 2025 Site Name (auto-generated if blank)"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { copyright: $event.target.value } })"
      />
    </div>

    <div class="flex items-center justify-between">
      <label class="text-xs font-medium text-muted-foreground">Show RSS / Sitemap links</label>
      <button
        type="button"
        class="relative inline-flex h-5 w-9 rounded-full border-2 border-transparent transition-colors"
        :class="block.data.showRss !== false ? 'bg-primary' : 'bg-muted'"
        @click="emit('update', { id: block.id, data: { showRss: block.data.showRss === false } })"
      >
        <span
          class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform"
          :class="block.data.showRss !== false ? 'translate-x-4' : 'translate-x-0'"
        />
      </button>
    </div>

    <!-- Link columns -->
    <div>
      <div class="flex items-center justify-between mb-2">
        <label class="text-xs font-medium text-muted-foreground">Link columns</label>
        <button
          type="button"
          class="text-[11px] text-primary hover:underline"
          @click="addColumn"
        >+ Add column</button>
      </div>

      <div class="space-y-3">
        <div v-for="(col, ci) in columns" :key="ci" class="rounded-md border bg-background/40 p-2 space-y-1.5">
          <div class="flex items-center gap-1.5">
            <input
              :value="col.heading"
              type="text"
              placeholder="Heading"
              class="flex-1 rounded border bg-background px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-ring"
              @input="updateColumn(ci, 'heading', $event.target.value)"
            />
            <button type="button" class="text-muted-foreground hover:text-destructive text-xs" @click="removeColumn(ci)">✕</button>
          </div>
          <div v-for="(link, li) in (col.links ?? [])" :key="li" class="flex items-center gap-1.5 pl-2">
            <input
              :value="link.label"
              type="text"
              placeholder="Label"
              class="w-24 rounded border bg-background px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-ring"
              @input="updateLink(ci, li, 'label', $event.target.value)"
            />
            <input
              :value="link.url"
              type="text"
              placeholder="URL"
              class="flex-1 rounded border bg-background px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-ring"
              @input="updateLink(ci, li, 'url', $event.target.value)"
            />
            <button type="button" class="text-muted-foreground hover:text-destructive text-xs" @click="removeLink(ci, li)">✕</button>
          </div>
          <button type="button" class="text-[11px] text-primary hover:underline pl-2" @click="addLink(ci)">+ Add link</button>
        </div>
      </div>
      <p v-if="!columns.length" class="text-xs text-muted-foreground/60 italic mt-1">No columns yet.</p>
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

const columns = computed(() => props.block.data?.columns ?? [])

function addColumn() {
  emit('update', { id: props.block.id, data: { columns: [...columns.value, { heading: '', links: [] }] } })
}
function removeColumn(ci) {
  emit('update', { id: props.block.id, data: { columns: columns.value.filter((_, i) => i !== ci) } })
}
function updateColumn(ci, key, val) {
  const updated = columns.value.map((c, i) => i === ci ? { ...c, [key]: val } : c)
  emit('update', { id: props.block.id, data: { columns: updated } })
}
function addLink(ci) {
  const updated = columns.value.map((c, i) => i === ci ? { ...c, links: [...(c.links ?? []), { label: '', url: '' }] } : c)
  emit('update', { id: props.block.id, data: { columns: updated } })
}
function removeLink(ci, li) {
  const updated = columns.value.map((c, i) => i === ci ? { ...c, links: c.links.filter((_, j) => j !== li) } : c)
  emit('update', { id: props.block.id, data: { columns: updated } })
}
function updateLink(ci, li, key, val) {
  const updated = columns.value.map((c, i) => i === ci
    ? { ...c, links: c.links.map((l, j) => j === li ? { ...l, [key]: val } : l) }
    : c
  )
  emit('update', { id: props.block.id, data: { columns: updated } })
}
</script>
