import os

# ─── Auth/VerifyEmail.vue ────────────────────────────────────────────────────
verify_email = r"""<template>
  <div class="min-h-screen flex items-center justify-center bg-background px-4">
    <div class="w-full max-w-md">
      <!-- Logo -->
      <div class="flex items-center justify-center gap-2 mb-8">
        <div class="w-8 h-8 rounded-md bg-primary flex items-center justify-center">
          <svg class="w-4 h-4 text-primary-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3L2 7l10 4 10-4-10-4zM2 17l10 4 10-4M2 12l10 4 10-4" />
          </svg>
        </div>
        <span class="font-semibold tracking-tight">Lambda CMS</span>
      </div>

      <div class="rounded-lg border bg-card p-8 space-y-5">
        <!-- Icon -->
        <div class="flex items-center justify-center">
          <div class="w-14 h-14 rounded-full bg-primary/10 flex items-center justify-center">
            <svg class="w-7 h-7 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
            </svg>
          </div>
        </div>

        <div class="text-center">
          <h1 class="text-lg font-semibold">Verify your email address</h1>
          <p class="text-sm text-muted-foreground mt-1">
            Thanks for signing up! Before you can access Lambda CMS, please verify your email address by clicking the link we sent you.
          </p>
        </div>

        <!-- Success banner -->
        <Transition name="fade">
          <div
            v-if="$page.props.flash?.status === 'verification-link-sent'"
            class="flex items-center gap-2 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700"
          >
            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            A new verification link has been sent to your email address.
          </div>
        </Transition>

        <!-- Resend button -->
        <form @submit.prevent="resend">
          <button
            type="submit"
            :disabled="processing"
            class="w-full rounded-md bg-primary px-4 py-2.5 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 disabled:opacity-50"
          >
            {{ processing ? 'Sending...' : 'Resend verification email' }}
          </button>
        </form>

        <div class="text-center">
          <button
            type="button"
            @click="logout"
            class="text-sm text-muted-foreground hover:text-foreground transition-colors"
          >
            Sign out
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from "vue";
import { router } from "@inertiajs/vue3";

defineOptions({ layout: null });

const processing = ref(false);

function resend() {
  processing.value = true;
  router.post(route("verification.send"), {}, {
    onFinish: () => { processing.value = false; },
  });
}

function logout() {
  router.post(route("logout"));
}
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
"""

