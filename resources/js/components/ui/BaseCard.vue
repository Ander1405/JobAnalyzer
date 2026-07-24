<script setup lang="ts">
import { computed } from 'vue';
import { cn } from '@/lib/utils';

type CardVariant = 'default' | 'raised' | 'subtle' | 'interactive';

const props = withDefaults(
    defineProps<{
        as?: string;
        variant?: CardVariant;
        padded?: boolean;
    }>(),
    {
        as: 'section',
        variant: 'default',
        padded: true,
    },
);

const cardClass = computed(() =>
    cn(
        // @container/card: la tarjeta puede vivir en un layout de 1 o de 3
        // columnas en la misma vista, así que su padding responde a SU ancho,
        // no al viewport. Convención: nombra el container "/card" para que
        // el slot pueda anidar sus propios @container sin chocar variantes.
        'rounded-card border border-line @container/card',
        props.padded && 'p-4 @sm/card:p-5',
        props.variant === 'default' && 'bg-surface shadow-card',
        props.variant === 'raised' && 'bg-surface-raised shadow-raised',
        props.variant === 'subtle' && 'bg-surface-subtle',
        props.variant === 'interactive' &&
            'bg-surface shadow-card transition-[border-color,box-shadow,transform] duration-150 ease-out focus-within:border-primary hover:-translate-y-0.5 hover:border-primary hover:shadow-raised',
    ),
);
</script>

<template>
    <component :is="as" :class="cardClass">
        <slot />
    </component>
</template>
