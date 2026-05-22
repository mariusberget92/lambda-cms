<!-- resources/js/Components/BlockEditor/EditorAccordionBlock.vue -->
<template>
  <div class="border border-dashed border-white/20 rounded-lg overflow-hidden">
    <!-- Header -->
    <div class="px-3 py-1.5 border-b border-white/8 bg-white/3 flex items-center justify-between">
      <span class="text-[10px] font-semibold uppercase tracking-wider text-white/40">
        {{ block.blockName || 'Accordion' }} ({{ block.children?.length ?? 0 }} items)
      </span>
    </div>

    <!-- Items -->
    <div class="divide-y divide-white/8">
      <div
        v-for="(item, idx) in localChildren"
        :key="item.id"
        class="group"
      >
        <!-- Item header row -->
        <div
          class="flex items-center gap-2 px-3 py-2 cursor-pointer transition-colors"
          :class="item.id === selectedId
            ? 'bg-primary/10 border-l-2 border-primary'
            : 'hover:bg-white/5'"
          @click.stop="$emit('select', item.id)"
        >
          <GripVertical class="w-3 h-3 text-white/25 shrink-0 cursor-grab" />
          <span class="flex-1 text-xs text-white/60 truncate">
            {{ item.data?.title || `Item ${idx + 1}` }}
          </span>
          <ChevronDown class="w-3 h-3 text-white/25 shrink-0" />
        </div>

        <!-- Item children drop zone -->
        <VueDraggable
          v-model="itemChildren[item.id]"
          tag="div"
          class="pl-6 pr-2 pb-1 min-h-[28px] space-y-1"
          :group="{ name: 'canvas' }"
          :animation="150"
          ghost-class="opacity-40"
          @change="onChildrenChange(item.id)"
        >
          <div
            v-for="child in itemChildren[item.id]"
            :key="child.id"
            class="flex items-center gap-1.5 rounded border border-white/10 bg-background/40 px-2 py-1 text-xs cursor-pointer text-white/50 transition-colors hover:border-white/25"
            :class="child.id === selectedId ? 'border-primary ring-1 ring-primary' : ''"
            @click.stop="$emit('select', child.id)"
          >
            <GripVertical class="w-3 h-3 text-white/20 shrink-0" />
            {{ child.blockName || LABELS[child.type] || child.type }}
          </div>
          <div
            v-if="!itemChildren[item.id]?.length"
            class="text-[10px] text-white/20 py-1 pl-1 pointer-events-none"
          >
            Drop blocks here
          </div>
        </VueDraggable>
      </div>
    </div>

    <!-- Add Item button -->
    <button
      type="button"
      class="w-full px-3 py-2 text-xs text-white/40 hover:text-white/60 hover:bg-white/5 transition-colors flex items-center gap-1.5 border-t border-white/8"
      @click.stop="addItem"
    >
      <Plus class="w-3 h-3" /> Add Item
    </button>
  </div>
</template>

<script setup>
import { ref, computed, watch, reactive } from 'vue'
import { VueDraggable } from 'vue-draggable-plus'
import { GripVertical, ChevronDown, Plus } from '@lucide/vue'

const LABELS = {
  paragraph: 'Paragraph', heading: 'Heading', image: 'Image',
  quote: 'Quote', code: 'Code', gallery: 'Gallery', video: 'Video',
  divider: 'Divider', cta: 'CTA', html: 'HTML', link: 'Link',
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
    syncToParent()
  },
})

const itemChildren = reactive(
  Object.fromEntries((props.block.children ?? []).map(c => [c.id, [...(c.children ?? [])]]))
)

watch(() => props.block.children, (items) => {
  ;(items ?? []).forEach(c => {
    if (!itemChildren[c.id]) itemChildren[c.id] = [...(c.children ?? [])]
  })
})

function syncToParent() {
  const updated = _children.value.map(item => ({
    ...item,
    children: itemChildren[item.id] ?? [],
  }))
  emit('update-children', { id: props.block.id, children: updated })
}

function onChildrenChange() {
  syncToParent()
}

function addItem() {
  const id = crypto.randomUUID()
  const newItem = {
    id,
    type: 'accordion-item',
    data: { title: `Item ${_children.value.length + 1}` },
    customId: '', customClasses: '', customCss: '', fontFamily: '',
    children: [],
  }
  itemChildren[id] = []
  _children.value = [..._children.value, newItem]
  syncToParent()
  emit('select', id)
}
</script>
