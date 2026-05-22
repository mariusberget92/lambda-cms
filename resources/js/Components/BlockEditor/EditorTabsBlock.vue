<!-- resources/js/Components/BlockEditor/EditorTabsBlock.vue -->
<template>
  <div class="border border-dashed border-white/20 rounded-lg overflow-hidden">
    <!-- Mock tab bar -->
    <div class="flex items-center gap-0 border-b border-white/8 bg-white/3 overflow-x-auto">
      <button
        v-for="(item, i) in localChildren"
        :key="item.id"
        type="button"
        class="px-3 py-2 text-xs whitespace-nowrap border-b-2 transition-colors"
        :class="activeIdx === i
          ? 'border-primary text-primary'
          : 'border-transparent text-white/40 hover:text-white/60'"
        @click.stop="activeIdx = i; $emit('select', item.id)"
      >
        {{ item.data?.label || `Tab ${i + 1}` }}
      </button>

      <!-- Add Tab button -->
      <button
        type="button"
        class="px-2 py-2 text-white/30 hover:text-white/60 transition-colors ml-1"
        title="Add tab"
        @click.stop="addTab"
      >
        <Plus class="w-3 h-3" />
      </button>
    </div>

    <!-- Active tab children drop zone -->
    <div v-if="localChildren[activeIdx]">
      <VueDraggable
        v-model="itemChildren[localChildren[activeIdx].id]"
        tag="div"
        class="p-2 min-h-[40px] space-y-1"
        :group="{ name: 'canvas' }"
        :animation="150"
        ghost-class="opacity-40"
        @change="onChildrenChange(localChildren[activeIdx].id)"
      >
        <div
          v-for="child in itemChildren[localChildren[activeIdx].id]"
          :key="child.id"
          class="flex items-center gap-1.5 rounded border border-white/10 bg-background/40 px-2 py-1 text-xs cursor-pointer text-white/50 transition-colors hover:border-white/25"
          :class="child.id === selectedId ? 'border-primary ring-1 ring-primary' : ''"
          @click.stop="$emit('select', child.id)"
        >
          <GripVertical class="w-3 h-3 text-white/20 shrink-0" />
          {{ child.blockName || LABELS[child.type] || child.type }}
        </div>
        <div
          v-if="!itemChildren[localChildren[activeIdx].id]?.length"
          class="text-[10px] text-white/20 py-1 pl-1 pointer-events-none"
        >
          Drop blocks into this tab
        </div>
      </VueDraggable>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, reactive, computed } from 'vue'
import { VueDraggable } from 'vue-draggable-plus'
import { GripVertical, Plus } from '@lucide/vue'

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

const activeIdx = ref(0)

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

function addTab() {
  const id = crypto.randomUUID()
  const newTab = {
    id,
    type: 'tab-item',
    data: { label: `Tab ${_children.value.length + 1}` },
    customId: '', customClasses: '', customCss: '', fontFamily: '',
    children: [],
  }
  itemChildren[id] = []
  _children.value = [..._children.value, newTab]
  activeIdx.value = _children.value.length - 1
  syncToParent()
  emit('select', id)
}
</script>
