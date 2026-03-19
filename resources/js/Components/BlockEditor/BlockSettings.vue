<!-- resources/js/Components/BlockEditor/BlockSettings.vue -->
<template>
  <div class="w-60 shrink-0 border-l flex flex-col bg-sidebar">
    <div class="px-3 py-2 border-b">
      <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
        {{ block ? blockLabel(block.type) + ' Settings' : 'Settings' }}
      </p>
    </div>

    <div class="flex-1 overflow-y-auto p-3">
      <!-- No block selected -->
      <div v-if="!block" class="h-full flex items-center justify-center">
        <p class="text-xs text-muted-foreground text-center">Select a block<br>to edit its settings</p>
      </div>

      <!-- HTML block — non-admin disabled state -->
      <div v-else-if="block.type === 'html' && !isAdmin" class="rounded-md border border-dashed p-4 text-center">
        <p class="text-xs text-muted-foreground">HTML blocks are admin-only and cannot be edited here.</p>
      </div>

      <!-- Dynamic settings component -->
      <component
        v-else
        :is="settingsComponent"
        :block="block"
        :is-admin="isAdmin"
        @update="$emit('update', $event)"
      />
    </div>
  </div>
</template>

<script setup>
import { computed }         from 'vue'
import HeadingSettings     from './blocks/HeadingSettings.vue'
import ParagraphSettings   from './blocks/ParagraphSettings.vue'
import ImageSettings       from './blocks/ImageSettings.vue'
import QuoteSettings       from './blocks/QuoteSettings.vue'
import CodeSettings        from './blocks/CodeSettings.vue'
import GallerySettings     from './blocks/GallerySettings.vue'
import VideoSettings       from './blocks/VideoSettings.vue'
import DividerSettings     from './blocks/DividerSettings.vue'
import CtaSettings         from './blocks/CtaSettings.vue'
import HtmlSettings        from './blocks/HtmlSettings.vue'

const props = defineProps({
  block:   { type: Object,  default: null },
  isAdmin: { type: Boolean, default: false },
})

defineEmits(['update'])

const COMPONENT_MAP = {
  paragraph: ParagraphSettings,
  heading:   HeadingSettings,
  image:     ImageSettings,
  quote:     QuoteSettings,
  code:      CodeSettings,
  gallery:   GallerySettings,
  video:     VideoSettings,
  divider:   DividerSettings,
  cta:       CtaSettings,
  html:      HtmlSettings,
}

const LABELS = {
  paragraph: 'Paragraph',
  heading:   'Heading',
  image:     'Image',
  quote:     'Quote',
  code:      'Code',
  gallery:   'Gallery',
  video:     'Video',
  divider:   'Divider',
  cta:       'CTA',
  html:      'HTML',
}

const settingsComponent = computed(() =>
  props.block ? COMPONENT_MAP[props.block.type] ?? null : null
)

function blockLabel(type) {
  return LABELS[type] ?? type
}
</script>
