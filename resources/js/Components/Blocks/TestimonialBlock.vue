<template>
  <!-- Card variant -->
  <div v-if="variant === 'card'" :style="{ background: 'var(--card)', border: '1px solid var(--border)', borderRadius: '0.75rem', padding: '1.5rem' }">
    <!-- Stars -->
    <div v-if="d.rating > 0" style="display:flex;gap:0.25rem;margin-bottom:0.75rem;">
      <svg v-for="i in 5" :key="i" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
        :fill="i <= d.rating ? '#ebcb8b' : 'none'" :stroke="i <= d.rating ? '#ebcb8b' : 'var(--border)'" stroke-width="2"
      ><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
    </div>
    <blockquote :style="{ fontSize: '1rem', lineHeight: '1.6', color: 'var(--foreground)', margin: '0 0 1rem', fontStyle: 'italic' }">
      "{{ d.quote }}"
    </blockquote>
    <div style="display:flex;align-items:center;gap:0.75rem;">
      <img v-if="d.avatar?.url" :src="d.avatar.url" :alt="d.author ?? ''" :style="{ width: '2.5rem', height: '2.5rem', borderRadius: '50%', objectFit: 'cover', flexShrink: 0 }" />
      <div v-else-if="d.author" :style="{ width: '2.5rem', height: '2.5rem', borderRadius: '50%', background: 'var(--primary)', color: 'var(--primary-foreground)', display: 'flex', alignItems: 'center', justifyContent: 'center', fontWeight: '700', fontSize: '1rem', flexShrink: 0 }">{{ d.author.charAt(0).toUpperCase() }}</div>
      <div>
        <p v-if="d.author" :style="{ fontWeight: '600', fontSize: '0.875rem', color: 'var(--foreground)', margin: 0 }">{{ d.author }}</p>
        <p v-if="d.role || d.company" :style="{ fontSize: '0.75rem', color: 'var(--muted-foreground)', margin: 0 }">{{ [d.role, d.company].filter(Boolean).join(' · ') }}</p>
      </div>
    </div>
  </div>

  <!-- Inline variant -->
  <div v-else-if="variant === 'inline'" style="display:flex;gap:1.25rem;align-items:flex-start;">
    <div style="flex-shrink:0;">
      <img v-if="d.avatar?.url" :src="d.avatar.url" :alt="d.author ?? ''" style="width:3.5rem;height:3.5rem;border-radius:50%;object-fit:cover;" />
      <div v-else-if="d.author" :style="{ width: '3.5rem', height: '3.5rem', borderRadius: '50%', background: 'var(--primary)', color: 'var(--primary-foreground)', display: 'flex', alignItems: 'center', justifyContent: 'center', fontWeight: '700', fontSize: '1.25rem' }">{{ d.author.charAt(0).toUpperCase() }}</div>
    </div>
    <div style="flex:1;min-width:0;">
      <div v-if="d.rating > 0" style="display:flex;gap:0.2rem;margin-bottom:0.5rem;">
        <svg v-for="i in 5" :key="i" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" :fill="i <= d.rating ? '#ebcb8b' : 'none'" :stroke="i <= d.rating ? '#ebcb8b' : 'var(--border)'" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
      </div>
      <blockquote :style="{ fontSize: '0.9375rem', lineHeight: '1.6', color: 'var(--foreground)', margin: '0 0 0.5rem', fontStyle: 'italic' }">"{{ d.quote }}"</blockquote>
      <p :style="{ fontSize: '0.8125rem', color: 'var(--muted-foreground)', margin: 0 }">
        <span v-if="d.author" style="font-weight:600;color:var(--foreground)">{{ d.author }}</span>
        <template v-if="d.author && (d.role || d.company)"> · </template>
        {{ [d.role, d.company].filter(Boolean).join(', ') }}
      </p>
    </div>
  </div>

  <!-- Minimal variant -->
  <div v-else style="text-align:center;padding:1rem 0;">
    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="var(--primary)" style="margin:0 auto 0.75rem;opacity:0.3;"><path d="M11.9 4H3v9h4.9c.1 4-2.9 6-2.9 6h3c0 0 5-2 5-9zm11 0h-8.9v9h4.9c.1 4-2.9 6-2.9 6h3c0 0 5-2 5-9z"/></svg>
    <blockquote :style="{ fontSize: '1.0625rem', lineHeight: '1.7', color: 'var(--foreground)', fontStyle: 'italic', margin: '0 0 1rem' }">"{{ d.quote }}"</blockquote>
    <div v-if="d.rating > 0" style="display:flex;gap:0.25rem;justify-content:center;margin-bottom:0.5rem;">
      <svg v-for="i in 5" :key="i" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" :fill="i <= d.rating ? '#ebcb8b' : 'none'" :stroke="i <= d.rating ? '#ebcb8b' : 'var(--border)'" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
    </div>
    <p :style="{ fontSize: '0.875rem', color: 'var(--muted-foreground)', margin: 0 }">
      <span v-if="d.author" style="font-weight:600;color:var(--foreground)">{{ d.author }}</span>
      <template v-if="d.author && (d.role || d.company)"> · </template>
      {{ [d.role, d.company].filter(Boolean).join(', ') }}
    </p>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({ block: { type: Object, required: true } })

const d       = computed(() => props.block.data ?? {})
const variant = computed(() => d.value.variant ?? 'card')
</script>
