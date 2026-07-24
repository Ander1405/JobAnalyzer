<script setup lang="ts">
import { nextTick, onBeforeUnmount, ref, useId, watch } from 'vue';
import { cn } from '@/lib/utils';

export type DropdownActionItem = {
    value: string;
    label: string;
    disabled?: boolean;
    destructive?: boolean;
};

const props = withDefaults(
    defineProps<{
        items?: DropdownActionItem[];
        align?: 'start' | 'end';
        menuLabel?: string;
        triggerLabel?: string;
        disabled?: boolean;
        closeOnSelect?: boolean;
    }>(),
    {
        items: () => [],
        align: 'end',
        menuLabel: 'Menú de acciones',
        triggerLabel: 'Abrir menú de acciones',
        disabled: false,
        closeOnSelect: true,
    },
);

const emit = defineEmits<{
    select: [item: DropdownActionItem];
}>();

const open = defineModel<boolean>('open', { default: false });
const root = ref<HTMLElement | null>(null);
const trigger = ref<HTMLButtonElement | null>(null);
const menu = ref<HTMLElement | null>(null);
const menuStyle = ref<Record<string, string>>({});
const menuId = useId();
let nextFocus: 'first' | 'last' = 'first';
let restoreFocus = true;

const menuItemClass = cn(
    'flex min-h-11 w-full items-center gap-2 rounded-control px-3 py-2 text-left text-sm font-medium text-ink transition-colors duration-150 outline-none hover:bg-surface-subtle focus-visible:bg-primary-subtle focus-visible:text-primary disabled:cursor-not-allowed disabled:text-ink-subtle',
);

watch(open, async (isOpen, wasOpen) => {
    if (typeof document === 'undefined') {
        return;
    }

    if (isOpen) {
        document.addEventListener('pointerdown', onDocumentPointerdown, true);
        window.addEventListener('resize', positionMenu);
        window.addEventListener('scroll', positionMenu, true);
        await nextTick();
        positionMenu();
        focusMenuItem(nextFocus);

        return;
    }

    removeDocumentListener();

    if (wasOpen && restoreFocus) {
        await nextTick();
        trigger.value?.focus();
    }

    restoreFocus = true;
});

onBeforeUnmount(removeDocumentListener);

function removeDocumentListener(): void {
    if (typeof document !== 'undefined') {
        document.removeEventListener(
            'pointerdown',
            onDocumentPointerdown,
            true,
        );
        window.removeEventListener('resize', positionMenu);
        window.removeEventListener('scroll', positionMenu, true);
    }
}

function positionMenu(): void {
    if (!trigger.value || !menu.value) {
        return;
    }

    const triggerRect = trigger.value.getBoundingClientRect();
    const menuWidth = menu.value.offsetWidth;
    const menuHeight = menu.value.offsetHeight;
    const viewportPadding = 8;
    const gap = 6;
    const preferredLeft =
        props.align === 'end'
            ? triggerRect.right - menuWidth
            : triggerRect.left;
    const left = Math.min(
        Math.max(preferredLeft, viewportPadding),
        window.innerWidth - menuWidth - viewportPadding,
    );
    const fitsBelow =
        triggerRect.bottom + gap + menuHeight <=
        window.innerHeight - viewportPadding;
    const top = fitsBelow
        ? triggerRect.bottom + gap
        : Math.max(viewportPadding, triggerRect.top - menuHeight - gap);

    menuStyle.value = {
        top: `${Math.round(top)}px`,
        left: `${Math.round(left)}px`,
    };
}

function menuItems(): HTMLElement[] {
    if (!menu.value) {
        return [];
    }

    return Array.from(
        menu.value.querySelectorAll<HTMLElement>(
            '[role="menuitem"]:not([aria-disabled="true"]):not(:disabled)',
        ),
    );
}

function focusMenuItem(position: 'first' | 'last'): void {
    const items = menuItems();
    const item = position === 'last' ? items.at(-1) : items[0];

    (item ?? menu.value)?.focus();
}

function openMenu(position: 'first' | 'last' = 'first'): void {
    if (props.disabled) {
        return;
    }

    nextFocus = position;
    open.value = true;
}

function closeMenu(shouldRestoreFocus = true): void {
    restoreFocus = shouldRestoreFocus;
    open.value = false;
}

function toggleMenu(): void {
    if (open.value) {
        closeMenu();
    } else {
        openMenu();
    }
}

