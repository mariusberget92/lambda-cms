<template>
  <AppLayout title="Navigation">
    <Head title="Navigation" />
    <div>
      <h2 class="text-lg font-semibold">Navigation Manager</h2>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
  items: Array,
  pages: Array,
});
</script>
