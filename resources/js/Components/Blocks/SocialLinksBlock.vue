<!-- resources/js/Components/Blocks/SocialLinksBlock.vue -->
<template>
  <div :class="['flex flex-wrap gap-2', ALIGN_MAP[block.data?.align ?? 'left']]">
    <a
      v-for="(link, i) in block.data?.links ?? []"
      :key="i"
      :href="link.url || '#'"
      :title="link.label || link.platform"
      target="_blank"
      rel="noopener noreferrer"
      :class="linkClass"
    >
      <Icon
        v-if="platformIcon(link.platform)"
        :icon="platformIcon(link.platform)"
        :style="{ fontSize: SIZE_MAP[block.data?.size ?? 'md'] }"
        aria-hidden="true"
      />
      <span v-if="style !== 'icon-only'" class="text-sm">{{ link.label || link.platform }}</span>
    </a>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Icon } from '@iconify/vue'

const props = defineProps({ block: { type: Object, required: true } })

const PLATFORM_ICONS = {
  twitter:   'fa6-brands:x-twitter',
  x:         'fa6-brands:x-twitter',
  linkedin:  'fa6-brands:linkedin-in',
  github:    'fa6-brands:github',
  facebook:  'fa6-brands:facebook-f',
  instagram: 'fa6-brands:instagram',
  youtube:   'fa6-brands:youtube',
  tiktok:    'fa6-brands:tiktok',
  discord:   'fa6-brands:discord',
  twitch:    'fa6-brands:twitch',
  pinterest: 'fa6-brands:pinterest',
  reddit:    'fa6-brands:reddit',
  whatsapp:  'fa6-brands:whatsapp',
  telegram:  'fa6-brands:telegram',
  email:     'lucide:mail',
  website:   'lucide:globe',
  rss:       'lucide:rss',
}

const ALIGN_MAP = { left: 'justify-start', center: 'justify-center', right: 'justify-end' }
const SIZE_MAP  = { sm: '1rem', md: '1.25rem', lg: '1.5rem' }

const style = computed(() => props.block.data?.style ?? 'icon-only')

const linkClass = computed(() => {
  const base = 'inline-flex items-center gap-1.5 rounded-md transition-colors text-muted-foreground hover:text-foreground'
  if (style.value === 'icon-only') return `${base} p-2 hover:bg-muted`
  return `${base} px-3 py-1.5 border border-border hover:bg-muted`
})

function platformIcon(platform) {
  return PLATFORM_ICONS[platform?.toLowerCase()] ?? 'lucide:link'
}
</script>
