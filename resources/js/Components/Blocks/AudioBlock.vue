<!-- resources/js/Components/Blocks/AudioBlock.vue -->
<template>
  <div v-if="block.data?.src" class="rounded-xl border border-border bg-card overflow-hidden">
    <div v-if="block.data?.coverUrl || block.data?.title || block.data?.artist" class="flex items-center gap-4 p-4">
      <img
        v-if="block.data?.coverUrl"
        :src="block.data.coverUrl"
        alt="Cover"
        class="w-14 h-14 rounded-lg object-cover shrink-0"
      />
      <div v-if="block.data?.title || block.data?.artist">
        <p v-if="block.data?.title"  class="font-semibold text-sm">{{ block.data.title }}</p>
        <p v-if="block.data?.artist" class="text-xs text-muted-foreground">{{ block.data.artist }}</p>
      </div>
    </div>
    <div class="px-4 pb-4">
      <audio
        :src="block.data.src"
        controls
        class="w-full h-10"
        style="accent-color: var(--primary)"
      />
    </div>
  </div>
  <div v-else class="flex items-center justify-center border border-dashed rounded-lg py-6 text-muted-foreground/40 text-sm">
    No audio source set
  </div>
</template>

<script setup>
defineProps({ block: { type: Object, required: true } })
</script>