# ─── Users/Index.vue ─────────────────────────────────────────────────────────
users_index = r"""<template>
  <AppLayout title="Users">
    <Head title="Users" />

    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-lg font-semibold">Users</h2>
        <p class="text-sm text-muted-foreground mt-0.5">Manage who has access to Lambda CMS.</p>
      </div>
      <a
        :href="route('users.create')"
        class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        Invite user
      </a>
    </div>

    <!-- Flash -->
    <Transition name="fade">
      <div
        v-if="$page.props.flash?.status"
        class="flex items-center gap-2 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700 mb-4"
      >
        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        {{ $page.props.flash.status }}
      </div>
    </Transition>

    <!-- Table -->
    <div class="rounded-lg border bg-card overflow-hidden">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b bg-muted/40">
            <th class="text-left px-4 py-3 font-medium text-muted-foreground">User</th>
            <th class="text-left px-4 py-3 font-medium text-muted-foreground">Role</th>
            <th class="text-left px-4 py-3 font-medium text-muted-foreground">Verified</th>
            <th class="text-left px-4 py-3 font-medium text-muted-foreground">Last seen</th>
            <th class="px-4 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y">
          <tr v-for="user in users.data" :key="user.id" class="hover:bg-muted/20 transition-colors">
            <!-- User -->
            <td class="px-4 py-3">
              <div class="flex items-center gap-3">
                <!-- Avatar / initials -->
                <div class="relative shrink-0">
                  <div class="w-8 h-8 rounded-full overflow-hidden bg-muted flex items-center justify-center text-xs font-semibold uppercase">
                    <img v-if="user.avatar_url" :src="user.avatar_url" :alt="user.name" class="w-full h-full object-cover" />
                    <span v-else>{{ initials(user.name) }}</span>
                  </div>
                  <!-- Online indicator -->
                  <span
                    v-if="user.is_online"
                    class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-green-500 ring-2 ring-card"
                  ></span>
                </div>
                <div>
                  <p class="font-medium">{{ user.name }}</p>
                  <p class="text-xs text-muted-foreground">{{ user.email }}</p>
                </div>
              </div>
            </td>
            <!-- Role -->
            <td class="px-4 py-3">
              <span
                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                :class="user.role === 'administrator'
                  ? 'bg-indigo-100 text-indigo-700'
                  : 'bg-slate-100 text-slate-600'"
              >
                {{ user.role === 'administrator' ? 'Administrator' : 'User' }}
              </span>
            </td>
            <!-- Verified -->
            <td class="px-4 py-3">
              <span v-if="user.email_verified" class="text-green-600">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
              </span>
              <span v-else class="text-muted-foreground text-xs">Pending</span>
            </td>
            <!-- Last seen -->
            <td class="px-4 py-3 text-muted-foreground text-xs">
              {{ user.last_seen_at ?? 'Never' }}
            </td>
            <!-- Actions -->
            <td class="px-4 py-3">
              <div class="flex items-center justify-end gap-2">
                <a
                  :href="route('users.edit', user.id)"
                  class="rounded-md p-1.5 text-muted-foreground hover:bg-accent hover:text-foreground transition-colors"
                  title="Edit"
                >
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                  </svg>
                </a>
                <button
                  v-if="user.id !== currentUserId"
                  type="button"
                  @click="confirmDelete(user)"
                  class="rounded-md p-1.5 text-muted-foreground hover:bg-destructive/10 hover:text-destructive transition-colors"
                  title="Delete"
                >
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                  </svg>
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="users.data.length === 0">
            <td colspan="5" class="px-4 py-10 text-center text-sm text-muted-foreground">No users found.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="users.last_page > 1" class="flex justify-end gap-1 mt-4">
      <a
        v-for="link in users.links"
        :key="link.label"
        :href="link.url ?? '#'"
        v-html="link.label"
        class="px-3 py-1.5 rounded-md text-sm border transition-colors"
        :class="link.active
          ? 'bg-primary text-primary-foreground border-primary'
          : link.url
            ? 'hover:bg-accent text-foreground border-border'
            : 'text-muted-foreground border-border cursor-default pointer-events-none'"
      />
    </div>

    <!-- Delete confirmation modal -->
    <Transition name="fade">
      <div v-if="deleteTarget" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm">
        <div class="bg-card rounded-lg border shadow-lg max-w-sm w-full p-6 space-y-4">
          <h3 class="text-sm font-semibold">Delete user?</h3>
          <p class="text-sm text-muted-foreground">
            <strong>{{ deleteTarget.name }}</strong> ({{ deleteTarget.email }}) will be permanently removed. This action cannot be undone.
          </p>
          <div class="flex justify-end gap-3">
            <button
              type="button"
              @click="deleteTarget = null"
              class="rounded-md border px-4 py-2 text-sm font-medium hover:bg-accent transition-colors"
            >
              Cancel
            </button>
            <button
              type="button"
              @click="deleteUser"
              :disabled="deleting"
              class="rounded-md bg-destructive px-4 py-2 text-sm font-medium text-destructive-foreground hover:bg-destructive/90 disabled:opacity-50 transition-colors"
            >
              {{ deleting ? 'Deleting...' : 'Delete' }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from "vue";
import { Head, router, usePage } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";

defineProps({
  users: { type: Object, required: true },
});

const page          = usePage();
const currentUserId = computed(() => page.props.auth.user?.id);

const deleteTarget = ref(null);
const deleting     = ref(false);

function initials(name) {
  return name.split(" ").map(n => n[0]).slice(0, 2).join("").toUpperCase();
}

function confirmDelete(user) {
  deleteTarget.value = user;
}

function deleteUser() {
  if (!deleteTarget.value) return;
  deleting.value = true;
  router.delete(route("users.destroy", deleteTarget.value.id), {
    onFinish: () => {
      deleting.value = false;
      deleteTarget.value = null;
    },
  });
}
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
"""

