<template>
  <AppLayout title="Settings">
    <Head title="Settings" />

    <PageHeader title="Settings" description="Configure your site." />

    <div class="max-w-2xl space-y-6">

      <!-- Tab bar -->
      <div class="flex border-b border-border">
        <button
          v-for="tab in tabs"
          :key="tab.key"
          type="button"
          class="px-4 py-2 text-sm font-medium transition-colors border-b-2 -mb-px"
          :class="activeTab === tab.key
            ? 'border-primary text-primary'
            : 'border-transparent text-muted-foreground hover:text-foreground'"
          @click="activeTab = tab.key"
        >
          {{ tab.label }}
        </button>
      </div>

      <!-- Tab panels — v-show preserves form state when switching -->

      <!-- General: Site + Locale -->
      <div v-show="activeTab === 'general'" class="space-y-6">

        <!-- ── Site panel ──────────────────────────────────────────────────── -->
        <form @submit.prevent="submitSite">
          <ContentCard
            title="Site"
            description="Basic site identity settings."
          >
            <div class="space-y-4">
              <div class="space-y-1">
                <label for="site_name" class="text-sm font-medium">Site name</label>
                <input
                  id="site_name"
                  v-model="siteForm['site.name']"
                  type="text"
                  class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                  :class="{ 'border-destructive': siteForm.errors['site.name'] }"
                />
                <p v-if="siteForm.errors['site.name']" class="text-xs text-destructive mt-1">{{ siteForm.errors['site.name'] }}</p>
              </div>

              <div class="space-y-1">
                <label for="site_url" class="text-sm font-medium">Site URL</label>
                <input
                  id="site_url"
                  v-model="siteForm['site.url']"
                  type="url"
                  class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                  :class="{ 'border-destructive': siteForm.errors['site.url'] }"
                />
                <p v-if="siteForm.errors['site.url']" class="text-xs text-destructive mt-1">{{ siteForm.errors['site.url'] }}</p>
              </div>
            </div>

            <template #footer>
              <button
                type="submit"
                :disabled="siteForm.processing"
                class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)] disabled:opacity-50"
              >
                {{ siteForm.processing ? 'Saving...' : 'Save changes' }}
              </button>
            </template>
          </ContentCard>
        </form>

        <!-- ── Locale panel ────────────────────────────────────────────────── -->
        <form @submit.prevent="submitLocale">
          <ContentCard
            title="Locale"
            description="Timezone and date formatting preferences."
          >
            <div class="space-y-4">
              <div class="space-y-1">
                <label for="locale_timezone" class="text-sm font-medium">Timezone</label>
                <SelectBox
                  id="locale_timezone"
                  v-model="localeForm['locale.timezone']"
                  :data="timezoneOptions"
                  searchable
                />
              </div>

              <div class="space-y-1">
                <label for="locale_date_format" class="text-sm font-medium">Date format</label>
                <input
                  id="locale_date_format"
                  v-model="localeForm['locale.date_format']"
                  type="text"
                  placeholder="Y-m-d"
                  class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                  :class="{ 'border-destructive': localeForm.errors['locale.date_format'] }"
                />
                <p v-if="localeForm.errors['locale.date_format']" class="text-xs text-destructive mt-1">{{ localeForm.errors['locale.date_format'] }}</p>
                <p class="text-xs text-muted-foreground">PHP date format string (e.g. Y-m-d, d/m/Y, m/d/Y)</p>
              </div>
            </div>

            <template #footer>
              <button
                type="submit"
                :disabled="localeForm.processing"
                class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)] disabled:opacity-50"
              >
                {{ localeForm.processing ? 'Saving...' : 'Save changes' }}
              </button>
            </template>
          </ContentCard>
        </form>

      </div>

      <!-- Mail: Mail form + Test email form -->
      <div v-show="activeTab === 'mail'" class="space-y-6">

        <!-- ── Mail panel ──────────────────────────────────────────────────── -->
        <form @submit.prevent="submitMail">
          <ContentCard
            title="Mail"
            description="Outgoing email driver and SMTP configuration."
          >
            <div class="space-y-4">
              <div class="space-y-1">
                <label for="mail_driver" class="text-sm font-medium">Driver</label>
                <SelectBox
                  id="mail_driver"
                  v-model="mailForm['mail.driver']"
                  :data="[
                    { value: 'smtp',    label: 'SMTP' },
                    { value: 'log',     label: 'Log (development)' },
                    { value: 'mailgun', label: 'Mailgun' },
                  ]"
                />
              </div>

              <div class="space-y-1">
                <label for="mail_host" class="text-sm font-medium">Host</label>
                <input
                  id="mail_host"
                  v-model="mailForm['mail.host']"
                  type="text"
                  class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                  :class="{ 'border-destructive': mailForm.errors['mail.host'] }"
                />
                <p v-if="mailForm.errors['mail.host']" class="text-xs text-destructive mt-1">{{ mailForm.errors['mail.host'] }}</p>
              </div>

              <div class="space-y-1">
                <label for="mail_port" class="text-sm font-medium">Port</label>
                <NumberInput
                  id="mail_port"
                  v-model="mailForm['mail.port']"
                  :error="!!mailForm.errors['mail.port']"
                />
                <p v-if="mailForm.errors['mail.port']" class="text-xs text-destructive mt-1">{{ mailForm.errors['mail.port'] }}</p>
              </div>

              <div class="space-y-1">
                <label for="mail_username" class="text-sm font-medium">Username</label>
                <input
                  id="mail_username"
                  v-model="mailForm['mail.username']"
                  type="text"
                  autocomplete="off"
                  class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                  :class="{ 'border-destructive': mailForm.errors['mail.username'] }"
                />
                <p v-if="mailForm.errors['mail.username']" class="text-xs text-destructive mt-1">{{ mailForm.errors['mail.username'] }}</p>
              </div>

              <div class="space-y-1">
                <label for="mail_password" class="text-sm font-medium">Password</label>
                <input
                  id="mail_password"
                  v-model="mailForm['mail.password']"
                  type="password"
                  autocomplete="new-password"
                  placeholder="Leave blank to keep current"
                  class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                  :class="{ 'border-destructive': mailForm.errors['mail.password'] }"
                />
                <p v-if="mailForm.errors['mail.password']" class="text-xs text-destructive mt-1">{{ mailForm.errors['mail.password'] }}</p>
              </div>

              <div class="space-y-1">
                <label for="mail_from_address" class="text-sm font-medium">From address</label>
                <input
                  id="mail_from_address"
                  v-model="mailForm['mail.from_address']"
                  type="email"
                  class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                  :class="{ 'border-destructive': mailForm.errors['mail.from_address'] }"
                />
                <p v-if="mailForm.errors['mail.from_address']" class="text-xs text-destructive mt-1">{{ mailForm.errors['mail.from_address'] }}</p>
              </div>

              <div class="space-y-1">
                <label for="mail_from_name" class="text-sm font-medium">From name</label>
                <input
                  id="mail_from_name"
                  v-model="mailForm['mail.from_name']"
                  type="text"
                  class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                  :class="{ 'border-destructive': mailForm.errors['mail.from_name'] }"
                />
                <p v-if="mailForm.errors['mail.from_name']" class="text-xs text-destructive mt-1">{{ mailForm.errors['mail.from_name'] }}</p>
              </div>

              <div class="space-y-1">
                <label for="mail_encryption" class="text-sm font-medium">Encryption</label>
                <SelectBox
                  id="mail_encryption"
                  v-model="mailForm['mail.encryption']"
                  :data="[
                    { value: 'tls', label: 'TLS' },
                    { value: 'ssl', label: 'SSL' },
                    { value: '',    label: 'None' },
                  ]"
                />
              </div>
            </div>

            <template #footer>
              <button
                type="submit"
                :disabled="mailForm.processing"
                class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)] disabled:opacity-50"
              >
                {{ mailForm.processing ? 'Saving...' : 'Save changes' }}
              </button>
            </template>
          </ContentCard>
        </form>

        <!-- ── Test email panel ────────────────────────────────────────────── -->
        <form @submit.prevent="sendTestEmail">
          <ContentCard
            title="Send test email"
            description="Send a test email using the current mail configuration to your account address."
          >
            <template #footer>
              <button
                type="submit"
                :disabled="testMailForm.processing"
                class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)] disabled:opacity-50 inline-flex items-center gap-2"
              >
                <Loader2 v-if="testMailForm.processing" class="w-4 h-4 animate-spin" />
                {{ testMailForm.processing ? 'Sending...' : 'Send test email' }}
              </button>
            </template>
          </ContentCard>
        </form>

      </div>

      <!-- Media -->
      <div v-show="activeTab === 'media'">

        <!-- ── Media panel ─────────────────────────────────────────────────── -->
        <form @submit.prevent="submitMedia">
          <ContentCard
            title="Media"
            description="Upload limits and image processing settings."
          >
            <div class="space-y-4">
              <div class="space-y-1">
                <label for="media_max_upload_mb" class="text-sm font-medium">Max upload size (MB)</label>
                <NumberInput
                  id="media_max_upload_mb"
                  v-model="mediaForm['media.max_upload_mb']"
                  :min="1"
                  :max="100"
                  :error="!!mediaForm.errors['media.max_upload_mb']"
                />
                <p v-if="mediaForm.errors['media.max_upload_mb']" class="text-xs text-destructive mt-1">{{ mediaForm.errors['media.max_upload_mb'] }}</p>
              </div>

              <div class="space-y-1">
                <label for="media_resize_max_width" class="text-sm font-medium">Max resize width (px)</label>
                <NumberInput
                  id="media_resize_max_width"
                  v-model="mediaForm['media.resize_max_width']"
                  :min="320"
                  :max="8000"
                  :error="!!mediaForm.errors['media.resize_max_width']"
                />
                <p v-if="mediaForm.errors['media.resize_max_width']" class="text-xs text-destructive mt-1">{{ mediaForm.errors['media.resize_max_width'] }}</p>
              </div>

              <!-- Allowed file type categories -->
              <div class="space-y-2">
                <label class="text-sm font-medium">Allowed file types</label>
                <div class="grid grid-cols-2 gap-2">
                  <label v-for="cat in [
                    { key: 'image', label: 'Images' },
                    { key: 'document', label: 'Documents' },
                    { key: 'video', label: 'Video' },
                    { key: 'audio', label: 'Audio' },
                  ]" :key="cat.key" class="flex items-center gap-2 text-sm cursor-pointer">
                    <input
                      type="checkbox"
                      :value="cat.key"
                      v-model="mediaForm.media_allowed_categories"
                      class="rounded border-border"
                    />
                    {{ cat.label }}
                  </label>
                </div>
              </div>

              <!-- Custom MIME types tag input -->
              <div class="space-y-2">
                <label class="text-sm font-medium">Custom MIME types</label>
                <div class="flex flex-wrap gap-1.5 mb-1.5">
                  <span
                    v-for="mime in mediaForm.media_custom_mimes"
                    :key="mime"
                    class="inline-flex items-center gap-1 rounded-full bg-muted px-2.5 py-0.5 text-xs font-medium"
                  >
                    {{ mime }}
                    <button type="button" @click="removeCustomMime(mime)" class="hover:text-destructive transition-colors">&times;</button>
                  </span>
                </div>
                <div class="flex gap-2">
                  <input
                    v-model="customMimeInput"
                    type="text"
                    placeholder="e.g. application/json"
                    class="flex-1 rounded-md border bg-background px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                    @keydown.enter.prevent="addCustomMime"
                  />
                  <button type="button" @click="addCustomMime" class="rounded-md border px-3 py-1.5 text-sm hover:bg-accent transition-colors">Add</button>
                </div>
                <p class="text-xs text-muted-foreground">Press Enter or click Add. Example: <code>image/tiff</code></p>
              </div>
            </div>

            <template #footer>
              <button
                type="submit"
                :disabled="mediaForm.processing"
                class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)] disabled:opacity-50"
              >
                {{ mediaForm.processing ? 'Saving...' : 'Save changes' }}
              </button>
            </template>
          </ContentCard>
        </form>

      </div>

      <!-- Comments -->
      <div v-show="activeTab === 'comments'">

        <!-- ── Comments panel ────────────────────────────────────────────── -->
        <form @submit.prevent="submitComments">
          <ContentCard
            title="Comments"
            description="Control comment visibility and loading behaviour."
          >
            <div class="space-y-4">
              <div class="flex items-center justify-between">
                <div>
                  <label for="comments_enabled" class="text-sm font-medium">Enable comments</label>
                  <p class="text-xs text-muted-foreground mt-0.5">When disabled, existing comments remain visible but new submissions are blocked.</p>
                </div>
                <input
                  id="comments_enabled"
                  v-model="commentsForm['comments.enabled']"
                  type="checkbox"
                  class="w-4 h-4 rounded border-border accent-nord-green"
                />
              </div>

              <div class="space-y-1">
                <label for="comments_per_page" class="text-sm font-medium">Comments per page</label>
                <NumberInput
                  id="comments_per_page"
                  v-model="commentsForm['comments.per_page']"
                  :min="5"
                  :max="100"
                  :error="!!commentsForm.errors['comments.per_page']"
                />
                <p v-if="commentsForm.errors['comments.per_page']" class="text-xs text-destructive mt-1">{{ commentsForm.errors['comments.per_page'] }}</p>
                <p class="text-xs text-muted-foreground">How many comments load initially and per "Load more" click (5–100).</p>
              </div>
            </div>

            <template #footer>
              <button
                type="submit"
                :disabled="commentsForm.processing"
                class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)] disabled:opacity-50"
              >
                {{ commentsForm.processing ? 'Saving...' : 'Save changes' }}
              </button>
            </template>
          </ContentCard>
        </form>

      </div>

      <!-- SEO -->
      <div v-show="activeTab === 'seo'">

        <!-- ── SEO panel ──────────────────────────────────────────────────────────────────── -->
        <form @submit.prevent="submitSeo">
          <ContentCard
            title="SEO"
            description="Default meta tags for public blog pages."
          >
            <div class="space-y-4">
              <div class="space-y-1">
                <label for="seo_title_separator" class="text-sm font-medium">Title separator</label>
                <input
                  id="seo_title_separator"
                  v-model="seoForm['seo.title_separator']"
                  type="text"
                  class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                />
                <p class="text-xs text-muted-foreground">Characters between post title and site name, e.g. " | ".</p>
              </div>

              <div class="space-y-1">
                <label for="seo_default_description" class="text-sm font-medium">Default meta description</label>
                <textarea
                  id="seo_default_description"
                  v-model="seoForm['seo.default_description']"
                  rows="3"
                  class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring resize-none"
                  placeholder="Used when a post has no excerpt or custom meta description"
                />
              </div>

              <div class="space-y-1">
                <label for="seo_og_image_url" class="text-sm font-medium">Default OG image URL</label>
                <input
                  id="seo_og_image_url"
                  v-model="seoForm['seo.default_og_image_url']"
                  type="url"
                  class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                  placeholder="https://example.com/og-default.jpg"
                />
                <p class="text-xs text-muted-foreground">Used on pages with no featured image.</p>
              </div>

              <div class="space-y-1">
                <label for="seo_default_keywords" class="text-sm font-medium">Default keywords</label>
                <input
                  id="seo_default_keywords"
                  v-model="seoForm['seo.default_keywords']"
                  type="text"
                  class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                  placeholder="e.g. laravel, cms, blog"
                />
                <p class="text-xs text-muted-foreground">Comma-separated. Used on pages with no post-specific keywords.</p>
              </div>
            </div>

            <template #footer>
              <button
                type="submit"
                :disabled="seoForm.processing"
                class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)] disabled:opacity-50"
              >
                {{ seoForm.processing ? 'Saving...' : 'Save changes' }}
              </button>
            </template>
          </ContentCard>
        </form>

      </div>

      <!-- Appearance -->
      <div v-show="activeTab === 'appearance'">
        <form @submit.prevent="submitAppearance">
          <ContentCard
            title="Appearance"
            description="Choose an accent color for the admin interface and public site."
          >
            <div class="space-y-4">
              <div class="space-y-2">
                <label class="text-sm font-medium">Accent color</label>
                <div class="flex flex-wrap gap-3 mt-2">
                  <button
                    v-for="swatch in ACCENT_SWATCHES"
                    :key="swatch.value"
                    type="button"
                    :title="swatch.label"
                    @click="appearanceForm['site.accent_color'] = swatch.value"
                    class="relative w-9 h-9 rounded-full border-2 transition-all focus:outline-none"
                    :style="{ backgroundColor: swatch.value }"
                    :class="appearanceForm['site.accent_color'] === swatch.value
                      ? 'border-foreground scale-110 shadow-md'
                      : 'border-transparent hover:scale-105'"
                  >
                    <svg
                      v-if="appearanceForm['site.accent_color'] === swatch.value"
                      class="w-4 h-4 absolute inset-0 m-auto text-white drop-shadow"
                      fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"
                    >
                      <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                  </button>
                </div>
              </div>
            </div>

            <template #footer>
              <button
                type="submit"
                :disabled="appearanceForm.processing"
                class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)] disabled:opacity-50"
              >
                {{ appearanceForm.processing ? 'Saving...' : 'Save changes' }}
              </button>
            </template>
          </ContentCard>
        </form>
      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { Loader2 } from 'lucide-vue-next'
