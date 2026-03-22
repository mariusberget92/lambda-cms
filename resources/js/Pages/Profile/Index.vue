<template>
  <AppLayout title="Profile">
    <Head title="Profile" />

    <div class="max-w-2xl space-y-6">

      <!-- Page header -->
      <div>
        <h2 class="text-lg font-semibold">Profile settings</h2>
        <p class="text-sm text-muted-foreground mt-0.5">Manage your account information and security.</p>
      </div>

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
            <p v-if="infoForm.errors.name" class="text-xs text-destructive">{{ infoForm.errors.name }}</p>
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
            <p v-if="infoForm.errors.email" class="text-xs text-destructive">{{ infoForm.errors.email }}</p>
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
            <p v-if="passwordForm.errors.current_password" class="text-xs text-destructive">{{ passwordForm.errors.current_password }}</p>
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
            <p v-if="passwordForm.errors.password" class="text-xs text-destructive">{{ passwordForm.errors.password }}</p>
          </div>

          <div class="space-y-1">
            <label for="password_confirmation" class="text-sm font-medium">Confirm new password</label>
            <input
              id="password_confirmation"
              v-model="passwordForm.password_confirmation"
              type="password"
              autocomplete="new-password"
              class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
            />
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
            <p v-if="avatarForm.errors.avatar" class="text-xs text-destructive">{{ avatarForm.errors.avatar }}</p>
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

    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from "vue";
import { Head, useForm, usePage, router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";

const page = usePage();
const user = computed(() => page.props.auth.user ?? { name: "", email: "", avatar_url: null });

// ── Panel 1: Profile info ──────────────────────────────────────────────────
const infoForm = useForm({
  name:  user.value.name  ?? "",
  email: user.value.email ?? "",
});

function submitInfo() {
  infoForm.post(route("profile.info"), { preserveScroll: true });
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
  });
}

function deleteAvatar() {
  showDeleteConfirm.value = false;
  router.delete(route("profile.avatar.delete"), { preserveScroll: true });
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
