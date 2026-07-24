<script setup lang="ts">
import { computed } from 'vue';
import { lineDiff } from '@/lib/diff';
import { cn } from '@/lib/utils';

const props = defineProps<{
    before: string;
    after: string;
}>();

const rows = computed(() => lineDiff(props.before, props.after));
</script>

<template>
    <div
        class="overflow-x-auto rounded-card border border-line bg-surface shadow-card"
    >
        <div
            class="grid grid-cols-2 divide-x divide-gray-200 text-xs dark:divide-gray-800"
        >
            <template v-for="(row, index) in rows" :key="index">
                <div
                    :class="
                        cn(
                            'min-w-0 px-2 py-0.5 font-mono whitespace-pre-wrap',
                            row.type === 'removed' &&
                                'bg-error-surface text-error',
                        )
                    "
                >
                    {{ row.left ?? '' }}
                </div>
                <div
                    :class="
                        cn(
                            'min-w-0 px-2 py-0.5 font-mono whitespace-pre-wrap',
                            row.type === 'added' &&
                                'bg-success-surface text-success',
                        )
                    "
                >
                    {{ row.right ?? '' }}
                </div>
            </template>
        </div>
    </div>
</template>
