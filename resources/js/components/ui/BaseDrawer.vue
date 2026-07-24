<script setup lang="ts">
import BaseOverlay from './BaseOverlay.vue';

defineOptions({ inheritAttrs: false });

withDefaults(
    defineProps<{
        open: boolean;
        title: string;
        description?: string;
        closeLabel?: string;
        closeOnBackdrop?: boolean;
    }>(),
    {
        description: undefined,
        closeLabel: 'Cerrar panel',
        closeOnBackdrop: true,
    },
);

const emit = defineEmits<{
    close: [];
}>();
</script>

<template>
    <BaseOverlay
        v-bind="$attrs"
        :open="open"
        :title="title"
        :description="description"
        :close-label="closeLabel"
        :close-on-backdrop="closeOnBackdrop"
        mode="drawer"
        @close="emit('close')"
    >
        <slot />
        <template v-if="$slots.footer" #footer>
            <slot name="footer" />
        </template>
    </BaseOverlay>
</template>
