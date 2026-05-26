<script setup>
import { inject, computed } from 'vue'

defineProps({ block: { type: Object, required: true } })

const loopItem = inject('loopItem', null)
const post     = computed(() => loopItem?.value ?? null)

// Category accent via OKLCH from hue field
const primaryCat  = computed(() => post.value?.categories?.[0] ?? null)
const catHue      = computed(() => primaryCat.value?.hue ?? 220)
const accentColor = computed(() => `oklch(0.62 0.16 ${catHue.value})`)

// Derived cover fields
const filepath = computed(() => post.value?.slug ? `~/lambdacms/posts/${post.value.slug}.md` : '~/lambdacms/posts/untitled.md')
const callsign = computed(() => post.value?.id ? `λ.${post.value.id}` : 'λ.—')
const glyph    = computed(() => primaryCat.value?.name?.toLowerCase() || 'posts')
const issueNo  = computed(() => post.value?.id ? `№ ${post.value.id}` : '№ —')

const readingTime = computed(() => {
  const text  = post.value?.excerpt || post.value?.body || ''
  const words = text.trim().split(/\s+/).length
  return Math.max(1, Math.ceil(words / 200))
})
</script>

<template>
  <article
    v-if="post"
    class="group flex flex-col h-full transition-colors duration-150"
    :style="{
      background: 'var(--panel)',
      border: '1px solid var(--line-strong)',
      borderRadius: 'var(--blog-radius, 6px)',
      overflow: 'hidden',
    }"
    @mouseenter="$event.currentTarget.style.borderColor = accentColor"
    @mouseleave="$event.currentTarget.style.borderColor = 'var(--line-strong)'"
  >
    <!-- Terminal cover -->
    <a :href="post.url" tabindex="-1" aria-hidden="true" class="block relative shrink-0" style="background:var(--code); min-height:9rem;">
      <!-- Filepath + callsign -->
      <div class="flex items-center justify-between px-4 pt-3 pb-2" style="border-bottom:1px solid rgba(255,255,255,0.06);">
        <span class="font-mono-blog text-[10px] truncate" style="color:var(--code-ink); opacity:0.45;">{{ filepath }}</span>
        <span class="font-mono-blog text-[10px] shrink-0 ml-3" :style="{ color: accentColor }">{{ callsign }}</span>
      </div>

      <!-- Glyph body -->
      <div class="px-4 py-4">
        <span class="font-mono-blog text-[9px] uppercase tracking-widest block mb-1" style="color:var(--code-ink); opacity:0.3;">{{ issueNo }}</span>
        <div
          class="font-display font-bold leading-none"
          :style="{
            fontFamily: '\'Space Grotesk\', sans-serif',
            fontSize: 'clamp(1.4rem, 3.5vw, 2rem)',
            color: accentColor,
            letterSpacing: '-0.04em',
          }"
        >{{ glyph }}</div>
      </div>

      <!-- Status bar -->
      <div class="absolute bottom-0 left-0 right-0 flex items-center gap-2 px-4 py-1.5" style="border-top:1px solid rgba(255,255,255,0.06);">
        <span class="w-1.5 h-1.5 rounded-full inline-block" :style="{ background: accentColor }"></span>
        <span class="font-mono-blog text-[9px]" style="color:var(--code-ink); opacity:0.35;">build · ok</span>
      </div>
    </a>

    <!-- Card body -->
    <div class="flex-1 flex flex-col p-5">

      <!-- Category chip -->
      <div v-if="primaryCat" class="mb-3">
        <a
          :href="`/blog/category/${primaryCat.slug}`"
          class="font-mono-blog text-[10px] px-2.5 py-1 rounded-full inline-flex transition-all duration-150"
          :style="{ border: `1px solid ${accentColor}`, color: accentColor }"
          @mouseenter="e => { e.currentTarget.style.background = accentColor; e.currentTarget.style.color = 'var(--accent-ink)'; }"
          @mouseleave="e => { e.currentTarget.style.background = 'transparent'; e.currentTarget.style.color = accentColor; }"
        >{{ primaryCat.name }}</a>
      </div>

      <!-- Title -->
      <h2 class="font-display font-semibold leading-snug mb-2 flex-1" :style="{ fontFamily: '\'Space Grotesk\', sans-serif', letterSpacing: '-0.025em' }">
        <a
          :href="post.url"
          class="transition-colors duration-150"
          style="color:var(--ink); font-size:clamp(1rem, 2vw, 1.15rem);"
          @mouseenter="e => e.currentTarget.style.color = accentColor"
          @mouseleave="e => e.currentTarget.style.color = 'var(--ink)'"
        >{{ post.title }}</a>
      </h2>

      <!-- Excerpt -->
      <p v-if="post.excerpt" class="text-sm leading-relaxed line-clamp-2 mb-4" style="color:var(--soft); font-family:'Inter', sans-serif;">
        {{ post.excerpt }}
      </p>

      <!-- Meta row -->
      <div class="flex items-center gap-3 pt-3 mt-auto font-mono-blog text-[10px]" style="border-top:1px solid var(--line); color:var(--soft);">
        <span class="truncate">{{ post.author_name ?? 'Unknown' }}</span>
        <span style="color:var(--line-strong);">·</span>
        <span class="shrink-0">{{ post.published_at_formatted }}</span>
        <span class="ml-auto shrink-0 tabular-nums">{{ readingTime }} min</span>
      </div>

    </div>
  </article>
</template>
