<template>
  <AppLayout title="Activity Log">
    <Head title="Activity Log" />

    <PageHeader title="Activity Log" description="A record of all admin actions in the CMS." />

    <!-- Filter bar -->
    <div class="flex gap-1 mb-6 border-b">
      <a
        v-for="filter in actionFilters"
        :key="filter.value"
        :href="filter.value ? route('activity-log.index') + '?action=' + filter.value : route('activity-log.index')"
        class="px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors"
        :class="activeAction === filter.value
          ? 'border-primary text-foreground'
          : 'border-transparent text-muted-foreground hover:text-foreground'"
      >{{ filter.label }}</a>
    </div>

    <!-- Empty state -->
    <div v-if="logs.data.length === 0" class="py-16 text-center">
      <ShieldCheck class="w-10 h-10 mx-auto mb-3 text-muted-foreground/30" />
      <p class="text-muted-foreground text-sm">No activity recorded yet.</p>
    </div>

    <!-- Timeline -->
    <div v-else class="relative pl-6 space-y-0">
      <!-- vertical line -->
      <div class="absolute left-2 top-2 bottom-2 w-px bg-border" aria-hidden="true" />

      <div
        v-for="log in logs.data"
        :key="log.id"
        class="relative pb-6"
      >
        <!-- Dot -->
        <div
          class="absolute -left-4 top-1.5 w-3 h-3 rounded-full border-2 border-background ring-2"
          :class="dotClass(log.action)"
          aria-hidden="true"
        />

        <div class="bg-card border rounded-lg p-4 ml-2">
          <div class="flex items-start justify-between gap-3 flex-wrap">
            <div class="flex items-center gap-2 flex-wrap">
              <!-- Action badge -->
              <span
                class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold"
                :class="badgeClass(log.action)"
              >{{ log.action }}</span>

              <!-- Model type -->
              <span
                v-if="log.model_type"
                class="text-xs bg-muted px-1.5 py-0.5 rounded text-muted-foreground"
              >{{ log.model_type }}</span>
            </div>

            <!-- Timestamp -->
            <span class="text-xs text-muted-foreground shrink-0">{{ log.created_at }}</span>
          </div>

          <!-- Description -->
          <p class="mt-1.5 text-sm text-foreground">{{ log.description }}</p>

          <!-- Footer: user + IP -->
          <div class="mt-2 flex items-center gap-3 text-xs text-muted-foreground/70">
            <span>
              <span class="font-medium text-muted-foreground">{{ log.user?.name ?? 'System' }}</span>
              <span v-if="log.user?.role" class="ml-1 opacity-70">({{ log.user.role }})</span>
            </span>
            <span v-if="log.ip_address">· {{ log.ip_address }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="logs.last_page > 1" class="mt-2 flex justify-center gap-2">
      <a
        v-for="page in logs.links"
        :key="page.label"
        :href="page.url"
        class="rounded-md border px-3 py-1.5 text-sm transition-colors"
        :class="page.active
          ? 'bg-primary text-primary-foreground border-primary'
          : page.url ? 'hover:bg-accent' : 'opacity-40 cursor-default pointer-events-none'"
      >{{ decodeHtmlEntities(page.label) }}</a>
    </div>
  </AppLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import { ShieldCheck } from 'lucide-vue-next'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/PageHeader.vue'
import { decodeHtmlEntities } from '@/lib/utils.js'

defineProps({
  logs:         Object,
  activeAction: { type: String, default: null },
})

const actionFilters = [
  { label: 'All',       value: null },
  { label: 'Created',   value: 'created' },
  { label: 'Updated',   value: 'updated' },
  { label: 'Deleted',   value: 'deleted' },
  { label: 'Published', value: 'published' },
]

function badgeClass(action) {
  const map = {
    created:   'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
    updated:   'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
    deleted:   'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
    published: 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
    banned:    'bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-300',
    unbanned:  'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300',
  }
  return map[action] ?? 'bg-muted text-muted-foreground'
}

function dotClass(action) {
  const map = {
    created:   'ring-green-400',
    updated:   'ring-blue-400',
    deleted:   'ring-red-400',
    published: 'ring-green-400',
    banned:    'ring-orange-400',
    unbanned:  'ring-gray-400',
  }
  return map[action] ?? 'ring-muted-foreground/40'
}
</script>
