<!-- resources/js/Components/BlockEditor/EditorContainerBlock.vue -->
<template>
  <div class="px-3 py-3">
    <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-2">Container</p>

    <VueDraggable
      v-model="localChildren"
      tag="div"
      class="min-h-[60px] rounded-md border-2 border-dashed border-border p-2 space-y-1.5"
      :group="{ name: 'canvas' }"
      :animation="150"
      handle=".child-drag-handle"
      ghost-class="opacity-40"
      @add="onAdd"
    >
      <div
        v-for="child in localChildren"
        :key="child.id"
        class="group relative flex items-center gap-2 rounded-md border bg-background px-2 py-1.5 cursor-pointer text-xs transition-colors"
        :class="child.id === selectedId
          ? 'border-primary ring-1 ring-primary'
          : 'border-border hover:border-muted-foreground'"
        @click.stop="$emit('select', child.id)"
      >
        <span class="child-drag-handle cursor-grab active:cursor-grabbing text-muted-foreground shrink-0" @click.stop>
          <GripVertical class="w-3 h-3" />
        </span>
        <span class="flex-1 truncate text-muted-foreground">{{ LABELS[child.type] ?? child.type }}</span>
      </div>

      <div v-if="localChildren.length === 0"
        class="text-center py-2 text-xs text-muted-foreground/60 pointer-events-none">
        Drop blocks here
      </div>
    </VueDraggable>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { VueDraggable } from 'vue-draggable-plus'
import { GripVertical } from 'lucide-vue-next'

const LABELS = {
  paragraph: 'Paragraph', heading: 'Heading', image: 'Image',
  quote: 'Quote', code: 'Code', gallery: 'Gallery', video: 'Video',
  divider: 'Divider', cta: 'CTA', html: 'HTML', component: 'Component',
  container: 'Container',
}

const props = defineProps({
  block:      { type: Object, required: true },
  selectedId: { type: String, default: null },
})

const emit = defineEmits(['select', 'update-children'])

const localChildren = computed({
  get: () => props.block.children ?? [],
  set: (val) => emit('update-children', { id: props.block.id, children: val }),
})

function onAdd(evt) {
  const newChild = localChildren.value[evt.newIndex]
  if (newChild) emit('select', newChild.id)
}
</script>
