<script setup lang="ts">
import { nextTick, onBeforeUnmount, ref, useId, watch } from 'vue';
import { cn } from '@/lib/utils';

defineOptions({ inheritAttrs: false });

export type OverlayMode = 'modal' | 'drawer';

const props = withDefaults(
    defineProps<{
        open: boolean;
        title: string;
        description?: string;
        mode?: OverlayMode;
        closeLabel?: string;
        closeOnBackdrop?: boolean;
    }>(),
    {
        description: undefined,
        mode: 'modal',
        closeLabel: 'Cerrar',
        closeOnBackdrop: true,
    },
);

const emit = defineEmits<{
    close: [];
}>();

const panel = ref<HTMLElement | null>(null);
const backdrop = ref<HTMLElement | null>(null);
const titleId = useId();
const descriptionId = useId();
let previousFocus: HTMLElement | null = null;
let previousOverflow = '';
let managesDocument = false;
let inertedElements: Array<{
    element: HTMLElement;
    inert: boolean;
    ariaHidden: string | null;
}> = [];

watch(
    () => props.open,
    async (isOpen) => {
        if (typeof document === 'undefined') {
            return;
        }

        if (!isOpen) {
            restoreDocument();

            return;
        }

        previousFocus = document.activeElement as HTMLElement | null;
        previousOverflow = document.body.style.overflow;
        managesDocument = true;
        document.body.style.overflow = 'hidden';
        await nextTick();
        makeBackgroundInert();
        document.addEventListener('keydown', onKeydown, true);
        document.addEventListener('focusin', onDocumentFocusIn, true);
        focusFirstElement();
    },
    { immediate: true },
);

onBeforeUnmount(restoreDocument);

function close(): void {
    emit('close');
}

function restoreDocument(): void {
    if (!managesDocument || typeof document === 'undefined') {
        return;
    }

    document.body.style.overflow = previousOverflow;
    document.removeEventListener('keydown', onKeydown, true);
    document.removeEventListener('focusin', onDocumentFocusIn, true);
    restoreBackground();

    if (previousFocus?.isConnected) {
        previousFocus.focus();
    }

    previousFocus = null;
    managesDocument = false;
}

function makeBackgroundInert(): void {
    if (!backdrop.value) {
        return;
    }

    inertedElements = Array.from(document.body.children)
        .filter(
            (element): element is HTMLElement =>
                element instanceof HTMLElement && element !== backdrop.value,
        )
        .map((element) => {
            const previousState = {
                element,
                inert: element.inert,
                ariaHidden: element.getAttribute('aria-hidden'),
            };

            element.inert = true;
            element.setAttribute('aria-hidden', 'true');

            return previousState;
        });
}

function restoreBackground(): void {
    for (const { element, inert, ariaHidden } of inertedElements) {
        element.inert = inert;

        if (ariaHidden === null) {
            element.removeAttribute('aria-hidden');
        } else {
            element.setAttribute('aria-hidden', ariaHidden);
        }
    }

    inertedElements = [];
}

function focusableElements(): HTMLElement[] {
    if (!panel.value) {
        return [];
    }

    return Array.from(
        panel.value.querySelectorAll<HTMLElement>(
            'a[href], area[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), [contenteditable="true"], [tabindex]:not([tabindex="-1"])',
        ),
    ).filter(
        (element) =>
            element.getAttribute('aria-hidden') !== 'true' && !element.inert,
    );
}

function focusFirstElement(): void {
    const elements = focusableElements();
    (elements[0] ?? panel.value)?.focus();
}

function onBackdropClick(): void {
    if (props.closeOnBackdrop) {
        close();
    }
}

function onDocumentFocusIn(event: FocusEvent): void {
    if (
        props.open &&
        panel.value &&
        event.target instanceof Node &&
        !panel.value.contains(event.target)
    ) {
        focusFirstElement();
    }
}

