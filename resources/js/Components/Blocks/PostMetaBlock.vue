<script setup>
import { inject, computed } from 'vue'
const props = defineProps({ block: Object })
const post = inject('postContext', null)
const show = (field) => props.block.data?.[field] !== false
const readTime = computed(() => {
  if (!post?.body) return null
  const words = post.body.replace(/<[^>]+>/g, ' ').split(/\s+/).filter(Boolean).length
  return Math.ceil(words / 200) + ' min read'
})
</script>
<template>
  <div v-if="post" class="flex flex-wrap gap-3 text-sm text-muted-foreground">
    <span v-if="show('date') && post.published_at">{{ post.published_at }}</span>
    <span v-if="show('author') && post.author?.name">by {{ post.author.name }}</span>
    <span v-if="show('readTime') && readTime">{{ readTime }}</span>
  </div>
  <div v-else class="h-4 rounded bg-muted/40 animate-pulse w-48" />
</template>
