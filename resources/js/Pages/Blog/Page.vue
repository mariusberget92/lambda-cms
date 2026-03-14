<script setup>
import BlogLayout from '@/Layouts/BlogLayout.vue'
defineOptions({ layout: BlogLayout })
defineProps({ page: Object, seo: Object })
</script>
<template>
  <div>
    <h1>{{ page?.title }}</h1>
  </div>
</template>
