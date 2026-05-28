<template>
  <AppLayout title="Profile">
    <Head title="Profile" />

    <div class="max-w-2xl space-y-6">

      <!-- Page header -->
      <PageHeader title="Profile" description="Update your account details" />

      <!-- Panel 1: Profile information -->
      <form @submit.prevent="submitInfo">
        <div class="rounded-lg border bg-card p-6 space-y-4">
          <div>
            <h3 class="text-sm font-semibold">Profile information</h3>
            <p class="text-xs text-muted-foreground mt-0.5">Update your name and email address.</p>
          </div>

          <div class="space-y-1">
            <label for="name" class="text-sm font-medium">Name</label>
            <input
              id="name"
              v-model="infoForm.name"
              type="text"
              autocomplete="name"
              class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
              :class="{ 'border-destructive': infoForm.errors.name }"
            />
            <p v-if="infoForm.errors.name" class="text-xs text-destructive mt-1">{{ infoForm.errors.name }}</p>
          </div>

          <div class="space-y-1">
            <label for="email" class="text-sm font-medium">Email address</label>
            <input
              id="email"
              v-model="infoForm.email"
              type="email"
              autocomplete="email"
              class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
              :class="{ 'border-destructive': infoForm.errors.email }"
            />
            <p v-if="infoForm.errors.email" class="text-xs text-destructive mt-1">{{ infoForm.errors.email }}</p>
          </div>

          <div class="flex justify-end pt-1">
            <button
              type="submit"
              :disabled="infoForm.processing"
              class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)] disabled:opacity-50"
            >
              {{ infoForm.processing ? 'Saving...' : 'Save changes' }}
            </button>
          </div>
        </div>
      </form>

      <!-- Panel 2: Password -->
      <form @submit.prevent="submitPassword">
        <div class="rounded-lg border bg-card p-6 space-y-4">
          <div>
            <h3 class="text-sm font-semibold">Update password</h3>
            <p class="text-xs text-muted-foreground mt-0.5">Use a strong password you don't use anywhere else.</p>
          </div>

          <div class="space-y-1">
            <label for="current_password" class="text-sm font-medium">Current password</label>
            <input
              id="current_password"
              v-model="passwordForm.current_password"
              type="password"
              autocomplete="current-password"
              class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
              :class="{ 'border-destructive': passwordForm.errors.current_password }"
            />
            <p v-if="passwordForm.errors.current_password" class="text-xs text-destructive mt-1">{{ passwordForm.errors.current_password }}</p>
          </div>

          <div class="space-y-1">
            <label for="password" class="text-sm font-medium">New password</label>
            <input
              id="password"
              v-model="passwordForm.password"
              type="password"
              autocomplete="new-password"
              class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
              :class="{ 'border-destructive': passwordForm.errors.password }"
            />
            <p v-if="passwordForm.errors.password" class="text-xs text-destructive mt-1">{{ passwordForm.errors.password }}</p>
          </div>

          <div class="space-y-1">
            <label for="password_confirmation" class="text-sm font-medium">Confirm new password</label>
            <input
              id="password_confirmation"
              v-model="passwordForm.password_confirmation"
              type="password"
              autocomplete="new-password"
              class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
              :class="{ 'border-destructive': passwordForm.errors.password_confirmation }"
            />
            <p v-if="passwordForm.errors.password_confirmation" class="text-xs text-destructive mt-1">{{ passwordForm.errors.password_confirmation }}</p>
          </div>

          <div class="flex justify-end pt-1">
            <button
              type="submit"
              :disabled="passwordForm.processing"
              class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)] disabled:opacity-50"
            >
              {{ passwordForm.processing ? 'Updating...' : 'Update password' }}
            </button>
          </div>
        </div>
      </form>

      <!-- Panel 3: Avatar -->
      <div class="rounded-lg border bg-card p-6 space-y-4">
        <div>
          <h3 class="text-sm font-semibold">Avatar</h3>
          <p class="text-xs text-muted-foreground mt-0.5">Shown next to your name throughout the CMS.</p>
        </div>

        <div class="flex items-center gap-6">
          <!-- Avatar display: new file preview > saved avatar > SVG default -->
          <div class="w-20 h-20 rounded-full overflow-hidden shrink-0 bg-muted flex items-center justify-center ring-2 ring-border">
            <img
              v-if="avatarPreview"
              :src="avatarPreview"
              alt="Preview"
              class="w-full h-full object-cover"
            />
            <img
              v-else-if="user.avatar_url"
              :src="user.avatar_url"
              :alt="user.name"
              class="w-full h-full object-cover"
            />
            <svg
              v-else
              class="w-10 h-10 text-muted-foreground/40"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
              stroke-width="1"
            >
              <path stroke-linecap="round" stroke-linejoin="round"
                d="M5.121 17.804A9 9 0 1118.88 6.196M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
          </div>

          <div class="space-y-3 flex-1">
            <!-- Hidden real file input -->
            <input
              ref="avatarInput"
              type="file"
              accept="image/*"
              class="hidden"
              @change="onFileSelected"
            />

            <div class="flex flex-wrap gap-2">
              <!-- Upload / replace button -->
              <button
                type="button"
                @click="triggerAvatarInput"
                class="rounded-md border px-4 py-2 text-sm font-medium hover:bg-accent transition-colors"
              >
                {{ user.avatar_url ? 'Replace avatar' : 'Upload avatar' }}
              </button>

              <!-- Save button — only visible when a new file has been chosen -->
              <button
                v-if="avatarPreview"
                type="button"
                :disabled="avatarForm.processing"
                @click="submitAvatar"
                class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)] disabled:opacity-50"
              >
                {{ avatarForm.processing ? 'Uploading...' : 'Save avatar' }}
              </button>

              <!-- Delete button — only when a saved avatar exists and no new file is pending -->
              <button
                v-if="user.avatar_url && !avatarPreview"
                type="button"
                @click="showDeleteConfirm = true"
                class="rounded-md border border-destructive/30 px-4 py-2 text-sm font-medium text-destructive hover:bg-destructive/10 transition-colors"
              >
                Delete avatar
              </button>
            </div>

            <p class="text-xs text-muted-foreground">JPG, PNG, GIF or WebP &mdash; max 5 MB.</p>
          </div>
        </div>

        <!-- Inline delete confirmation -->
        <Transition name="fade">
          <div
            v-if="showDeleteConfirm"
            class="rounded-md border border-destructive/20 bg-destructive/5 p-4"
          >
            <p class="text-sm font-medium text-destructive mb-3">Remove your avatar? This cannot be undone.</p>
            <div class="flex gap-3">
              <button
                type="button"
                @click="showDeleteConfirm = false"
                class="rounded-md border px-4 py-2 text-sm font-medium hover:bg-accent transition-colors"
              >
                Cancel
              </button>
              <button
                type="button"
                @click="deleteAvatar"
                class="rounded-md bg-destructive px-4 py-2 text-sm font-medium text-destructive-foreground hover:bg-destructive/90 transition-colors"
              >
                Delete
              </button>
            </div>
          </div>
        </Transition>
      </div>

      <!-- Panel 4: Two-factor authentication -->
      <div class="rounded-lg border bg-card p-6 space-y-5">
        <div class="flex items-start justify-between gap-4">
          <div>
            <h3 class="text-sm font-semibold">Two-factor authentication</h3>
            <p class="text-xs text-muted-foreground mt-0.5">
              Add an extra layer of security with an authenticator app.
            </p>
          </div>
          <span
            class="shrink-0 rounded-full px-2.5 py-0.5 text-xs font-medium"
            :class="twoFactorStatus === 'enabled'
              ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
              : 'bg-muted text-muted-foreground'"
          >
            {{ twoFactorStatus === 'enabled' ? 'Enabled' : 'Disabled' }}
          </span>
        </div>

        <!-- State: disabled — prompt to enable -->
        <template v-if="twoFactorStatus === 'disabled'">
          <p class="text-xs text-muted-foreground">
            When enabled, you will be asked for a code from your authenticator app each time you sign in.
          </p>
          <button
            type="button"
            :disabled="tfaLoading"
            @click="startSetup"
            class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)] disabled:opacity-50"
          >
            {{ tfaLoading ? 'Preparing…' : 'Enable 2FA' }}
          </button>
        </template>

        <!-- State: setup — show QR code + confirm -->
        <template v-if="twoFactorStatus === 'setup'">
          <div class="space-y-4">
            <p class="text-xs text-muted-foreground">
              Scan the QR code below with your authenticator app (Google Authenticator, Authy, 1Password, etc.), then enter the 6-digit code to confirm.
            </p>

            <!-- QR code -->
            <div class="flex flex-col items-center gap-3">
              <div class="rounded-lg border p-3 bg-white inline-block">
                <img v-if="qrDataUrl" :src="qrDataUrl" alt="2FA QR Code" class="w-40 h-40" />
                <div v-else class="w-40 h-40 flex items-center justify-center">
                  <svg class="w-6 h-6 animate-spin text-muted-foreground" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                  </svg>
                </div>
              </div>

              <!-- Manual key -->
              <div class="text-center">
                <p class="text-xs text-muted-foreground mb-1">Or enter this key manually:</p>
                <code class="text-xs font-mono bg-muted px-2 py-1 rounded tracking-widest select-all">
                  {{ tfaSetupSecret }}
                </code>
              </div>
            </div>

            <!-- Confirm input -->
            <div class="space-y-1">
              <label class="text-sm font-medium">Confirmation code</label>
              <input
                v-model="tfaConfirmCode"
                type="text"
                inputmode="numeric"
                maxlength="6"
                placeholder="000000"
                class="w-full rounded-md border bg-background px-3 py-2 text-sm tracking-widest text-center focus:outline-none focus:ring-2 focus:ring-ring"
                :class="{ 'border-destructive': tfaConfirmError }"
                @keydown.enter="confirmSetup"
              />
              <p v-if="tfaConfirmError" class="text-xs text-destructive">{{ tfaConfirmError }}</p>
            </div>

            <div class="flex gap-2">
              <button
                type="button"
                @click="cancelSetup"
                class="rounded-md border px-4 py-2 text-sm hover:bg-accent transition-colors"
              >
                Cancel
              </button>
              <button
                type="button"
                :disabled="tfaLoading || tfaConfirmCode.length < 6"
                @click="confirmSetup"
                class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)] disabled:opacity-50"
              >
                {{ tfaLoading ? 'Verifying…' : 'Confirm & enable' }}
              </button>
            </div>
          </div>
        </template>

        <!-- State: confirmed — show recovery codes after fresh enable -->
        <template v-if="twoFactorStatus === 'confirmed'">
          <div class="rounded-md border border-amber-200 bg-amber-50 dark:border-amber-800/40 dark:bg-amber-900/10 p-4 space-y-3">
            <p class="text-sm font-medium text-amber-800 dark:text-amber-400">
              Save your recovery codes
            </p>
            <p class="text-xs text-amber-700 dark:text-amber-500">
              Store these codes somewhere safe. Each code can only be used once to sign in if you lose access to your authenticator app.
            </p>
            <div class="grid grid-cols-2 gap-1.5">
              <code
                v-for="c in recoveryCodes"
                :key="c"
                class="rounded bg-white dark:bg-background border px-2 py-1 text-xs font-mono tracking-widest text-center select-all"
              >
                {{ c }}
              </code>
            </div>
            <button
              type="button"
              @click="twoFactorStatus = 'enabled'"
              class="w-full rounded-md border px-4 py-2 text-sm font-medium hover:bg-accent transition-colors"
            >
              I've saved my codes
            </button>
          </div>
        </template>

        <!-- State: enabled — management options -->
        <template v-if="twoFactorStatus === 'enabled'">
          <div class="space-y-4">
            <!-- Recovery codes section -->
            <div>
              <div class="flex items-center justify-between mb-2">
                <p class="text-xs font-medium">Recovery codes</p>
                <button
                  type="button"
                  :disabled="tfaLoading"
                  @click="showRecoveryCodes ? showRecoveryCodes = false : loadRecoveryCodes()"
                  class="text-xs text-primary hover:underline underline-offset-2"
                >
                  {{ showRecoveryCodes ? 'Hide' : 'View codes' }}
                </button>
              </div>

              <Transition name="fade">
                <div v-if="showRecoveryCodes" class="space-y-3">
                  <div class="grid grid-cols-2 gap-1.5">
                    <code
                      v-for="c in recoveryCodes"
                      :key="c"
                      class="rounded border bg-muted px-2 py-1 text-xs font-mono tracking-widest text-center select-all"
                    >
                      {{ c }}
                    </code>
                  </div>
                  <button
                    type="button"
                    :disabled="tfaLoading"
                    @click="regenerateCodes"
                    class="text-xs text-muted-foreground hover:text-foreground underline-offset-2 hover:underline transition-colors"
                  >
                    {{ tfaLoading ? 'Regenerating…' : 'Regenerate codes' }}
                  </button>
                </div>
              </Transition>
            </div>

            <!-- Disable -->
            <div v-if="!showDisableConfirm">
              <button
                type="button"
                @click="showDisableConfirm = true"
                class="rounded-md border border-destructive/40 px-4 py-2 text-sm text-destructive hover:bg-destructive/10 transition-colors"
              >
                Disable 2FA
              </button>
            </div>

            <Transition name="fade">
              <div
                v-if="showDisableConfirm"
                class="rounded-md border border-destructive/20 bg-destructive/5 p-4 space-y-3"
              >
                <p class="text-sm font-medium text-destructive">Disable two-factor authentication?</p>
                <p class="text-xs text-muted-foreground">This will remove the extra security layer from your account.</p>
                <div class="flex gap-2">
                  <button
                    type="button"
                    @click="showDisableConfirm = false"
                    class="rounded-md border px-4 py-2 text-sm hover:bg-accent transition-colors"
                  >
                    Cancel
                  </button>
                  <button
                    type="button"
                    :disabled="tfaLoading"
                    @click="disableTfa"
                    class="rounded-md bg-destructive px-4 py-2 text-sm font-medium text-destructive-foreground hover:bg-destructive/90 disabled:opacity-50 transition-colors"
                  >
                    {{ tfaLoading ? 'Disabling…' : 'Yes, disable' }}
                  </button>
                </div>
              </div>
            </Transition>
          </div>
        </template>
      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from "vue";
