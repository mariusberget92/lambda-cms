<script setup>
import { inject } from 'vue'

const props = defineProps({ block: Object })
const post = inject('postContext', null)
</script>

<template>
  <div v-if="post" class="taxonomy-panel p-4">
    <p class="font-mono-blog text-[10px] uppercase tracking-widest mb-3 taxonomy-soft">Topics</p>
    <div class="flex flex-wrap gap-1.5">
      <template v-if="props.block.data?.showCategories !== false">
        <a
          v-for="cat in post.categories"
          :key="cat.id"
          :href="`/blog/category/${cat.slug}`"
          class="tax-cat font-mono-blog text-[11px] px-3 py-1 rounded-full inline-flex transition-opacity duration-150"
        >{{ cat.name }}</a>
      </template>
      <template v-if="props.block.data?.showTags !== false">
        <a
          v-for="tag in post.tags"
          :key="tag.slug"
          :href="`/blog/tag/${tag.slug}`"
          class="tax-tag font-mono-blog text-[11px] px-3 py-1 rounded-full inline-flex transition-all duration-150"
        >#{{ tag.name }}</a>
      </template>
    </div>
  </div>
  <div v-else class="taxonomy-panel p-4">
    <div class="h-3 w-12 rounded mb-3 taxonomy-skel-label" />
    <div class="flex gap-1.5">
      <div class="h-6 w-16 rounded-full taxonomy-skel" />
      <div class="h-6 w-20 rounded-full taxonomy-skel taxonomy-skel--dim" />
    </div>
  </div>
</template>

<style scoped>
.taxonomy-panel {
  background: var(--panel);
  border: 1px solid var(--line);
  border-radius: var(--blog-radius);
}
.taxonomy-soft { color: var(--soft); }

.tax-cat {
  background: var(--accent);
  color: var(--accent-ink);
  border: 1px solid var(--accent);
}
.tax-cat:hover { opacity: 0.82; }

.tax-tag {
  background: transparent;
  color: var(--soft);
  border: 1px solid var(--line-strong);
}
.tax-tag:hover {
  border-color: var(--accent);
  color: var(--accent);
}

.taxonomy-skel-label { background: var(--line-strong); }
.taxonomy-skel       { background: var(--line-strong); }
.taxonomy-skel--dim  { background: var(--line); }
</style>
