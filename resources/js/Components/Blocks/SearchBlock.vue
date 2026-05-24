<script setup>
const props = defineProps({ block: Object })
const currentQ = typeof window !== 'undefined'
  ? new URLSearchParams(window.location.search).get('q') ?? ''
  : ''
</script>

<template>
  <div class="rounded-2xl p-5 bg-white" style="box-shadow:0 2px 12px rgba(0,0,0,0.06), 0 1px 4px rgba(0,0,0,0.03);">
    <p class="text-[10px] font-bold uppercase tracking-[0.14em] mb-3" style="color:#94a3b8;">Search</p>
    <form method="GET" action="/search" class="relative">
      <input
        type="text"
        name="q"
        :placeholder="block.data?.placeholder ?? 'Search posts…'"
        :value="currentQ"
        class="w-full rounded-xl px-4 py-2.5 pr-10 text-sm text-foreground placeholder:text-muted-foreground/50 focus:outline-none transition-all"
        style="background:#f1f5f9; border:1.5px solid #e2e8f0;"
        @focus="e => { e.target.style.borderColor='#6366f1'; e.target.style.boxShadow='0 0 0 3px rgba(99,102,241,0.15)'; }"
        @blur="e => { e.target.style.borderColor='#e2e8f0'; e.target.style.boxShadow='none'; }"
      />
      <button
        type="submit"
        class="absolute right-3 top-1/2 -translate-y-1/2 transition-colors"
        style="color:#94a3b8;"
        aria-label="Search"
        @mouseenter="$event.currentTarget.style.color='#6366f1'"
        @mouseleave="$event.currentTarget.style.color='#94a3b8'"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
        </svg>
      </button>
    </form>
  </div>
</template>
