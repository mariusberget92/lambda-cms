<template>
  <AppLayout title="Email Templates">
    <Head title="Email Templates" />

    <PageHeader title="Email Templates" description="Customize system email content and appearance" />

    <DataTable :empty="templates.length === 0">
      <template #empty>
        <Mail class="w-8 h-8 mx-auto mb-3 opacity-40" />
        No email templates found.
      </template>
      <template #headers>
        <th class="text-left">Template</th>
        <th class="text-left hidden sm:table-cell">Description</th>
        <th class="text-left hidden md:table-cell w-40">Updated</th>
        <th class="w-10"></th>
      </template>
      <template #rows>
        <tr v-for="t in templates" :key="t.id" class="group">
          <td>
            <div class="font-medium text-sm">{{ t.name }}</div>
            <div class="text-xs text-muted-foreground font-mono mt-0.5">{{ t.key }}</div>
          </td>
          <td class="hidden sm:table-cell">
            <span class="text-sm text-muted-foreground">{{ t.description }}</span>
          </td>
          <td class="hidden md:table-cell">
            <span class="text-sm text-muted-foreground">{{ timeAgo(t.updated_at) }}</span>
          </td>
          <td>
            <div class="flex items-center justify-end">
              <a
                :href="route('email-templates.edit', t.id)"
                class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-accent hover:text-accent-foreground transition-colors"
                title="Edit"
              >
                <Pencil class="w-3.5 h-3.5" />
              </a>
            </div>
          </td>
        </tr>
      </template>
    </DataTable>
  </AppLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/PageHeader.vue'
import DataTable from '@/Components/DataTable.vue'
import { Mail, Pencil } from '@lucide/vue'

defineProps({
  templates: { type: Array, required: true },
})

function timeAgo(dateStr) {
  const seconds = Math.floor((Date.now() - new Date(dateStr).getTime()) / 1000)
  if (seconds < 60) return 'just now'
  const minutes = Math.floor(seconds / 60)
  if (minutes < 60) return `${minutes}m ago`
  const hours = Math.floor(minutes / 60)
  if (hours < 24) return `${hours}h ago`
  const days = Math.floor(hours / 24)
  if (days < 30) return `${days}d ago`
  return new Date(dateStr).toLocaleDateString()
}
</script>
