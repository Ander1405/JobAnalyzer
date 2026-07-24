<script setup lang="ts">
import { computed, ref, watch } from 'vue';

const props = withDefaults(
    defineProps<{
        company: string;
        src?: string | null;
        size?: 'sm' | 'md' | 'lg';
    }>(),
    {
        src: null,
        size: 'md',
    },
);

const imageFailed = ref(false);
const initials = computed(() =>
    props.company
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((word) => word[0])
        .join('')
        .toUpperCase(),
);
const sizeClass = computed(
    () =>
        ({
            sm: 'h-9 w-9 rounded-control text-[0.6875rem]',
            md: 'h-11 w-11 rounded-control text-xs',
            lg: 'h-16 w-16 rounded-card text-sm',
        })[props.size],
);
const imageSize = computed(
    () =>
        ({
            sm: 36,
            md: 44,
            lg: 64,
        })[props.size],
);

watch(
    () => props.src,
    () => {
        imageFailed.value = false;
    },
);
</script>

<template>
    <img
        v-if="src && !imageFailed"
        :src="src"
        :alt="`Logo de ${company}`"
        :width="imageSize"
        :height="imageSize"
        :class="`shrink-0 border border-line bg-white object-contain p-1 ${sizeClass}`"
        @error="imageFailed = true"
    />
    <span
        v-else
        :class="`inline-flex shrink-0 items-center justify-center border border-primary/20 bg-primary-subtle font-data font-semibold tracking-[-0.04em] text-primary ${sizeClass}`"
        :aria-label="company"
        role="img"
    >
        {{ initials || 'JH' }}
    </span>
</template>
