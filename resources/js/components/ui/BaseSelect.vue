<script setup lang="ts">
import { computed, useId } from 'vue';
import { cn } from '@/lib/utils';

defineOptions({ inheritAttrs: false });

export type SelectOption = {
    value: string | number | null;
    label: string;
    disabled?: boolean;
};

const props = withDefaults(
    defineProps<{
        id?: string;
        label: string;
        options?: SelectOption[];
        hint?: string;
        error?: string;
        optional?: boolean;
        disabled?: boolean;
    }>(),
    {
        id: undefined,
        options: () => [],
        hint: undefined,
        error: undefined,
        optional: false,
        disabled: false,
    },
);

const model = defineModel<string | number | null>();
const generatedId = useId();
const selectId = computed(() => props.id ?? generatedId);
const hintId = computed(() => `${selectId.value}-hint`);
const errorId = computed(() => `${selectId.value}-error`);
const describedBy = computed(() => {
    const ids = [];

    if (props.hint) {
        ids.push(hintId.value);
    }

    if (props.error) {
        ids.push(errorId.value);
    }

    return ids.length > 0 ? ids.join(' ') : undefined;
});
</script>

<template>
    <div class="grid gap-1.5">
        <div class="flex items-baseline justify-between gap-3">
            <label :for="selectId" class="text-sm font-semibold text-ink">
                {{ label }}
            </label>
            <span v-if="optional" class="text-xs text-ink-subtle">
                Opcional
            </span>
        </div>

        <div class="relative">
            <select
                v-bind="$attrs"
                :id="selectId"
                v-model="model"
                :disabled="disabled"
                :aria-invalid="error ? 'true' : undefined"
                :aria-describedby="describedBy"
                :class="
                    cn(
                        'min-h-11 w-full appearance-none rounded-control border border-line-strong bg-surface py-2.5 pr-10 pl-3 text-sm text-ink shadow-card transition-[border-color,box-shadow,background-color] duration-150 outline-none hover:border-primary focus:border-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus disabled:cursor-not-allowed disabled:bg-surface-subtle disabled:text-ink-subtle',
                        error && 'border-error focus:border-error',
                    )
                "
            >
                <slot>
                    <option
                        v-for="option in options"
                        :key="String(option.value)"
                        :value="option.value"
                        :disabled="option.disabled"
                    >
                        {{ option.label }}
                    </option>
                </slot>
            </select>
            <svg
                class="pointer-events-none absolute top-1/2 right-3 h-4 w-4 -translate-y-1/2 text-ink-muted"
                viewBox="0 0 20 20"
                fill="none"
                aria-hidden="true"
            >
                <path
                    d="m6 8 4 4 4-4"
                    stroke="currentColor"
                    stroke-width="1.75"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                />
            </svg>
        </div>

        <p v-if="hint" :id="hintId" class="text-xs leading-5 text-ink-muted">
            {{ hint }}
        </p>
        <p
            v-if="error"
            :id="errorId"
            class="flex items-center gap-1.5 text-xs leading-5 font-medium text-error"
            role="alert"
        >
            <span aria-hidden="true">!</span>
            {{ error }}
        </p>
    </div>
</template>
