<!-- resources/js/Components/Blocks/LinkBlock.vue -->
<script setup>
import { computed } from 'vue'
import BlockRenderer      from '@/Components/BlockRenderer.vue'
import { useFieldBinding } from '@/composables/useLoopBinding.js'

const props = defineProps({ block: { type: Object, required: true } })

const resolvedLabel = useFieldBinding(() => props.block, 'label')
const resolvedUrl   = useFieldBinding(() => props.block, 'url')

const hasChildren = computed(() => props.block.children?.length > 0)

const href   = computed(() => resolvedUrl.value   || '#')
const target = computed(() => props.block.data?.target || '_self')
const rel    = computed(() => props.block.data?.rel    || undefined)
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
      {{ resolvedLabel || resolvedUrl || href }}
    </template>

    <!-- Container-style link: child blocks wrapped in the anchor -->
    <BlockRenderer
      v-else
      :blocks="block.children"
      wrapper-class="contents"
    />
  </a>
</template>
