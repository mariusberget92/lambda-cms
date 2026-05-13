<!-- Newsletter Campaign block editor — mirrors the Templates/Edit pattern -->
<script setup>
import PageBuilderLayout from '@/Layouts/PageBuilderLayout.vue'
import BlockEditor       from '@/Components/BlockEditor/BlockEditor.vue'
import { useForm, usePage, Head } from '@inertiajs/vue3'
import { filterEmptyBlocks } from '@/lib/utils.js'
import { ref } from 'vue'
import axios from 'axios'
import { useNotifications } from '@/composables/useNotifications.js'
import { ArrowLeft, Send, Clock, Save } from 'lucide-vue-next'

const authUser = usePage().props.auth.user
const { notify } = useNotifications()

const props = defineProps({
  campaign: { type: Object, required: true },
})

const form = useForm({
  title:        props.campaign.title,
  subject:      props.campaign.subject,
  blocks:       props.campaign.blocks ?? [],
  scheduled_at: props.campaign.scheduled_at
    ? new Date(props.campaign.scheduled_at).toISOString().slice(0, 16)
    : '',
})

const showSchedulePanel = ref(false)
const showSendModal     = ref(false)

function save() {
  form.blocks = filterEmptyBlocks(form.blocks)
  form.put(route('newsletter.campaigns.update', props.campaign.id), {
    onSuccess: () => notify('Campaign saved.', 'success'),
    onError: (errors) => notify(Object.values(errors).join(' '), 'error'),
  })
}

function schedule() {
  if (!form.scheduled_at) {
    notify('Please pick a date and time before scheduling.', 'error')
    showSchedulePanel.value = true
    return
  }
  form.blocks = filterEmptyBlocks(form.blocks)
  form.put(route('newsletter.campaigns.update', props.campaign.id), {
    onSuccess: () => notify('Campaign scheduled!', 'success'),
    onError: (errors) => notify(Object.values(errors).join(' '), 'error'),
  })
}

function sendNow() {
  showSendModal.value = false
  // Save blocks first, then trigger send via a separate POST
  form.blocks = filterEmptyBlocks(form.blocks)
  form.put(route('newsletter.campaigns.update', props.campaign.id), {
    onSuccess: () => {
      // Now send
      useForm({}).post(route('newsletter.campaigns.send', props.campaign.id), {
        onSuccess: () => notify('Campaign sent!', 'success'),
        onError: () => notify('Failed to send campaign.', 'error'),
      })
    },
  })
}
</script>

