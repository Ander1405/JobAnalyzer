<script setup lang="ts">
import { computed } from 'vue';
import type { Component } from 'vue';
import { cn } from '@/lib/utils';

defineOptions({ inheritAttrs: false });

type ButtonVariant = 'primary' | 'secondary' | 'quiet' | 'danger';
type ButtonSize = 'sm' | 'md' | 'lg' | 'icon';

const props = withDefaults(
    defineProps<{
        variant?: ButtonVariant;
        size?: ButtonSize;
        as?: string | Component;
        type?: 'button' | 'submit' | 'reset';
        disabled?: boolean;
        loading?: boolean;
        loadingLabel?: string;
    }>(),
    {
        variant: 'primary',
        size: 'md',
        as: 'button',
        type: 'button',
        disabled: false,
        loading: false,
        loadingLabel: 'Procesando',
    },
);

function onClick(event: MouseEvent): void {
    if (props.as !== 'button' && (props.disabled || props.loading)) {
        event.preventDefault();
        event.stopPropagation();
    }
}

// Press-scale queda en CSS (active:), no motion-v: "as" es polimórfico
// (button, Link de Inertia, ancla...) y motion-v no garantiza reenviar el
// ref/comportamiento de un componente arbitrario. Un :active con transform
// ya es correcto y performante para este caso — ver mockup .btn:active.
const buttonClass = computed(() =>
    cn(
        'inline-flex shrink-0 items-center justify-center gap-2 rounded-control border text-sm font-semibold whitespace-nowrap transition-[color,background-color,border-color,box-shadow,transform] duration-150 ease-out focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus disabled:pointer-events-none disabled:opacity-50',
        props.variant === 'primary' &&
            'border-primary bg-primary text-primary-contrast shadow-action hover:border-primary-hover hover:bg-primary-hover active:translate-y-px active:scale-[0.985] active:border-primary-active active:bg-primary-active',
        props.variant === 'secondary' &&
            'border-line-strong bg-surface text-ink shadow-card hover:border-primary hover:bg-primary-subtle hover:text-primary active:translate-y-px active:scale-[0.985]',
        props.variant === 'quiet' &&
            'border-transparent bg-transparent text-ink-muted hover:bg-surface-subtle hover:text-ink active:translate-y-px active:scale-[0.985]',
        props.variant === 'danger' &&
            'border-error bg-error text-error-contrast shadow-card hover:brightness-90 active:translate-y-px active:scale-[0.985]',
        props.size === 'sm' && 'min-h-11 px-3 py-1.5 text-xs',
        props.size === 'md' && 'min-h-11 px-4 py-2.5',
        props.size === 'lg' && 'min-h-12 px-5 py-3 text-base',
        props.size === 'icon' && 'h-11 w-11 p-0',
    ),
);
</script>

<template>
    <component
        :is="as"
        v-bind="$attrs"
        :type="as === 'button' ? type : undefined"
        :disabled="as === 'button' ? disabled || loading : undefined"
        :aria-disabled="
            as !== 'button' && (disabled || loading) ? 'true' : undefined
        "
        :aria-busy="loading || undefined"
        :class="buttonClass"
        @click="onClick"
    >
        <svg
            v-if="loading"
            class="h-4 w-4 animate-spin"
            viewBox="0 0 24 24"
            fill="none"
            aria-hidden="true"
        >
            <circle
                cx="12"
                cy="12"
                r="9"
                stroke="currentColor"
                stroke-width="3"
                opacity="0.28"
            />
            <path
                d="M21 12a9 9 0 0 0-9-9"
                stroke="currentColor"
                stroke-width="3"
                stroke-linecap="round"
            />
        </svg>
        <slot name="leading" />
        <span v-if="loading" class="sr-only">{{ loadingLabel }}</span>
        <span v-if="$slots.default"><slot /></span>
        <slot name="trailing" />
    </component>
</template>
