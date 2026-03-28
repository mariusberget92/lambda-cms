<!-- resources/js/Components/BlockEditor/BlockLayers.vue -->
<template>
  <div class="w-80 shrink-0 border-l flex flex-col bg-sidebar overflow-hidden">

    <!-- Layers list -->
    <div class="flex flex-col shrink-0" style="max-height: 40%">
      <div class="px-3 py-2 border-b border-white/8 shrink-0 flex items-center justify-between bg-black/20">
        <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Layers</p>
        <div class="flex items-center gap-1">
          <button
            type="button"
            :disabled="!canUndo"
            class="p-1 rounded text-muted-foreground hover:text-foreground hover:bg-accent transition-colors disabled:opacity-30 disabled:cursor-not-allowed"
            title="Undo (Ctrl+Z)"
            @click="$emit('undo')"
          >
            <RotateCcw class="w-3.5 h-3.5" />
          </button>
          <button
            type="button"
            :disabled="!canRedo"
            class="p-1 rounded text-muted-foreground hover:text-foreground hover:bg-accent transition-colors disabled:opacity-30 disabled:cursor-not-allowed"
            title="Redo (Ctrl+Y)"
            @click="$emit('redo')"
          >
            <RotateCw class="w-3.5 h-3.5" />
          </button>
        </div>
      </div>

      <div v-if="blocks.length === 0" class="px-3 py-4 text-xs text-muted-foreground text-center">
        No blocks yet
      </div>

      <div v-else class="flex-1 overflow-y-auto scrollbar-hidden">
        <VueDraggable
          v-model="draggableBlocks"
          tag="div"
          class="p-1.5 space-y-0.5"
          handle=".layer-handle"
          :group="{ name: 'layers' }"
          :animation="150"
        >
          <div v-for="block in draggableBlocks" :key="block.id">
            <LayerItem
              :block="block"
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
        </VueDraggable>
      </div>
    </div>

    <!-- Settings panel -->
    <div class="flex-1 flex flex-col border-t border-white/8 overflow-hidden">

      <div v-if="!selectedBlock" class="flex-1 flex items-center justify-center">
        <p class="text-xs text-muted-foreground text-center">Select a block<br>to edit its settings</p>
      </div>

      <div v-else-if="selectedBlock.type === 'html' && !isAdmin" class="p-4">
        <div class="rounded-md border border-dashed p-4 text-center">
          <p class="text-xs text-muted-foreground">HTML blocks are admin-only and cannot be edited here.</p>
        </div>
      </div>

      <template v-else>
        <Tabs v-model="settingsTab" class="flex flex-col flex-1 overflow-hidden">
          <TabsList class="shrink-0 w-full rounded-none border-b border-white/8 bg-black/20 px-2 h-9 gap-0 justify-start">
            <TabsTrigger
              value="content"
              class="text-xs h-full rounded-none border-b-2 border-transparent data-[state=active]:border-primary data-[state=active]:bg-transparent data-[state=active]:text-foreground px-3"
            >
              Content
            </TabsTrigger>
            <TabsTrigger
              v-if="STYLE_BLOCKS.has(selectedBlock.type)"
              value="style"
              class="text-xs h-full rounded-none border-b-2 border-transparent data-[state=active]:border-primary data-[state=active]:bg-transparent data-[state=active]:text-foreground px-3"
            >
              Style
            </TabsTrigger>
            <TabsTrigger
              value="advanced"
              class="text-xs h-full rounded-none border-b-2 border-transparent data-[state=active]:border-primary data-[state=active]:bg-transparent data-[state=active]:text-foreground px-3"
            >
              Advanced
            </TabsTrigger>
          </TabsList>

          <div class="flex-1 overflow-y-auto scrollbar-hidden">
            <div class="p-3">

              <!-- Content + Style tabs: delegate to block's settings component -->
              <template v-if="settingsTab === 'content' || settingsTab === 'style'">
                <component
                  :is="settingsComponent"
                  :block="selectedBlock"
                  :tab="settingsTab"
                  :is-admin="isAdmin"
                  :meta="meta"
                  :loop-fields="loopFields"
                  :available-fields="availableFields"
                  @update="$emit('update', $event)"
                />
              </template>

              <!-- Advanced tab -->
              <template v-else-if="settingsTab === 'advanced'">
                <AdvancedSettings
                  :block="selectedBlock"
                  @update="$emit('update', $event)"
                />
                <ConditionSettings
                  v-if="loopFields.length"
                  :block="selectedBlock"
                  :loop-fields="loopFields"
                  @update="$emit('update', $event)"
                />
              </template>

            </div>
          </div>
        </Tabs>
      </template>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { RotateCcw, RotateCw } from 'lucide-vue-next'
