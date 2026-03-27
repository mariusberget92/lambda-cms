<script setup>
import { useForm } from '@inertiajs/vue3'
import InstallLayout from '@/Layouts/InstallLayout.vue'
import { useNotifications } from '@/composables/useNotifications.js'
import NumberInput from '@/Components/NumberInput.vue'

defineOptions({ layout: InstallLayout })

defineProps({
  step: Number,
})

const { notify } = useNotifications()

const form = useForm({
  driver: 'sqlite',
  host: '127.0.0.1',
  port: 3306,
  database: 'lambda_cms',
  username: '',
  password: '',
  prefix: '',
})

function submit() {
  form.post('/install/database', {
    onError: (errors) => notify('Please fix the following:', 'error', { items: Object.values(errors) }),
  })
}
</script>

<template>
  <div>
    <h2 class="text-lg font-semibold mb-1">Database Configuration</h2>
    <p class="text-sm text-muted-foreground mb-6">Configure your database connection.</p>

    <form @submit.prevent="submit" class="space-y-4">
      <!-- Driver -->
      <div>
        <label class="block text-sm font-medium mb-1.5">Database Driver</label>
        <div class="flex gap-3">
          <label class="flex items-center gap-2 cursor-pointer">
            <input v-model="form.driver" type="radio" value="sqlite" class="accent-primary" />
            <span class="text-sm">SQLite</span>
          </label>
          <label class="flex items-center gap-2 cursor-pointer">
            <input v-model="form.driver" type="radio" value="mysql" class="accent-primary" />
            <span class="text-sm">MySQL / MariaDB</span>
          </label>
        </div>
      </div>

      <!-- MySQL fields -->
      <template v-if="form.driver === 'mysql'">
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-sm font-medium mb-1.5">Host</label>
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

        <div>
          <label class="block text-sm font-medium mb-1.5">Database Name</label>
          <input
            v-model="form.database"
            type="text"
            class="w-full border rounded-md px-3 py-2 text-sm bg-background focus:outline-none focus:ring-2 focus:ring-primary/50"
            :class="{ 'border-destructive': form.errors.database }"
          />
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

        <div>
          <label class="block text-sm font-medium mb-1.5">Table Prefix <span class="text-muted-foreground font-normal">(optional)</span></label>
          <input
            v-model="form.prefix"
            type="text"
            placeholder="e.g. lcms_"
            class="w-full border rounded-md px-3 py-2 text-sm bg-background focus:outline-none focus:ring-2 focus:ring-primary/50"
          />
        </div>

      </template>

      <div v-if="form.driver === 'sqlite'" class="rounded-lg border bg-muted/40 p-4 text-sm text-muted-foreground">
        SQLite will be used — a <code class="font-mono text-xs bg-muted px-1 py-0.5 rounded">database/database.sqlite</code> file will be created automatically.
      </div>

      <div class="flex justify-end pt-2">
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
