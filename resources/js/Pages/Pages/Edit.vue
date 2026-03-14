<template>
  <AppLayout title="Edit Page">
    <Head title="Edit Page" />
    <div>
      <h2 class="text-lg font-semibold">Edit Page</h2>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
  page: Object,
});
</script>
