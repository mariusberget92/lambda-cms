<script setup>
defineProps({
    posts:   Object,
    sidebar: Object,
    seo:     Object,
    heading: Object,
});
</script>

<template>
    <div>
        <slot />
    </div>
</template>
