<!-- Terminal spec-sheet cover block — standalone or inside a loop -->
<script setup>
import { inject, computed } from 'vue'

const props = defineProps({ block: { type: Object, required: true } })

const loopItem = inject('loopItem', null)
const post     = computed(() => loopItem?.value ?? null)

// Derive cover fields from loop context when available, fall back to block.data
const filepath = computed(() => {
  if (post.value?.slug) return `~/lambdacms/posts/${post.value.slug}.md`
  return props.block.data?.filepath || '~/lambdacms/posts/untitled.md'
})

const callsign = computed(() => {
  if (post.value?.id) return `λ.${post.value.id}`
  return props.block.data?.callsign || 'λ.—'
})

const glyph = computed(() => {
  if (post.value?.categories?.[0]?.name) return post.value.categories[0].name.toLowerCase()
  return props.block.data?.glyph || 'posts'
})

const issueNo = computed(() => {
  if (post.value?.id) return `№ ${post.value.id}`
  return props.block.data?.issueNo || '№ —'
})

const statusOk = computed(() => props.block.data?.statusOk !== false)

// Accent from category hue (OKLCH)
const catHue = computed(() => {
  const h = post.value?.categories?.[0]?.hue ?? props.block.data?.hue ?? 220
  return h
})
const accentColor = computed(() => `oklch(0.62 0.16 ${catHue.value})`)

const variant = computed(() => props.block.data?.variant || 'flat')

// Stripe pattern for 'stripe' variant
const stripeStyle = computed(() => {
  if (variant.value !== 'stripe') return {}
  return {
    backgroundImage: `repeating-linear-gradient(
      -45deg,
      transparent,
      transparent 6px,
      rgba(255,255,255,0.025) 6px,
      rgba(255,255,255,0.025) 12px
    )`,
  }
})

// Frame extra border for 'frame' variant
const frameClass = computed(() => variant.value === 'frame' ? 'ring-inset' : '')
</script>

<template>
  <div
    class="relative overflow-hidden select-none"
    :style="{
      background: 'var(--code)',
      borderRadius: 'var(--blog-radius, 6px)',
      ...stripeStyle,
      ...(variant === 'frame' ? { outline: `2px solid ${accentColor}`, outlineOffset: '-4px' } : {}),
    }"
  >
    <!-- Top bar: filepath + callsign -->
    <div class="flex items-center justify-between px-4 pt-3 pb-2" style="border-bottom:1px solid rgba(255,255,255,0.06);">
      <span class="font-mono-blog text-[11px] truncate" style="color:var(--code-ink); opacity:0.5;">{{ filepath }}</span>
      <span class="font-mono-blog text-[11px] shrink-0 ml-4" :style="{ color: accentColor }">{{ callsign }}</span>
    </div>

    <!-- Main body -->
    <div class="px-4 py-6 flex flex-col gap-2">
      <!-- Issue number — small mono label -->
      <span class="font-mono-blog text-[10px] uppercase tracking-widest" style="color:var(--code-ink); opacity:0.35;">{{ issueNo }}</span>

      <!-- Glyph — large display word -->
      <div
        class="font-display font-bold leading-none"
        :style="{
          fontFamily: '\'Space Grotesk\', sans-serif',
          fontSize: 'clamp(2rem, 6vw, 3.5rem)',
          color: accentColor,
          letterSpacing: '-0.04em',
        }"
      >{{ glyph }}</div>

      <!-- Accent rule -->
      <div class="mt-2 h-px w-12" :style="{ background: accentColor, opacity: '0.4' }" />
    </div>

    <!-- Status footer -->
    <div v-if="statusOk" class="flex items-center gap-2 px-4 py-2" style="border-top:1px solid rgba(255,255,255,0.06);">
      <span class="w-1.5 h-1.5 rounded-full inline-block" :style="{ background: accentColor }"></span>
      <span class="font-mono-blog text-[10px]" style="color:var(--code-ink); opacity:0.4;">build · ok</span>
    </div>
  </div>
</template>
