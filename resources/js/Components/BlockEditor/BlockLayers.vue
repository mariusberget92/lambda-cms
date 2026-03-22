<!-- resources/js/Components/BlockEditor/BlockLayers.vue -->
<template>
  <div class="w-64 shrink-0 border-l flex flex-col bg-sidebar overflow-hidden">

    <!-- Layers list -->
    <div class="flex flex-col shrink-0" style="max-height: 40%">
      <div class="px-3 py-2 border-b shrink-0">
        <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Layers</p>
      </div>

      <div v-if="draggableBlocks.length === 0" class="px-3 py-4 text-xs text-muted-foreground text-center">
        No blocks yet
      </div>

      <VueDraggable
        v-else
        v-model="draggableBlocks"
        tag="div"
        class="overflow-y-auto p-1.5 space-y-0.5"
        handle=".layer-handle"
        :animation="150"
      >
        <div v-for="block in draggableBlocks" :key="block.id">
          <LayerItem
            :block="block"
            :selected-id="selectedId"
            @select="$emit('select', $event)"
            @remove="$emit('remove', $event)"
          />
        </div>
      </VueDraggable>
    </div>

    <!-- Settings panel -->
    <div class="flex-1 flex flex-col border-t overflow-hidden">
      <div class="px-3 py-2 border-b shrink-0">
        <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
          {{ selectedBlock ? blockLabel(selectedBlock.type) + ' Settings' : 'Settings' }}
        </p>
      </div>

      <div class="flex-1 overflow-y-auto p-3">
        <div v-if="!selectedBlock" class="h-full flex items-center justify-center">
          <p class="text-xs text-muted-foreground text-center">Select a block<br>to edit its settings</p>
        </div>

        <div v-else-if="selectedBlock.type === 'html' && !isAdmin" class="rounded-md border border-dashed p-4 text-center">
          <p class="text-xs text-muted-foreground">HTML blocks are admin-only and cannot be edited here.</p>
        </div>

        <template v-else>
          <component
            :is="settingsComponent"
            :block="selectedBlock"
            :is-admin="isAdmin"
            :meta="meta"
            :loop-fields="loopFields"
            @update="$emit('update', $event)"
          />
          <AdvancedSettings
            :block="selectedBlock"
            @update="$emit('update', $event)"
          />
          <!-- Condition settings — only shown when block is inside a Loop -->
          <ConditionSettings
            v-if="loopFields.length"
            :block="selectedBlock"
            :loop-fields="loopFields"
            @update="$emit('update', $event)"
          />
        </template>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { VueDraggable } from 'vue-draggable-plus'
import LayerItem         from './LayerItem.vue'
import AdvancedSettings  from './blocks/AdvancedSettings.vue'
import ConditionSettings from './blocks/ConditionSettings.vue'
import LoopSettings      from './blocks/LoopSettings.vue'
import ContainerSettings from './blocks/ContainerSettings.vue'
import SectionSettings  from './blocks/SectionSettings.vue'
import SpacerSettings   from './blocks/SpacerSettings.vue'
import HeadingSettings   from './blocks/HeadingSettings.vue'
import ParagraphSettings from './blocks/ParagraphSettings.vue'
import ImageSettings     from './blocks/ImageSettings.vue'
import QuoteSettings     from './blocks/QuoteSettings.vue'
import CodeSettings      from './blocks/CodeSettings.vue'
import GallerySettings   from './blocks/GallerySettings.vue'
import VideoSettings     from './blocks/VideoSettings.vue'
import DividerSettings   from './blocks/DividerSettings.vue'
import CtaSettings       from './blocks/CtaSettings.vue'
import HtmlSettings      from './blocks/HtmlSettings.vue'
import ComponentSettings from './blocks/ComponentSettings.vue'

const props = defineProps({
  blocks:        { type: Array,   default: () => [] },
  selectedId:    { type: String,  default: null },
  selectedBlock: { type: Object,  default: null },
  isAdmin:       { type: Boolean, default: false },
  meta:          { type: Object,  default: () => ({}) },
  loopFields:    { type: Array,   default: () => [] },
})

const emit = defineEmits(['select', 'remove', 'reorder', 'update'])

const _list = ref([...(props.blocks ?? [])])
watch(() => props.blocks, (v) => { _list.value = v })

const draggableBlocks = computed({
  get: () => _list.value,
  set: (val) => {
    _list.value = val
    emit('reorder', val)
  },
})

const LABELS = {
  paragraph: 'Paragraph', heading: 'Heading', image: 'Image',
  quote: 'Quote', code: 'Code', gallery: 'Gallery', video: 'Video',
  divider: 'Divider', cta: 'CTA', html: 'HTML', component: 'Component',
  container: 'Container', section: 'Section', spacer: 'Spacer', loop: 'Loop',
}

const COMPONENT_MAP = {
  paragraph: ParagraphSettings, heading: HeadingSettings, image: ImageSettings,
  quote: QuoteSettings, code: CodeSettings, gallery: GallerySettings,
  video: VideoSettings, divider: DividerSettings, cta: CtaSettings,
  html: HtmlSettings, component: ComponentSettings, container: ContainerSettings,
  section: SectionSettings, spacer: SpacerSettings, loop: LoopSettings,
}

const settingsComponent = computed(() =>
  props.selectedBlock ? COMPONENT_MAP[props.selectedBlock.type] ?? null : null
)

function blockLabel(type) {
  return LABELS[type] ?? type
}
</script>
