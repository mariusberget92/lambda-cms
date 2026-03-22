<script setup>
import { watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useNotifications } from '@/composables/useNotifications.js'
import NotificationItem from '@/Components/NotificationItem.vue'

const page = usePage()
const { notifications, notify, dismiss } = useNotifications()

watch(
  () => page.props.flash,
  (flash) => {
    if (flash?.status) notify(flash.status, 'success')
    if (flash?.error)  notify(flash.error,  'error')
  },
  { deep: true }
)
</script>

<template>
  <div class="fixed top-4 right-4 z-50 flex flex-col gap-2">
    <TransitionGroup name="notif" tag="div" class="flex flex-col gap-2">
      <NotificationItem
        v-for="n in notifications"
        :key="n.id"
        :id="n.id"
        :type="n.type"
        :message="n.message"
        :duration="n.duration"
        :actions="n.actions"
        @dismiss="dismiss"
      />
    </TransitionGroup>
  </div>
</template>

<style scoped>
.notif-enter-active { transition: transform 0.25s ease, opacity 0.25s ease; }
.notif-leave-active { transition: transform 0.2s  ease, opacity 0.2s  ease; }
.notif-enter-from,
.notif-leave-to     { transform: translateX(110%); opacity: 0; }
</style>
