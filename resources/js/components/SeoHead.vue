<script setup>
import { Head } from '@inertiajs/vue3'

defineProps({ seo: { type: Object, required: true } })
</script>

<template>
  <Head>
    <title>{{ seo.title }}</title>
    <meta name="description"        :content="seo.description"       v-if="seo.description" />
    <link rel="canonical"           :href="seo.canonical" />
    <meta property="og:type"        :content="seo.type ?? 'website'" />
    <meta property="og:url"         :content="seo.canonical" />
    <meta property="og:title"       :content="seo.title" />
    <meta property="og:description" :content="seo.description"       v-if="seo.description" />
    <meta property="og:image"       :content="seo.image"             v-if="seo.image" />
  </Head>
</template>
