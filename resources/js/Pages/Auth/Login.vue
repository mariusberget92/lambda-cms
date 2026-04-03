<template>
  <div>
    <div class="mb-8 text-center">
      <h1 class="text-2xl font-bold tracking-tight">Lambda CMS</h1>
      <p class="text-muted-foreground text-sm mt-1">Sign in to your account</p>
    </div>

    <form @submit.prevent="submit" class="space-y-4">
      <div class="space-y-1">
        <label for="email" class="text-sm font-medium">Email</label>
        <input
          id="email"
          v-model="form.email"
          type="email"
          autocomplete="email"
          required
          class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring disabled:opacity-50"
          placeholder="you@example.com"
        />
      </div>

      <div class="space-y-1">
        <div class="flex items-center justify-between">
          <label for="password" class="text-sm font-medium">Password</label>
          <a :href="route('password.request')" class="text-xs text-muted-foreground hover:text-foreground underline-offset-4 hover:underline">
            Forgot password?
          </a>
        </div>
        <input
          id="password"
          v-model="form.password"
          type="password"
          autocomplete="current-password"
          required
          class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring disabled:opacity-50"
        />
      </div>

      <div class="flex items-center gap-2">
        <input id="remember" v-model="form.remember" type="checkbox" class="rounded border accent-nord-green" />
        <label for="remember" class="text-sm">Remember me</label>
      </div>

      <button
        type="submit"
        :disabled="form.processing"
        class="w-full rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)] disabled:opacity-50"
      >
        <span v-if="form.processing">Signing in...</span>
        <span v-else>Sign in</span>
      </button>
    </form>
  </div>
</template>

<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue'
import { useForm } from "@inertiajs/vue3";
import { useNotifications } from '@/composables/useNotifications'

defineOptions({ layout: AuthLayout });

const { notify } = useNotifications()

const form = useForm({
  email: "",
  password: "",
  remember: false,
});

function submit() {
  form.post(route("auth.login"), {
    onFinish: () => form.reset("password"),
    onError: () => notify('Invalid credentials. Please try again.', 'error'),
  });
}
</script>
