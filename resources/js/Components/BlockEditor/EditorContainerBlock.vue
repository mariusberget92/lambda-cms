<!-- resources/js/Components/BlockEditor/EditorContainerBlock.vue -->
<template>
  <div class="px-3 py-3">
    <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-2">
      {{ block.blockName || 'Container' }}
    </p>

    <VueDraggable
      v-model="localChildren"
      tag="div"
      :class="draggableClass"
      :group="{ name: 'canvas' }"
      :animation="150"
      handle=".child-drag-handle"
      ghost-class="opacity-40"
      @add="onAdd"
    >
      <template v-for="child in localChildren" :key="child.id">
        <!-- Nestable child: render as full recursive editor -->
        <div
          v-if="isNestable(child.type)"
          :id="child.customId || `block-${child.id}`"
          class="rounded-md border bg-background/50 overflow-hidden transition-colors"
          :class="[
            child.id === selectedId
              ? 'border-primary ring-1 ring-primary'
              : 'border-border hover:border-muted-foreground',
            isFlexRow ? 'flex-1 min-w-0' : '',
          ]"
        >
          <!-- Header row: drag handle + label -->
          <div
            class="flex items-center gap-2 px-2 py-1.5 border-b border-border/30 cursor-pointer"
            @click.stop="$emit('select', child.id)"
          >
            <span class="child-drag-handle cursor-grab active:cursor-grabbing text-muted-foreground shrink-0" @click.stop>
              <GripVertical class="w-3 h-3" />
            </span>
            <span class="flex-1 text-[10px] font-semibold uppercase tracking-wider text-muted-foreground truncate">
              {{ child.blockName || LABELS[child.type] || child.type }}
            </span>
          </div>
          <!-- Recursive editor -->
          <EditorContainerBlock
            v-if="child.type === 'container'"
            :block="child"
            :selected-id="selectedId"
            @select="$emit('select', $event)"
            @update-children="$emit('update-children', $event)"
          />
          <EditorSectionBlock
            v-else-if="child.type === 'section'"
            :block="child"
            :selected-id="selectedId"
            @select="$emit('select', $event)"
            @update-children="$emit('update-children', $event)"
          />
          <EditorLoopBlock
            v-else-if="child.type === 'loop' || child.type === 'archive-loop'"
            :block="child"
            :selected-id="selectedId"
            @select="$emit('select', $event)"
            @update-children="$emit('update-children', $event)"
          />
        </div>

        <!-- Leaf child: pill -->
        <div
          v-else
          :id="child.customId || `block-${child.id}`"
          class="group relative flex items-center gap-2 rounded-md border bg-background px-2 py-1.5 cursor-pointer text-xs transition-colors"
          :class="[
            child.id === selectedId
              ? 'border-primary ring-1 ring-primary'
              : 'border-border hover:border-muted-foreground',
            isFlexRow ? 'flex-1 min-w-0' : '',
          ]"
          @click.stop="$emit('select', child.id)"
        >
          <span class="child-drag-handle cursor-grab active:cursor-grabbing text-muted-foreground shrink-0" @click.stop>
            <GripVertical class="w-3 h-3" />
          </span>
          <div class="flex-1 min-w-0 overflow-hidden">
            <span class="text-xs block truncate leading-none">
              {{ child.blockName || LABELS[child.type] || child.type }}
            </span>
            <span v-if="child.blockName" class="text-[10px] text-muted-foreground/50 leading-none mt-0.5 block">
              {{ LABELS[child.type] || child.type }}
            </span>
          </div>
        </div>
      </template>

      <div v-if="localChildren.length === 0"
        class="text-center py-2 text-xs text-muted-foreground/60 pointer-events-none">
        Drop blocks here
      </div>
    </VueDraggable>
  </div>
</template>

<script setup>
import { computed, defineAsyncComponent, ref, watch } from 'vue'
import { VueDraggable } from 'vue-draggable-plus'
import { GripVertical } from 'lucide-vue-next'

defineOptions({ name: 'EditorContainerBlock' })

const EditorSectionBlock = defineAsyncComponent(() => import('./EditorSectionBlock.vue'))
const EditorLoopBlock    = defineAsyncComponent(() => import('./EditorLoopBlock.vue'))

const NESTABLE = ['container', 'section', 'loop', 'archive-loop']
function isNestable(type) { return NESTABLE.includes(type) }

const LABELS = {
  paragraph: 'Paragraph', heading: 'Heading', image: 'Image',
  quote: 'Quote', code: 'Code', gallery: 'Gallery', video: 'Video',
  divider: 'Divider', cta: 'CTA', html: 'HTML', component: 'Component',
  container: 'Container', section: 'Section', spacer: 'Spacer', loop: 'Loop',
  'archive-loop': 'Archive Loop',
}

const props = defineProps({
  block:      { type: Object, required: true },
  selectedId: { type: String, default: null },
})

const emit = defineEmits(['select', 'update-children'])

const mode = computed(() => props.block.data?.mode ?? 'flex')

const isFlexRow = computed(() => {
  if (mode.value !== 'flex') return false
  const dir = props.block.data?.direction
  const defaultDir = typeof dir === 'object' ? (dir?.default ?? 'row') : (dir ?? 'row')
  return defaultDir !== 'column'
})

// Mirror the actual flex/grid layout in the editor so the preview matches the live page
const draggableClass = computed(() => {
  const base = 'min-h-[40px] rounded-md border-2 border-dashed border-border p-2'
  if (isFlexRow.value) return `${base} flex flex-row flex-wrap gap-2`
  return `${base} space-y-1.5`
})

const _children = ref([...(props.block.children ?? [])])
watch(() => props.block.children, (v) => { _children.value = v ?? [] })

const localChildren = computed({
  get: () => _children.value,
  set: (val) => {
    _children.value = val
    emit('update-children', { id: props.block.id, children: val })
  },
})

function onAdd(evt) {
  const newChild = localChildren.value[evt.newIndex]
  if (newChild) emit('select', newChild.id)
}
</script>
