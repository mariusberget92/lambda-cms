<template>
  <div>
    <div class="mb-8 text-center">
      <h1 class="text-2xl font-bold tracking-tight">Lambda CMS</h1>
      <p class="text-muted-foreground text-sm mt-1">Two-factor authentication</p>
    </div>

    <p class="text-sm text-muted-foreground mb-6 text-center leading-relaxed">
      <template v-if="!useRecovery">
        Open your authenticator app and enter the 6-digit code.
      </template>
      <template v-else>
        Enter one of your saved recovery codes to sign in.
      </template>
    </p>

    <form @submit.prevent="submit" class="space-y-4">
      <div class="space-y-1">
        <label :for="useRecovery ? 'recovery' : 'code'" class="text-sm font-medium">
          {{ useRecovery ? 'Recovery code' : 'Authentication code' }}
        </label>
        <input
          :id="useRecovery ? 'recovery' : 'code'"
          ref="inputRef"
          v-model="code"
          :type="useRecovery ? 'text' : 'text'"
          :inputmode="useRecovery ? 'text' : 'numeric'"
          :autocomplete="useRecovery ? 'off' : 'one-time-code'"
          :placeholder="useRecovery ? 'XXXXX-XXXXX' : '000000'"
          :maxlength="useRecovery ? undefined : 6"
          class="w-full rounded-md border bg-background px-3 py-2 text-sm tracking-widest text-center placeholder:text-muted-foreground placeholder:tracking-normal focus:outline-none focus:ring-2 focus:ring-ring disabled:opacity-50"
          :class="{ 'border-destructive': error }"
        />
        <p v-if="error" class="text-xs text-destructive mt-1">{{ error }}</p>
      </div>

      <button
        type="submit"
        :disabled="processing || !code.trim()"
        class="w-full rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)] disabled:opacity-50"
      >
        {{ processing ? 'Verifying…' : 'Verify' }}
      </button>

      <div class="text-center">
        <button
          type="button"
          @click="toggleMode"
          class="text-xs text-muted-foreground hover:text-foreground underline-offset-4 hover:underline transition-colors"
        >
          {{ useRecovery ? 'Use authenticator code instead' : 'Use a recovery code instead' }}
        </button>
      </div>

      <div class="text-center">
        <a
          :href="route('login')"
          class="text-xs text-muted-foreground hover:text-foreground underline-offset-4 hover:underline transition-colors"
        >
          Back to sign in
        </a>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, nextTick } from 'vue'
import AuthLayout from '@/Layouts/AuthLayout.vue'
import axios from 'axios'
import { router, usePage } from '@inertiajs/vue3'

defineOptions({ layout: AuthLayout })

const page       = usePage()
const inputRef   = ref(null)
const code       = ref('')
const error      = ref('')
const processing = ref(false)
const useRecovery = ref(false)

async function toggleMode() {
  useRecovery.value = !useRecovery.value
  code.value  = ''
  error.value = ''
  await nextTick()
  inputRef.value?.focus()
}

function submit() {
  if (processing.value) return
  error.value  = ''
  processing.value = true

  router.post(route('two-factor.verify'), { code: code.value }, {
    onError: (errors) => {
      error.value = errors.code ?? 'Invalid code.'
      code.value  = ''
      inputRef.value?.focus()
    },
    onFinish: () => { processing.value = false },
  })
}
</script>