# ─── Users/Form.vue ──────────────────────────────────────────────────────────
users_form = r"""<template>
  <AppLayout :title="isEditing ? 'Edit User' : 'Invite User'">
    <Head :title="isEditing ? 'Edit User' : 'Invite User'" />

    <form @submit.prevent="submit" class="max-w-xl">
      <div class="flex items-center gap-3 mb-6">
        <a
          :href="route('users.index')"
          class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-accent hover:text-accent-foreground transition-colors"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
          </svg>
        </a>
        <div>
          <h2 class="text-lg font-semibold">{{ isEditing ? 'Edit user' : 'Invite user' }}</h2>
          <p class="text-sm text-muted-foreground mt-0.5">
            {{ isEditing ? user.name : 'Create a new account and send a welcome email' }}
          </p>
        </div>
      </div>

      <div class="rounded-lg border bg-card p-6 space-y-4">

        <!-- Info banner -->
        <div class="flex items-start gap-3 rounded-md bg-muted/50 border px-4 py-3 text-sm text-muted-foreground">
          <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <span v-if="isEditing">
            The user manages their own password. You can update their name, email and role here.
          </span>
          <span v-else>
            A welcome email with an email verification link and password setup link will be sent automatically.
          </span>
        </div>

        <!-- Name -->
        <div class="space-y-1">
          <label for="name" class="text-sm font-medium">Name <span class="text-destructive">*</span></label>
          <input
            id="name"
            v-model="form.name"
            type="text"
            placeholder="Jane Doe"
            autofocus
            class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
            :class="{ 'border-destructive': form.errors.name }"
          />
          <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
        </div>

        <!-- Email -->
        <div class="space-y-1">
          <label for="email" class="text-sm font-medium">Email address <span class="text-destructive">*</span></label>
          <input
            id="email"
            v-model="form.email"
            type="email"
            placeholder="jane@example.com"
            class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
            :class="{ 'border-destructive': form.errors.email }"
          />
          <p v-if="form.errors.email" class="text-xs text-destructive">{{ form.errors.email }}</p>
        </div>

        <!-- Role -->
        <div class="space-y-1">
          <label for="role" class="text-sm font-medium">Role <span class="text-destructive">*</span></label>
          <select
            id="role"
            v-model="form.role"
            class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
            :class="{ 'border-destructive': form.errors.role }"
          >
            <option value="">— Select a role —</option>
            <option v-for="r in roles" :key="r" :value="r">
              {{ r === 'administrator' ? 'Administrator' : 'User' }}
            </option>
          </select>
          <p v-if="form.errors.role" class="text-xs text-destructive">{{ form.errors.role }}</p>
          <p class="text-xs text-muted-foreground">
            <strong>Administrator</strong> — full access.
            <strong>User</strong> — can manage posts, categories and tags.
          </p>
        </div>
      </div>

      <div class="flex gap-3 mt-4 justify-end">
        <a
          :href="route('users.index')"
          class="rounded-md border px-4 py-2 text-sm font-medium hover:bg-accent transition-colors"
        >
          Cancel
        </a>
        <button
          type="submit"
          :disabled="form.processing"
          class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 disabled:opacity-50"
        >
          {{ form.processing ? 'Saving...' : isEditing ? 'Save changes' : 'Send invitation' }}
        </button>
      </div>
    </form>
  </AppLayout>
</template>

<script setup>
import { computed } from "vue";
import { Head, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";

const props = defineProps({
  user:  { type: Object, default: null },
  roles: { type: Array,  default: () => [] },
});

const isEditing = computed(() => !!props.user);

const form = useForm({
  name:  props.user?.name  ?? "",
  email: props.user?.email ?? "",
  role:  props.user?.role  ?? "",
});

function submit() {
  if (isEditing.value) {
    form.put(route("users.update", props.user.id));
  } else {
    form.post(route("users.store"));
  }
}
</script>
"""

os.makedirs(r"C:\Users\mariu\Herd\lambda-cms\resources\js\Pages\Auth",  exist_ok=True)
os.makedirs(r"C:\Users\mariu\Herd\lambda-cms\resources\js\Pages\Users", exist_ok=True)

with open(r"C:\Users\mariu\Herd\lambda-cms\resources\js\Pages\Auth\VerifyEmail.vue",  "w", encoding="utf-8", newline="\n") as f:
    f.write(verify_email)

with open(r"C:\Users\mariu\Herd\lambda-cms\resources\js\Pages\Users\Index.vue", "w", encoding="utf-8", newline="\n") as f:
    f.write(users_index)

with open(r"C:\Users\mariu\Herd\lambda-cms\resources\js\Pages\Users\Form.vue", "w", encoding="utf-8", newline="\n") as f:
    f.write(users_form)

print("All 3 Vue files written successfully.")
