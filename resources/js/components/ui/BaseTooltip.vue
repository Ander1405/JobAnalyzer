<script setup lang="ts">
import { AnimatePresence, motion } from 'motion-v';
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

// La burbuja no tiene ref propio ni maneja foco: es puramente decorativa,
// así que puede animarse con motion-v sin riesgo (a diferencia de overlays
// con focus-trap, ver BaseOverlay.vue). MotionConfig global respeta
// prefers-reduced-motion.
const tooltipTransition = { duration: 0.14, ease: [0.16, 1, 0.3, 1] };
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
        <AnimatePresence>
            <motion.span
                v-if="visible && !disabled"
                :id="tooltipId"
                role="tooltip"
                :initial="{ opacity: 0, scale: 0.96 }"
                :animate="{ opacity: 1, scale: 1 }"
                :exit="{ opacity: 0, scale: 0.96 }"
                :transition="tooltipTransition"
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
            </motion.span>
        </AnimatePresence>
    </span>
</template>
