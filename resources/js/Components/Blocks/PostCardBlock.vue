<script setup>
import { inject, computed } from 'vue'

defineProps({ block: { type: Object, required: true } })

const loopItem = inject('loopItem', null)
const post     = computed(() => loopItem?.value ?? null)

const primaryCat = computed(() => post.value?.categories?.[0] ?? null)
const filepath   = computed(() => post.value?.slug ? `~/posts/${post.value.slug}.md` : '~/posts/untitled.md')
const callsign   = computed(() => post.value?.id ? `λ.${String(post.value.id).padStart(3,'0')}` : 'λ.—')
const glyph      = computed(() => primaryCat.value?.name?.toLowerCase() || 'posts')
const issueNo    = computed(() => post.value?.id ? `№ ${String(post.value.id).padStart(3,'0')}` : '№ —')

const readingTime = computed(() => {
  const text  = post.value?.excerpt || post.value?.body || ''
  const words = text.trim().split(/\s+/).length
  return Math.max(1, Math.ceil(words / 200))
})
</script>

<template>
  <article v-if="post" class="post-card flex flex-col h-full">

    <!-- Terminal cover -->
    <a :href="post.url" tabindex="-1" aria-hidden="true" class="post-card__cover block relative shrink-0">
      <div class="post-card__cover-bar flex items-center justify-between px-4 pt-3 pb-2">
        <span class="font-mono-blog text-[10px] truncate post-card__filepath">{{ filepath }}</span>
        <span class="font-mono-blog text-[10px] shrink-0 ml-3 post-card__callsign">{{ callsign }}</span>
      </div>
      <div class="px-4 py-4">
        <span class="font-mono-blog text-[9px] uppercase tracking-widest block mb-1 post-card__issue">{{ issueNo }}</span>
        <div class="post-card__glyph font-display font-bold leading-none">{{ glyph }}</div>
      </div>
      <div class="post-card__status absolute bottom-0 left-0 right-0 flex items-center gap-2 px-4 py-1.5">
        <span class="post-card__dot w-1.5 h-1.5 rounded-full inline-block"></span>
        <span class="font-mono-blog text-[9px] post-card__status-text">build · ok</span>
      </div>
    </a>

    <!-- Card body -->
    <div class="flex-1 flex flex-col p-5">
      <div v-if="primaryCat" class="mb-3">
        <a :href="`/blog/category/${primaryCat.slug}`" class="post-card__chip font-mono-blog text-[10px] px-2.5 py-1 rounded-full inline-flex transition-all duration-150">
          {{ primaryCat.name }}
        </a>
      </div>

      <h2 class="font-display font-semibold leading-snug mb-2 flex-1" style="font-family:'Space Grotesk', sans-serif; letter-spacing:-0.025em;">
        <a :href="post.url" class="post-card__title transition-colors duration-150" style="font-size:clamp(1rem, 2vw, 1.15rem);">
          {{ post.title }}
        </a>
      </h2>

      <p v-if="post.excerpt" class="text-sm leading-relaxed line-clamp-2 mb-4 post-card__excerpt" style="font-family:'Inter', sans-serif;">
        {{ post.excerpt }}
      </p>

      <div class="flex items-center gap-3 pt-3 mt-auto font-mono-blog text-[10px] post-card__meta">
        <span class="truncate">{{ post.author_name ?? 'Unknown' }}</span>
        <span class="post-card__dot-sep">·</span>
        <span class="shrink-0">{{ post.published_at_formatted }}</span>
        <span class="ml-auto shrink-0 tabular-nums">{{ readingTime }} min</span>
      </div>
    </div>
  </article>
</template>

<style scoped>
.post-card {
  background: var(--panel);
  border: 1px solid var(--line-strong);
  border-radius: var(--blog-radius);
  overflow: hidden;
  transition: border-color 150ms;
}
.post-card:hover { border-color: var(--accent); }

.post-card__cover {
  background: var(--code);
  min-height: 9rem;
}
.post-card__cover-bar { border-bottom: 1px solid rgba(255,255,255,0.06); }

.post-card__filepath  { color: var(--code-ink); opacity: 0.45; }
.post-card__callsign  { color: var(--code-ink); opacity: 0.55; }
.post-card__issue     { color: var(--code-ink); opacity: 0.30; }

.post-card__glyph {
  font-family: 'Space Grotesk', sans-serif;
  font-size: clamp(1.4rem, 3.5vw, 2rem);
  color: var(--code-ink);
  opacity: 0.75;
  letter-spacing: -0.04em;
}

.post-card__status { border-top: 1px solid rgba(255,255,255,0.06); }
.post-card__dot    { background: var(--accent); opacity: 0.7; }
.post-card__status-text { color: var(--code-ink); opacity: 0.35; }

.post-card__chip {
  border: 1px solid var(--line-strong);
  color: var(--soft);
}
.post-card__chip:hover {
  border-color: var(--accent);
  color: var(--accent);
}

.post-card__title { color: var(--ink); }
.post-card__title:hover { color: var(--accent); }

.post-card__excerpt { color: var(--soft); }

.post-card__meta    { border-top: 1px solid var(--line); color: var(--soft); }
.post-card__dot-sep { color: var(--line-strong); }
</style>