import { Head, useForm, usePage } from "@inertiajs/vue3";
import { useNotifications } from '@/composables/useNotifications.js'
import AppLayout from "@/Layouts/AppLayout.vue";
import PageHeader from '@/Components/PageHeader.vue'
import ContentCard from '@/Components/ContentCard.vue'
import SelectBox from '@/Components/SelectBox.vue'
import NumberInput from '@/Components/NumberInput.vue'

const props = defineProps({
  settings: {
    type: Object,
    required: true,
  },
});

// ── Tabs ─────────────────────────────────────────────────────────────────────
const activeTab = ref('general')

const tabs = [
  { key: 'general',    label: 'General'    },
  { key: 'mail',       label: 'Mail'       },
  { key: 'media',      label: 'Media'      },
  { key: 'comments',   label: 'Comments'   },
  { key: 'seo',        label: 'SEO'        },
  { key: 'appearance', label: 'Appearance' },
]

// ── Auto-switch to mail tab when mail flash messages appear ───────────────────
const page = usePage()
const { notify } = useNotifications()

watch(
  () => page.props.flash,
  (flash) => {
    if (!flash) return
    if (flash.mail_status || flash.mail_error) { activeTab.value = 'mail' }
    if (flash.mail_status) notify(flash.mail_status, 'success')
    if (flash.mail_error)  notify(flash.mail_error,  'error')
    // The generic flash.status is emitted by all forms; we can't distinguish which
    // panel it came from, so leave the tab as-is (user already knows where they saved).
  },
  { immediate: false }
)