function selectItem(item: DropdownActionItem): void {
    if (item.disabled) {
        return;
    }

    emit('select', item);

    if (props.closeOnSelect) {
        closeMenu();
    }
}

function onDocumentPointerdown(event: PointerEvent): void {
    const target = event.target as Node;

    if (!root.value?.contains(target) && !menu.value?.contains(target)) {
        closeMenu();
    }
}

function onTriggerKeydown(event: KeyboardEvent): void {
    if (event.key === 'ArrowDown') {
        event.preventDefault();
        openMenu('first');
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        openMenu('last');
    } else if (event.key === 'Escape' && open.value) {
        event.preventDefault();
        closeMenu();
    }
}

function onMenuKeydown(event: KeyboardEvent): void {
    if (event.key === 'Escape') {
        event.preventDefault();
        event.stopPropagation();
        closeMenu();

        return;
    }

    if (event.key === 'Tab') {
        closeMenu(false);

        return;
    }

    const items = menuItems();

    if (items.length === 0) {
        return;
    }

    const currentIndex = items.indexOf(document.activeElement as HTMLElement);
    let nextItem: HTMLElement | undefined;

    if (event.key === 'Home') {
        nextItem = items[0];
    } else if (event.key === 'End') {
        nextItem = items.at(-1);
    } else if (event.key === 'ArrowDown') {
        nextItem = items[(Math.max(currentIndex, -1) + 1) % items.length];
    } else if (event.key === 'ArrowUp') {
        nextItem = items[(currentIndex <= 0 ? items.length : currentIndex) - 1];
    }

    if (!nextItem) {
        return;
    }

    event.preventDefault();
    nextItem.focus();
}
</script>

<template>
    <div ref="root" class="relative inline-flex">
        <button
            ref="trigger"
            type="button"
            :disabled="disabled"
            :aria-label="triggerLabel"
            aria-haspopup="menu"
            :aria-expanded="open"
            :aria-controls="menuId"
            class="inline-flex min-h-11 items-center justify-center gap-2 rounded-control border border-line-strong bg-surface px-3 py-2 text-sm font-semibold text-ink shadow-card transition-[color,background-color,border-color,box-shadow] duration-150 outline-none hover:border-primary hover:bg-primary-subtle hover:text-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus disabled:cursor-not-allowed disabled:opacity-50"
            @click="toggleMenu"
            @keydown="onTriggerKeydown"
        >
            <slot name="trigger" :open="open">Acciones</slot>
        </button>

        <Teleport to="body">
            <Transition name="dropdown-menu">
                <div
                    v-if="open"
                    :id="menuId"
                    ref="menu"
                    role="menu"
                    :aria-label="menuLabel"
                    tabindex="-1"
                    :style="menuStyle"
                    class="fixed z-110 grid max-w-80 min-w-48 gap-0.5 rounded-card border border-line bg-surface-raised p-1.5 shadow-raised outline-none"
                    @keydown="onMenuKeydown"
                >
                    <slot
                        v-if="$slots.items"
                        name="items"
                        :close="closeMenu"
                        :item-class="menuItemClass"
                    />
                    <template v-else>
                        <button
                            v-for="item in items"
                            :key="item.value"
                            type="button"
                            role="menuitem"
                            tabindex="-1"
                            :disabled="item.disabled"
                            :class="
                                cn(
                                    menuItemClass,
                                    item.destructive &&
                                        'text-error hover:bg-error-surface focus-visible:bg-error-surface focus-visible:text-error',
                                )
                            "
                            @click="selectItem(item)"
                        >
                            <slot name="item" :item="item">
                                <span class="min-w-0 flex-1 truncate">{{
                                    item.label
                                }}</span>
                            </slot>
                        </button>
                    </template>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<style scoped>
/* El menú carga el ref que usan positionMenu/menuItems/onMenuKeydown, así que
   se anima con <Transition> + CSS (mismo patrón que BaseOverlay.vue) en vez
   de motion-v: motion.div expone la instancia del componente por ref, no el
   nodo DOM, y hubiera obligado a reescribir ese acceso directo al elemento. */
.dropdown-menu-enter-active,
.dropdown-menu-leave-active {
    transition:
        opacity 140ms cubic-bezier(0.16, 1, 0.3, 1),
        transform 140ms cubic-bezier(0.16, 1, 0.3, 1);
}

.dropdown-menu-enter-from,
.dropdown-menu-leave-to {
    opacity: 0;
    transform: translateY(-0.25rem) scale(0.98);
}
</style>
