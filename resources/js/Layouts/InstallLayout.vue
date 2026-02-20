<script setup>
const props = defineProps({
  step: {
    type: Number,
    default: 1,
  },
})

const steps = [
  { number: 1, label: 'Database' },
  { number: 2, label: 'Site' },
  { number: 3, label: 'Admin' },
  { number: 4, label: 'Mail' },
]
</script>

<template>
  <div class="min-h-screen bg-background flex items-center justify-center p-4">
    <div class="w-full max-w-lg">
      <!-- Logo -->
      <div class="flex items-center justify-center gap-2 mb-8">
        <div class="w-8 h-8 rounded-md bg-primary flex items-center justify-center">
          <svg class="w-5 h-5 text-primary-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3L2 7l10 4 10-4-10-4zM2 17l10 4 10-4M2 12l10 4 10-4" />
          </svg>
        </div>
        <span class="text-xl font-semibold tracking-tight">Lambda CMS</span>
      </div>

      <!-- Card -->
      <div class="bg-card border rounded-xl shadow-sm">
        <!-- Step progress -->
        <div class="border-b px-6 py-4">
          <div class="flex items-center justify-between gap-2">
            <template v-for="(s, index) in steps" :key="s.number">
              <!-- Step pill -->
              <div class="flex items-center gap-2">
                <div
                  class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-semibold shrink-0 transition-colors"
                  :class="{
                    'bg-primary text-primary-foreground': s.number === step,
                    'bg-primary/20 text-primary': s.number < step,
                    'bg-muted text-muted-foreground': s.number > step,
                  }"
                >
                  <!-- Checkmark for completed steps -->
                  <svg v-if="s.number < step" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                  </svg>
                  <span v-else>{{ s.number }}</span>
                </div>
                <span
                  class="text-xs font-medium hidden sm:inline transition-colors"
                  :class="{
                    'text-foreground': s.number === step,
                    'text-primary': s.number < step,
                    'text-muted-foreground': s.number > step,
                  }"
                >{{ s.label }}</span>
              </div>

              <!-- Connector line -->
              <div
                v-if="index < steps.length - 1"
                class="flex-1 h-px transition-colors"
                :class="s.number < step ? 'bg-primary/40' : 'bg-border'"
              />
            </template>
          </div>
        </div>

        <!-- Page content slot -->
        <div class="p-6">
          <slot />
        </div>
      </div>
    </div>
  </div>
</template>