// ── Common timezones ─────────────────────────────────────────────────────────
const timezones = [
  'UTC',
  'America/New_York',
  'America/Chicago',
  'America/Denver',
  'America/Los_Angeles',
  'America/Toronto',
  'Europe/London',
  'Europe/Paris',
  'Europe/Berlin',
  'Europe/Amsterdam',
  'Europe/Madrid',
  'Europe/Rome',
  'Europe/Warsaw',
  'Europe/Athens',
  'Asia/Dubai',
  'Asia/Kolkata',
  'Asia/Tokyo',
  'Asia/Shanghai',
  'Asia/Singapore',
  'Australia/Sydney',
  'Pacific/Auckland',
];

const timezoneOptions = computed(() => timezones.map(tz => ({ value: tz, label: tz })))

// ── Site form ────────────────────────────────────────────────────────────────
const siteForm = useForm({
  'site.name': props.settings['site.name'] ?? '',
  'site.url':  props.settings['site.url']  ?? '',
});

function submitSite() {
  siteForm.put(route('settings.update', 'site'), {
    preserveScroll: true,
    onSuccess: () => notify('Settings saved.', 'success'),
    onError: (errors) => notify('Please fix the following:', 'error', { items: Object.values(errors) }),
  });
}

// ── Locale form ──────────────────────────────────────────────────────────────
const localeForm = useForm({
  'locale.timezone':    props.settings['locale.timezone']    ?? 'UTC',
  'locale.date_format': props.settings['locale.date_format'] ?? 'Y-m-d',
});

