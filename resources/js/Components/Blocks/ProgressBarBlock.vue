<!-- resources/js/Components/Blocks/ProgressBarBlock.vue -->
<template>
  <div class="space-y-3">
    <div v-for="(item, i) in block.data?.items ?? []" :key="i">
      <div v-if="block.data?.showValues !== false" class="flex justify-between mb-1">
        <span class="text-sm font-medium">{{ item.label }}</span>
        <span class="text-sm text-muted-foreground">{{ item.value }}%</span>
      </div>
      <div v-else-if="item.label" class="mb-1">
        <span class="text-sm font-medium">{{ item.label }}</span>
      </div>
      <div
        class="w-full bg-muted rounded-full overflow-hidden"
        :style="{ height: block.data?.height ?? '0.5rem' }"
      >
        <div
          class="rounded-full transition-all duration-700 ease-out"
          :style="{
            width: mounted ? `${Math.min(100, Math.max(0, item.value ?? 0))}%` : '0%',
            height: '100%',
            backgroundColor: item.color ?? 'var(--primary)',
          }"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const props = defineProps({ block: { type: Object, required: true } })

const mounted = ref(false)
onMounted(() => { requestAnimationFrame(() => { mounted.value = true }) })
</script>
