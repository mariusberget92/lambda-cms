<template>
  <div>
    <div class="mb-8 text-center">
      <h1 class="text-2xl font-bold tracking-tight">Set a new password</h1>
      <p class="text-muted-foreground text-sm mt-1">Choose a strong password for your account.</p>
    </div>

    <form @submit.prevent="submit" class="space-y-4">
      <input type="hidden" v-model="form.token" />

      <div class="space-y-1">
        <label for="email" class="text-sm font-medium">Email</label>
        <input
          id="email"
          v-model="form.email"
          type="email"
          autocomplete="email"
          required
          class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring disabled:opacity-50"
          :class="{ 'border-destructive': form.errors.email }"
        />
        <p v-if="form.errors.email" class="text-xs text-destructive">{{ form.errors.email }}</p>
      </div>

      <div class="space-y-1">
        <label for="password" class="text-sm font-medium">New password</label>
        <input
          id="password"
          v-model="form.password"
          type="password"
          autocomplete="new-password"
          required
          class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring disabled:opacity-50"
          :class="{ 'border-destructive': form.errors.password }"
        />
        <p v-if="form.errors.password" class="text-xs text-destructive">{{ form.errors.password }}</p>
      </div>

      <div class="space-y-1">
        <label for="password_confirmation" class="text-sm font-medium">Confirm password</label>
        <input
          id="password_confirmation"
          v-model="form.password_confirmation"
          type="password"
          autocomplete="new-password"
          required
          class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring disabled:opacity-50"
          :class="{ 'border-destructive': form.errors.password_confirmation }"
        />
        <p v-if="form.errors.password_confirmation" class="text-xs text-destructive mt-1">{{ form.errors.password_confirmation }}</p>
      </div>

      <button
        type="submit"
        :disabled="form.processing"
        class="w-full rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)] disabled:opacity-50"
      >
        <span v-if="form.processing">Resetting...</span>
        <span v-else>Reset password</span>
      </button>
    </form>
  </div>
</template>

<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue'
import { useForm } from "@inertiajs/vue3";

defineOptions({ layout: AuthLayout });

const props = defineProps({
  token: String,
  email: String,
});

const form = useForm({
  token: props.token,
  email: props.email ?? "",
  password: "",
  password_confirmation: "",
});

function submit() {
  form.post(route("password.update"), {
    onFinish: () => form.reset("password", "password_confirmation"),
  });
}
</script>
