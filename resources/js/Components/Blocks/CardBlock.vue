<template>
  <div :style="cardStyle">
    <!-- Image -->
    <div v-if="d.image?.url || d.image?.showImage !== false && d.image?.media_id" :style="imageWrapStyle">
      <img
        :src="d.image.url"
        :alt="d.image.alt ?? ''"
        :style="{ width: '100%', height: '100%', objectFit: d.image.fit ?? 'cover', display: 'block' }"
      />
    </div>

    <!-- Body -->
    <div :style="bodyStyle">
      <p v-if="d.subtitle" :style="{ fontSize: '0.75rem', fontWeight: '600', textTransform: 'uppercase', letterSpacing: '0.05em', color: 'var(--muted-foreground)', marginBottom: '0.25rem' }">{{ d.subtitle }}</p>
      <h3 v-if="d.title" :style="{ fontSize: '1.125rem', fontWeight: '700', color: 'var(--foreground)', marginBottom: '0.5rem', lineHeight: '1.3' }">{{ d.title }}</h3>
      <div v-if="d.body" class="prose prose-sm max-w-none" v-html="d.body" />

      <a
        v-if="d.button?.show && d.button?.text"
        :href="d.button.href || undefined"
        :target="d.button.target ?? '_self'"
        :style="btnStyle"
        style="display:inline-flex;align-items:center;margin-top:1rem;font-weight:500;font-size:0.875rem;line-height:1.25;text-decoration:none;border-radius:0.375rem;padding:0.5rem 1.25rem;transition:opacity 0.15s;"
      >{{ d.button.text }}</a>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({ block: { type: Object, required: true } })

const d = computed(() => props.block.data ?? {})

const PADDING = { sm: '0.75rem', md: '1.25rem', lg: '2rem' }

const cardStyle = computed(() => {
  const v = d.value.variant ?? 'default'
  const base = { overflow: 'hidden', borderRadius: '0.5rem' }
  if (v === 'bordered')  return { ...base, border: '1px solid var(--border)' }
  if (v === 'elevated')  return { ...base, boxShadow: '0 4px 12px 0 rgb(0 0 0 / 0.08)', background: 'var(--card)' }
  if (v === 'flat')      return { ...base, background: 'var(--muted)' }
  return { ...base, background: 'var(--card)', border: '1px solid var(--border)' }
})

const imageWrapStyle = computed(() => ({
  width: '100%',
  aspectRatio: d.value.image?.aspectRatio ?? '16/9',
  overflow: 'hidden',
  flexShrink: '0',
}))

const bodyStyle = computed(() => ({
  padding: PADDING[d.value.padding ?? 'md'] ?? PADDING.md,
}))

const btnStyle = computed(() => {
  const v = d.value.button?.variant ?? 'solid'
  if (v === 'outline') return { backgroundColor: 'transparent', color: 'var(--primary)', border: '1px solid var(--primary)' }
  return { backgroundColor: 'var(--primary)', color: 'var(--primary-foreground)', border: '1px solid transparent' }
})
</script>
