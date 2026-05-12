<!-- resources/js/Components/Blocks/TeamMemberBlock.vue -->
<template>
  <div :class="wrapperClass">
    <div :class="imageWrapperClass">
      <img
        v-if="block.data?.imageUrl"
        :src="block.data.imageUrl"
        :alt="block.data?.name ?? 'Team member'"
        :class="imageClass"
      />
      <div v-else :class="[imageClass, 'bg-muted flex items-center justify-center text-2xl font-bold text-muted-foreground']">
        {{ initials }}
      </div>
    </div>
    <div :class="textWrapperClass">
      <h3 v-if="block.data?.name" class="font-semibold text-base">{{ block.data.name }}</h3>
      <p v-if="block.data?.role" class="text-sm text-primary font-medium">{{ block.data.role }}</p>
      <p v-if="block.data?.bio" class="text-sm text-muted-foreground mt-2 leading-relaxed">{{ block.data.bio }}</p>
      <div v-if="(block.data?.socialLinks ?? []).length" class="flex flex-wrap gap-1.5 mt-3">
        <a
          v-for="(sl, i) in block.data.socialLinks"
          :key="i"
          :href="sl.url || '#'"
          :title="sl.label || sl.platform"
          target="_blank"
          rel="noopener noreferrer"
          class="inline-flex items-center justify-center w-8 h-8 rounded-full border border-border text-muted-foreground hover:text-foreground hover:bg-muted transition-colors"
        >
          <Icon :icon="platformIcon(sl.platform)" style="font-size: 0.875rem" aria-hidden="true" />
        </a>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Icon } from '@iconify/vue'

const props = defineProps({ block: { type: Object, required: true } })

const PLATFORM_ICONS = {
  twitter: 'fa6-brands:x-twitter', linkedin: 'fa6-brands:linkedin-in',
  github:  'fa6-brands:github',    email:    'lucide:mail',
  website: 'lucide:globe',
}

const layout = computed(() => props.block.data?.layout ?? 'card')

const initials = computed(() => {
  const name = props.block.data?.name ?? ''
  return name.split(' ').map(p => p[0]).join('').toUpperCase().slice(0, 2) || '?'
})

const wrapperClass = computed(() => {
  if (layout.value === 'horizontal') return 'flex items-start gap-5'
  return 'flex flex-col'
})

const imageWrapperClass = computed(() => layout.value === 'horizontal' ? 'shrink-0' : 'mb-4')

const imageClass = computed(() => {
  if (layout.value === 'horizontal') return 'w-20 h-20 rounded-full object-cover'
  return props.block.data?.imageShape === 'square'
    ? 'w-full aspect-square object-cover rounded-lg'
    : 'w-24 h-24 rounded-full object-cover'
})

const textWrapperClass = computed(() => layout.value === 'horizontal' ? 'flex-1' : '')

function platformIcon(platform) {
  return PLATFORM_ICONS[platform?.toLowerCase()] ?? 'lucide:link'
}
</script>
