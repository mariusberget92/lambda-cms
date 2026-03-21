<template>
  <AppLayout title="Settings">
    <Head title="Settings" />

    <div class="max-w-2xl space-y-6">

      <!-- Page header -->
      <div>
        <h2 class="text-lg font-semibold">Settings</h2>
        <p class="text-sm text-muted-foreground mt-0.5">Manage site, locale, media, and mail configuration.</p>
      </div>

      <!-- Flash: general status (success) -->
      <Transition name="fade">
        <div
          v-if="$page.props.flash?.status"
          class="flex items-center gap-2 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700"
        >
          <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          {{ $page.props.flash.status }}
        </div>
      </Transition>

      <!-- Flash: mail test success -->
      <Transition name="fade">
        <div
          v-if="$page.props.flash?.mail_status"
          class="flex items-center gap-2 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700"
        >
          <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          {{ $page.props.flash.mail_status }}
        </div>
      </Transition>

      <!-- Flash: mail test error -->
      <Transition name="fade">
        <div
          v-if="$page.props.flash?.mail_error"
          class="flex items-center gap-2 rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700"
        >
          <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          {{ $page.props.flash.mail_error }}
        </div>
      </Transition>

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
          <div class="rounded-lg border bg-card p-6 space-y-4">
            <div>
              <h3 class="text-sm font-semibold">Site</h3>
              <p class="text-xs text-muted-foreground mt-0.5">Basic site identity settings.</p>
            </div>

            <div class="space-y-1">
              <label for="site_name" class="text-sm font-medium">Site name</label>
              <input
                id="site_name"
                v-model="siteForm['site.name']"
                type="text"
                class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                :class="{ 'border-destructive': siteForm.errors['site.name'] }"
              />
              <p v-if="siteForm.errors['site.name']" class="text-xs text-destructive">{{ siteForm.errors['site.name'] }}</p>
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
              <p v-if="siteForm.errors['site.url']" class="text-xs text-destructive">{{ siteForm.errors['site.url'] }}</p>
            </div>

            <div class="flex justify-end pt-1">
              <button
                type="submit"
                :disabled="siteForm.processing"
                class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)] disabled:opacity-50"
              >
                {{ siteForm.processing ? 'Saving...' : 'Save changes' }}
              </button>
            </div>
          </div>
        </form>

        <!-- ── Locale panel ────────────────────────────────────────────────── -->
        <form @submit.prevent="submitLocale">
          <div class="rounded-lg border bg-card p-6 space-y-4">
            <div>
              <h3 class="text-sm font-semibold">Locale</h3>
              <p class="text-xs text-muted-foreground mt-0.5">Timezone and date formatting preferences.</p>
            </div>

            <div class="space-y-1">
              <label for="locale_timezone" class="text-sm font-medium">Timezone</label>
              <SelectBox
                id="locale_timezone"
                v-model="localeForm['locale.timezone']"
                :data="timezoneOptions"
                searchable
              />
              <p v-if="localeForm.errors['locale.timezone']" class="text-xs text-destructive">{{ localeForm.errors['locale.timezone'] }}</p>
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
              <p class="text-xs text-muted-foreground">PHP date format string (e.g. Y-m-d, d/m/Y, m/d/Y)</p>
              <p v-if="localeForm.errors['locale.date_format']" class="text-xs text-destructive">{{ localeForm.errors['locale.date_format'] }}</p>
            </div>

            <div class="flex justify-end pt-1">
              <button
                type="submit"
                :disabled="localeForm.processing"
                class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)] disabled:opacity-50"
              >
                {{ localeForm.processing ? 'Saving...' : 'Save changes' }}
              </button>
            </div>
          </div>
        </form>

      </div>

      <!-- Mail: Mail form + Test email form -->
      <div v-show="activeTab === 'mail'" class="space-y-6">

        <!-- ── Mail panel ──────────────────────────────────────────────────── -->
        <form @submit.prevent="submitMail">
          <div class="rounded-lg border bg-card p-6 space-y-4">
            <div>
              <h3 class="text-sm font-semibold">Mail</h3>
              <p class="text-xs text-muted-foreground mt-0.5">Outgoing email driver and SMTP configuration.</p>
            </div>

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
              <p v-if="mailForm.errors['mail.driver']" class="text-xs text-destructive">{{ mailForm.errors['mail.driver'] }}</p>
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
              <p v-if="mailForm.errors['mail.host']" class="text-xs text-destructive">{{ mailForm.errors['mail.host'] }}</p>
            </div>

            <div class="space-y-1">
              <label for="mail_port" class="text-sm font-medium">Port</label>
              <input
                id="mail_port"
                v-model.number="mailForm['mail.port']"
                type="number"
                class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                :class="{ 'border-destructive': mailForm.errors['mail.port'] }"
              />
              <p v-if="mailForm.errors['mail.port']" class="text-xs text-destructive">{{ mailForm.errors['mail.port'] }}</p>
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
              <p v-if="mailForm.errors['mail.username']" class="text-xs text-destructive">{{ mailForm.errors['mail.username'] }}</p>
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
              <p v-if="mailForm.errors['mail.password']" class="text-xs text-destructive">{{ mailForm.errors['mail.password'] }}</p>
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
              <p v-if="mailForm.errors['mail.from_address']" class="text-xs text-destructive">{{ mailForm.errors['mail.from_address'] }}</p>
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
              <p v-if="mailForm.errors['mail.from_name']" class="text-xs text-destructive">{{ mailForm.errors['mail.from_name'] }}</p>
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
              <p v-if="mailForm.errors['mail.encryption']" class="text-xs text-destructive">{{ mailForm.errors['mail.encryption'] }}</p>
            </div>

            <div class="flex justify-end pt-1">
              <button
                type="submit"
                :disabled="mailForm.processing"
                class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)] disabled:opacity-50"
              >
                {{ mailForm.processing ? 'Saving...' : 'Save changes' }}
              </button>
            </div>
          </div>
        </form>

        <!-- ── Test email panel ────────────────────────────────────────────── -->
        <form @submit.prevent="sendTestEmail">
          <div class="rounded-lg border bg-card p-6 space-y-4">
            <div>
              <h3 class="text-sm font-semibold">Send test email</h3>
              <p class="text-xs text-muted-foreground mt-0.5">Send a test email using the current mail configuration to your account address.</p>
            </div>

            <div class="flex justify-end pt-1">
              <button
                type="submit"
                :disabled="testMailForm.processing"
                class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)] disabled:opacity-50 inline-flex items-center gap-2"
              >
                <Loader2 v-if="testMailForm.processing" class="w-4 h-4 animate-spin" />
                {{ testMailForm.processing ? 'Sending...' : 'Send test email' }}
              </button>
            </div>
          </div>
        </form>

      </div>

      <!-- Media -->
      <div v-show="activeTab === 'media'">

        <!-- ── Media panel ─────────────────────────────────────────────────── -->
        <form @submit.prevent="submitMedia">
          <div class="rounded-lg border bg-card p-6 space-y-4">
            <div>
              <h3 class="text-sm font-semibold">Media</h3>
              <p class="text-xs text-muted-foreground mt-0.5">Upload limits and image processing settings.</p>
            </div>

            <div class="space-y-1">
              <label for="media_max_upload_mb" class="text-sm font-medium">Max upload size (MB)</label>
              <input
                id="media_max_upload_mb"
                v-model.number="mediaForm['media.max_upload_mb']"
                type="number"
                min="1"
                max="100"
                class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                :class="{ 'border-destructive': mediaForm.errors['media.max_upload_mb'] }"
              />
              <p v-if="mediaForm.errors['media.max_upload_mb']" class="text-xs text-destructive">{{ mediaForm.errors['media.max_upload_mb'] }}</p>
            </div>

            <div class="space-y-1">
              <label for="media_resize_max_width" class="text-sm font-medium">Max resize width (px)</label>
              <input
                id="media_resize_max_width"
                v-model.number="mediaForm['media.resize_max_width']"
                type="number"
                min="320"
                max="8000"
                class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                :class="{ 'border-destructive': mediaForm.errors['media.resize_max_width'] }"
              />
              <p v-if="mediaForm.errors['media.resize_max_width']" class="text-xs text-destructive">{{ mediaForm.errors['media.resize_max_width'] }}</p>
            </div>

            <div class="flex justify-end pt-1">
              <button
                type="submit"
                :disabled="mediaForm.processing"
                class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)] disabled:opacity-50"
              >
                {{ mediaForm.processing ? 'Saving...' : 'Save changes' }}
              </button>
            </div>
          </div>
        </form>

      </div>

      <!-- Comments -->
      <div v-show="activeTab === 'comments'">

        <!-- ── Comments panel ────────────────────────────────────────────── -->
        <form @submit.prevent="submitComments">
          <div class="rounded-lg border bg-card p-6 space-y-4">
            <div>
              <h3 class="text-sm font-semibold">Comments</h3>
              <p class="text-xs text-muted-foreground mt-0.5">Control comment visibility and loading behaviour.</p>
            </div>

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
              <input
                id="comments_per_page"
                v-model.number="commentsForm['comments.per_page']"
                type="number"
                min="5"
                max="100"
                class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                :class="{ 'border-destructive': commentsForm.errors['comments.per_page'] }"
              />
              <p class="text-xs text-muted-foreground">How many comments load initially and per "Load more" click (5–100).</p>
              <p v-if="commentsForm.errors['comments.per_page']" class="text-xs text-destructive">{{ commentsForm.errors['comments.per_page'] }}</p>
            </div>

            <div class="flex justify-end pt-1">
              <button
                type="submit"
                :disabled="commentsForm.processing"
                class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)] disabled:opacity-50"
              >
                {{ commentsForm.processing ? 'Saving...' : 'Save changes' }}
              </button>
            </div>
          </div>
        </form>

      </div>

      <!-- SEO -->
      <div v-show="activeTab === 'seo'">

        <!-- ── SEO panel ──────────────────────────────────────────────────────────────────── -->
        <form @submit.prevent="submitSeo">
          <div class="rounded-lg border bg-card p-6 space-y-4">
            <div>
              <h3 class="text-sm font-semibold">SEO</h3>
              <p class="text-xs text-muted-foreground mt-0.5">Default meta tags for public blog pages.</p>
            </div>

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

            <div class="flex justify-end pt-1">
              <button
                type="submit"
                :disabled="seoForm.processing"
                class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)] disabled:opacity-50"
              >
                {{ seoForm.processing ? 'Saving...' : 'Save changes' }}
              </button>
            </div>
          </div>
        </form>

      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { Loader2 } from 'lucide-vue-next'
