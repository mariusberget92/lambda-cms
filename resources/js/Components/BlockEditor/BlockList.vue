<!-- resources/js/Components/BlockEditor/BlockList.vue -->
<template>
  <div class="w-44 shrink-0 border-r flex flex-col bg-sidebar">
    <!-- Block list header -->
    <div class="px-3 py-2 border-b">
      <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Blocks</p>
    </div>

    <!-- Draggable list -->
    <VueDraggable
      v-model="draggableBlocks"
      class="flex-1 overflow-y-auto p-2 space-y-1"
      handle=".drag-handle"
      :animation="150"
    >
      <div
        v-for="block in draggableBlocks"
        :key="block.id"
        class="flex items-center gap-1 rounded-md px-2 py-1.5 cursor-pointer text-sm transition-colors"
        :class="block.id === selectedId
          ? 'bg-primary text-primary-foreground'
          : 'hover:bg-accent text-foreground'"
        @click="$emit('select', block.id)"
      >
        <span class="drag-handle text-muted-foreground cursor-grab active:cursor-grabbing mr-1 shrink-0" @click.stop>
          <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
            <path d="M7 2a1 1 0 011 1v1a1 1 0 01-2 0V3a1 1 0 011-1zm6 0a1 1 0 011 1v1a1 1 0 01-2 0V3a1 1 0 011-1zM7 8a1 1 0 011 1v1a1 1 0 01-2 0V9a1 1 0 011-1zm6 0a1 1 0 011 1v1a1 1 0 01-2 0V9a1 1 0 011-1zM7 14a1 1 0 011 1v1a1 1 0 01-2 0v-1a1 1 0 011-1zm6 0a1 1 0 011 1v1a1 1 0 01-2 0v-1a1 1 0 011-1z"/>
          </svg>
        </span>
        <span class="flex-1 truncate text-xs">{{ blockLabel(block.type) }}</span>
        <button
          type="button"
          class="shrink-0 opacity-50 hover:opacity-100 transition-opacity"
          :class="block.id === selectedId ? 'text-primary-foreground' : 'text-muted-foreground'"
          @click.stop="$emit('remove', block.id)"
          title="Remove block"
        >
          <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>
    </VueDraggable>

    <!-- Add block button -->
    <div class="p-2 border-t relative">
      <button
        type="button"
        class="w-full rounded-md border border-dashed border-border px-2 py-1.5 text-xs text-muted-foreground hover:border-primary hover:text-primary transition-colors flex items-center justify-center gap-1"
        @click="showPicker = !showPicker"
      >
        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        Add block
      </button>

      <!-- Block type picker popover -->
      <div
        v-if="showPicker"
        class="absolute bottom-full left-0 mb-1 w-56 rounded-lg border bg-popover shadow-lg p-2 z-50"
      >
        <p class="text-xs font-semibold text-muted-foreground mb-2 px-1">Choose block type</p>
        <div class="grid grid-cols-2 gap-1">
          <button
            v-for="btype in availableTypes"
            :key="btype.type"
            type="button"
            class="flex items-center gap-1.5 rounded-md px-2 py-1.5 text-xs hover:bg-accent transition-colors text-left"
            @click="pickType(btype.type)"
          >
            <span>{{ btype.icon }}</span>
            <span>{{ btype.label }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { VueDraggable } from 'vue-draggable-plus'

const props = defineProps({
  blocks:     { type: Array,   default: () => [] },
  selectedId: { type: String,  default: null },
  isAdmin:    { type: Boolean, default: false },
})

const emit = defineEmits(['select', 'add', 'remove', 'reorder'])

const showPicker = ref(false)

// Wrap as writable computed so VueDraggable can mutate it via its v-model setter
const draggableBlocks = computed({
  get: () => props.blocks,
  set: (val) => emit('reorder', val),
})

const ALL_TYPES = [
  { type: 'paragraph', label: 'Paragraph',  icon: '¶' },
  { type: 'heading',   label: 'Heading',    icon: 'H' },
  { type: 'image',     label: 'Image',      icon: '🖼' },
  { type: 'quote',     label: 'Quote',      icon: '"' },
  { type: 'code',      label: 'Code',       icon: '</>' },
  { type: 'gallery',   label: 'Gallery',    icon: '⬛' },
  { type: 'video',     label: 'Video',      icon: '▶' },
  { type: 'divider',   label: 'Divider',    icon: '—' },
  { type: 'cta',       label: 'CTA',        icon: '📢' },
  { type: 'html',      label: 'HTML',       icon: '{}', adminOnly: true },
]

const availableTypes = computed(() =>
  ALL_TYPES.filter(t => !t.adminOnly || props.isAdmin)
)

const LABELS = Object.fromEntries(ALL_TYPES.map(t => [t.type, t.label]))

function blockLabel(type) {
  return LABELS[type] ?? type
}

function pickType(type) {
  showPicker.value = false
  emit('add', type)
}
</script>
