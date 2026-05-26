<script setup>
const props = defineProps({ block: { type: Object, required: true } })
const currentQ = typeof window !== 'undefined'
  ? new URLSearchParams(window.location.search).get('q') ?? ''
  : ''
</script>

<template>
  <div
    class="w-full"
    :style="{
      background: 'var(--panel)',
      border: '1px solid var(--line-strong)',
      borderRadius: 'var(--blog-radius, 6px)',
      padding: '1.25rem',
    }"
  >
    <form method="GET" action="/search" class="relative">
      <input
        type="text"
        name="q"
        :placeholder="block.data?.placeholder ?? 'Search posts…'"
        :value="currentQ"
        class="w-full text-sm focus:outline-none transition-all font-sans"
        :style="{
          background: 'var(--bg)',
          color: 'var(--ink)',
          border: '1px solid var(--line-strong)',
          borderRadius: 'var(--blog-radius, 6px)',
          padding: '0.5rem 2.5rem 0.5rem 0.875rem',
        }"
        @focus="e => e.target.style.borderColor = 'var(--accent)'"
        @blur="e => e.target.style.borderColor = 'var(--line-strong)'"
      />
      <button
        type="submit"
        class="absolute right-2.5 top-1/2 -translate-y-1/2 transition-colors duration-150"
        style="color:var(--soft);"
        aria-label="Search"
        @mouseenter="$event.currentTarget.style.color = 'var(--accent)'"
        @mouseleave="$event.currentTarget.style.color = 'var(--soft)'"
      >
        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
        </svg>
      </button>
    </form>
  </div>
</template>
