<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="$emit('close')" />
        <div class="modal-panel relative bg-card border rounded-xl w-full max-w-sm p-6">
          <h3 class="font-semibold text-base mb-2">{{ title }}</h3>
          <div class="text-sm text-muted-foreground mb-5">
            <slot>{{ description }}</slot>
            <slot name="body" />
          </div>
          <div class="flex gap-3 justify-end">
            <button
              type="button"
              @click="$emit('close')"
              class="rounded-md border px-4 py-2 text-sm font-medium hover:bg-accent transition-colors"
            >
              {{ cancelLabel }}
            </button>
            <button
              type="button"
              @click="$emit('confirm')"
              :disabled="processing"
              class="rounded-md px-4 py-2 text-sm font-medium transition-colors disabled:opacity-50"
              :class="variant === 'destructive'
                ? 'bg-destructive text-destructive-foreground hover:bg-destructive/90'
                : 'bg-primary text-primary-foreground hover:bg-[var(--primary-hover)]'"
            >
              {{ processing ? processingLabel : confirmLabel }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
defineProps({
  open:            { type: Boolean, default: false },
  title:           { type: String,  required: true },
  description:     { type: String,  default: '' },
  confirmLabel:    { type: String,  default: 'Confirm' },
  cancelLabel:     { type: String,  default: 'Cancel' },
  processingLabel: { type: String,  default: 'Processing...' },
  processing:      { type: Boolean, default: false },
  variant:         { type: String,  default: 'destructive' },
})

defineEmits(['close', 'confirm'])
</script>

<style scoped>
.modal-panel {
  box-shadow:
    0 16px 40px -8px rgb(0 0 0 / 0.18),
    0 4px 12px -2px rgb(0 0 0 / 0.08);
}
.modal-enter-active { transition: opacity 0.15s ease-out; }
.modal-enter-active .modal-panel { transition: transform 0.15s ease-out, opacity 0.15s ease-out; }
.modal-leave-active { transition: opacity 0.1s ease-in; }
.modal-leave-active .modal-panel { transition: transform 0.1s ease-in, opacity 0.1s ease-in; }
.modal-enter-from { opacity: 0; }
.modal-enter-from .modal-panel { transform: scale(0.95) translateY(4px); opacity: 0; }
.modal-leave-to { opacity: 0; }
.modal-leave-to .modal-panel { transform: scale(0.97); opacity: 0; }
</style>
