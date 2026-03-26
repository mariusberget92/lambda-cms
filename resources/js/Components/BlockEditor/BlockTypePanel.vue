<!-- resources/js/Components/BlockEditor/BlockTypePanel.vue -->
<template>
  <div class="w-48 shrink-0 border-r flex flex-col bg-sidebar">
    <div class="px-3 py-2 border-b shrink-0">
      <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Add Block</p>
    </div>

    <div class="flex-1 overflow-y-auto scrollbar-hidden">
      <template v-for="group in visibleGroups" :key="group.name">
        <div class="px-3 pt-3 pb-1">
          <p class="text-[10px] font-semibold uppercase tracking-widest text-muted-foreground/60">{{ group.name }}</p>
        </div>
        <VueDraggable
          :model-value="group.types"
          tag="div"
          class="px-2 pb-2 grid grid-cols-2 gap-1.5 content-start"
          :group="{ name: 'canvas', pull: 'clone', put: false }"
          :sort="false"
          :clone="cloneBlock"
          :animation="150"
        >
          <div
            v-for="btype in group.types"
            :key="btype.type"
            class="flex flex-col items-center justify-center gap-1.5 rounded-lg border border-border bg-background px-1 py-3 cursor-grab active:cursor-grabbing hover:border-primary hover:text-primary transition-colors select-none"
          >
            <component :is="btype.icon" class="w-4 h-4 shrink-0" />
            <span class="text-[10px] leading-none text-center">{{ btype.label }}</span>
          </div>
        </VueDraggable>
      </template>
    </div>
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
  // ── Content ──────────────────────────────────────────────────────────────
  { type: 'paragraph', label: 'Paragraph', icon: AlignLeft,         group: 'Content' },
  { type: 'heading',   label: 'Heading',   icon: Heading2,          group: 'Content' },
  { type: 'image',     label: 'Image',     icon: ImageIcon,         group: 'Content' },
  { type: 'quote',     label: 'Quote',     icon: Quote,             group: 'Content' },
  { type: 'code',      label: 'Code',      icon: Code2,             group: 'Content' },
  { type: 'gallery',   label: 'Gallery',   icon: LayoutGrid,        group: 'Content' },
  { type: 'video',     label: 'Video',     icon: Video,             group: 'Content' },
  // ── Layout ───────────────────────────────────────────────────────────────
  { type: 'container', label: 'Container', icon: LayoutTemplate,    group: 'Layout' },
  { type: 'section',   label: 'Section',   icon: Rows2,             group: 'Layout' },
  { type: 'divider',   label: 'Divider',   icon: Minus,             group: 'Layout' },
  { type: 'spacer',    label: 'Spacer',    icon: ArrowUpDown,       group: 'Layout' },
  // ── Interactive ──────────────────────────────────────────────────────────
  { type: 'cta',       label: 'CTA',       icon: MousePointerClick, group: 'Interactive' },
  { type: 'search',    label: 'Search',    icon: Search,            group: 'Interactive' },
  { type: 'loop',      label: 'Loop',      icon: Repeat2,           group: 'Interactive' },
  { type: 'component', label: 'Component', icon: Puzzle,            group: 'Interactive' },
  { type: 'html',      label: 'HTML',      icon: FileCode,          group: 'Developer', adminOnly: true },
  // ── Post ─────────────────────────────────────────────────────────────────
  { type: 'post-title',          label: 'Post Title',  icon: Heading1,      group: 'Post' },
  { type: 'post-body',           label: 'Post Body',   icon: AlignLeft,     group: 'Post' },
  { type: 'post-featured-image', label: 'Feat. Image', icon: ImageIcon,     group: 'Post' },
  { type: 'post-meta',           label: 'Post Meta',   icon: Info,          group: 'Post' },
  { type: 'post-author',         label: 'Author',      icon: User,          group: 'Post' },
  { type: 'post-taxonomy',       label: 'Taxonomy',    icon: Tag,           group: 'Post' },
  { type: 'post-comments',       label: 'Comments',    icon: MessageCircle, group: 'Post' },
  // ── Archive ──────────────────────────────────────────────────────────────
  { type: 'archive-title', label: 'Title', icon: FolderOpen, group: 'Archive' },
  { type: 'archive-loop',  label: 'Loop',  icon: List,       group: 'Archive' },
]

const GROUP_ORDER = ['Content', 'Layout', 'Interactive', 'Developer', 'Post', 'Archive']

const visibleGroups = computed(() => {
  const visible = ALL_TYPES.filter(t => !t.adminOnly || props.isAdmin)
  return GROUP_ORDER
    .map(name => ({ name, types: visible.filter(t => t.group === name) }))
    .filter(g => g.types.length > 0)
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
  container: {
    direction: 'row', wrap: true,
    gap: '1rem',
    justify: 'start', align: 'start', maxWidth: 'full',
    padding: { top: '1rem', right: '1rem', bottom: '1rem', left: '1rem' },
  },
  section: {
    bgType: 'none',
    bgColor: '#ffffff',
    bgImage: { url: '', position: 'center', size: 'cover' },
    bgGradient: { from: '#3b4252', to: '#4c566a', direction: 'to-r' },
    fullWidth: false,
    innerMaxWidth: 'xl',
    padding: { top: '4rem', right: '2rem', bottom: '4rem', left: '2rem' },
    minHeight: 'auto',
  },
  spacer: {
    height: { default: '2rem', sm: '1rem' },
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
