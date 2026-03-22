<script setup>
import { inject } from 'vue'
const post        = inject('postContext', null)
const commentsData = inject('commentsData', null)
</script>
<template>
  <div v-if="post">
    <p class="text-sm text-muted-foreground">Comments section ({{ commentsData?.total ?? 0 }} comments)</p>
  </div>
  <div v-else class="h-24 rounded bg-muted/40 animate-pulse" />
</template>
