<!-- resources/js/Pages/Blog/Page.vue -->
<script setup>
import { Head }      from '@inertiajs/vue3'
import BlogLayout    from '@/Layouts/BlogLayout.vue'
import SeoHead       from '@/Components/SeoHead.vue'
import BlockRenderer from '@/Components/BlockRenderer.vue'

// Uses the same layout as Blog/Show.vue
defineOptions({ layout: BlogLayout })

const props = defineProps({
  page: { type: Object, required: true }, // { title, slug, blocks }
  seo:  { type: Object, default: () => ({}) },
})
</script>

<template>
  <Head :title="seo.title ?? page.title" />
  <SeoHead v-if="seo.title" :seo="seo" />

  <article class="max-w-3xl mx-auto px-4 py-10">
    <h1 class="text-4xl font-bold mb-8">{{ page.title }}</h1>
    <BlockRenderer v-if="page.blocks?.length" :blocks="page.blocks" />
    <p v-else class="text-muted-foreground">This page has no content yet.</p>
  </article>
</template>
