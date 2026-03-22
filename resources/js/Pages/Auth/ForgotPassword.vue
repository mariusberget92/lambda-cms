<template>
  <div>
    <div class="mb-8 text-center">
      <h1 class="text-2xl font-bold tracking-tight">Reset your password</h1>
      <p class="text-muted-foreground text-sm mt-1">
        Enter your email and we will send you a reset link.
      </p>
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
          :class="{ 'border-destructive': form.errors.email }"
          placeholder="you@example.com"
        />
        <p v-if="form.errors.email" class="text-xs text-destructive">{{ form.errors.email }}</p>
      </div>

      <button
        type="submit"
        :disabled="form.processing"
        class="w-full rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)] disabled:opacity-50"
      >
        <span v-if="form.processing">Sending...</span>
        <span v-else>Send reset link</span>
      </button>

      <p class="text-center text-sm text-muted-foreground">
        Remember your password?
        <a :href="route('login')" class="underline underline-offset-4 hover:text-foreground">Sign in</a>
      </p>
    </form>
  </div>
</template>

<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue'
import { useForm } from "@inertiajs/vue3";

defineOptions({ layout: AuthLayout });

const form = useForm({
  email: "",
});

function submit() {
  form.post(route("password.email"));
}
</script>
