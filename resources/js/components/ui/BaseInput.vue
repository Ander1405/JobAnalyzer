<script setup lang="ts">
import { computed, useId } from 'vue';
import { cn } from '@/lib/utils';

defineOptions({ inheritAttrs: false });

const props = withDefaults(
    defineProps<{
        id?: string;
        label: string;
        type?: string;
        hint?: string;
        error?: string;
        optional?: boolean;
        disabled?: boolean;
    }>(),
    {
        id: undefined,
        type: 'text',
        hint: undefined,
        error: undefined,
        optional: false,
        disabled: false,
    },
);

const model = defineModel<string | number | null>();
const generatedId = useId();
const inputId = computed(() => props.id ?? generatedId);
const hintId = computed(() => `${inputId.value}-hint`);
const errorId = computed(() => `${inputId.value}-error`);
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

function updateModel(event: Event): void {
    const value = (event.target as HTMLInputElement).value;

    if (props.type === 'number') {
        model.value = value === '' ? null : Number(value);

        return;
    }

    model.value = value;
}
</script>

<template>
    <div class="grid gap-1.5">
        <div class="flex items-baseline justify-between gap-3">
            <label :for="inputId" class="text-sm font-semibold text-ink">
                {{ label }}
            </label>
            <span v-if="optional" class="text-xs text-ink-subtle">
                Opcional
            </span>
        </div>

        <div class="relative">
            <div
                v-if="$slots.prefix"
                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-ink-subtle"
            >
                <slot name="prefix" />
            </div>
            <input
                v-bind="$attrs"
                :id="inputId"
                :value="model ?? ''"
                :type="type"
                :disabled="disabled"
                :aria-invalid="error ? 'true' : undefined"
                :aria-describedby="describedBy"
                :class="
                    cn(
                        'min-h-11 w-full rounded-control border border-line-strong bg-surface px-3 py-2.5 text-sm text-ink shadow-card transition-[border-color,box-shadow,background-color] duration-150 outline-none placeholder:text-ink-subtle hover:border-primary focus:border-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus disabled:cursor-not-allowed disabled:bg-surface-subtle disabled:text-ink-subtle',
                        $slots.prefix && 'pl-10',
                        $slots.suffix && 'pr-10',
                        error && 'border-error focus:border-error',
                    )
                "
                @input="updateModel"
            />
            <div
                v-if="$slots.suffix"
                class="absolute inset-y-0 right-0 flex items-center pr-3 text-ink-subtle"
            >
                <slot name="suffix" />
            </div>
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
