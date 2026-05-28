<!-- Dark tag cloud band or light sidebar widget depending on variant -->
<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

const props = defineProps({ block: { type: Object, required: true } })

const variant = computed(() => props.block.data?.variant ?? 'band')
const isWidget = computed(() => variant.value === 'widget')

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
  <!-- Widget variant: light card matching Search/Categories boxes -->
  <div v-if="isWidget" class="band-widget">
    <p class="band-widget__label font-mono-blog text-[10px] uppercase tracking-widest mb-3">
      {{ block.data?.label || 'Tags' }}
    </p>

    <div v-if="loading" class="flex flex-wrap gap-2">
      <div v-for="i in 8" :key="i" class="h-6 rounded-full animate-pulse band-widget__skel" :style="{ width: `${60 + (i * 17) % 60}px` }" />
    </div>

    <div v-else class="flex flex-wrap gap-1.5">
      <a
        v-for="tag in tags"
        :key="tag.slug"
        :href="`/blog/tag/${tag.slug}`"
        class="band-widget__tag font-mono-blog text-[11px] px-2.5 py-1 rounded-full transition-all duration-150"
      >
        {{ tag.name }}
        <span v-if="block.data?.showCount && tag.posts_count" class="opacity-50 ml-0.5">{{ tag.posts_count }}</span>
      </a>
    </div>
  </div>

  <!-- Band variant: dark full-width tag cloud (original) -->
  <div
    v-else
    class="band-block relative overflow-hidden"
  >
    <!-- Subtle grid pattern -->
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

    <p
      v-if="block.data?.label"
      class="band-block__label font-mono-blog text-[10px] uppercase tracking-widest mb-6 relative z-10"
    >{{ block.data.label }}</p>

    <div v-if="loading" class="flex flex-wrap gap-2 relative z-10">
      <div v-for="i in 12" :key="i" class="h-6 rounded-full animate-pulse band-block__skel" :style="{ width: `${60 + (i * 17) % 60}px` }" />
    </div>

    <div v-else class="flex flex-wrap gap-2 relative z-10">
      <a
        v-for="tag in tags"
        :key="tag.slug"
        :href="`/blog/tag/${tag.slug}`"
        class="band-block__tag font-mono-blog text-[11px] px-3 py-1.5 rounded-full transition-all duration-150"
      >
        {{ tag.name }}
        <span v-if="block.data?.showCount && tag.posts_count" class="opacity-40 ml-1">{{ tag.posts_count }}</span>
      </a>
    </div>
  </div>
</template>

<style scoped>
/* ── Widget variant ── */
.band-widget {
  width: 100%;
  background: var(--panel);
  border: 1px solid var(--line-strong);
  border-radius: var(--blog-radius);
  padding: 1.25rem;
  box-sizing: border-box;
}
.band-widget__label { color: var(--soft); }
.band-widget__skel  { background: var(--line-strong); }
.band-widget__tag {
  color: var(--soft);
  border: 1px solid var(--line-strong);
  background: transparent;
}
.band-widget__tag:hover {
  background: var(--accent);
  color: var(--accent-ink);
  border-color: var(--accent);
}

/* ── Band variant ── */
.band-block {
  background: var(--code);
  padding: clamp(2.5rem, 5vw, 4rem) var(--gutter, 2rem);
}
.band-block__label {
  color: var(--code-ink);
  opacity: 0.4;
}
.band-block__skel  { background: rgba(255,255,255,0.06); }
.band-block__tag {
  border: 1px solid rgba(255,255,255,0.1);
  color: var(--code-ink);
}
.band-block__tag:hover {
  border-color: var(--accent);
  color: var(--accent);
}
</style>
