<script setup>
import { inject } from 'vue'
const props = defineProps({ block: Object })
const post = inject('postContext', null)
</script>
<template>
  <div v-if="post" class="flex flex-wrap gap-2">
    <template v-if="props.block.data?.showCategories !== false">
      <a v-for="cat in post.categories" :key="cat.id"
        :href="`/blog/category/${cat.slug}`"
        class="text-xs px-2.5 py-1 rounded-full bg-primary/10 text-primary hover:bg-primary/20 transition-colors">
        {{ cat.name }}
      </a>
    </template>
    <template v-if="props.block.data?.showTags !== false">
      <a v-for="tag in post.tags" :key="tag.slug"
        :href="`/blog/tag/${tag.slug}`"
        class="text-xs px-2.5 py-1 rounded-full border border-border text-muted-foreground hover:text-foreground hover:border-foreground transition-colors">
        #{{ tag.name }}
      </a>
    </template>
  </div>
  <div v-else class="flex gap-2">
    <div class="h-6 w-16 rounded-full bg-muted/40 animate-pulse" />
    <div class="h-6 w-20 rounded-full bg-muted/40 animate-pulse" />
  </div>
</template>
