<!-- resources/js/Components/Blocks/LinkBlock.vue -->
<script setup>
import { computed } from 'vue'
import BlockRenderer from '@/Components/BlockRenderer.vue'

const props = defineProps({ block: { type: Object, required: true } })

const d = computed(() => props.block.data ?? {})
const hasChildren = computed(() => props.block.children?.length > 0)

const href   = computed(() => d.value.url   || '#')
const target = computed(() => d.value.target || '_self')
const rel    = computed(() => d.value.rel    || undefined)
</script>

<template>
  <a
    :href="href"
    :target="target"
    :rel="rel"
    class="inline-block"
  >
    <!-- Label-only link (no children dragged in) -->
    <template v-if="!hasChildren">
      {{ d.label || d.url || 'Link' }}
    </template>

    <!-- Container-style link: child blocks wrapped in the anchor -->
    <BlockRenderer
      v-else
      :blocks="block.children"
      wrapper-class="contents"
    />
  </a>
</template>
