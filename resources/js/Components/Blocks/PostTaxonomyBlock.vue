<script setup>
import { inject } from 'vue'

const props = defineProps({ block: Object })
const post = inject('postContext', null)
</script>

<template>
  <div
    v-if="post"
    class="p-4"
    :style="{
      background: 'var(--panel)',
      border: '1px solid var(--line)',
      borderRadius: 'var(--blog-radius, 6px)',
    }"
  >
    <p class="font-mono-blog text-[10px] uppercase tracking-widest mb-3" style="color:var(--soft);">Topics</p>
    <div class="flex flex-wrap gap-1.5">
      <template v-if="props.block.data?.showCategories !== false">
        <a
          v-for="cat in post.categories"
          :key="cat.id"
          :href="`/blog/category/${cat.slug}`"
          class="font-mono-blog text-[11px] px-3 py-1 rounded-full inline-flex transition-all duration-150"
          style="background:var(--accent); color:var(--accent-ink); border:1px solid var(--accent);"
          @mouseenter="e => e.currentTarget.style.opacity = '0.85'"
          @mouseleave="e => e.currentTarget.style.opacity = '1'"
        >{{ cat.name }}</a>
      </template>
      <template v-if="props.block.data?.showTags !== false">
        <a
          v-for="tag in post.tags"
          :key="tag.slug"
          :href="`/blog/tag/${tag.slug}`"
          class="font-mono-blog text-[11px] px-3 py-1 rounded-full inline-flex transition-all duration-150"
          style="background:transparent; color:var(--soft); border:1px solid var(--line-strong);"
          @mouseenter="e => { e.currentTarget.style.borderColor = 'var(--accent)'; e.currentTarget.style.color = 'var(--accent)'; }"
          @mouseleave="e => { e.currentTarget.style.borderColor = 'var(--line-strong)'; e.currentTarget.style.color = 'var(--soft)'; }"
        >#{{ tag.name }}</a>
      </template>
    </div>
  </div>
  <div
    v-else
    class="p-4"
    :style="{
      background: 'var(--panel)',
      border: '1px solid var(--line)',
      borderRadius: 'var(--blog-radius, 6px)',
    }"
  >
    <div class="h-3 w-12 rounded mb-3" style="background:var(--line-strong);" />
    <div class="flex gap-1.5">
      <div class="h-6 w-16 rounded-full" style="background:var(--line-strong);" />
      <div class="h-6 w-20 rounded-full" style="background:var(--line);" />
    </div>
  </div>
</template>
