<!-- resources/js/Components/BlockEditor/BlockTypePanel.vue -->
<template>
  <div class="w-48 shrink-0 border-r flex flex-col bg-sidebar">
    <div class="px-3 py-2 border-b shrink-0">
      <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Add Block</p>
    </div>

    <VueDraggable
      v-model="typeList"
      tag="div"
      class="flex-1 overflow-y-auto p-2.5 grid grid-cols-2 gap-2 content-start"
      :group="{ name: 'canvas', pull: 'clone', put: false }"
      :sort="false"
      :clone="cloneBlock"
      :animation="150"
    >
      <div
        v-for="btype in typeList"
        :key="btype.type"
        class="flex flex-col items-center justify-center gap-2 rounded-lg border border-border bg-background px-2 py-4 cursor-grab active:cursor-grabbing hover:border-primary hover:text-primary transition-colors select-none"
      >
        <component :is="btype.icon" class="w-5 h-5 shrink-0" />
        <span class="text-xs leading-none">{{ btype.label }}</span>
      </div>
    </VueDraggable>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { VueDraggable } from 'vue-draggable-plus'
import {
  AlignLeft,
  Heading2,
  ImageIcon,
  Quote,
  Code2,
  LayoutGrid,
  Video,
  Minus,
  MousePointerClick,
  FileCode,
  Puzzle,
  LayoutTemplate,
  Rows2,
  ArrowUpDown,
  Repeat2,
  Heading1,
  Info,
  User,
  Tag,
  MessageCircle,
  FolderOpen,
  List,
  Search,
} from 'lucide-vue-next'

const props = defineProps({
  isAdmin: { type: Boolean, default: false },
})

const ALL_TYPES = [
  { type: 'paragraph', label: 'Paragraph', icon: AlignLeft },
  { type: 'heading',   label: 'Heading',   icon: Heading2 },
  { type: 'image',     label: 'Image',     icon: ImageIcon },
  { type: 'quote',     label: 'Quote',     icon: Quote },
  { type: 'code',      label: 'Code',      icon: Code2 },
  { type: 'gallery',   label: 'Gallery',   icon: LayoutGrid },
  { type: 'video',     label: 'Video',     icon: Video },
  { type: 'divider',   label: 'Divider',   icon: Minus },
  { type: 'cta',       label: 'CTA',       icon: MousePointerClick },
  { type: 'html',      label: 'HTML',      icon: FileCode, adminOnly: true },
  { type: 'component', label: 'Component', icon: Puzzle },
  { type: 'container', label: 'Container', icon: LayoutTemplate },
  { type: 'section',   label: 'Section',   icon: Rows2 },
  { type: 'spacer',    label: 'Spacer',    icon: ArrowUpDown },
  { type: 'loop',      label: 'Loop',      icon: Repeat2 },
  // Post group
  { type: 'post-title',          label: 'Post Title',    icon: Heading1 },
  { type: 'post-body',           label: 'Post Body',     icon: AlignLeft },
  { type: 'post-featured-image', label: 'Feat. Image',   icon: ImageIcon },
  { type: 'post-meta',           label: 'Post Meta',     icon: Info },
  { type: 'post-author',         label: 'Author',        icon: User },
  { type: 'post-taxonomy',       label: 'Categories & Tags', icon: Tag },
  { type: 'post-comments',       label: 'Comments',      icon: MessageCircle },
  // Archive group
  { type: 'archive-title',       label: 'Archive Title', icon: FolderOpen },
  { type: 'archive-loop',        label: 'Archive Loop',  icon: List },
  // Interactive group
  { type: 'search',              label: 'Search',        icon: Search },
]

const typeList = computed({
  get: () => ALL_TYPES.filter(t => !t.adminOnly || props.isAdmin),
  set: () => {}, // source list never mutated (clone mode + sort:false)
})

const DEFAULT_DATA = {
  paragraph: { content: '' },
  heading:   { level: 2, text: '' },
  image:     { media_id: null, url: '', caption: '', alt: '' },
  quote:     { text: '', attribution: '' },
  code:      { code: '', language: 'javascript' },
  gallery:   { items: [] },
  video:     { url: '', caption: '' },
  divider:   { style: 'line' },
  cta:       { headline: '', text: '', button_label: '', button_url: '' },
  html:      { content: '' },
  component: { component: 'post-list', limit: 6, offset: 0, order: 'latest', featured_only: false, category_ids: [], tag_ids: [] },
  container: { direction: 'row', wrap: true, gap: 4, justify: 'start', align: 'start', maxWidth: 'full', padding: 4 },
  section: {
    bgType: 'none',
    bgColor: '#ffffff',
    bgImage: { url: '', position: 'center', size: 'cover' },
    bgGradient: { from: '#3b4252', to: '#4c566a', direction: 'to-r' },
    fullWidth: false,
    innerMaxWidth: 'xl',
    paddingY: { default: 16, sm: 8 },
    paddingX: { default: 8,  sm: 4 },
    minHeight: 'auto',
  },
  spacer: {
    height: { default: 8, sm: 4 },
  },
  loop: {
    source:  'posts',
    filters: [],
    sort:    { field: 'published_at', direction: 'desc' },
    limit:   6,
    offset:  0,
    columns: 1,
    gap:     'md',
  },
  'post-title':          { tag: 'h1' },
  'post-body':           {},
  'post-featured-image': { maxWidth: '100%', aspectRatio: 'auto' },
  'post-meta':           { date: true, author: true, readTime: true },
  'post-author':         { showAvatar: true },
  'post-taxonomy':       { showCategories: true, showTags: true },
  'post-comments':       {},
  'archive-title':       { tag: 'h1' },
  'archive-loop':        { source: 'posts', limit: 12, columns: 3, gap: 6 },
  search:                { placeholder: 'Search…', buttonLabel: 'Search', scope: 'posts' },
}

function cloneBlock(typeDef) {
  const id = typeof crypto !== 'undefined' && crypto.randomUUID
    ? crypto.randomUUID()
    : Math.random().toString(36).slice(2) + Date.now().toString(36)
  return {
    id,
    type: typeDef.type,
    data: { ...(DEFAULT_DATA[typeDef.type] ?? {}) },
    customId: '',
    customClasses: '',
    customCss: '',
    fontFamily: '',
    ...(['container', 'section', 'loop', 'archive-loop'].includes(typeDef.type)
      ? { children: [] }
      : {}),
  }
}
</script>
