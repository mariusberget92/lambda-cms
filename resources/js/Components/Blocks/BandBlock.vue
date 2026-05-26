<!-- Dark tag cloud band — bound to --code background with grid pattern -->
<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const props = defineProps({ block: { type: Object, required: true } })

const tags    = ref(props.block.data?.resolved?.items ?? [])
const loading = ref(false)

onMounted(async () => {
  if (tags.value.length) return
  loading.value = true
  try {
    const { data } = await axios.post('/api/v1/query', {
      source: 'tags',
      filters: [],
      sort: { field: 'posts_count', direction: 'desc' },
      limit: props.block.data?.limit ?? 30,
      offset: 0,
    })
    tags.value = data.items ?? []
  } catch (_) {}
  finally { loading.value = false }
})
</script>

<template>
  <div
    class="relative overflow-hidden"
    :style="{
      background: 'var(--code)',
      padding: 'clamp(2.5rem, 5vw, 4rem) var(--gutter, 2rem)',
    }"
  >
    <!-- Subtle grid pattern via two linear-gradients -->
    <div
      class="absolute inset-0 pointer-events-none"
      style="
        background-image:
          linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),
          linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
        background-size: 32px 32px;
        mask-image: radial-gradient(ellipse at center, black 40%, transparent 100%);
      "
    />

    <!-- Section label -->
    <p
      v-if="block.data?.label"
      class="font-mono-blog text-[10px] uppercase tracking-widest mb-6 relative z-10"
      style="color:var(--code-ink); opacity:0.4;"
    >{{ block.data.label }}</p>

    <!-- Tag cloud -->
    <div v-if="loading" class="flex flex-wrap gap-2 relative z-10">
      <div v-for="i in 12" :key="i" class="h-6 rounded-full animate-pulse" :style="{ width: `${60 + (i * 17) % 60}px`, background: 'rgba(255,255,255,0.06)' }" />
    </div>

    <div v-else class="flex flex-wrap gap-2 relative z-10">
      <a
        v-for="tag in tags"
        :key="tag.slug"
        :href="`/blog/tag/${tag.slug}`"
        class="font-mono-blog text-[11px] px-3 py-1.5 rounded-full transition-all duration-150"
        style="border:1px solid rgba(255,255,255,0.1); color:var(--code-ink);"
        @mouseenter="e => { e.currentTarget.style.borderColor = 'var(--accent)'; e.currentTarget.style.color = 'var(--accent)'; }"
        @mouseleave="e => { e.currentTarget.style.borderColor = 'rgba(255,255,255,0.1)'; e.currentTarget.style.color = 'var(--code-ink)'; }"
      >
        {{ tag.name }}
        <span v-if="block.data?.showCount && tag.posts_count" class="opacity-40 ml-1">{{ tag.posts_count }}</span>
      </a>
    </div>
  </div>
</template>