function onKeydown(event: KeyboardEvent): void {
    if (event.key === 'Escape') {
        event.preventDefault();
        event.stopPropagation();
        close();

        return;
    }

    if (event.key !== 'Tab') {
        return;
    }

    const elements = focusableElements();

    if (elements.length === 0) {
        event.preventDefault();

        return;
    }

    const firstElement = elements[0];
    const lastElement = elements[elements.length - 1];
    const activeElement = document.activeElement;

    if (
        event.shiftKey &&
        (activeElement === firstElement ||
            !panel.value?.contains(activeElement))
    ) {
        event.preventDefault();
        lastElement.focus();
    } else if (
        !event.shiftKey &&
        (activeElement === lastElement || !panel.value?.contains(activeElement))
    ) {
        event.preventDefault();
        firstElement.focus();
    }
}
</script>

<template>
    <Teleport to="body">
        <Transition name="overlay">
            <div
                v-if="open"
                ref="backdrop"
                :class="
                    cn(
                        'fixed inset-0 z-100 flex overflow-hidden overscroll-contain bg-surface-inverse/45',
                        mode === 'drawer'
                            ? 'items-stretch justify-end'
                            : 'items-center justify-center p-3 sm:p-6',
                    )
                "
                @click.self="onBackdropClick"
            >
                <section
                    v-bind="$attrs"
                    ref="panel"
                    role="dialog"
                    aria-modal="true"
                    :aria-labelledby="titleId"
                    :aria-describedby="description ? descriptionId : undefined"
                    tabindex="-1"
                    :class="
                        cn(
                            'overlay-panel flex max-h-full w-full flex-col overflow-hidden border border-line bg-surface-raised text-ink shadow-raised outline-none',
                            mode === 'drawer'
                                ? 'overlay-panel--drawer h-full max-w-md rounded-l-panel border-y-0 border-r-0'
                                : 'overlay-panel--modal max-w-lg rounded-panel',
                        )
                    "
                >
                    <header
                        class="flex items-start justify-between gap-4 border-b border-line px-5 py-4"
                    >
                        <div class="grid gap-1">
                            <h2
                                :id="titleId"
                                class="text-lg font-semibold tracking-[-0.02em]"
                            >
                                {{ title }}
                            </h2>
                            <p
                                v-if="description"
                                :id="descriptionId"
                                class="text-sm leading-5 text-ink-muted"
                            >
                                {{ description }}
                            </p>
                        </div>
                        <button
                            type="button"
                            :aria-label="closeLabel"
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-control text-ink-muted transition-colors hover:bg-surface-subtle hover:text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus"
                            @click="close"
                        >
                            <svg
                                class="h-5 w-5"
                                viewBox="0 0 20 20"
                                fill="none"
                                aria-hidden="true"
                            >
                                <path
                                    d="m5 5 10 10M15 5 5 15"
                                    stroke="currentColor"
                                    stroke-width="1.75"
                                    stroke-linecap="round"
                                />
                            </svg>
                        </button>
                    </header>

                    <div
                        class="min-h-0 flex-1 overflow-y-auto overscroll-contain px-5 py-5"
                    >
                        <slot />
                    </div>

                    <footer
                        v-if="$slots.footer"
                        class="flex flex-wrap justify-end gap-2 border-t border-line bg-surface-subtle px-5 py-4"
                    >
                        <slot name="footer" />
                    </footer>
                </section>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.overlay-enter-active,
.overlay-leave-active {
    transition: opacity 220ms cubic-bezier(0.16, 1, 0.3, 1);
}

.overlay-enter-active .overlay-panel,
.overlay-leave-active .overlay-panel {
    transition:
        opacity 220ms cubic-bezier(0.16, 1, 0.3, 1),
        transform 220ms cubic-bezier(0.16, 1, 0.3, 1);
}

.overlay-enter-from,
.overlay-leave-to {
    opacity: 0;
}

.overlay-enter-from .overlay-panel,
.overlay-leave-to .overlay-panel {
    opacity: 0;
}

.overlay-enter-from .overlay-panel--modal,
.overlay-leave-to .overlay-panel--modal {
    transform: translateY(0.5rem);
}

.overlay-enter-from .overlay-panel--drawer,
.overlay-leave-to .overlay-panel--drawer {
    transform: translateX(1rem);
}
</style>
