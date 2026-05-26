<script setup>
const props = defineProps({ block: { type: Object, required: true } })
</script>

<template>
  <div class="space-y-2" style="padding: 0.25rem 0;">
    <!-- Number + rule + meta row -->
    <div class="flex items-center gap-4">
      <span
        v-if="block.data?.number"
        class="font-mono-blog text-[11px] shrink-0"
        style="color:var(--soft);"
      >§ {{ block.data.number }}</span>

      <!-- Hairline rule -->
      <div
        class="flex-1 h-px"
        style="background:var(--line-strong);"
      />

      <span
        v-if="block.data?.meta"
        class="font-mono-blog text-[10px] shrink-0 uppercase tracking-widest"
        style="color:var(--soft);"
      >{{ block.data.meta }}</span>
    </div>

    <!-- Title -->
    <h2
      v-if="block.data?.title"
      class="font-display font-bold leading-tight"
      :style="{
        fontFamily: '\'Space Grotesk\', sans-serif',
        fontSize: 'clamp(1.5rem, 3vw, 2rem)',
        color: 'var(--ink)',
        letterSpacing: '-0.03em',
      }"
    >{{ block.data.title }}</h2>

    <!-- Optional rule below title -->
    <div v-if="block.data?.showRule" class="h-px w-10 mt-1" style="background:var(--accent);" />
  </div>
</template>
