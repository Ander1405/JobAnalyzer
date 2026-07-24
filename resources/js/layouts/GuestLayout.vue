<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppIcon from '@/components/AppIcon.vue';
import ThemeSwitcher from '@/components/ThemeSwitcher.vue';
import { BaseButton } from '@/components/ui';

const page = usePage();
const isLoginPage = computed(() => page.url.startsWith('/login'));
const isRegistrationPage = computed(() => page.url.startsWith('/register'));
const showSecondaryLogin = computed(
    () => !isLoginPage.value && !isRegistrationPage.value,
);
const primaryAction = computed(() =>
    isRegistrationPage.value
        ? { href: '/login', label: 'Iniciar sesión' }
        : { href: '/register', label: 'Crear cuenta' },
);
</script>

<template>
    <div class="flex min-h-screen flex-col bg-canvas text-ink">
        <a
            href="#main-content"
            class="fixed top-3 left-3 z-50 -translate-y-20 rounded-control bg-primary px-4 py-3 text-sm font-semibold text-primary-contrast shadow-action transition-transform focus:translate-y-0 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus"
        >
            Ir al contenido
        </a>

        <header class="border-b border-slate-800 bg-signal-950 text-white">
            <div
                class="mx-auto flex min-h-18 w-full max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8"
            >
                <Link
                    href="/"
                    class="inline-flex items-center gap-2 rounded-control text-lg font-semibold tracking-[-0.03em] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-signal-200"
                    aria-label="JobHunter, inicio"
                >
                    <span
                        class="grid h-9 w-9 place-items-center rounded-control border border-slate-700 bg-slate-900 text-signal-200"
                    >
                        <AppIcon name="brand" class="h-5 w-5" />
                    </span>
                    JobHunter
                </Link>

                <nav
                    class="flex items-center gap-1.5 sm:gap-2"
                    aria-label="Acceso"
                >
                    <span v-if="showSecondaryLogin" class="hidden sm:block">
                        <BaseButton
                            :as="Link"
                            href="/login"
                            variant="quiet"
                            size="sm"
                            class="text-slate-200 hover:bg-slate-800 hover:text-white"
                        >
                            Iniciar sesión
                        </BaseButton>
                    </span>
                    <BaseButton :as="Link" :href="primaryAction.href" size="sm">
                        {{ primaryAction.label }}
                    </BaseButton>
                    <ThemeSwitcher :collapsed="true" />
                </nav>
            </div>
        </header>

        <main id="main-content" tabindex="-1" class="flex-1 outline-none">
            <slot />
        </main>

        <slot name="footer" />
    </div>
</template>