<template>
  <PageBuilderLayout>
    <Head :title="`Edit Campaign — ${campaign.title}`" />

    <template #bar>
      <!-- Campaign builder bar -->
      <header class="flex items-center gap-3 px-3 h-11 shrink-0 border-b border-white/10 bg-[#181825] z-10">
        <!-- Back -->
        <a
          :href="route('newsletter.campaigns')"
          class="inline-flex items-center justify-center w-7 h-7 rounded text-white/50 hover:text-white hover:bg-white/10 transition-colors shrink-0"
          title="Back to campaigns"
        >
          <ArrowLeft class="w-4 h-4" />
        </a>

        <div class="w-px h-5 bg-white/10 shrink-0" />

        <span class="shrink-0 px-2 py-0.5 rounded text-[11px] font-semibold uppercase tracking-wider bg-white/10 text-white/50">
          Campaign
        </span>

        <!-- Title -->
        <input
          v-model="form.title"
          type="text"
          placeholder="Campaign title…"
          class="min-w-0 bg-transparent rounded px-2 py-1 text-sm font-medium text-white placeholder:text-white/30 focus:outline-none w-40"
        />

        <div class="w-px h-5 bg-white/10 shrink-0" />

        <!-- Subject -->
        <input
          v-model="form.subject"
          type="text"
          placeholder="Email subject…"
          class="flex-1 min-w-0 bg-transparent rounded px-2 py-1 text-sm text-white/80 placeholder:text-white/30 focus:outline-none"
        />

        <div class="w-px h-5 bg-white/10 shrink-0" />

        <!-- Schedule toggle -->
        <button
          type="button"
          @click="showSchedulePanel = !showSchedulePanel"
          class="flex items-center gap-1.5 rounded px-2 py-1 text-xs font-medium transition-colors shrink-0"
          :class="showSchedulePanel ? 'bg-white/15 text-white' : 'text-white/50 hover:text-white hover:bg-white/10'"
          title="Schedule"
        >
          <Clock class="w-3.5 h-3.5" />
          <span v-if="form.scheduled_at" class="text-[11px]">
            {{ new Date(form.scheduled_at).toLocaleDateString(undefined, { month:'short', day:'numeric', hour:'2-digit', minute:'2-digit' }) }}
          </span>
          <span v-else>Schedule</span>
        </button>

        <!-- Date/time picker (inline) -->
        <template v-if="showSchedulePanel">
          <input
            v-model="form.scheduled_at"
            type="datetime-local"
            class="rounded bg-white/10 border border-white/10 px-2 py-1 text-xs text-white focus:outline-none focus:border-white/30"
            :min="new Date().toISOString().slice(0,16)"
          />
          <button
            type="button"
            @click="form.scheduled_at = ''; showSchedulePanel = false"
            class="text-white/40 hover:text-white text-xs px-1"
            title="Clear schedule"
          >✕</button>
        </template>

        <div class="w-px h-5 bg-white/10 shrink-0" />

        <!-- Save -->
        <button
          type="button"
          :disabled="form.processing"
          @click="save"
          class="flex items-center gap-1.5 rounded px-3 py-1.5 text-xs font-medium bg-white/10 text-white/70 hover:bg-white/15 hover:text-white transition-colors shrink-0 disabled:opacity-40"
        >
          <Save class="w-3.5 h-3.5" />
          Save
        </button>

        <!-- Schedule or Send -->
        <template v-if="form.scheduled_at">
          <button
            type="button"
            :disabled="form.processing"
            @click="schedule"
            class="flex items-center gap-1.5 rounded px-3 py-1.5 text-xs font-semibold bg-[var(--sidebar-primary,#5e81ac)] text-white hover:opacity-90 transition-opacity shrink-0 disabled:opacity-40"
          >
            <Clock class="w-3.5 h-3.5" />
            Schedule
          </button>
        </template>
        <template v-else>
          <button
            type="button"
            :disabled="form.processing"
            @click="showSendModal = true"
            class="flex items-center gap-1.5 rounded px-3 py-1.5 text-xs font-semibold bg-[var(--sidebar-primary,#5e81ac)] text-white hover:opacity-90 transition-opacity shrink-0 disabled:opacity-40"
          >
            <Send class="w-3.5 h-3.5" />
            Send now
          </button>
        </template>
      </header>
    </template>

    <!-- Block editor canvas -->
    <BlockEditor
      fullscreen
      :model-value="form.blocks"
      :is-admin="authUser?.role === 'administrator'"
      @update:model-value="form.blocks = $event"
    />
  </PageBuilderLayout>

  <!-- Send confirmation modal -->
  <Transition
    enter-active-class="transition ease-out duration-150"
    enter-from-class="opacity-0"
    enter-to-class="opacity-100"
    leave-active-class="transition ease-in duration-100"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0"
  >
    <div v-if="showSendModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" @click.self="showSendModal = false">
      <div class="w-full max-w-sm rounded-lg border bg-card p-6 shadow-lg space-y-4">
        <h3 class="text-base font-semibold">Send campaign now?</h3>
        <p class="text-sm text-muted-foreground">
          "<span class="font-medium text-foreground">{{ campaign.title }}</span>"
          will be sent immediately to all confirmed subscribers. This cannot be undone.
        </p>
        <div class="flex justify-end gap-2">
          <button type="button" class="rounded-md border px-4 py-2 text-sm font-medium hover:bg-accent transition-colors" @click="showSendModal = false">Cancel</button>
          <button type="button" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-[var(--primary-hover)] transition-colors" @click="sendNow">Send now</button>
        </div>
      </div>
    </div>
  </Transition>
</template>
