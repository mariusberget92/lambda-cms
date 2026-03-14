<template>
  <AppLayout title="Pages">
    <Head title="Pages" />
    <div>
      <h2 class="text-lg font-semibold">Pages</h2>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
  pages: Object,
});
</script>
