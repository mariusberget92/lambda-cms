<!-- resources/js/Components/Blocks/TimelineBlock.vue -->
<template>
  <div :class="wrapperClass">
    <div
      v-for="(item, i) in block.data?.items ?? []"
      :key="i"
      :class="itemClass"
    >
      <!-- Connector dot + line (vertical layout) -->
      <template v-if="(block.data?.layout ?? 'vertical') === 'vertical'">
        <div class="flex flex-col items-center shrink-0 mr-4">
          <div class="w-3 h-3 rounded-full bg-primary shrink-0 mt-1" />
          <div v-if="i < (block.data.items.length - 1)" class="w-px flex-1 bg-border mt-1" />
        </div>
        <div class="pb-6 flex-1">
          <span v-if="item.date" class="text-xs font-semibold text-primary uppercase tracking-wider">{{ item.date }}</span>
          <h3 v-if="item.title" class="font-semibold text-sm mt-0.5">{{ item.title }}</h3>
          <p v-if="item.description" class="text-sm text-muted-foreground mt-1 leading-relaxed">{{ item.description }}</p>
        </div>
      </template>

      <!-- Horizontal layout -->
      <template v-else>
        <div class="flex flex-col items-center">
          <div class="w-3 h-3 rounded-full bg-primary shrink-0" />
          <div v-if="i < (block.data.items.length - 1)" class="h-px flex-1 w-full bg-border mt-1 hidden" />
        </div>
        <div class="mt-3 text-center px-2">
          <span v-if="item.date" class="text-xs font-semibold text-primary uppercase tracking-wider">{{ item.date }}</span>
          <h3 v-if="item.title" class="font-semibold text-sm mt-0.5">{{ item.title }}</h3>
          <p v-if="item.description" class="text-xs text-muted-foreground mt-1 leading-relaxed">{{ item.description }}</p>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({ block: { type: Object, required: true } })

const layout = computed(() => props.block.data?.layout ?? 'vertical')

const wrapperClass = computed(() =>
  layout.value === 'horizontal'
    ? 'flex gap-0 overflow-x-auto'
    : 'flex flex-col'
)

const itemClass = computed(() =>
  layout.value === 'horizontal'
    ? 'flex flex-col items-center flex-1 min-w-[8rem] relative'
    : 'flex'
)
</script>
