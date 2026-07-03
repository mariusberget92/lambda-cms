<script setup>
import { inject, computed } from 'vue'

defineProps({ block: { type: Object, required: true } })

const loopItem = inject('loopItem', null)
const post     = computed(() => loopItem?.value ?? null)

const primaryCat = computed(() => post.value?.categories?.[0] ?? null)

const readingTime = computed(() => {
  const text  = post.value?.excerpt || post.value?.body || ''
  const words = text.trim().split(/\s+/).length
  return Math.max(1, Math.ceil(words / 200))
})
</script>

<template>
  <article v-if="post" class="post-card flex flex-col h-full">
    <div class="post-card__accent"></div>

    <div class="flex-1 flex flex-col p-5">
      <div v-if="primaryCat" class="mb-3">
        <a :href="`/blog/category/${primaryCat.slug}`" class="post-card__chip">
          {{ primaryCat.name }}
        </a>
      </div>

      <h2 class="post-card__heading flex-1">
        <a :href="post.url" class="post-card__title">
          {{ post.title }}
        </a>
      </h2>

      <p v-if="post.excerpt" class="post-card__excerpt">
        {{ post.excerpt }}
      </p>

      <div class="post-card__meta">
        <span class="truncate">{{ post.author_name ?? 'Unknown' }}</span>
        <span class="post-card__dot">·</span>
        <span class="shrink-0">{{ post.published_at_formatted }}</span>
        <span class="ml-auto shrink-0 tabular-nums">{{ readingTime }} min</span>
      </div>
    </div>
  </article>
</template>

<style scoped>
.post-card {
  background: var(--panel);
  border: 1px solid var(--line);
  border-radius: var(--blog-radius);
  overflow: hidden;
  transition: border-color 150ms, box-shadow 150ms;
}
.post-card:hover {
  border-color: var(--accent);
  box-shadow: 0 2px 12px rgba(124, 58, 237, 0.08);
}

.post-card__accent {
  height: 3px;
  background: var(--accent);
  opacity: 0.6;
  transition: opacity 150ms;
}
.post-card:hover .post-card__accent { opacity: 1; }

.post-card__chip {
  font-family: 'JetBrains Mono', monospace;
  font-size: 0.65rem;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  padding: 0.2rem 0.6rem;
  border-radius: 9999px;
  border: 1px solid var(--line-strong);
  color: var(--soft);
  display: inline-flex;
  transition: all 150ms;
}
.post-card__chip:hover {
  border-color: var(--accent);
  color: var(--accent);
}

.post-card__heading {
  font-family: 'Space Grotesk', sans-serif;
  font-weight: 600;
  font-size: clamp(1rem, 2vw, 1.15rem);
  line-height: 1.35;
  letter-spacing: -0.025em;
  margin-bottom: 0.5rem;
}

.post-card__title {
  color: var(--ink);
  transition: color 150ms;
}
.post-card__title:hover { color: var(--accent); }

.post-card__excerpt {
  font-family: 'Inter', sans-serif;
  font-size: 0.875rem;
  line-height: 1.6;
  color: var(--soft);
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  margin-bottom: 1rem;
}

.post-card__meta {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding-top: 0.75rem;
  margin-top: auto;
  border-top: 1px solid var(--line);
  font-family: 'JetBrains Mono', monospace;
  font-size: 0.625rem;
  color: var(--soft);
}
.post-card__dot { color: var(--line-strong); }
</style>
