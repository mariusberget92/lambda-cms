<!-- resources/js/Components/BlockEditor/BlockTypePanel.vue -->
<template>
  <div class="w-48 shrink-0 border-r border-white/8 flex flex-col bg-sidebar">
    <div class="px-3 py-2 border-b border-white/8 shrink-0 bg-black/20">
      <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Add Block</p>
    </div>

    <div class="flex-1 overflow-y-auto scrollbar-hidden">
      <template v-for="group in visibleGroups" :key="group.name">
        <div class="px-3 pt-3 pb-1 flex items-center">
          <span class="inline-flex bg-white/5 rounded-full px-2 py-0.5 text-[9px] font-semibold uppercase tracking-widest text-white/40">
            {{ group.name }}
          </span>
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
            class="flex flex-col items-center justify-center gap-1.5 rounded-lg border bg-white/5 border-white/10 px-1 py-4 cursor-grab active:cursor-grabbing hover:bg-white/10 hover:border-white/20 active:border-primary/60 active:bg-primary/10 transition-colors select-none"
          >
            <component :is="btype.icon" class="w-5 h-5 shrink-0" :class="GROUP_COLORS[btype.group]" />
            <span class="text-[11px] leading-none text-center text-muted-foreground">{{ btype.label }}</span>
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
  Link,
  ChevronDown,
  Layers,
  LayoutPanelTop,
  PlayCircle,
  ChevronRight as ChevronRightIcon,
  Navigation2,
  Table2,
  Filter,
  RectangleHorizontal,
  ListChecks,
  Columns2,
  Terminal,
  BarChart3,
  Hash,
  Sparkles,
  SeparatorHorizontal,
} from '@lucide/vue'

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
  { type: 'accordion',      label: 'Accordion', icon: Layers,          group: 'Content' },
  { type: 'accordion-item', label: 'Acc. Item', icon: ChevronDown,     group: 'Content', hiddenFromPalette: true },
  { type: 'tabs',           label: 'Tabs',      icon: LayoutPanelTop,  group: 'Content' },
  { type: 'tab-item',       label: 'Tab',       icon: LayoutPanelTop,  group: 'Content', hiddenFromPalette: true },
  { type: 'embed',          label: 'Embed',     icon: PlayCircle,      group: 'Content' },
  // ── Layout ───────────────────────────────────────────────────────────────
  { type: 'columns',   label: 'Columns',   icon: Columns2,          group: 'Layout' },
  { type: 'container', label: 'Container', icon: LayoutTemplate,    group: 'Layout' },
  { type: 'section',   label: 'Section',   icon: Rows2,             group: 'Layout' },
  { type: 'divider',   label: 'Divider',   icon: Minus,             group: 'Layout' },
  { type: 'spacer',      label: 'Spacer',     icon: ArrowUpDown,       group: 'Layout' },
  { type: 'navigation', label: 'Navigation', icon: Navigation2,       group: 'Layout' },
  // ── Interactive ──────────────────────────────────────────────────────────
  { type: 'button',    label: 'Button',    icon: RectangleHorizontal, group: 'Interactive' },
  { type: 'icon-list', label: 'Icon List', icon: ListChecks,        group: 'Interactive' },
  { type: 'cta',       label: 'CTA',       icon: MousePointerClick, group: 'Interactive' },
  { type: 'search',    label: 'Search',    icon: Search,            group: 'Interactive' },
  { type: 'loop',      label: 'Loop',      icon: Repeat2,           group: 'Interactive' },
  { type: 'link',        label: 'Link',        icon: Link,              group: 'Interactive' },
  { type: 'filter-link', label: 'Filter Link', icon: Filter,            group: 'Interactive' },
  { type: 'template',   label: 'Template',    icon: LayoutTemplate,    group: 'Interactive' },
  { type: 'pagination',  label: 'Pagination',  icon: ChevronRightIcon,  group: 'Interactive' },
  { type: 'table',     label: 'Table',     icon: Table2,            group: 'Interactive' },
  { type: 'html',      label: 'HTML',      icon: FileCode,          group: 'Developer', adminOnly: true },
  // ── Design (Lambda design system blocks) ─────────────────────────────────
  { type: 'cover',          label: 'Cover',       icon: Terminal,          group: 'Design' },
  { type: 'stat-card',      label: 'Stat Card',   icon: BarChart3,         group: 'Design' },
  { type: 'category-chip',  label: 'Cat. Chip',   icon: Hash,              group: 'Design' },
  { type: 'band',           label: 'Band',        icon: Sparkles,          group: 'Design' },
  { type: 'section-header', label: 'Sec. Header', icon: SeparatorHorizontal, group: 'Design' },
  // ── Post (hidden from palette — only used inside loop blocks) ───────────
  { type: 'post-title',          label: 'Post Title',  icon: Heading1,      group: 'Post', hiddenFromPalette: true },
  { type: 'post-body',           label: 'Post Body',   icon: AlignLeft,     group: 'Post', hiddenFromPalette: true },
  { type: 'post-featured-image', label: 'Feat. Image', icon: ImageIcon,     group: 'Post', hiddenFromPalette: true },
  { type: 'post-meta',           label: 'Post Meta',   icon: Info,          group: 'Post', hiddenFromPalette: true },
  { type: 'post-author',         label: 'Author',      icon: User,          group: 'Post', hiddenFromPalette: true },
  { type: 'post-taxonomy',       label: 'Taxonomy',    icon: Tag,           group: 'Post', hiddenFromPalette: true },
  { type: 'post-comments',       label: 'Comments',    icon: MessageCircle, group: 'Post', hiddenFromPalette: true },
  // ── Archive ──────────────────────────────────────────────────────────────
  { type: 'archive-title', label: 'Title', icon: FolderOpen, group: 'Archive' },
  { type: 'archive-loop',  label: 'Loop',  icon: List,       group: 'Archive' },
]