function submitLocale() {
  localeForm.put(route('settings.update', 'locale'), {
    preserveScroll: true,
    onSuccess: () => notify('Settings saved.', 'success'),
    onError: (errors) => notify('Please fix the following:', 'error', { items: Object.values(errors) }),
  });
}

// ── Media form ───────────────────────────────────────────────────────────────
const mediaForm = useForm({
  'media.max_upload_mb':    Number(props.settings['media.max_upload_mb']    ?? 10),
  'media.resize_max_width': Number(props.settings['media.resize_max_width'] ?? 2048),
  media_allowed_categories: props.settings?.['media.allowed_categories']
    ? JSON.parse(props.settings['media.allowed_categories'])
    : ['image', 'document', 'video', 'audio'],
  media_custom_mimes: props.settings?.['media.custom_mimes']
    ? JSON.parse(props.settings['media.custom_mimes'])
    : [],
})

const customMimeInput = ref('')

function addCustomMime() {
  const mime = customMimeInput.value.trim()
  if (mime && !mediaForm.media_custom_mimes.includes(mime)) {
    mediaForm.media_custom_mimes.push(mime)
  }
  customMimeInput.value = ''
}

function removeCustomMime(mime) {
  mediaForm.media_custom_mimes = mediaForm.media_custom_mimes.filter(m => m !== mime)
}

