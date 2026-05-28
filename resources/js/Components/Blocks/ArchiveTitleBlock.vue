<script setup>
import { inject } from 'vue'

defineProps({ block: Object })

const archive = inject('archiveContext', null)
</script>

<template>
  <div
    v-if="archive"
    class="relative overflow-hidden"
    :style="{
      background: 'var(--code)',
      borderRadius: 'var(--blog-radius, 6px)',
      padding: '1.75rem',
    }"
  >
    <!-- Grid pattern overlay -->
    <div
      class="absolute inset-0 pointer-events-none"
      style="
        background-image: linear-gradient(rgba(255,255,255,0.04) 1px, transparent 1px),
                          linear-gradient(90deg, rgba(255,255,255,0.04) 1px, transparent 1px);
        background-size: 32px 32px;
        mask-image: radial-gradient(ellipse at 30% 50%, black 30%, transparent 80%);
      "
    />

    <div class="relative">
      <p class="font-mono-blog text-[10px] uppercase tracking-widest mb-2" style="color:var(--code-ink); opacity:0.45;">
        {{ archive.type === 'category' ? 'Category' : 'Tag' }}
      </p>
      <h1
        class="font-display font-bold leading-tight mb-3"
        :style="{
          fontFamily: '\'Space Grotesk\', sans-serif',
          fontSize: 'clamp(1.75rem, 4vw, 2.5rem)',
          letterSpacing: '-0.03em',
          color: 'var(--code-ink)',
        }"
      >{{ archive.name }}</h1>
      <!-- Accent rule -->
      <div class="h-px w-10 mb-3" style="background:var(--accent);" />
      <p class="font-mono-blog text-[11px]" style="color:var(--code-ink); opacity:0.4;">
        {{ archive.postsCount }} {{ archive.postsCount === 1 ? 'post' : 'posts' }}
      </p>
    </div>
  </div>
  <div
    v-else
    class="h-32 rounded"
    style="background:var(--panel); border:1px solid var(--line);"
  />
</template>
