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
  { deep: true, immediate: true }
)
</script>

<template>
  <Teleport to="body">
    <div class="fixed top-4 right-4 z-50 flex flex-col gap-2 pointer-events-none">
      <TransitionGroup
        tag="div"
        class="flex flex-col gap-2"
        style="position: relative"
        enter-active-class="animate__animated animate__fadeInDown"
        leave-active-class="animate__animated animate__fadeOut"
      >
        <div v-for="n in notifications" :key="n.id" class="pointer-events-auto">
          <NotificationItem
            :id="n.id"
            :type="n.type"
            :message="n.message"
            :duration="n.duration"
            :actions="n.actions"
            @dismiss="dismiss"
          />
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<style>
.animate__fadeInDown,
.animate__fadeOut {
  --animate-duration: 0.25s;
}
</style>
