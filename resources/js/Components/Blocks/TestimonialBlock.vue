<!-- resources/js/Components/Blocks/TestimonialBlock.vue -->
<template>
  <div :class="wrapperClass">
    <!-- Star rating -->
    <div v-if="block.data?.rating" class="flex gap-0.5 mb-3">
      <Icon
        v-for="n in 5"
        :key="n"
        icon="lucide:star"
        aria-hidden="true"
        :style="{ fontSize: '1rem', color: n <= block.data.rating ? '#f59e0b' : '#d1d5db', fill: n <= block.data.rating ? '#f59e0b' : 'none' }"
      />
    </div>

    <!-- Quote text -->
    <blockquote :class="quoteClass">{{ resolvedText }}</blockquote>

    <!-- Author -->
    <div class="flex items-center gap-3 mt-4">
      <img
        v-if="block.data?.avatarUrl"
        :src="block.data.avatarUrl"
        :alt="block.data.authorName ?? 'Author'"
        class="w-10 h-10 rounded-full object-cover shrink-0"
      />
      <div v-else-if="block.data?.authorName" class="w-10 h-10 rounded-full bg-muted flex items-center justify-center shrink-0 text-sm font-semibold text-muted-foreground">
        {{ block.data.authorName.charAt(0).toUpperCase() }}
      </div>
      <div>
        <p v-if="block.data?.authorName" class="font-semibold text-sm">{{ block.data.authorName }}</p>
        <p v-if="block.data?.authorRole || block.data?.authorCompany" class="text-xs text-muted-foreground">
          {{ [block.data.authorRole, block.data.authorCompany].filter(Boolean).join(', ') }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Icon } from '@iconify/vue'
import { useFieldBinding } from '@/composables/useLoopBinding.js'

const props = defineProps({ block: { type: Object, required: true } })

const resolvedText = useFieldBinding(() => props.block, 'text')

const layout = computed(() => props.block.data?.layout ?? 'card')

const wrapperClass = computed(() => {
  if (layout.value === 'featured') return 'p-8 rounded-2xl bg-primary text-primary-foreground'
  if (layout.value === 'minimal')  return 'py-4'
  return 'p-6 rounded-xl border border-border bg-card'
})

const quoteClass = computed(() => {
  if (layout.value === 'minimal') return 'text-base italic leading-relaxed border-l-4 border-primary pl-4'
  if (layout.value === 'featured') return 'text-lg leading-relaxed'
  return 'text-sm leading-relaxed'
})
</script>
