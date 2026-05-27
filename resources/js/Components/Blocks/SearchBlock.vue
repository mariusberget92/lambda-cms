<script setup>
const props = defineProps({ block: { type: Object, required: true } })
const currentQ = typeof window !== 'undefined'
  ? new URLSearchParams(window.location.search).get('q') ?? ''
  : ''
</script>

<template>
  <div class="search-block">
    <form method="GET" action="/search" class="relative">
      <input
        type="text"
        name="q"
        :placeholder="block.data?.placeholder ?? 'Search posts…'"
        :value="currentQ"
        class="search-block__input w-full text-sm focus:outline-none transition-all font-sans"
      />
      <button type="submit" class="search-block__btn absolute right-2.5 top-1/2 -translate-y-1/2" aria-label="Search">
        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
        </svg>
      </button>
    </form>
  </div>
</template>

<style scoped>
.search-block {
  width: 100%;
  background: var(--panel);
  border: 1px solid var(--line-strong);
  border-radius: var(--blog-radius);
  padding: 1.25rem;
}
.search-block__input {
  width: 100%;
  background: var(--bg);
  color: var(--ink);
  border: 1px solid var(--line-strong);
  border-radius: var(--blog-radius);
  padding: 0.5rem 2.5rem 0.5rem 0.875rem;
}
.search-block__input:focus {
  border-color: var(--accent);
  outline: none;
}
.search-block__btn {
  color: var(--soft);
  transition: color 150ms;
}
.search-block__btn:hover { color: var(--accent); }
</style>
