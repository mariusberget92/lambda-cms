<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

const props = defineProps({ block: { type: Object, required: true } })

const page      = usePage()
const appName   = computed(() => page.props.appName || 'Blog')
const year      = new Date().getFullYear()
const copyright = computed(() => props.block.data?.copyright || `© ${year} ${appName.value}`)
const columns   = computed(() => props.block.data?.columns ?? [])
const showRss   = computed(() => props.block.data?.showRss !== false)
</script>

<template>
  <footer class="site-footer">
    <div class="max-w-[1320px] mx-auto px-8 py-10">

      <!-- Columns grid -->
      <div v-if="columns.length" class="grid gap-8 mb-8" :style="`grid-template-columns: repeat(${Math.min(columns.length + 1, 4)}, minmax(0, 1fr));`">
        <!-- Brand column always first -->
        <div>
          <div class="flex items-center gap-2 mb-3">
            <div class="footer-brand__mark w-7 h-7 rounded flex items-center justify-center">
              <span class="font-mono-blog text-xs">λ</span>
            </div>
            <span class="font-semibold text-sm" style="font-family:'Space Grotesk',sans-serif; color:var(--ink);">{{ appName }}</span>
          </div>
          <p v-if="block.data?.tagline" class="text-xs leading-relaxed footer-soft">{{ block.data.tagline }}</p>
        </div>

        <!-- User-configured columns -->
        <div v-for="(col, i) in columns" :key="i">
          <p v-if="col.heading" class="font-mono-blog text-[10px] uppercase tracking-widest mb-3 footer-soft">{{ col.heading }}</p>
          <div class="space-y-2">
            <a
              v-for="(link, j) in (col.links ?? [])"
              :key="j"
              :href="link.url || '#'"
              class="footer-link block text-sm transition-colors"
            >{{ link.label }}</a>
          </div>
        </div>
      </div>

      <!-- Simple bottom row -->
      <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-6 footer-bottom">
        <span class="font-mono-blog text-xs footer-soft">{{ copyright }}</span>
        <div v-if="showRss" class="flex items-center gap-4">
          <a href="/feed"        class="footer-link font-mono-blog text-xs transition-colors">RSS</a>
          <span class="footer-sep">·</span>
          <a href="/sitemap.xml" class="footer-link font-mono-blog text-xs transition-colors">Sitemap</a>
        </div>
      </div>

    </div>
  </footer>
</template>

<style scoped>
.site-footer {
  background: var(--panel);
  border-top: 1px solid var(--line-strong);
  margin-top: 5rem;
}
.footer-brand__mark {
  border: 1px solid var(--line-strong);
  color: var(--ink);
}
.footer-soft    { color: var(--soft); }
.footer-link    { color: var(--soft); }
.footer-link:hover { color: var(--ink); }
.footer-sep     { color: var(--line-strong); }
.footer-bottom  { border-top: 1px solid var(--line); }
</style>
