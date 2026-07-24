<script setup lang="ts">
withDefaults(
    defineProps<{
        title: string;
        description: string;
        compact?: boolean;
    }>(),
    {
        compact: false,
    },
);
</script>

<template>
    <section
        class="@container/empty flex flex-col items-start border border-dashed border-line-strong bg-surface px-5 text-left"
        :class="
            compact
                ? 'gap-3 rounded-card py-6'
                : 'gap-5 rounded-panel py-10 @sm/empty:px-8'
        "
    >
        <div
            class="flex w-full flex-col items-start gap-4 @sm/empty:flex-row @sm/empty:items-center"
        >
            <div
                v-if="$slots.icon"
                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-card border border-primary/20 bg-primary-subtle text-primary"
                aria-hidden="true"
            >
                <slot name="icon" />
            </div>
            <div class="grid max-w-xl gap-1.5">
                <h2 class="text-lg font-semibold tracking-[-0.02em] text-ink">
                    {{ title }}
                </h2>
                <p class="text-sm leading-6 text-ink-muted">
                    {{ description }}
                </p>
            </div>
        </div>
        <div v-if="$slots.action" class="flex flex-wrap gap-2">
            <slot name="action" />
        </div>
    </section>
</template>
