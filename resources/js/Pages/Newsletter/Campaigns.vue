<template>
  <AppLayout title="Newsletter Campaigns">
    <Head title="Newsletter Campaigns" />

    <PageHeader title="Newsletter Campaigns" description="Create and send campaigns to your subscribers." />

    <!-- Create campaign form -->
    <div class="bg-card border rounded-xl p-6 mb-6">
      <h3 class="font-semibold text-base mb-4">New Campaign</h3>
      <form @submit.prevent="submitCampaign" class="flex flex-col sm:flex-row gap-3 items-end">
        <div class="flex-1">
          <label class="block text-sm font-medium mb-1.5">Internal title <span class="text-destructive">*</span></label>
          <input
            v-model="form.title"
            type="text"
            placeholder="e.g. May 2026 newsletter"
            class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
            required
          />
        </div>
        <div class="flex-1">
          <label class="block text-sm font-medium mb-1.5">Email subject <span class="text-destructive">*</span></label>
          <input
            v-model="form.subject"
            type="text"
            placeholder="Subject line subscribers will see"
            class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
            required
          />
        </div>
        <button
          type="submit"
          class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)] shrink-0"
        >
          <Plus class="w-4 h-4" />
          Create &amp; design
        </button>
      </form>
      <p class="mt-2 text-xs text-muted-foreground">After creating, you'll be taken to the block editor to design the email content.</p>
    </div>

    <!-- Empty state -->
    <div v-if="campaigns.data.length === 0" class="py-16 text-center">
      <Send class="w-10 h-10 mx-auto mb-3 text-muted-foreground/30" />
      <p class="text-muted-foreground text-sm">No campaigns yet.</p>
    </div>

    <!-- Campaign list -->
    <div v-else class="space-y-3">
      <div
        v-for="campaign in campaigns.data"
        :key="campaign.id"
        class="bg-card border rounded-xl p-4 flex items-start gap-4"
      >
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2 flex-wrap">
            <span class="font-medium text-sm">{{ campaign.title }}</span>
            <!-- Status badge -->
            <span
              class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold"
              :class="statusClass(campaign)"
            >{{ statusLabel(campaign) }}</span>
          </div>
          <p class="text-xs text-muted-foreground mt-0.5">Subject: {{ campaign.subject }}</p>
          <p v-if="campaign.sent_at" class="text-xs text-muted-foreground">
            Sent on {{ campaign.sent_at }} · {{ campaign.recipients_count }} recipient(s)
          </p>
          <p v-else-if="campaign.scheduled_at" class="text-xs text-muted-foreground flex items-center gap-1">
            <Clock class="w-3 h-3" />
            Scheduled for {{ formatScheduled(campaign.scheduled_at) }}
          </p>
          <p v-else class="text-xs text-muted-foreground">Created {{ campaign.created_at }} · Draft</p>
        </div>
        <div class="flex items-center gap-2 shrink-0">
          <!-- Edit (draft/scheduled only) -->
          <a
            v-if="!campaign.sent_at"
            :href="route('newsletter.campaigns.edit', campaign.id)"
            class="inline-flex items-center gap-1.5 rounded-md border px-3 py-1.5 text-xs font-medium transition-colors hover:bg-accent"
          >
            <Pencil class="w-3 h-3" />
            Edit
          </a>
          <button
            type="button"
            @click="confirmDeleteCampaign(campaign)"
            class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-destructive/10 hover:text-destructive transition-colors"
            title="Delete"
          >
            <Trash2 class="w-3.5 h-3.5" />
          </button>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="campaigns.last_page > 1" class="flex justify-center gap-1 mt-4">
      <a
        v-for="link in campaigns.links"
        :key="link.label"
        :href="link.url ?? undefined"
        class="inline-flex items-center justify-center px-3 py-1.5 rounded-md text-sm transition-colors"
        :class="link.active
          ? 'bg-primary text-primary-foreground font-medium'
          : link.url
            ? 'text-muted-foreground hover:bg-accent'
            : 'text-muted-foreground/40 cursor-not-allowed pointer-events-none'"
      >{{ decodeHtmlEntities(link.label) }}</a>
    </div>

    <!-- Delete campaign modal -->
    <Transition name="fade">
      <div v-if="deleteCampaignTarget" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="deleteCampaignTarget = null" />
        <div class="relative bg-card border rounded-xl shadow-xl w-full max-w-sm p-6">
          <h3 class="font-semibold text-base mb-2">Delete campaign?</h3>
          <p class="text-sm text-muted-foreground mb-5">
            "<span class="font-medium text-foreground">{{ deleteCampaignTarget.title }}</span>" will be permanently deleted.
          </p>
          <div class="flex gap-3 justify-end">
            <button type="button" @click="deleteCampaignTarget = null" class="rounded-md border px-4 py-2 text-sm font-medium hover:bg-accent transition-colors">Cancel</button>
            <button type="button" @click="doDeleteCampaign" class="rounded-md bg-destructive px-4 py-2 text-sm font-medium text-destructive-foreground hover:bg-destructive/90 transition-colors">Delete</button>
          </div>
        </div>
      </div>
    </Transition>
  </AppLayout>
</template>

<script setup>
import { ref, watch } from 'vue'
import { Head, router, usePage, useForm } from '@inertiajs/vue3'
import { Send, Plus, Trash2, Clock, Pencil } from 'lucide-vue-next'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/PageHeader.vue'
import { decodeHtmlEntities } from '@/lib/utils.js'
import { useNotifications } from '@/composables/useNotifications'

const props = defineProps({
  campaigns: Object,
})

const page = usePage()
const { notify } = useNotifications()

watch(() => page.props.flash, (flash) => {
  if (flash?.status) notify(flash.status, 'success')
  if (flash?.error)  notify(flash.error,  'error')
})

const form = useForm({ title: '', subject: '' })

function submitCampaign() {
  form.post(route('newsletter.campaigns.store'), {
    onError: (errors) => notify(Object.values(errors).join(' '), 'error'),
  })
}

function statusLabel(campaign) {
  if (campaign.sent_at) return 'Sent'
  if (campaign.scheduled_at) return 'Scheduled'
  return 'Draft'
}

function statusClass(campaign) {
  if (campaign.sent_at) return 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300'
  if (campaign.scheduled_at) return 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300'
  return 'bg-muted text-muted-foreground'
}

function formatScheduled(iso) {
  if (!iso) return ''
  return new Date(iso).toLocaleString(undefined, {
    month: 'short', day: 'numeric', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
}

const deleteCampaignTarget = ref(null)

function confirmDeleteCampaign(campaign) { deleteCampaignTarget.value = campaign }

function doDeleteCampaign() {
  if (!deleteCampaignTarget.value) return
  router.delete(route('newsletter.campaigns.destroy', deleteCampaignTarget.value.id), {
    onFinish: () => { deleteCampaignTarget.value = null },
  })
}
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
