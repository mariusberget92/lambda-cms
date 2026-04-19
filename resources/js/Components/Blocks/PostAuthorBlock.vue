<script setup>
import { inject } from 'vue'
const props = defineProps({ block: Object })
const post = inject('postContext', null)
</script>
<template>
  <div v-if="post" class="flex items-center gap-3">
    <img v-if="props.block.data?.showAvatar !== false && post.author?.avatar_url"
      :src="post.author.avatar_url" :alt="post.author.name"
      class="w-10 h-10 rounded-full object-cover" />
    <div v-else-if="props.block.data?.showAvatar !== false"
      class="w-10 h-10 rounded-full bg-muted flex items-center justify-center text-sm font-semibold">
      {{ post.author?.name?.[0] ?? '?' }}
    </div>
    <p class="font-medium text-sm">{{ post.author?.name }}</p>
  </div>
  <div v-else class="flex items-center gap-3">
    <div class="w-10 h-10 rounded-full bg-muted/40 animate-pulse" />
    <div class="h-4 w-32 rounded bg-muted/40 animate-pulse" />
  </div>
</template>