import { Head, useForm, usePage } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import SelectBox from '@/Components/SelectBox.vue'

const props = defineProps({
  settings: {
    type: Object,
    required: true,
  },
});

// ── Tabs ─────────────────────────────────────────────────────────────────────
const activeTab = ref('general')

const tabs = [
  { key: 'general',  label: 'General'  },
  { key: 'mail',     label: 'Mail'     },
  { key: 'media',    label: 'Media'    },
  { key: 'comments', label: 'Comments' },
  { key: 'seo',      label: 'SEO'      },
]

// ── Auto-switch to mail tab when mail flash messages appear ───────────────────
const page = usePage()

watch(
  () => page.props.flash,
  (flash) => {
    if (!flash) return
    if (flash.mail_status || flash.mail_error) { activeTab.value = 'mail'; return }
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
  siteForm.put(route('settings.update', 'site'), { preserveScroll: true });
}

// ── Locale form ──────────────────────────────────────────────────────────────
const localeForm = useForm({
  'locale.timezone':    props.settings['locale.timezone']    ?? 'UTC',
  'locale.date_format': props.settings['locale.date_format'] ?? 'Y-m-d',
});

function submitLocale() {
  localeForm.put(route('settings.update', 'locale'), { preserveScroll: true });
}

// ── Media form ───────────────────────────────────────────────────────────────
const mediaForm = useForm({
  'media.max_upload_mb':    Number(props.settings['media.max_upload_mb']    ?? 10),
  'media.resize_max_width': Number(props.settings['media.resize_max_width'] ?? 2048),
});

function submitMedia() {
  mediaForm.put(route('settings.update', 'media'), { preserveScroll: true });
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
  mailForm.put(route('settings.update', 'mail'), { preserveScroll: true });
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
    .put(route('settings.update', 'comments'), { preserveScroll: true })
}

// ── SEO form ──────────────────────────────────────────────────────────────────
const seoForm = useForm({
  'seo.title_separator':      props.settings['seo.title_separator']      ?? ' | ',
  'seo.default_description':  props.settings['seo.default_description']  ?? '',
  'seo.default_og_image_url': props.settings['seo.default_og_image_url'] ?? '',
  'seo.default_keywords':     props.settings['seo.default_keywords']     ?? '',
})

function submitSeo() {
  seoForm.put(route('settings.update', 'seo'), { preserveScroll: true })
}

// ── Test email form ──────────────────────────────────────────────────────────
const testMailForm = useForm({});

function sendTestEmail() {
  testMailForm.post(route('settings.test-email'), { preserveScroll: true });
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
