<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
</script>

<template>
    <Head title="JobHunter" />

    <AppLayout>
        <router-view />
    </AppLayout>
</template>