function submitMedia() {
  mediaForm.put(route('settings.update', 'media'), {
    preserveScroll: true,
    onSuccess: () => notify('Settings saved.', 'success'),
    onError: (errors) => notify('Please fix the following:', 'error', { items: Object.values(errors) }),
  });
}

// ── Mail form ────────────────────────────────────────────────────────────────
const mailForm = useForm({
  'mail.driver':       props.settings['mail.driver']       ?? 'log',
  'mail.host':         props.settings['mail.host']         ?? '',
  'mail.port':         Number(props.settings['mail.port']  ?? 587) || '',
  'mail.username':     props.settings['mail.username']     ?? '',
  'mail.password':     '',
  'mail.from_address': props.settings['mail.from_address'] ?? '',
  'mail.from_name':    props.settings['mail.from_name']    ?? '',
  'mail.encryption':   props.settings['mail.encryption']   ?? 'tls',
});

function submitMail() {
  mailForm.put(route('settings.update', 'mail'), {
    preserveScroll: true,
    onSuccess: () => notify('Settings saved.', 'success'),
    onError: (errors) => notify('Please fix the following:', 'error', { items: Object.values(errors) }),
  });
}

// ── Comments form ─────────────────────────────────────────────────────────────
const commentsForm = useForm({
  'comments.enabled':  props.settings['comments.enabled'] === '1' || props.settings['comments.enabled'] === true,
  'comments.per_page': Number(props.settings['comments.per_page'] ?? 10),
})

