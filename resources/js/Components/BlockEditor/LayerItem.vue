<!-- resources/js/Components/BlockEditor/LayerItem.vue -->
<template>
  <div>
    <!-- Layer row -->
    <div
      class="group flex items-center gap-1.5 rounded-md px-1.5 py-1.5 cursor-pointer transition-colors text-xs"
      :class="block.id === selectedId
        ? 'bg-primary text-primary-foreground'
        : 'hover:bg-accent text-foreground'"
      @click="$emit('select', block.id)"
    >
      <span
        class="layer-handle cursor-grab active:cursor-grabbing shrink-0"
        :class="block.id === selectedId ? 'text-primary-foreground/60' : 'text-muted-foreground'"
        @click.stop
      >
        <GripVertical class="w-3 h-3" />
      </span>

      <span class="flex-1 truncate">{{ block.blockName || LABELS[block.type] || block.type }}</span>

      <!-- Duplicate -->
      <button
        type="button"
        class="shrink-0 opacity-0 group-hover:opacity-60 hover:!opacity-100 transition-opacity"
        :class="block.id === selectedId ? 'text-primary-foreground' : 'text-muted-foreground'"
        title="Duplicate (Ctrl+D)"
        @click.stop="$emit('duplicate', block.id)"
      >
        <CopyPlus class="w-3 h-3" />
      </button>

      <!-- Copy -->
      <button
        type="button"
        class="shrink-0 opacity-0 group-hover:opacity-60 hover:!opacity-100 transition-opacity"
        :class="block.id === selectedId ? 'text-primary-foreground' : 'text-muted-foreground'"
        title="Copy (Ctrl+C)"
        @click.stop="$emit('copy', block.id)"
      >
        <Copy class="w-3 h-3" />
      </button>

      <!-- Paste inside (only on container-capable blocks when clipboard has content) -->
      <button
        v-if="CHILD_CAPABLE.has(block.type) && clipboard"
        type="button"
        class="shrink-0 opacity-0 group-hover:opacity-60 hover:!opacity-100 transition-opacity"
        :class="block.id === selectedId ? 'text-primary-foreground' : 'text-muted-foreground'"
        title="Paste inside (Ctrl+V)"
        @click.stop="$emit('paste', block.id)"
      >
        <Clipboard class="w-3 h-3" />
      </button>

      <!-- Remove -->
      <button
        type="button"
        class="shrink-0 opacity-50 hover:opacity-100 transition-opacity"
        :class="block.id === selectedId ? 'text-primary-foreground' : 'text-muted-foreground'"
        title="Remove block"
        @click.stop="$emit('remove', block.id)"
      >
        <X class="w-3 h-3" />
      </button>
    </div>

    <!-- Indented children — works for container, section, loop and any future parent -->
    <ul v-if="block.children?.length" class="pl-4 space-y-0.5 mt-0.5">
      <li v-for="child in block.children" :key="child.id">
        <LayerItem
          :block="child"
          :selected-id="selectedId"
          :clipboard="clipboard"
          @select="$emit('select', $event)"
          @remove="$emit('remove', $event)"
          @duplicate="$emit('duplicate', $event)"
          @copy="$emit('copy', $event)"
          @paste="$emit('paste', $event)"
        />
      </li>
    </ul>
  </div>
</template>

<script setup>
import { GripVertical, X, CopyPlus, Copy, Clipboard } from 'lucide-vue-next'

defineOptions({ name: 'LayerItem' })

const CHILD_CAPABLE = new Set(['container', 'section', 'loop', 'archive-loop'])

const LABELS = {
  paragraph: 'Paragraph', heading: 'Heading', image: 'Image',
  quote: 'Quote', code: 'Code', gallery: 'Gallery', video: 'Video',
  divider: 'Divider', cta: 'CTA', html: 'HTML', component: 'Component',
  container: 'Container', section: 'Section', spacer: 'Spacer', loop: 'Loop',
}

defineProps({
  block:      { type: Object, required: true },
  selectedId: { type: String, default: null },
  clipboard:  { type: Object, default: null },
})

defineEmits(['select', 'remove', 'duplicate', 'copy', 'paste'])
</script>
