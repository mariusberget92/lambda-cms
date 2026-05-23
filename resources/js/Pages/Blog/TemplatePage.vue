<!-- resources/js/Pages/Blog/TemplatePage.vue -->
<script setup>
import { provide, onMounted, onUnmounted } from 'vue'
import { Head }      from '@inertiajs/vue3'
import BlogLayout    from '@/Layouts/BlogLayout.vue'
import SeoHead       from '@/Components/SeoHead.vue'
import BlockRenderer from '@/Components/BlockRenderer.vue'

defineOptions({ layout: BlogLayout })

const props = defineProps({
  blocks:         { type: Array,  default: () => [] },
  customJs:       { type: String, default: null },
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

const SCRIPT_ATTR = 'data-lambda-template-js'

onMounted(() => {
  const code = props.customJs
  if (!code) return
  const el = document.createElement('script')
  el.setAttribute(SCRIPT_ATTR, '1')
  el.textContent = code
  document.head.appendChild(el)
})

onUnmounted(() => {
  document.querySelectorAll(`[${SCRIPT_ATTR}]`).forEach(el => el.remove())
})
</script>

<template>
  <Head :title="seo.title ?? ''" />
  <SeoHead v-if="seo.title" :seo="seo" />

  <div class="py-10">
    <BlockRenderer v-if="blocks.length" :blocks="blocks" />
    <p v-else class="px-4 sm:px-6 text-muted-foreground">This template has no content yet.</p>
  </div>
</template>
