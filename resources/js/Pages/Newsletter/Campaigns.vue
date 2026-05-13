<template>
  <AppLayout title="Newsletter Campaigns">
    <Head title="Newsletter Campaigns" />

    <PageHeader title="Newsletter Campaigns" description="Create and send campaigns to your subscribers." />

    <!-- Create campaign form -->
    <div class="bg-card border rounded-xl p-6 mb-6">
      <h3 class="font-semibold text-base mb-4">New Campaign</h3>
      <form @submit.prevent="submitCampaign" class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium mb-1.5">Title <span class="text-destructive">*</span></label>
            <input
              v-model="form.title"
              type="text"
              placeholder="Internal title"
              class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
              required
            />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1.5">Email Subject <span class="text-destructive">*</span></label>
            <input
              v-model="form.subject"
              type="text"
              placeholder="Subject line subscribers will see"
              class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
              required
            />
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1.5">Body <span class="text-destructive">*</span></label>
          <textarea
            v-model="form.body"
            rows="6"
            placeholder="Write your email content here…"
            class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring resize-y"
            required
          />
          <p class="mt-1 text-xs text-muted-foreground">Plain text. An unsubscribe link will be appended automatically.</p>
        </div>
        <div class="flex justify-end">
          <button
            type="submit"
            class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)]"
          >
            <Plus class="w-4 h-4" />
            Create campaign
          </button>
        </div>
      </form>
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
            <span
              class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold"
              :class="campaign.sent_at
                ? 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300'
                : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300'"
            >{{ campaign.sent_at ? 'Sent' : 'Draft' }}</span>
          </div>
          <p class="text-xs text-muted-foreground mt-0.5">Subject: {{ campaign.subject }}</p>
          <p v-if="campaign.sent_at" class="text-xs text-muted-foreground">
            Sent on {{ campaign.sent_at }} · {{ campaign.recipients_count }} recipient(s)
          </p>
          <p v-else class="text-xs text-muted-foreground">Created {{ campaign.created_at }}</p>
        </div>
        <div class="flex items-center gap-2 shrink-0">
          <button
            v-if="!campaign.sent_at"
            type="button"
            @click="confirmSend(campaign)"
            class="inline-flex items-center gap-1.5 rounded-md bg-primary px-3 py-1.5 text-xs font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)]"
          >
            <Send class="w-3 h-3" />
            Send
          </button>
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

    <!-- Send confirmation modal -->
    <Transition name="fade">
      <div v-if="sendTarget" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="sendTarget = null" />
        <div class="relative bg-card border rounded-xl shadow-xl w-full max-w-sm p-6">
          <h3 class="font-semibold text-base mb-2">Send campaign?</h3>
          <p class="text-sm text-muted-foreground mb-5">
            "<span class="font-medium text-foreground">{{ sendTarget.title }}</span>" will be sent to all confirmed subscribers. This cannot be undone.
          </p>
          <div class="flex gap-3 justify-end">
            <button type="button" @click="sendTarget = null" class="rounded-md border px-4 py-2 text-sm font-medium hover:bg-accent transition-colors">Cancel</button>
            <button type="button" @click="doSend" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-[var(--primary-hover)] transition-colors">Send now</button>
          </div>
        </div>
      </div>
    </Transition>

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
import { Head, router, usePage } from '@inertiajs/vue3'
import { Send, Plus, Trash2 } from 'lucide-vue-next'
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

const form = ref({ title: '', subject: '', body: '' })
const sendTarget           = ref(null)
const deleteCampaignTarget = ref(null)

function submitCampaign() {
  router.post(route('newsletter.campaigns.store'), form.value, {
    onSuccess: () => {
      form.value = { title: '', subject: '', body: '' }
    },
  })
}

function confirmSend(campaign) { sendTarget.value = campaign }

function doSend() {
  if (!sendTarget.value) return
  router.post(route('newsletter.campaigns.send', sendTarget.value.id), {}, {
    onFinish: () => { sendTarget.value = null },
  })
}

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
