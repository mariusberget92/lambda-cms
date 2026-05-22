<!-- resources/js/Components/BlockEditor/EditorSectionBlock.vue -->
<template>
  <div class="border-2 border-dashed border-primary/50 rounded-lg p-2 relative min-h-[60px]">
    <span class="absolute top-1 left-1 text-[10px] text-primary font-semibold uppercase tracking-wider select-none">
      {{ block.blockName || 'Section' }}
    </span>

    <VueDraggable
      v-model="localChildren"
      tag="div"
      class="pt-4 min-h-[40px] space-y-1.5"
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
          :class="child.id === selectedId
            ? 'border-primary ring-1 ring-primary'
            : 'border-border hover:border-muted-foreground'"
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
          :class="child.id === selectedId
            ? 'border-primary ring-1 ring-primary'
            : 'border-border hover:border-muted-foreground'"
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
import { GripVertical } from '@lucide/vue'

defineOptions({ name: 'EditorSectionBlock' })

const EditorContainerBlock = defineAsyncComponent(() => import('./EditorContainerBlock.vue'))
const EditorLoopBlock      = defineAsyncComponent(() => import('./EditorLoopBlock.vue'))

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
