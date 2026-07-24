<script setup lang="ts">
import BaseToast from '@/components/ui/BaseToast.vue';
import { useToast } from '@/lib/toast';

const { toasts, dismiss } = useToast();
</script>

<template>
    <div
        class="fixed inset-x-4 bottom-4 z-100 flex flex-col items-end gap-2 sm:inset-x-auto sm:right-4"
    >
        <BaseToast
            v-for="toast in toasts"
            :key="toast.id"
            :type="toast.type"
            :message="toast.message"
            @dismiss="dismiss(toast.id)"
        />
    </div>
</template>
