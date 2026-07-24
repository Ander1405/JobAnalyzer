<script setup lang="ts">
import { computed } from 'vue';
import { cn } from '@/lib/utils';

type SkeletonShape = 'text' | 'block' | 'circle';

const props = withDefaults(
    defineProps<{
        shape?: SkeletonShape;
    }>(),
    {
        shape: 'block',
    },
);

const skeletonClass = computed(() =>
    cn(
        'animate-skeleton bg-surface-subtle',
        props.shape === 'text' && 'h-3.5 rounded',
        props.shape === 'block' && 'rounded-card',
        props.shape === 'circle' && 'aspect-square rounded-full',
    ),
);
</script>

<template>
    <div :class="skeletonClass" aria-hidden="true" />
</template>
