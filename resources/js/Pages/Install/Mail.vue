<script setup>
import { useForm, router } from '@inertiajs/vue3'
import InstallLayout from '@/Layouts/InstallLayout.vue'
import { useNotifications } from '@/composables/useNotifications.js'
import NumberInput from '@/Components/NumberInput.vue'

defineOptions({ layout: InstallLayout })

defineProps({
  step: Number,
})

const { notify } = useNotifications()

const form = useForm({
  mailer: 'log',
  host: 'smtp.example.com',
  port: 587,
  username: '',
  password: '',
  from_address: '',
  from_name: '',
})

function submit() {
  form.post('/install/mail', {
    onError: (errors) => notify('Please fix the following:', 'error', { items: Object.values(errors) }),
  })
}
</script>

<template>
  <div>
    <h2 class="text-lg font-semibold mb-1">Mail Configuration</h2>
    <p class="text-sm text-muted-foreground mb-6">Configure how emails are sent. You can change this later in your <code class="text-xs font-mono bg-muted px-1 py-0.5 rounded">.env</code> file.</p>

    <form @submit.prevent="submit" class="space-y-4">
      <!-- Mailer driver -->
      <div>
        <label class="block text-sm font-medium mb-1.5">Mail Driver</label>
        <div class="flex gap-3">
          <label class="flex items-center gap-2 cursor-pointer">
            <input v-model="form.mailer" type="radio" value="log" class="accent-primary" />
            <span class="text-sm">Log (development)</span>
          </label>
          <label class="flex items-center gap-2 cursor-pointer">
            <input v-model="form.mailer" type="radio" value="smtp" class="accent-primary" />
            <span class="text-sm">SMTP</span>
          </label>
        </div>
      </div>

      <!-- Log notice -->
      <div v-if="form.mailer === 'log'" class="rounded-lg border bg-muted/40 p-4 text-sm text-muted-foreground">
        Emails will be written to <code class="font-mono text-xs bg-muted px-1 py-0.5 rounded">storage/logs/laravel.log</code> instead of being sent. Useful for local development.
      </div>

      <!-- SMTP fields -->
      <template v-if="form.mailer === 'smtp'">
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-sm font-medium mb-1.5">SMTP Host</label>
            <input
              v-model="form.host"
              type="text"
              class="w-full border rounded-md px-3 py-2 text-sm bg-background focus:outline-none focus:ring-2 focus:ring-primary/50"
              :class="{ 'border-destructive': form.errors.host }"
            />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1.5">Port</label>
            <NumberInput
              v-model="form.port"
              :error="!!form.errors.port"
            />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-sm font-medium mb-1.5">Username</label>
            <input
              v-model="form.username"
              type="text"
              class="w-full border rounded-md px-3 py-2 text-sm bg-background focus:outline-none focus:ring-2 focus:ring-primary/50"
              :class="{ 'border-destructive': form.errors.username }"
            />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1.5">Password</label>
            <input
              v-model="form.password"
              type="password"
              class="w-full border rounded-md px-3 py-2 text-sm bg-background focus:outline-none focus:ring-2 focus:ring-primary/50"
            />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-sm font-medium mb-1.5">From Address</label>
            <input
              v-model="form.from_address"
              type="email"
              placeholder="no-reply@example.com"
              class="w-full border rounded-md px-3 py-2 text-sm bg-background focus:outline-none focus:ring-2 focus:ring-primary/50"
              :class="{ 'border-destructive': form.errors.from_address }"
            />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1.5">From Name</label>
            <input
              v-model="form.from_name"
              type="text"
              placeholder="Lambda CMS"
              class="w-full border rounded-md px-3 py-2 text-sm bg-background focus:outline-none focus:ring-2 focus:ring-primary/50"
              :class="{ 'border-destructive': form.errors.from_name }"
            />
          </div>
        </div>
      </template>

      <div class="flex items-center justify-between pt-2">
        <button type="button" @click="router.visit('/install/admin')" class="text-sm text-muted-foreground hover:text-foreground transition-colors">
          ← Back
        </button>
        <button
          type="submit"
          :disabled="form.processing"
          class="inline-flex items-center gap-2 bg-primary text-primary-foreground text-sm font-medium px-5 py-2 rounded-md hover:bg-[var(--primary-hover)] transition-colors disabled:opacity-50"
        >
          <span v-if="form.processing">Installing…</span>
          <span v-else>Install Lambda CMS</span>
          <svg v-if="!form.processing" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
          </svg>
        </button>
      </div>
    </form>
  </div>
</template>
