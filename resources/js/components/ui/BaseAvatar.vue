<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { cn } from '@/lib/utils';

export type AvatarSize = 'sm' | 'md' | 'lg';

const props = withDefaults(
    defineProps<{
        name: string;
        src?: string;
        alt?: string;
        size?: AvatarSize;
    }>(),
    {
        src: undefined,
        alt: undefined,
        size: 'md',
    },
);

const imageFailed = ref(false);
const accessibleName = computed(() => props.alt ?? props.name);
const initials = computed(() => {
    const parts = props.name.trim().split(/\s+/).filter(Boolean);

    if (parts.length === 0) {
        return '?';
    }

    const firstInitial = parts[0]?.charAt(0) ?? '';
    const lastInitial = parts.length > 1 ? parts.at(-1)?.charAt(0) : '';

    return `${firstInitial}${lastInitial ?? ''}`.toLocaleUpperCase();
});
const avatarClass = computed(() =>
    cn(
        'inline-flex shrink-0 items-center justify-center overflow-hidden rounded-full border border-line bg-primary-subtle font-semibold text-primary',
        props.size === 'sm' && 'h-8 w-8 text-xs',
        props.size === 'md' && 'h-10 w-10 text-sm',
        props.size === 'lg' && 'h-12 w-12 text-base',
    ),
);
const imageSize = computed(
    () =>
        ({
            sm: 32,
            md: 40,
            lg: 48,
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
    <span :class="avatarClass">
        <img
            v-if="src && !imageFailed"
            :src="src"
            :alt="accessibleName"
            :width="imageSize"
            :height="imageSize"
            class="h-full w-full object-cover"
            @error="imageFailed = true"
        />
        <span v-else role="img" :aria-label="accessibleName" aria-live="off">
            {{ initials }}
        </span>
    </span>
</template>
