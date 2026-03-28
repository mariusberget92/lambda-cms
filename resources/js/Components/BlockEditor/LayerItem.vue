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
      <!--
        Handle class depends on nesting depth:
        - Top-level items (.layer-handle)  are grabbed by the outer VueDraggable in BlockLayers.vue
        - Child items (.layer-child-handle) are grabbed by the nested VueDraggable inside LayerItem

        Using the SAME class for both caused both Sortable instances to respond to the same
        mousedown event, making the browser fall back to native HTML5 DnD ("file drag" visual).
      -->
      <span
        :class="[
          isChild ? 'layer-child-handle' : 'layer-handle',
          'cursor-grab active:cursor-grabbing shrink-0',
          block.id === selectedId ? 'text-primary-foreground/60' : 'text-muted-foreground',
        ]"
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

    <!-- Indented children — always rendered for CHILD_CAPABLE blocks (even empty)
         so there is a valid drop target when the container has no children yet.

         Uses handle=".layer-child-handle" (different from the outer VueDraggable's
         ".layer-handle") so the two Sortable instances never both fire on the same event. -->
    <div v-if="CHILD_CAPABLE.has(block.type)" class="pl-4 mt-0.5">
      <VueDraggable
        v-model="localChildren"
        tag="div"
        class="space-y-0.5 min-h-[28px] rounded border border-dashed border-border/40 p-0.5"
        handle=".layer-child-handle"
        ghost-class="opacity-40"
        :group="{ name: 'layers' }"
        :animation="150"
      >
        <div v-for="child in localChildren" :key="child.id">
          <LayerItem
            :block="child"
            :is-child="true"
            :selected-id="selectedId"
            :clipboard="clipboard"
            @select="$emit('select', $event)"
            @remove="$emit('remove', $event)"
            @duplicate="$emit('duplicate', $event)"
            @copy="$emit('copy', $event)"
            @paste="$emit('paste', $event)"
            @update-children="$emit('update-children', $event)"
          />
        </div>
        <div
          v-if="localChildren.length === 0"
          class="text-center py-1.5 text-[10px] text-muted-foreground/40 pointer-events-none select-none"
        >
          Drop here
        </div>
      </VueDraggable>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import { VueDraggable } from 'vue-draggable-plus'
import { GripVertical, X, CopyPlus, Copy, Clipboard } from 'lucide-vue-next'

defineOptions({ name: 'LayerItem' })

const CHILD_CAPABLE = new Set(['container', 'section', 'loop', 'archive-loop'])

const LABELS = {
  paragraph: 'Paragraph', heading: 'Heading', image: 'Image',
  quote: 'Quote', code: 'Code', gallery: 'Gallery', video: 'Video',
  divider: 'Divider', cta: 'CTA', html: 'HTML', component: 'Component',
  container: 'Container', section: 'Section', spacer: 'Spacer', loop: 'Loop',
}

const props = defineProps({
  block:      { type: Object,  required: true },
  selectedId: { type: String,  default: null },
  clipboard:  { type: Object,  default: null },
  // true when this item is rendered inside a parent block's nested VueDraggable.
  // Controls which handle class is used so the outer and inner Sortables never
  // both respond to the same mousedown event.
  isChild:    { type: Boolean, default: false },
})

const emit = defineEmits(['select', 'remove', 'duplicate', 'copy', 'paste', 'update-children'])

// ── Nested children drag-to-reorder / drag-to-nest ────────────────────────────
// Local ref pattern: keeps the getter synchronously in sync with the setter so
// VueDraggable accepts cross-list moves without reverting.
const _children = ref([...(props.block.children ?? [])])
watch(() => props.block.children, (v) => { _children.value = v ?? [] })

const localChildren = computed({
  get: () => _children.value,
  set: (val) => {
    _children.value = val
    emit('update-children', { id: props.block.id, children: val })
  },
})
</script>