function submitComments() {
  commentsForm
    .transform((data) => ({
      'comments.enabled':  data['comments.enabled'] ? '1' : '0',
      'comments.per_page': data['comments.per_page'],
    }))
    .put(route('settings.update', 'comments'), {
      preserveScroll: true,
      onSuccess: () => notify('Settings saved.', 'success'),
      onError: (errors) => notify('Please fix the following:', 'error', { items: Object.values(errors) }),
    })
}

// ── SEO form ──────────────────────────────────────────────────────────────────
const seoForm = useForm({
  'seo.title_separator':      props.settings['seo.title_separator']      ?? ' | ',
  'seo.default_description':  props.settings['seo.default_description']  ?? '',
  'seo.default_og_image_url': props.settings['seo.default_og_image_url'] ?? '',
  'seo.default_keywords':     props.settings['seo.default_keywords']     ?? '',
})

function submitSeo() {
  seoForm.put(route('settings.update', 'seo'), {
    preserveScroll: true,
    onSuccess: () => notify('Settings saved.', 'success'),
    onError: (errors) => notify('Please fix the following:', 'error', { items: Object.values(errors) }),
  })
}

// ── Appearance form ───────────────────────────────────────────────────────────
const ACCENT_SWATCHES = [
  { label: 'Frost Blue (default)', value: '#5e81ac', hover: '#4a6d92' },
  { label: 'Nord Green',           value: '#a3be8c', hover: '#8aaa70' },
  { label: 'Nord Yellow',          value: '#ebcb8b', hover: '#d4b06a' },
  { label: 'Nord Orange',          value: '#d08770', hover: '#bb6f58' },
  { label: 'Nord Red',             value: '#bf616a', hover: '#a84d56' },
  { label: 'Nord Purple',          value: '#b48ead', hover: '#9d7596' },
]

const appearanceForm = useForm({
  'site.accent_color': props.settings['site.accent_color'] || '#5e81ac',
})

function submitAppearance() {
  const color = appearanceForm['site.accent_color']
  const swatch = ACCENT_SWATCHES.find(s => s.value === color)
  appearanceForm.put(route('settings.update', 'appearance'), {
    preserveScroll: true,
    onSuccess: () => {
      notify('Settings saved.', 'success')
      if (color && swatch) {
        document.documentElement.style.setProperty('--primary', color)
        document.documentElement.style.setProperty('--primary-hover', swatch.hover)
        document.documentElement.style.setProperty('--primary-foreground', '#ffffff')
        document.documentElement.style.setProperty('--sidebar-primary', color)
        document.documentElement.style.setProperty('--sidebar-primary-foreground', '#eceff4')
      }
    },
    onError: (errors) => notify('Please fix the following:', 'error', { items: Object.values(errors) }),
  })
}

// ── Test email form ──────────────────────────────────────────────────────────
const testMailForm = useForm({});

function sendTestEmail() {
  testMailForm.post(route('settings.test-email'), {
    preserveScroll: true,
    onError: (errors) => notify('Please fix the following:', 'error', { items: Object.values(errors) }),
  });
}
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
