<!-- resources/js/Pages/Blog/Page.vue -->
<script setup>
import { onMounted, onUnmounted } from 'vue'
import { Head }      from '@inertiajs/vue3'
import BlogLayout    from '@/Layouts/BlogLayout.vue'
import SeoHead       from '@/Components/SeoHead.vue'
import BlockRenderer from '@/Components/BlockRenderer.vue'

// Uses the same layout as Blog/Show.vue
defineOptions({ layout: BlogLayout })

const props = defineProps({
  page: { type: Object, required: true }, // { title, slug, blocks, custom_js }
  seo:  { type: Object, default: () => ({}) },
})

const SCRIPT_ATTR = 'data-lambda-page-js'

onMounted(() => {
  const code = props.page?.custom_js
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
  <Head :title="seo.title ?? page.title" />
  <SeoHead v-if="seo.title" :seo="seo" />

  <div class="py-10">
    <BlockRenderer v-if="page.blocks?.length" :blocks="page.blocks" />
    <p v-else class="px-4 sm:px-6 text-center text-muted-foreground">This page has no content yet.</p>
  </div>
</template>
