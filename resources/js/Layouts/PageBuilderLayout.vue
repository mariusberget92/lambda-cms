<!-- resources/js/Layouts/PageBuilderLayout.vue -->
<template>
  <div data-theme="dark" class="fixed inset-0 z-40 flex flex-col bg-[#1e1e2e] overflow-hidden">
    <slot name="bar" />
    <div class="flex-1 overflow-hidden">
      <slot />
    </div>
  </div>
  <Notifications />
</template>

<script setup>
import Notifications from '@/Components/Notifications.vue'
</script>
