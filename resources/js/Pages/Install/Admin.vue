<script setup>
import { useForm } from '@inertiajs/vue3'
import InstallLayout from '@/Layouts/InstallLayout.vue'
import { useNotifications } from '@/composables/useNotifications.js'

defineOptions({ layout: InstallLayout })

defineProps({
  step: Number,
})

const { notify } = useNotifications()

const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
})

function submit() {
  form.post('/install/admin', {
    onError: (errors) => notify('Please fix the following:', 'error', { items: Object.values(errors) }),
  })
}
</script>

<template>
  <div>
    <h2 class="text-lg font-semibold mb-1">Admin Account</h2>
    <p class="text-sm text-muted-foreground mb-6">Create your administrator account.</p>

    <form @submit.prevent="submit" class="space-y-4">
      <div>
        <label class="block text-sm font-medium mb-1.5">Full Name</label>
        <input
          v-model="form.name"
          type="text"
          placeholder="John Doe"
          autocomplete="name"
          class="w-full border rounded-md px-3 py-2 text-sm bg-background focus:outline-none focus:ring-2 focus:ring-primary/50"
          :class="{ 'border-destructive': form.errors.name }"
        />
      </div>

      <div>
        <label class="block text-sm font-medium mb-1.5">Email Address</label>
        <input
          v-model="form.email"
          type="email"
          placeholder="admin@example.com"
          autocomplete="email"
          class="w-full border rounded-md px-3 py-2 text-sm bg-background focus:outline-none focus:ring-2 focus:ring-primary/50"
          :class="{ 'border-destructive': form.errors.email }"
        />
      </div>

      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-sm font-medium mb-1.5">Password</label>
          <input
            v-model="form.password"
            type="password"
            autocomplete="new-password"
            class="w-full border rounded-md px-3 py-2 text-sm bg-background focus:outline-none focus:ring-2 focus:ring-primary/50"
            :class="{ 'border-destructive': form.errors.password }"
          />
        </div>
        <div>
          <label class="block text-sm font-medium mb-1.5">Confirm Password</label>
          <input
            v-model="form.password_confirmation"
            type="password"
            autocomplete="new-password"
            class="w-full border rounded-md px-3 py-2 text-sm bg-background focus:outline-none focus:ring-2 focus:ring-primary/50"
          />
        </div>
      </div>

      <div class="flex items-center justify-between pt-2">
        <a href="/install/site" class="text-sm text-muted-foreground hover:text-foreground transition-colors">
          ← Back
        </a>
        <button
          type="submit"
          :disabled="form.processing"
          class="inline-flex items-center gap-2 bg-primary text-primary-foreground text-sm font-medium px-5 py-2 rounded-md hover:bg-[var(--primary-hover)] transition-colors disabled:opacity-50"
        >
          <span>Next</span>
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
          </svg>
        </button>
      </div>
    </form>
  </div>
</template>