const GROUP_COLORS = {
  'Content':     'text-[var(--chart-1)]',
  'Layout':      'text-[var(--chart-2)]',
  'Interactive': 'text-[var(--chart-3)]',
  'Developer':   'text-[var(--chart-4)]',
  'Post':        'text-[var(--chart-5)]',
  'Archive':     'text-[var(--chart-1)]',
  'Design':      'text-[var(--chart-2)]',
}

const GROUP_ORDER = ['Content', 'Layout', 'Interactive', 'Developer', 'Post', 'Archive', 'Design']

const visibleGroups = computed(() => {
  const visible = ALL_TYPES.filter(t =>
    (!t.adminOnly || props.isAdmin) &&
    !t.hiddenFromPalette
  )
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
  button: {
    label: 'Click here',
    url: '',
    target: '_self',
    rel: '',
    variant: 'filled',
    size: 'md',
    alignment: 'left',
    fullWidth: false,
    bgColor: null,
    textColor: null,
    radius: null,
    icon: { name: null, position: 'prefix', size: '1.1em', color: null, gap: '0.5em' },
  },
  'icon-list': {
    items: [
      { icon: 'lucide:check', text: 'Feature one' },
      { icon: 'lucide:check', text: 'Feature two' },
      { icon: 'lucide:check', text: 'Feature three' },
    ],
    direction: 'vertical',
    gap: '0.75rem',
    iconGap: '0.6em',
    iconSize: '1.1em',
    iconColor: null,
  },
  columns: {
    direction: 'row',
    wrap: true,
    gap: '1rem',
    justify: 'start',
    align: 'start',
    maxWidth: 'full',
    columns: '2',
    padding: { top: '1rem', right: '1rem', bottom: '1rem', left: '1rem' },
  },
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
  navigation: { links: [], style: 'horizontal', alignment: 'left' },
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
  link: {
    url: '',
    target: '_self',
    rel: '',
    icon: { name: null, position: 'prefix', size: '1.25em', color: null, gap: '0.5em' },
  },
  'filter-link': { paramName: 'category', label: '' },
  'template': { template_id: null },
  accordion: {
    defaultState: 'first-open',
    borderStyle: 'bordered',
  },
  'accordion-item': {
    title: 'Item title',
  },
  tabs: {
    alignment: 'left',
    tabStyle: 'underline',
  },
  'tab-item': {
    label: 'Tab',
  },
  embed: {
    url: '',
    caption: '',
    aspectRatio: '16/9',
    maxWidth: '',
  },
  pagination: {
    pageParam: 'page',
    style: 'prev-next',
    prevLabel: '← Previous',
    nextLabel: 'Next →',
    alignment: 'center',
    buttonStyle: 'outline',
  },
  cover: {
    variant: 'flat',
    filepath: '~/lambdacms/posts/slug.md',
    callsign: 'λ.01',
    glyph: 'essays',
    issueNo: '№ 01',
    hue: 220,
    statusOk: true,
  },
  'stat-card': {
    value: '2.4K',
    label: 'articles',
    trend: '↑ +12%',
    trendTone: 'positive',
  },
  'category-chip': {
    label: 'Category',
    slug: '',
    hue: 220,
    active: false,
  },
  band: {
    label: 'All Topics',
    limit: 30,
    showCount: false,
  },
  'section-header': {
    number: '01',
    title: 'Section Title',
    meta: '',
    showRule: false,
  },
  table: {
    mode: 'static',
    columns: [
      { id: 'col-1', label: 'Column 1', field: '', prefix: '', suffix: '', align: 'left' },
      { id: 'col-2', label: 'Column 2', field: '', prefix: '', suffix: '', align: 'left' },
    ],
    rows: [ { 'col-1': '', 'col-2': '' }, { 'col-1': '', 'col-2': '' } ],
    source: 'posts',
    filters: [],
    filter_logic: 'and',
    sort: { field: 'published_at', direction: 'desc' },
    limit: 10,
    offset: 0,
    striped: true,
    borderStyle: 'full',
    headerStyle: true,
    responsive: 'scroll',
  },
}

function cloneBlock(typeDef) {
  const makeId = () => typeof crypto !== 'undefined' && crypto.randomUUID
    ? crypto.randomUUID()
    : Math.random().toString(36).slice(2) + Date.now().toString(36)

  const id = makeId()
  const block = {
    id,
    type: typeDef.type,
    data: { ...(DEFAULT_DATA[typeDef.type] ?? {}) },
    customId: '',
    customClasses: '',
    customCss: '',
    fontFamily: '',
    ...(['container', 'section', 'loop', 'archive-loop', 'link', 'accordion', 'tabs', 'accordion-item', 'tab-item', 'columns'].includes(typeDef.type)
      ? { children: [] }
      : {}),
  }

  // Auto-populate accordion with 3 items
  if (typeDef.type === 'accordion') {
    block.children = [1, 2, 3].map(i => ({
      id: makeId(),
      type: 'accordion-item',
      data: { title: `Item ${i}` },
      customId: '', customClasses: '', customCss: '', fontFamily: '',
      children: [],
    }))
  }

  // Auto-populate columns with equal-width container children
  if (typeDef.type === 'columns') {
    const n = parseInt(block.data.columns ?? '2')
    block.children = Array.from({ length: n }, () => ({
      id: makeId(),
      type: 'container',
      data: {
        direction: 'column', wrap: false,
        gap: '0.5rem', justify: 'start', align: 'start', maxWidth: 'full',
        padding: { top: '0.5rem', right: '0.5rem', bottom: '0.5rem', left: '0.5rem' },
      },
      customId: '', customClasses: '', customCss: '', fontFamily: '',
      children: [],
    }))
  }

  // Auto-populate tabs with 2 tab items
  if (typeDef.type === 'tabs') {
    block.children = [1, 2].map(i => ({
      id: makeId(),
      type: 'tab-item',
      data: { label: `Tab ${i}` },
      customId: '', customClasses: '', customCss: '', fontFamily: '',
      children: [],
    }))
  }

  return block
}
</script>
