<script setup lang="ts">
import { AnimatePresence, motion } from 'motion-v';
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import AppIcon from '@/components/AppIcon.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import ToastContainer from '@/components/ToastContainer.vue';
import { usePersistedRef } from '@/lib/persisted';

const collapsed = usePersistedRef('sidebar:collapsed', false);
const mobileOpen = ref(false);
const mobileTrigger = ref<HTMLButtonElement | null>(null);
const mobilePanel = ref<HTMLElement | null>(null);
const mobileClose = ref<HTMLButtonElement | null>(null);
const badges = ref({ marketplace: 0, tracking: 0 });
let previousOverflow = '';

onMounted(loadBadges);
onBeforeUnmount(restoreMobileNavigation);

watch(mobileOpen, async (isOpen) => {
    if (isOpen) {
        previousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        document.addEventListener('keydown', onMobileKeydown, true);
        await nextTick();
        mobileClose.value?.focus();

        return;
    }

    restoreMobileNavigation();
});

async function loadBadges() {
    try {
        const response = await fetch('/api/nav/badges', {
            headers: { Accept: 'application/json' },
        });
        badges.value = await response.json();
    } catch {
        // Badges are a non-critical enhancement; ignore failures.
    }
}

function mobileFocusableElements(): HTMLElement[] {
    if (!mobilePanel.value) {
        return [];
    }

    return Array.from(
        mobilePanel.value.querySelectorAll<HTMLElement>(
            'a[href], button:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])',
        ),
    );
}

function closeMobileNavigation(): void {
    mobileOpen.value = false;
    void nextTick(() => mobileTrigger.value?.focus());
}

function restoreMobileNavigation(): void {
    if (typeof document === 'undefined') {
        return;
    }

    document.body.style.overflow = previousOverflow;
    document.removeEventListener('keydown', onMobileKeydown, true);
}

function onMobileKeydown(event: KeyboardEvent): void {
    if (event.key === 'Escape') {
        event.preventDefault();
        closeMobileNavigation();

        return;
    }

    if (event.key !== 'Tab') {
        return;
    }

    const elements = mobileFocusableElements();

    if (elements.length === 0) {
        event.preventDefault();

        return;
    }

    const firstElement = elements[0];
    const lastElement = elements[elements.length - 1];

    if (event.shiftKey && document.activeElement === firstElement) {
        event.preventDefault();
        lastElement.focus();
    } else if (!event.shiftKey && document.activeElement === lastElement) {
        event.preventDefault();
        firstElement.focus();
    }
}
</script>

<template>
    <div class="flex min-h-screen bg-canvas text-ink">
        <a
            href="#main-content"
            class="fixed top-3 left-3 z-200 -translate-y-20 rounded-control bg-primary px-4 py-3 text-sm font-semibold text-primary-contrast shadow-action transition-transform duration-150 focus:translate-y-0 focus:outline-2 focus:outline-offset-2 focus:outline-focus"
        >
            Ir al contenido
        </a>

        <AnimatePresence>
            <motion.button
                v-if="mobileOpen"
                type="button"
                tabindex="-1"
                aria-label="Cerrar navegación"
                :initial="{ opacity: 0 }"
                :animate="{ opacity: 1 }"
                :exit="{ opacity: 0 }"
                :transition="{ duration: 0.18 }"
                class="fixed inset-0 z-40 bg-black/30 lg:hidden"
                @click="closeMobileNavigation"
            />
        </AnimatePresence>

        <aside
            class="hidden border-r border-slate-800 bg-signal-950 lg:sticky lg:top-0 lg:block lg:h-screen"
            :class="collapsed ? 'lg:w-16' : 'lg:w-56'"
        >
            <AppSidebar
                :collapsed="collapsed"
                :badges="badges"
                @toggle-collapsed="collapsed = !collapsed"
            />
        </aside>

        <!-- El panel carga el ref que usa el focus-trap (mobileFocusableElements),
             así que se anima con <Transition> + CSS, no motion-v: ver la nota en
             BaseDropdown.vue sobre por qué motion.div no es seguro aquí. El
             backdrop de arriba sí es motion-v porque no tiene ref. -->
        <Transition name="mobile-nav">
            <aside
                v-if="mobileOpen"
                id="mobile-navigation"
                ref="mobilePanel"
                class="fixed inset-y-0 left-0 z-50 w-64 border-r border-slate-800 bg-signal-950 shadow-raised lg:hidden"
            >
                <button
                    ref="mobileClose"
                    type="button"
                    class="absolute top-3 right-3 z-10 flex h-11 w-11 items-center justify-center rounded-control text-slate-300 hover:bg-slate-800 hover:text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-signal-200"
                    aria-label="Cerrar navegación"
                    @click="closeMobileNavigation"
                >
                    <span aria-hidden="true" class="text-xl leading-none"
                        >×</span
                    >
                </button>
                <AppSidebar
                    :collapsed="false"
                    :badges="badges"
                    @toggle-collapsed="collapsed = !collapsed"
                    @navigate="closeMobileNavigation"
                />
            </aside>
        </Transition>

        <div class="flex min-h-screen min-w-0 flex-1 flex-col">
            <header
                class="flex items-center gap-3 border-b border-line bg-surface px-4 py-3 lg:hidden"
            >
                <button
                    ref="mobileTrigger"
                    type="button"
                    class="flex h-11 w-11 items-center justify-center rounded-control text-ink-muted hover:bg-surface-subtle hover:text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus"
                    aria-label="Abrir navegación"
                    aria-controls="mobile-navigation"
                    :aria-expanded="mobileOpen"
                    @click="mobileOpen = true"
                >
                    <AppIcon name="menu" class="h-5 w-5" />
                </button>
                <span class="text-lg font-semibold tracking-[-0.04em]"
                    >JobHunter</span
                >
            </header>

            <!-- @container/content: convención para toda la app — las vistas
                 hijas usan @sm/content:, @lg/content:, etc. sin declarar su
                 propio @container ni duplicar breakpoints de viewport. -->
            <main
                id="main-content"
                tabindex="-1"
                class="@container/content min-w-0 flex-1 p-4 sm:p-6 lg:p-8"
            >
                <slot />
            </main>
        </div>

        <ToastContainer />
    </div>
</template>

<style scoped>
.mobile-nav-enter-active,
.mobile-nav-leave-active {
    transition: transform 220ms cubic-bezier(0.16, 1, 0.3, 1);
}

.mobile-nav-enter-from,
.mobile-nav-leave-to {
    transform: translateX(-100%);
}
</style>
