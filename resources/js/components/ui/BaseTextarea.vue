<script setup lang="ts">
import { computed, useId } from 'vue';
import { cn } from '@/lib/utils';

defineOptions({ inheritAttrs: false });

const props = withDefaults(
    defineProps<{
        id?: string;
        label: string;
        rows?: number;
        hint?: string;
        error?: string;
        optional?: boolean;
        disabled?: boolean;
    }>(),
    {
        id: undefined,
        rows: 4,
        hint: undefined,
        error: undefined,
        optional: false,
        disabled: false,
    },
);

const model = defineModel<string>({ default: '' });
const generatedId = useId();
const textareaId = computed(() => props.id ?? generatedId);
const hintId = computed(() => `${textareaId.value}-hint`);
const errorId = computed(() => `${textareaId.value}-error`);
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
    model.value = (event.target as HTMLTextAreaElement).value;
}
</script>

<template>
    <div class="grid gap-1.5">
        <div class="flex items-baseline justify-between gap-3">
            <label :for="textareaId" class="text-sm font-semibold text-ink">
                {{ label }}
            </label>
            <span v-if="optional" class="text-xs text-ink-subtle">
                Opcional
            </span>
        </div>

        <textarea
            v-bind="$attrs"
            :id="textareaId"
            :value="model"
            :rows="rows"
            :disabled="disabled"
            :aria-invalid="error ? 'true' : undefined"
            :aria-describedby="describedBy"
            :class="
                cn(
                    'min-h-22 w-full resize-y rounded-control border border-line-strong bg-surface px-3 py-2.5 text-sm leading-5 text-ink shadow-card transition-[border-color,box-shadow,background-color] duration-150 outline-none placeholder:text-ink-subtle hover:border-primary focus:border-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus disabled:cursor-not-allowed disabled:resize-none disabled:bg-surface-subtle disabled:text-ink-subtle',
                    error && 'border-error focus:border-error',
                )
            "
            @input="updateModel"
        />

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
