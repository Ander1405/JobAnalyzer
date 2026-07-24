<script setup lang="ts">
import { computed } from 'vue';
import { cn } from '@/lib/utils';

type TagTone =
    | 'neutral'
    | 'primary'
    | 'success'
    | 'warning'
    | 'error'
    | 'info'
    | 'score-excellent'
    | 'score-very-good'
    | 'score-acceptable'
    | 'score-low';

const props = withDefaults(
    defineProps<{
        as?: string;
        tone?: TagTone;
    }>(),
    {
        as: 'span',
        tone: 'neutral',
    },
);

const tagClass = computed(() =>
    cn(
        'inline-flex min-h-6 items-center gap-1 rounded-full border px-2 py-0.5 text-xs font-semibold',
        props.tone === 'neutral' &&
            'border-line bg-surface-subtle text-ink-muted',
        props.tone === 'primary' &&
            'border-primary/20 bg-primary-subtle text-primary',
        props.tone === 'success' &&
            'border-success/20 bg-success-surface text-success',
        props.tone === 'warning' &&
            'border-warning/20 bg-warning-surface text-warning',
        props.tone === 'error' && 'border-error/20 bg-error-surface text-error',
        props.tone === 'info' && 'border-info/20 bg-info-surface text-info',
        props.tone === 'score-excellent' &&
            'border-score-excellent/20 bg-score-excellent-surface text-score-excellent',
        props.tone === 'score-very-good' &&
            'border-score-very-good/20 bg-score-very-good-surface text-score-very-good',
        props.tone === 'score-acceptable' &&
            'border-score-acceptable/20 bg-score-acceptable-surface text-score-acceptable',
        props.tone === 'score-low' &&
            'border-score-low/20 bg-score-low-surface text-score-low',
    ),
);
</script>

<template>
    <component :is="as" :class="tagClass">
        <slot name="icon" />
        <slot />
    </component>
</template>
