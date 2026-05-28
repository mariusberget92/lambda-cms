<!-- Blog index masthead / hero — full-width dark panel with title, subtitle, stat row -->
<script setup>
import { computed } from 'vue'

const props = defineProps({ block: { type: Object, required: true } })

const stats = computed(() => props.block.data?.stats ?? [])

// Parse "Engineering Notes from a ||runtime||" → renders ||word|| as mono accent span
const titleParts = computed(() => {
  const raw = props.block.data?.title ?? ''
  const parts = []
  const regex = /\|\|(.+?)\|\|/g
  let last = 0, m
  while ((m = regex.exec(raw)) !== null) {
    if (m.index > last) parts.push({ text: raw.slice(last, m.index), mono: false })
    parts.push({ text: m[1], mono: true })
    last = m.index + m[0].length
  }
  if (last < raw.length) parts.push({ text: raw.slice(last), mono: false })
  return parts.length ? parts : [{ text: raw, mono: false }]
})
</script>

<template>
  <div
    class="relative overflow-hidden w-full"
    :style="{
      background: 'var(--code)',
      padding: 'clamp(3rem, 6vw, 5rem) var(--gutter, 2rem)',
    }"
  >
    <!-- Subtle grid pattern -->
    <div
      class="absolute inset-0 pointer-events-none"
      style="
        background-image:
          linear-gradient(rgba(255,255,255,0.025) 1px, transparent 1px),
          linear-gradient(90deg, rgba(255,255,255,0.025) 1px, transparent 1px);
        background-size: 32px 32px;
        mask-image: radial-gradient(ellipse 80% 60% at 50% 100%, black 40%, transparent 100%);
      "
    />

    <div class="relative z-10 max-w-[1320px] mx-auto">

      <!-- Eyebrow row -->
      <div class="flex items-center justify-between mb-6">
        <span
          v-if="block.data?.eyebrow"
          class="font-mono-blog text-[11px] tracking-widest"
          style="color:var(--code-ink); opacity:0.45;"
        >{{ block.data.eyebrow }}</span>

        <div v-if="block.data?.accentWord" class="flex items-center gap-2">
          <div class="h-px w-8" style="background:var(--accent);"></div>
          <span class="font-mono-blog text-[10px] uppercase tracking-widest" style="color:var(--accent);">
            {{ block.data.accentWord }}
          </span>
        </div>
      </div>

      <!-- Main title — supports ||mono|| inline markers -->
      <h1
        v-if="block.data?.title"
        :style="{
          fontFamily: '\'Space Grotesk\', ui-sans-serif, system-ui, sans-serif',
          fontWeight: '700',
          fontSize: 'clamp(2.25rem, 6vw, 4.5rem)',
          color: 'var(--code-ink)',
          lineHeight: '1.05',
          letterSpacing: '-0.04em',
          marginBottom: block.data?.subtitle ? '0.75rem' : '0',
        }"
      >
        <template v-for="(part, i) in titleParts" :key="i">
          <span
            v-if="part.mono"
            :style="{
              fontFamily: '\'JetBrains Mono\', monospace',
              fontSize: '0.82em',
              color: 'var(--accent)',
              fontWeight: '400',
            }"
          >&lt;{{ part.text }}&nbsp;/&gt;</span>
          <template v-else>{{ part.text }}</template>
        </template>
      </h1>

      <!-- Subtitle -->
      <p
        v-if="block.data?.subtitle"
        class="text-base leading-relaxed mb-8 max-w-2xl"
        style="color:var(--code-ink); opacity:0.5; font-family:'Inter', sans-serif;"
      >{{ block.data.subtitle }}</p>

      <!-- Divider -->
      <div v-if="stats.length" class="h-px mb-8" style="background:var(--line-strong);" />

      <!-- Stat cards row -->
      <div v-if="stats.length" class="flex flex-wrap gap-8">
        <div
          v-for="stat in stats"
          :key="stat.label"
          class="flex flex-col gap-0.5"
        >
          <span
            :style="{
              fontFamily: '\'Space Grotesk\', sans-serif',
              fontVariantNumeric: 'tabular-nums',
              fontWeight: '700',
              fontSize: 'clamp(1.5rem, 3vw, 2.25rem)',
              color: 'var(--code-ink)',
              lineHeight: '1',
              letterSpacing: '-0.03em',
            }"
          >{{ stat.value }}</span>
          <span
            class="font-mono-blog text-[10px] uppercase tracking-widest mt-1"
            style="color:var(--code-ink); opacity:0.35;"
          >{{ stat.label }}</span>
        </div>
      </div>

    </div>
  </div>
</template>
