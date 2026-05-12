<!-- resources/js/Components/Blocks/TocBlock.vue -->
<template>
  <nav class="rounded-xl border border-border bg-card px-5 py-4" aria-label="Table of contents">
    <p v-if="block.data?.title" class="font-semibold text-sm mb-3">{{ block.data.title }}</p>
    <component :is="block.data?.ordered ? 'ol' : 'ul'" class="space-y-1.5">
      <li
        v-for="(item, i) in block.data?.items ?? []"
        :key="i"
        :style="{ paddingLeft: `${((item.level ?? 1) - 1) * 1}rem` }"
      >
        <a
          :href="item.anchor ? `#${item.anchor}` : '#'"
          class="text-sm text-muted-foreground hover:text-primary transition-colors hover:underline underline-offset-2"
        >
          <span v-if="block.data?.ordered" class="mr-1 font-medium text-foreground/60">{{ i + 1 }}.</span>
          {{ item.label }}
        </a>
      </li>
    </component>
  </nav>
</template>

<script setup>
defineProps({ block: { type: Object, required: true } })
</script>
