<!-- resources/js/Components/BlockEditor/BlockTypePanel.vue -->
<template>
  <div class="w-44 shrink-0 border-r flex flex-col bg-sidebar">
    <div class="px-3 py-2 border-b shrink-0">
      <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Add Block</p>
    </div>

    <VueDraggable
      v-model="typeList"
      tag="div"
      class="flex-1 overflow-y-auto p-2 grid grid-cols-2 gap-1.5 content-start"
      :group="{ name: 'canvas', pull: 'clone', put: false }"
      :sort="false"
      :clone="cloneBlock"
      :animation="150"
    >
      <div
        v-for="btype in typeList"
        :key="btype.type"
        class="flex flex-col items-center gap-1 rounded-md border border-border bg-background px-2 py-2.5 text-xs cursor-grab active:cursor-grabbing hover:border-primary hover:text-primary transition-colors select-none"
      >
        <span class="text-base leading-none">{{ btype.icon }}</span>
        <span>{{ btype.label }}</span>
      </div>
    </VueDraggable>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { VueDraggable } from 'vue-draggable-plus'

const props = defineProps({
  isAdmin: { type: Boolean, default: false },
})

const ALL_TYPES = [
  { type: 'paragraph', label: 'Paragraph', icon: '¶' },
  { type: 'heading',   label: 'Heading',   icon: 'H' },
  { type: 'image',     label: 'Image',     icon: '🖼' },
  { type: 'quote',     label: 'Quote',     icon: '"' },
  { type: 'code',      label: 'Code',      icon: '</>' },
  { type: 'gallery',   label: 'Gallery',   icon: '⬛' },
  { type: 'video',     label: 'Video',     icon: '▶' },
  { type: 'divider',   label: 'Divider',   icon: '—' },
  { type: 'cta',       label: 'CTA',       icon: '📢' },
  { type: 'html',      label: 'HTML',      icon: '{}', adminOnly: true },
  { type: 'component', label: 'Component', icon: '⚙️' },
]

const typeList = computed({
  get: () => ALL_TYPES.filter(t => !t.adminOnly || props.isAdmin),
  set: () => {}, // source list never mutated (clone mode + sort:false)
})

const DEFAULT_DATA = {
  paragraph: { content: '' },
  heading:   { level: 2, text: '' },
  image:     { media_id: null, url: '', caption: '', alt: '' },
  quote:     { text: '', attribution: '' },
  code:      { code: '', language: 'javascript' },
  gallery:   { items: [] },
  video:     { url: '', caption: '' },
  divider:   { style: 'line' },
  cta:       { headline: '', text: '', button_label: '', button_url: '' },
  html:      { content: '' },
  component: { component: 'post-list', limit: 6, offset: 0, order: 'latest', featured_only: false, category_ids: [], tag_ids: [] },
}

function cloneBlock(typeDef) {
  const id = typeof crypto !== 'undefined' && crypto.randomUUID
    ? crypto.randomUUID()
    : Math.random().toString(36).slice(2) + Date.now().toString(36)
  return { id, type: typeDef.type, data: { ...(DEFAULT_DATA[typeDef.type] ?? {}) } }
}
</script>