import { VueDraggable } from 'vue-draggable-plus'
import LayerItem from './LayerItem.vue'
import AdvancedSettings  from './blocks/AdvancedSettings.vue'
import ConditionSettings from './blocks/ConditionSettings.vue'
import LoopSettings      from './blocks/LoopSettings.vue'
import PostTitleSettings         from './blocks/PostTitleSettings.vue'
import PostBodySettings          from './blocks/PostBodySettings.vue'
import PostFeaturedImageSettings from './blocks/PostFeaturedImageSettings.vue'
import PostMetaSettings          from './blocks/PostMetaSettings.vue'
import PostAuthorSettings        from './blocks/PostAuthorSettings.vue'
import PostTaxonomySettings      from './blocks/PostTaxonomySettings.vue'
import ArchiveTitleSettings      from './blocks/ArchiveTitleSettings.vue'
import SearchSettings            from './blocks/SearchSettings.vue'
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
  loopFields:      { type: Array,   default: () => [] },
  availableFields: { type: Array,   default: () => [] },
  clipboard: { type: Object,  default: null },
  canUndo:   { type: Boolean, default: false },
  canRedo:   { type: Boolean, default: false },
})

const emit = defineEmits(['select', 'remove', 'update', 'reorder', 'update-children', 'duplicate', 'copy', 'paste', 'undo', 'redo'])

// ── Drag-to-reorder ───────────────────────────────────────────────────────────
// _list is kept in sync with props.blocks via a flush:'sync' watcher so it is
// always up-to-date before VueDraggable can react, preventing stale-data reverts.
const _list = ref([...(props.blocks ?? [])])
watch(
  () => props.blocks,
  (v) => { _list.value = [...(v ?? [])] },
  { flush: 'sync' }
)

const draggableBlocks = computed({
  get: () => _list.value,
  set: (val) => {
    _list.value = val
    emit('reorder', val)
  },
})

const settingsTab = ref('content')

// Reset to appropriate default tab when selected block changes
watch(() => props.selectedId, () => {
  settingsTab.value = DEFAULT_TAB[props.selectedBlock?.type] ?? 'content'
})

// Block types that have a Style tab (only include types where style fields actually exist)
const STYLE_BLOCKS = new Set([
  'container', 'section', 'spacer', 'divider', 'loop',
  'component', 'post-featured-image', 'archive-loop',
])

// Block types where Style should be the default active tab
const DEFAULT_TAB = {
  divider:             'style',
  spacer:              'style',
  section:             'style',
  'post-featured-image': 'style',
  'archive-loop':       'style',
}

const LABELS = {
  paragraph: 'Paragraph', heading: 'Heading', image: 'Image',
  quote: 'Quote', code: 'Code', gallery: 'Gallery', video: 'Video',
  divider: 'Divider', cta: 'CTA', html: 'HTML', component: 'Component',
  container: 'Container', section: 'Section', spacer: 'Spacer', loop: 'Loop',
  'post-title': 'Post Title',
  'post-body': 'Post Body',
  'post-featured-image': 'Featured Image',
  'post-meta': 'Post Meta',
  'post-author': 'Author',
  'post-taxonomy': 'Categories & Tags',
  'post-comments': 'Comments',
  'archive-title': 'Archive Title',
  'archive-loop': 'Archive Loop',
  search: 'Search',
}

const COMPONENT_MAP = {
  paragraph: ParagraphSettings, heading: HeadingSettings, image: ImageSettings,
  quote: QuoteSettings, code: CodeSettings, gallery: GallerySettings,
  video: VideoSettings, divider: DividerSettings, cta: CtaSettings,
  html: HtmlSettings, component: ComponentSettings, container: ContainerSettings,
  section: SectionSettings, spacer: SpacerSettings, loop: LoopSettings,
  'post-title':          PostTitleSettings,
  'post-body':           PostBodySettings,
  'post-featured-image': PostFeaturedImageSettings,
  'post-meta':           PostMetaSettings,
  'post-author':         PostAuthorSettings,
  'post-taxonomy':       PostTaxonomySettings,
  'post-comments':       null,
  'archive-title':       ArchiveTitleSettings,
  'archive-loop':        LoopSettings,
  search:                SearchSettings,
}

const settingsComponent = computed(() =>
  props.selectedBlock ? COMPONENT_MAP[props.selectedBlock.type] ?? null : null
)

function blockLabel(type) {
  return LABELS[type] ?? type
}
</script>
