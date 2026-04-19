// resources/js/composables/useNotifications.js
import { ref } from 'vue'

// Module-level singleton — shared across all component instances
const notifications = ref([])
let counter = 0

function readingDuration(message) {
  const words = message.trim().split(/\s+/).length
  return Math.max(3000, words * 350) // ~170 WPM, 3 s floor
}

export function useNotifications() {
  function notify(message, type = 'success', options = {}) {
    if (typeof message !== 'string' || !message) return

    if (import.meta.env.DEV) {
      const VALID_TYPES = ['success', 'error', 'warning', 'info']
      if (!VALID_TYPES.includes(type)) {
        console.warn(`[useNotifications] Unknown type "${type}". Must be one of: ${VALID_TYPES.join(', ')}`)
      }
    }

    const id = Date.now() + (++counter)
    const duration = 'duration' in options ? options.duration : readingDuration(message)
    const actions  = options.actions ?? []
    const items    = options.items ?? []

    // Enforce max 5 — remove oldest if needed
    if (notifications.value.length >= 5) {
      notifications.value.shift()
    }

    notifications.value.push({ id, type, message, duration, actions, items })
    return id
  }

  function dismiss(id) {
    const idx = notifications.value.findIndex(n => n.id === id)
    if (idx !== -1) notifications.value.splice(idx, 1)
  }

  return { notifications, notify, dismiss }
}
