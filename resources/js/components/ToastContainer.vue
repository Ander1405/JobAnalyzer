<script setup lang="ts">
import { AnimatePresence, motion } from 'motion-v';
import BaseToast from '@/components/ui/BaseToast.vue';
import { useToast } from '@/lib/toast';

const { toasts, dismiss } = useToast();

// El toast en sí no tiene refs ni maneja foco (dismiss es un emit), así que
// motion-v puede envolver la lista completa: da entrada Y salida, algo que la
// animación CSS de un solo sentido en BaseToast no cubría.
const toastTransition = { duration: 0.22, ease: [0.16, 1, 0.3, 1] };
</script>

<template>
    <div
        class="fixed inset-x-4 bottom-4 z-100 flex flex-col items-end gap-2 sm:inset-x-auto sm:right-4"
    >
        <AnimatePresence>
            <motion.div
                v-for="toast in toasts"
                :key="toast.id"
                :initial="{ opacity: 0, y: 12, scale: 0.98 }"
                :animate="{ opacity: 1, y: 0, scale: 1 }"
                :exit="{ opacity: 0, scale: 0.98 }"
                :transition="toastTransition"
                class="w-full max-w-sm"
            >
                <BaseToast
                    :type="toast.type"
                    :message="toast.message"
                    @dismiss="dismiss(toast.id)"
                />
            </motion.div>
        </AnimatePresence>
    </div>
</template>
