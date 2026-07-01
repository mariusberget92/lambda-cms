<template>
  <AppLayout :title="campaign ? 'Edit Campaign' : 'New Campaign'">
    <Head :title="campaign ? 'Edit Campaign' : 'New Campaign'" />

    <PageHeader
      :title="campaign ? 'Edit Campaign' : 'New Campaign'"
      :description="campaign ? 'Update your campaign before sending' : 'Create a new email campaign'"
    >
      <template #actions>
        <a
          :href="route('campaigns.index')"
          class="inline-flex items-center gap-2 rounded-md border px-4 py-2 text-sm font-medium hover:bg-accent transition-colors"
        >
          Back to Campaigns
        </a>
      </template>
    </PageHeader>

    <div class="max-w-3xl space-y-6">
      <ContentCard title="Campaign details">
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium mb-1.5">Name</label>
            <input
              v-model="form.name"
              type="text"
              class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
              placeholder="Internal campaign name..."
            />
            <p v-if="form.errors.name" class="text-xs text-destructive mt-1">{{ form.errors.name }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1.5">Subject line</label>
            <input
              v-model="form.subject"
              type="text"
              class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
              placeholder="Email subject..."
            />
            <p v-if="form.errors.subject" class="text-xs text-destructive mt-1">{{ form.errors.subject }}</p>
          </div>
        </div>
      </ContentCard>

      <ContentCard title="Email body">
        <TiptapEditor v-model="form.body" placeholder="Write your campaign email..." />
        <p v-if="form.errors.body" class="text-xs text-destructive mt-1">{{ form.errors.body }}</p>
      </ContentCard>

      <!-- Info + actions -->
      <div class="rounded-lg border bg-card p-6">
        <div class="flex items-center justify-between">
          <div class="text-sm text-muted-foreground">
            <Users class="w-4 h-4 inline-block mr-1 -mt-0.5" />
            This campaign will be sent to <strong class="text-foreground">{{ subscriberCount }}</strong> active subscriber{{ subscriberCount !== 1 ? 's' : '' }}.
          </div>
        </div>

        <!-- Scheduled notice -->
        <div v-if="campaign?.status === 'scheduled' && campaign?.scheduled_at" class="flex items-center gap-2 mt-4 pt-4 border-t text-sm">
          <Clock class="w-4 h-4 text-status-info-fg" />
          <span>Scheduled for <strong>{{ formatDateTime(campaign.scheduled_at) }}</strong></span>
          <button
            type="button"
            @click="unscheduleCampaign"
            class="ml-auto text-xs font-medium text-destructive hover:underline"
          >
            Cancel schedule
          </button>
        </div>

        <div class="flex items-center gap-3 mt-4 pt-4 border-t">
          <button
            type="button"
            :disabled="form.processing"
            @click="saveDraft"
            class="inline-flex items-center gap-2 rounded-md border px-5 py-2 text-sm font-medium transition-colors hover:bg-accent disabled:opacity-50"
          >
            {{ form.processing ? 'Saving...' : 'Save as draft' }}
          </button>
          <button
            v-if="canSendOrSchedule"
            type="button"
            :disabled="form.processing || subscriberCount === 0"
            @click="showSendModal = true"
            class="inline-flex items-center gap-2 rounded-md bg-primary px-5 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)] disabled:opacity-50"
          >
            <Send class="w-4 h-4" />
            Send now
          </button>
          <button
            v-if="canSendOrSchedule"
            type="button"
            :disabled="form.processing || subscriberCount === 0"
            @click="showScheduleModal = true"
            class="inline-flex items-center gap-2 rounded-md border px-5 py-2 text-sm font-medium transition-colors hover:bg-accent disabled:opacity-50"
          >
            <Clock class="w-4 h-4" />
            Schedule
          </button>
        </div>
      </div>
    </div>

    <ConfirmModal
      :open="showSendModal"
      title="Send campaign?"
      :description="`This will immediately send '${form.name || 'this campaign'}' to ${subscriberCount} active subscriber(s). This cannot be undone.`"
      confirm-label="Send now"
      variant="default"
      :processing="sendProcessing"
      @close="showSendModal = false"
      @confirm="sendCampaign"
    />

    <!-- Schedule modal -->
    <ConfirmModal
      :open="showScheduleModal"
      title="Schedule campaign"
      :description="`Schedule '${form.name || 'this campaign'}' to send to ${subscriberCount} subscriber(s) at a specific date and time.`"
      confirm-label="Schedule"
      variant="default"
      :processing="scheduleProcessing"
      @close="showScheduleModal = false"
      @confirm="scheduleCampaign"
    >
      <template #body>
        <div class="mt-4">
          <label class="block text-sm font-medium mb-1.5">Send date and time</label>
          <input
            v-model="scheduledAt"
            type="datetime-local"
            :min="minDateTime"
            class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
          />
          <p v-if="scheduleError" class="text-xs text-destructive mt-1">{{ scheduleError }}</p>
        </div>
      </template>
    </ConfirmModal>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/PageHeader.vue'
import ContentCard from '@/Components/ContentCard.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'
import TiptapEditor from '@/Components/TiptapEditor.vue'
import { Send, Users, Clock } from '@lucide/vue'

const props = defineProps({
  campaign: { type: Object, default: null },
  subscriberCount: { type: Number, default: 0 },
})

const form = useForm({
  name: props.campaign?.name ?? '',
  subject: props.campaign?.subject ?? '',
  body: props.campaign?.body ?? '',
})

const showSendModal = ref(false)
const showScheduleModal = ref(false)
const sendProcessing = ref(false)
const scheduleProcessing = ref(false)
const scheduledAt = ref('')
const scheduleError = ref('')

const canSendOrSchedule = computed(() =>
  !props.campaign || props.campaign.status === 'draft' || props.campaign.status === 'scheduled'
)

const minDateTime = computed(() => {
  const now = new Date()
  now.setMinutes(now.getMinutes() + 1)
  return now.toISOString().slice(0, 16)
})

function saveDraft() {
  if (props.campaign) {
    form.put(route('campaigns.update', props.campaign.id))
  } else {
    form.post(route('campaigns.store'))
  }
}

function sendCampaign() {
  sendProcessing.value = true

  const saveMethod = props.campaign
    ? () => form.put(route('campaigns.update', props.campaign.id), {
        onSuccess: () => doSend(),
        onError: () => { sendProcessing.value = false },
      })
    : () => form.post(route('campaigns.store'), {
        onSuccess: (page) => {
          const id = page.props?.campaign?.id
          if (id) {
            router.post(route('campaigns.send', id), {}, {
              onFinish: () => { sendProcessing.value = false },
            })
          }
        },
        onError: () => { sendProcessing.value = false },
      })

  saveMethod()
}

function doSend() {
  router.post(route('campaigns.send', props.campaign.id), {}, {
    onFinish: () => { sendProcessing.value = false; showSendModal.value = false },
  })
}

function scheduleCampaign() {
  if (!scheduledAt.value) {
    scheduleError.value = 'Please select a date and time.'
    return
  }

  const selectedDate = new Date(scheduledAt.value)
  if (selectedDate <= new Date()) {
    scheduleError.value = 'Scheduled time must be in the future.'
    return
  }

  scheduleError.value = ''
  scheduleProcessing.value = true

  const doSchedule = (campaignId) => {
    router.post(route('campaigns.schedule', campaignId), {
      scheduled_at: scheduledAt.value,
    }, {
      onFinish: () => { scheduleProcessing.value = false; showScheduleModal.value = false },
      onError: () => { scheduleProcessing.value = false },
    })
  }

  if (props.campaign) {
    form.put(route('campaigns.update', props.campaign.id), {
      onSuccess: () => doSchedule(props.campaign.id),
      onError: () => { scheduleProcessing.value = false },
    })
  } else {
    form.post(route('campaigns.store'), {
      onSuccess: (page) => {
        const id = page.props?.campaign?.id
        if (id) {
          doSchedule(id)
        }
      },
      onError: () => { scheduleProcessing.value = false },
    })
  }
}

function unscheduleCampaign() {
  router.post(route('campaigns.unschedule', props.campaign.id))
}

function formatDateTime(dateStr) {
  return new Date(dateStr).toLocaleString('en-US', {
    month: 'short', day: 'numeric', year: 'numeric',
    hour: 'numeric', minute: '2-digit',
  })
}
</script>
