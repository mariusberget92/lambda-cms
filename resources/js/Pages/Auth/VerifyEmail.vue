<template>
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

        <!-- Resend button -->
        <form @submit.prevent="resend">
          <button
            type="submit"
            :disabled="processing"
            class="w-full rounded-md bg-primary px-4 py-2.5 text-sm font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)] disabled:opacity-50"
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

