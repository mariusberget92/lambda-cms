<!-- resources/js/Pages/Blog/TemplatePage.vue -->
<script setup>
import { provide }   from 'vue'
import { Head }      from '@inertiajs/vue3'
import BlogLayout    from '@/Layouts/BlogLayout.vue'
import SeoHead       from '@/Components/SeoHead.vue'
import BlockRenderer from '@/Components/BlockRenderer.vue'

defineOptions({ layout: BlogLayout })

const props = defineProps({
  blocks:         { type: Array,  default: () => [] },
  postContext:    { type: Object, default: null },
  archiveContext: { type: Object, default: null },
  searchContext:  { type: Object, default: null },
  commentsData:   { type: Object, default: null },
  seo:            { type: Object, default: () => ({}) },
})

provide('postContext',    props.postContext)
provide('archiveContext', props.archiveContext)
provide('searchContext',  props.searchContext)
provide('commentsData',   props.commentsData)
</script>

<template>
  <Head :title="seo.title ?? ''" />
  <SeoHead v-if="seo.title" :seo="seo" />

  <div class="py-10">
    <BlockRenderer v-if="blocks.length" :blocks="blocks" />
    <p v-else class="px-4 sm:px-6 text-muted-foreground">This template has no content yet.</p>
  </div>
</template>
