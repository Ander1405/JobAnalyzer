<script setup lang="ts">
import { computed } from 'vue';
import type { ToastType } from '@/lib/toast';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        type: ToastType;
        message: string;
        title?: string;
        dismissLabel?: string;
    }>(),
    {
        title: undefined,
        dismissLabel: 'Cerrar notificación',
    },
);

const emit = defineEmits<{
    dismiss: [];
}>();

const stateClass = computed(() =>
    cn(
        props.type === 'success' && 'bg-success-surface text-success',
        props.type === 'error' && 'bg-error-surface text-error',
        props.type === 'info' && 'bg-info-surface text-info',
    ),
);
</script>

<template>
    <article
        :role="type === 'error' ? 'alert' : 'status'"
        class="flex w-full max-w-sm animate-toast-in items-start gap-3 rounded-card border border-line bg-surface-raised p-4 text-ink shadow-raised"
    >
        <span
            :class="
                cn(
                    'flex h-8 w-8 shrink-0 items-center justify-center rounded-full',
                    stateClass,
                )
            "
            aria-hidden="true"
        >
            <svg
                v-if="type === 'success'"
                class="h-4 w-4"
                viewBox="0 0 20 20"
                fill="none"
            >
                <path
                    d="m4 10 4 4 8-8"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                />
            </svg>
            <svg
                v-else-if="type === 'error'"
                class="h-4 w-4"
                viewBox="0 0 20 20"
                fill="none"
            >
                <path
                    d="M10 6v5m0 3h.01M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z"
                    stroke="currentColor"
                    stroke-width="1.75"
                    stroke-linecap="round"
                />
            </svg>
            <svg v-else class="h-4 w-4" viewBox="0 0 20 20" fill="none">
                <path
                    d="M10 9v5m0-8h.01M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z"
                    stroke="currentColor"
                    stroke-width="1.75"
                    stroke-linecap="round"
                />
            </svg>
        </span>
        <div class="min-w-0 flex-1 pt-0.5">
            <p v-if="title" class="text-sm font-semibold text-ink">
                {{ title }}
            </p>
            <p class="text-sm leading-5 text-ink-muted">{{ message }}</p>
        </div>
        <button
            type="button"
            :aria-label="dismissLabel"
            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-control text-ink-subtle hover:bg-surface-subtle hover:text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus"
            @click="emit('dismiss')"
        >
            <svg
                class="h-4 w-4"
                viewBox="0 0 20 20"
                fill="none"
                aria-hidden="true"
            >
                <path
                    d="m5 5 10 10M15 5 5 15"
                    stroke="currentColor"
                    stroke-width="1.75"
                    stroke-linecap="round"
                />
            </svg>
        </button>
    </article>
</template>
