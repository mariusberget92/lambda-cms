<!-- resources/js/Components/Blocks/PricingBlock.vue -->
<template>
  <div :class="cardClass">
    <!-- Badge -->
    <div v-if="block.data?.badge" class="mb-3">
      <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-primary text-primary-foreground">
        {{ block.data.badge }}
      </span>
    </div>

    <!-- Plan name & description -->
    <h3 class="font-bold text-lg">{{ block.data?.title || 'Plan' }}</h3>
    <p v-if="block.data?.description" class="text-sm text-muted-foreground mt-1">{{ block.data.description }}</p>

    <!-- Price -->
    <div class="flex items-baseline gap-1 mt-4 mb-5">
      <span class="text-4xl font-bold">{{ block.data?.price }}</span>
      <span v-if="block.data?.period" class="text-sm text-muted-foreground">{{ block.data.period }}</span>
    </div>

    <!-- CTA button -->
    <a
      v-if="block.data?.buttonLabel"
      :href="block.data?.buttonUrl || '#'"
      :class="btnClass"
    >{{ block.data.buttonLabel }}</a>

    <!-- Features list -->
    <ul v-if="(block.data?.features ?? []).length" class="mt-5 space-y-2.5">
      <li
        v-for="(feat, i) in block.data.features"
        :key="i"
        class="flex items-start gap-2 text-sm"
        :class="feat.included === false ? 'opacity-40' : ''"
      >
        <Icon
          :icon="feat.included === false ? 'lucide:x' : 'lucide:check'"
          class="shrink-0 mt-0.5"
          :class="feat.included === false ? 'text-muted-foreground' : 'text-primary'"
          style="font-size: 0.875rem"
          aria-hidden="true"
        />
        <span>{{ feat.text }}</span>
      </li>
    </ul>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Icon } from '@iconify/vue'

const props = defineProps({ block: { type: Object, required: true } })

const featured = computed(() => props.block.data?.featured ?? false)

const cardClass = computed(() => {
  const base = 'rounded-2xl p-6 flex flex-col'
  if (featured.value) return `${base} bg-primary text-primary-foreground ring-2 ring-primary`
  return `${base} border border-border bg-card`
})

const btnClass = computed(() => {
  const base = 'w-full text-center rounded-lg px-4 py-2.5 text-sm font-medium transition-colors'
  if (featured.value) return `${base} bg-white text-primary hover:bg-white/90`
  return `${base} bg-primary text-primary-foreground hover:bg-primary/90`
})
</script>
