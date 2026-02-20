<template>
  <div class="min-h-screen bg-background flex items-center justify-center">
    <div class="w-full max-w-sm">
      <div class="mb-8 text-center">
        <h1 class="text-2xl font-bold tracking-tight">Lambda CMS</h1>
        <p class="text-muted-foreground text-sm mt-1">Sign in to your account</p>
      </div>

      <form @submit.prevent="submit" class="space-y-4">
        <div v-if="$page.props.flash?.status" class="text-sm text-green-600 bg-green-50 border border-green-200 rounded-md px-4 py-3">
          {{ $page.props.flash.status }}
        </div>

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
            :class="{ 'border-destructive': form.errors.password }"
          />
          <p v-if="form.errors.password" class="text-xs text-destructive">{{ form.errors.password }}</p>
        </div>

        <div class="flex items-center gap-2">
          <input id="remember" v-model="form.remember" type="checkbox" class="rounded border" />
          <label for="remember" class="text-sm">Remember me</label>
        </div>

        <button
          type="submit"
          :disabled="form.processing"
          class="w-full rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 disabled:opacity-50"
        >
          <span v-if="form.processing">Signing in...</span>
          <span v-else>Sign in</span>
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { useForm } from "@inertiajs/vue3";

defineOptions({ layout: null });

const form = useForm({
  email: "",
  password: "",
  remember: false,
});

function submit() {
  form.post(route("auth.login"), {
    onFinish: () => form.reset("password"),
  });
}
</script>
