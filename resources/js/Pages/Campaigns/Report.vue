<template>
  <AppLayout title="Campaign Report">
    <Head :title="`Report — ${campaign.name}`" />

    <PageHeader :title="campaign.name" :description="`Campaign report — ${campaign.subject}`">
      <template #actions>
        <StatusBadge :status="campaign.status" />
        <a
          :href="route('campaigns.index')"
          class="inline-flex items-center gap-2 rounded-md border px-4 py-2 text-sm font-medium hover:bg-accent transition-colors"
        >
          Back to Campaigns
        </a>
      </template>
    </PageHeader>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
      <div class="rounded-lg border bg-card p-4 text-center">
        <p class="text-2xl font-bold">{{ campaign.total_count ?? 0 }}</p>
        <p class="text-xs text-muted-foreground mt-1">Total</p>
      </div>
      <div class="rounded-lg border bg-card p-4 text-center">
        <p class="text-2xl font-bold text-green-600">{{ campaign.sent_count ?? 0 }}</p>
        <p class="text-xs text-muted-foreground mt-1">Sent</p>
      </div>
      <div class="rounded-lg border bg-card p-4 text-center">
        <p class="text-2xl font-bold text-destructive">{{ campaign.failed_count ?? 0 }}</p>
        <p class="text-xs text-muted-foreground mt-1">Failed</p>
      </div>
      <div class="rounded-lg border bg-card p-4 text-center">
        <p class="text-2xl font-bold text-muted-foreground">{{ campaign.pending_count ?? 0 }}</p>
        <p class="text-xs text-muted-foreground mt-1">Pending</p>
      </div>
    </div>

    <p v-if="campaign.sent_at" class="text-sm text-muted-foreground mb-4">
      Sent on {{ new Date(campaign.sent_at).toLocaleString() }}
    </p>

    <!-- Recipients table -->
    <DataTable :empty="recipients.data.length === 0">
      <template #empty>
        <UserRound class="w-8 h-8 mx-auto mb-3 opacity-40" />
        No recipients yet.
      </template>
      <template #headers>
        <th class="text-left">Email</th>
        <th class="text-left hidden sm:table-cell">Name</th>
        <th class="text-left w-28">Status</th>
        <th class="text-left hidden md:table-cell w-36">Sent At</th>
        <th class="text-left hidden lg:table-cell">Error</th>
      </template>
      <template #rows>
        <tr v-for="r in recipients.data" :key="r.id" class="group">
          <td>
            <span class="text-sm font-medium">{{ r.subscriber?.email ?? '(deleted)' }}</span>
          </td>
          <td class="hidden sm:table-cell">
            <span class="text-sm text-muted-foreground">{{ r.subscriber?.name || '—' }}</span>
          </td>
          <td>
            <StatusBadge :status="r.status" />
          </td>
          <td class="hidden md:table-cell">
            <span class="text-sm text-muted-foreground">{{ r.sent_at ? new Date(r.sent_at).toLocaleString() : '—' }}</span>
          </td>
          <td class="hidden lg:table-cell">
            <span v-if="r.error" class="text-xs text-destructive truncate max-w-[200px] block" :title="r.error">{{ r.error }}</span>
            <span v-else class="text-sm text-muted-foreground">—</span>
          </td>
        </tr>
      </template>
    </DataTable>

    <!-- Pagination -->
    <div v-if="recipients.last_page > 1" class="flex items-center justify-between mt-4 text-sm">
      <p class="text-muted-foreground">
        Showing {{ recipients.from }}–{{ recipients.to }} of {{ recipients.total }}
      </p>
      <div class="flex gap-1">
        <template v-for="link in recipients.links" :key="link.label">
          <component
            :is="link.url ? 'a' : 'span'"
            :href="link.url"
            v-html="link.label"
            class="px-3 py-1 rounded-md text-sm"
            :class="link.active ? 'bg-primary text-primary-foreground font-medium' : link.url ? 'hover:bg-accent text-muted-foreground' : 'text-muted-foreground/30 cursor-not-allowed'"
          />
        </template>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/PageHeader.vue'
import DataTable from '@/Components/DataTable.vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import { UserRound } from '@lucide/vue'

defineProps({
  campaign: { type: Object, required: true },
  recipients: { type: Object, required: true },
})
</script>
