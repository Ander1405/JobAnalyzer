<script setup lang="ts">
import { ref, useId } from 'vue';

withDefaults(
    defineProps<{
        text: string;
        position?: 'top' | 'right' | 'bottom';
        disabled?: boolean;
    }>(),
    {
        position: 'top',
        disabled: false,
    },
);

const tooltipId = useId();
const visible = ref(false);
</script>

<template>
    <span
        class="relative inline-flex"
        @mouseenter="visible = true"
        @mouseleave="visible = false"
        @focusin="visible = true"
        @focusout="visible = false"
        @keydown.esc.stop="visible = false"
    >
        <slot :describedby="tooltipId" />
        <span
            v-show="visible && !disabled"
            :id="tooltipId"
            role="tooltip"
            class="absolute z-50 w-max max-w-64 rounded-control bg-surface-inverse px-2.5 py-1.5 text-xs font-medium text-ink-inverse shadow-raised"
            :class="
                position === 'right'
                    ? 'top-1/2 left-[calc(100%+0.5rem)] -translate-y-1/2'
                    : position === 'top'
                      ? 'bottom-[calc(100%+0.5rem)] left-1/2 -translate-x-1/2'
                      : 'top-[calc(100%+0.5rem)] left-1/2 -translate-x-1/2'
            "
        >
            {{ text }}
        </span>
    </span>
</template>
