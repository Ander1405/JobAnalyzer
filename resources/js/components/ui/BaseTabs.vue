<script setup lang="ts">
import { computed, nextTick, ref, useId } from 'vue';
import { cn } from '@/lib/utils';

export type TabsItem = {
    value: string;
    label: string;
    disabled?: boolean;
};

const props = withDefaults(
    defineProps<{
        items: TabsItem[];
        label?: string;
    }>(),
    {
        label: 'Secciones',
    },
);

const model = defineModel<string>({ required: true });
const baseId = useId();
const tabButtons = ref<HTMLButtonElement[]>([]);
const activeItem = computed(() =>
    props.items.find((item) => item.value === model.value),
);
const enabledItems = computed(() =>
    props.items.filter((item) => !item.disabled),
);
const rovingValue = computed(
    () =>
        enabledItems.value.find((item) => item.value === model.value)?.value ??
        enabledItems.value[0]?.value,
);

function itemIndex(item: TabsItem): number {
    return props.items.indexOf(item);
}

function tabId(item: TabsItem): string {
    return `${baseId}-tab-${itemIndex(item)}`;
}

function panelId(item: TabsItem): string {
    return `${baseId}-panel-${itemIndex(item)}`;
}

function activate(item: TabsItem): void {
    if (!item.disabled) {
        model.value = item.value;
    }
}

async function focusItem(item: TabsItem): Promise<void> {
    activate(item);
    await nextTick();
    tabButtons.value
        .find((button) => button.dataset.tabValue === item.value)
        ?.focus();
}

function onKeydown(event: KeyboardEvent, currentItem: TabsItem): void {
    const items = enabledItems.value;

    if (items.length === 0) {
        return;
    }

    const currentIndex = Math.max(
        0,
        items.findIndex((item) => item.value === currentItem.value),
    );
    let nextItem: TabsItem | undefined;

    if (event.key === 'Home') {
        nextItem = items[0];
    } else if (event.key === 'End') {
        nextItem = items.at(-1);
    } else if (event.key === 'ArrowRight' || event.key === 'ArrowDown') {
        nextItem = items[(currentIndex + 1) % items.length];
    } else if (event.key === 'ArrowLeft' || event.key === 'ArrowUp') {
        nextItem = items[(currentIndex - 1 + items.length) % items.length];
    }

    if (!nextItem) {
        return;
    }

    event.preventDefault();
    void focusItem(nextItem);
}
</script>

<template>
    <div class="min-w-0">
        <div
            role="tablist"
            :aria-label="label"
            class="flex min-w-0 gap-1 overflow-x-auto border-b border-line"
        >
            <button
                v-for="item in items"
                :id="tabId(item)"
                ref="tabButtons"
                :key="item.value"
                type="button"
                role="tab"
                :data-tab-value="item.value"
                :aria-selected="model === item.value"
                :aria-controls="panelId(item)"
                :disabled="item.disabled"
                :tabindex="rovingValue === item.value ? 0 : -1"
                :class="
                    cn(
                        'relative min-h-11 shrink-0 px-3 py-2 text-sm font-semibold text-ink-muted transition-[color,background-color] duration-150 outline-none after:absolute after:inset-x-3 after:bottom-0 after:h-0.5 after:bg-transparent after:content-[\'\'] hover:bg-surface-subtle hover:text-ink focus-visible:rounded-t-control focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-focus disabled:cursor-not-allowed disabled:opacity-50',
                        model === item.value &&
                            'text-primary after:bg-primary hover:text-primary',
                    )
                "
                @click="activate(item)"
                @keydown="onKeydown($event, item)"
            >
                {{ item.label }}
            </button>
        </div>

        <div
            v-if="activeItem"
            :id="panelId(activeItem)"
            role="tabpanel"
            :aria-labelledby="tabId(activeItem)"
            tabindex="0"
            class="outline-none focus-visible:rounded-control focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus"
        >
            <slot :value="model" :item="activeItem" />
        </div>
    </div>
</template>