import { Head, useForm, usePage, router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import PageHeader from '@/Components/PageHeader.vue'
import { useNotifications } from '@/composables/useNotifications.js'
import axios from 'axios'
import QRCode from 'qrcode'
const { notify } = useNotifications()

const page = usePage();
const user = computed(() => page.props.auth.user ?? { name: "", email: "", avatar_url: null });

// ── Panel 1: Profile info ──────────────────────────────────────────────────
const infoForm = useForm({
  name:  user.value.name  ?? "",
  email: user.value.email ?? "",
});

function submitInfo() {
  infoForm.post(route("profile.info"), {
    preserveScroll: true,
    onError: (errors) => notify('Please fix the following:', 'error', { items: Object.values(errors) }),
  });
}

// ── Panel 2: Password ──────────────────────────────────────────────────────
const passwordForm = useForm({
  current_password:      "",
  password:              "",
  password_confirmation: "",
});

function submitPassword() {
  passwordForm.post(route("profile.password"), {
    preserveScroll: true,
    onSuccess: () => passwordForm.reset(),
    onError: (errors) => notify('Please fix the following:', 'error', { items: Object.values(errors) }),
  });
}

// ── Panel 3: Avatar ────────────────────────────────────────────────────────
const avatarInput       = ref(null);
const avatarPreview     = ref(null);
const showDeleteConfirm = ref(false);
const avatarForm        = useForm({ avatar: null });

function triggerAvatarInput() {
  avatarInput.value.click();
}

function onFileSelected(event) {
  const file = event.target.files[0];
  if (!file) return;

  avatarForm.avatar = file;

  const reader = new FileReader();
  reader.onload = (e) => { avatarPreview.value = e.target.result; };
  reader.readAsDataURL(file);
}

function submitAvatar() {
  avatarForm.post(route("profile.avatar"), {
    preserveScroll: true,
    forceFormData:  true,
    onSuccess: () => {
      avatarPreview.value = null;
      avatarForm.reset();
    },
    onError: (errors) => notify('Please fix the following:', 'error', { items: Object.values(errors) }),
  });
}

function deleteAvatar() {
  showDeleteConfirm.value = false;
  router.delete(route("profile.avatar.delete"), { preserveScroll: true });
}

// ── Panel 4: Two-factor authentication ────────────────────────────────────
// twoFactorStatus: 'disabled' | 'setup' | 'confirmed' | 'enabled'
const twoFactorStatus   = ref(user.value.two_factor_enabled ? 'enabled' : 'disabled')
const tfaLoading        = ref(false)
const tfaSetupSecret    = ref('')
const tfaConfirmCode    = ref('')
const tfaConfirmError   = ref('')
const qrDataUrl         = ref('')
const recoveryCodes     = ref([])
const showRecoveryCodes = ref(false)
const showDisableConfirm = ref(false)

async function startSetup() {
  tfaLoading.value = true
  try {
    const { data } = await axios.post(route('profile.two-factor.enable'))
    tfaSetupSecret.value = data.secret
    qrDataUrl.value = await QRCode.toDataURL(data.qr_uri, { width: 160, margin: 1 })
    twoFactorStatus.value = 'setup'
    tfaConfirmCode.value  = ''
    tfaConfirmError.value = ''
  } catch {
    notify('Could not start 2FA setup.', 'error')
  } finally {
    tfaLoading.value = false
  }
}

function cancelSetup() {
  twoFactorStatus.value = 'disabled'
  tfaSetupSecret.value  = ''
  qrDataUrl.value       = ''
  tfaConfirmCode.value  = ''
  tfaConfirmError.value = ''
  // Clean up the pending secret server-side
  axios.delete(route('profile.two-factor.disable')).catch(() => {})
}

async function confirmSetup() {
  if (tfaLoading.value || tfaConfirmCode.value.length < 6) return
  tfaLoading.value    = true
  tfaConfirmError.value = ''
  try {
    const { data } = await axios.post(route('profile.two-factor.confirm'), {
      code: tfaConfirmCode.value,
    })
    recoveryCodes.value   = data.recovery_codes
    twoFactorStatus.value = 'confirmed'
    notify('Two-factor authentication enabled.', 'success')
  } catch (err) {
    tfaConfirmError.value = err.response?.data?.message ?? 'Invalid code.'
    tfaConfirmCode.value  = ''
  } finally {
    tfaLoading.value = false
  }
}

async function disableTfa() {
  tfaLoading.value = true
  try {
    await axios.delete(route('profile.two-factor.disable'))
    twoFactorStatus.value  = 'disabled'
    showDisableConfirm.value = false
    recoveryCodes.value    = []
    showRecoveryCodes.value = false
    notify('Two-factor authentication disabled.', 'success')
  } catch {
    notify('Could not disable 2FA.', 'error')
  } finally {
    tfaLoading.value = false
  }
}

async function loadRecoveryCodes() {
  tfaLoading.value = true
  try {
    const { data } = await axios.get(route('profile.two-factor.recovery-codes'))
    recoveryCodes.value     = data.recovery_codes
    showRecoveryCodes.value = true
  } catch {
    notify('Could not load recovery codes.', 'error')
  } finally {
    tfaLoading.value = false
  }
}

async function regenerateCodes() {
  tfaLoading.value = true
  try {
    const { data } = await axios.post(route('profile.two-factor.regenerate-recovery-codes'))
    recoveryCodes.value = data.recovery_codes
    notify('Recovery codes regenerated. Save the new codes.', 'success')
  } catch {
    notify('Could not regenerate codes.', 'error')
  } finally {
    tfaLoading.value = false
  }
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
