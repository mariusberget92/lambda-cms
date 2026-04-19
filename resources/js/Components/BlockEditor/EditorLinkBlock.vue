<!-- resources/js/Components/BlockEditor/EditorLinkBlock.vue -->
<template>
  <div class="border border-dashed border-white/20 rounded-lg p-2 relative min-h-[48px]">
    <span class="absolute top-1 left-1 text-[10px] text-white/40 font-semibold uppercase tracking-wider select-none flex items-center gap-1">
      <LinkIcon class="w-3 h-3" />
      {{ block.blockName || (block.data?.url ? block.data.url.slice(0, 30) : 'Link') }}
    </span>

    <VueDraggable
      v-model="localChildren"
      tag="div"
      class="pt-5 min-h-[32px] space-y-1.5"
      :group="{ name: 'canvas' }"
      :animation="150"
      handle=".child-drag-handle"
      ghost-class="opacity-40"
      @add="onAdd"
    >
      <template v-for="child in localChildren" :key="child.id">
        <div
          :id="child.customId || `block-${child.id}`"
          class="group relative flex items-center gap-2 rounded-md border bg-background/50 px-2 py-1.5 cursor-pointer text-xs transition-colors"
          :class="child.id === selectedId
            ? 'border-primary ring-1 ring-primary'
            : 'border-white/10 hover:border-white/25'"
          @click.stop="$emit('select', child.id)"
        >
          <span class="child-drag-handle cursor-grab active:cursor-grabbing text-white/30 shrink-0" @click.stop>
            <GripVertical class="w-3 h-3" />
          </span>
          <span class="text-xs truncate text-white/60">
            {{ child.blockName || LABELS[child.type] || child.type }}
          </span>
        </div>
      </template>

      <div
        v-if="localChildren.length === 0"
        class="text-center py-2 text-xs text-white/25 pointer-events-none"
      >
        Drop blocks inside this link
      </div>
    </VueDraggable>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { VueDraggable } from 'vue-draggable-plus'
import { GripVertical, Link as LinkIcon } from 'lucide-vue-next'

const LABELS = {
  paragraph: 'Paragraph', heading: 'Heading', image: 'Image',
  quote: 'Quote', code: 'Code', gallery: 'Gallery', video: 'Video',
  divider: 'Divider', cta: 'CTA', html: 'HTML',
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
