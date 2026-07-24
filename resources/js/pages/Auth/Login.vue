<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { motion } from 'motion-v';
import AppIcon from '@/components/AppIcon.vue';
import { BaseButton, BaseCard, BaseInput } from '@/components/ui';
import GuestLayout from '@/layouts/GuestLayout.vue';

// Entrada sutil al montar (no en scroll: el formulario ya está en el
// primer viewport). motion.div hereda reducedMotion="user" del
// MotionConfig global, y BaseCard no depende de su propio ref, así que
// envolverlo es seguro (ver nota en CLAUDE.md sobre focus-trap/refs).
const cardTransition = { duration: 0.45, ease: [0.16, 1, 0.3, 1] as const };

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

function submit() {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <GuestLayout>
        <Head title="Iniciar sesión" />

        <section
            class="mx-auto grid w-full max-w-6xl gap-10 px-4 py-12 sm:px-6 sm:py-16 lg:grid-cols-[minmax(0,1fr)_26rem] lg:items-center lg:px-8 lg:py-24"
        >
            <div class="max-w-xl">
                <div
                    class="mb-7 inline-flex items-center gap-2 rounded-control bg-primary-subtle px-3 py-2 font-data text-step-eyebrow font-semibold tracking-[0.12em] text-primary uppercase"
                >
                    <AppIcon name="brand" class="h-4 w-4" />
                    Tu búsqueda, bajo control
                </div>
                <h1
                    class="max-w-lg text-step-h2 leading-[1.04] font-semibold tracking-[-0.04em] text-balance"
                >
                    Retoma tu próxima oportunidad.
                </h1>
                <p class="mt-5 max-w-lg text-step-lead text-ink-muted">
                    Revisa tus mejores matches, continúa tus postulaciones y
                    adapta tu CV sin perder el contexto.
                </p>
                <div
                    class="mt-9 grid max-w-lg gap-px overflow-hidden rounded-card border border-line bg-line sm:grid-cols-3"
                >
                    <div class="bg-surface p-4">
                        <p class="font-data text-lg font-semibold text-primary">
                            Match
                        </p>
                        <p class="mt-1 text-sm text-ink-muted">
                            Prioriza mejor
                        </p>
                    </div>
                    <div class="bg-surface p-4">
                        <p class="font-data text-lg font-semibold text-primary">
                            Tracking
                        </p>
                        <p class="mt-1 text-sm text-ink-muted">
                            Sigue el avance
                        </p>
                    </div>
                    <div class="bg-surface p-4">
                        <p class="font-data text-lg font-semibold text-primary">
                            CV + ATS
                        </p>
                        <p class="mt-1 text-sm text-ink-muted">Adapta con IA</p>
                    </div>
                </div>
            </div>

            <motion.div
                :initial="{ opacity: 0, y: 10 }"
                :animate="{ opacity: 1, y: 0 }"
                :transition="cardTransition"
            >
                <BaseCard as="div" variant="raised" class="p-6 sm:p-8">
                    <div class="mb-7">
                        <p class="text-sm font-semibold text-primary">
                            Bienvenido
                        </p>
                        <h2
                            class="mt-2 text-step-h3 font-semibold tracking-[-0.03em]"
                        >
                            Inicia sesión en JobHunter
                        </h2>
                    </div>

                    <form class="grid gap-5" @submit.prevent="submit">
                        <BaseInput
                            v-model="form.email"
                            label="Email"
                            type="email"
                            autocomplete="username"
                            required
                            :error="form.errors.email"
                        />

                        <BaseInput
                            v-model="form.password"
                            label="Contraseña"
                            type="password"
                            autocomplete="current-password"
                            required
                            :error="form.errors.password"
                        />

                        <label
                            class="flex w-fit items-center gap-2.5 text-sm font-medium text-ink-muted"
                        >
                            <input
                                v-model="form.remember"
                                type="checkbox"
                                class="h-4 w-4 rounded border-line-strong accent-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus"
                            />
                            Recordarme
                        </label>

                        <BaseButton
                            type="submit"
                            size="lg"
                            class="w-full"
                            :loading="form.processing"
                            loading-label="Iniciando sesión"
                        >
                            Iniciar sesión
                        </BaseButton>
                    </form>

                    <p class="mt-6 text-center text-sm text-ink-muted">
                        ¿No tienes cuenta?
                        <Link
                            href="/register"
                            class="font-semibold text-primary underline-offset-4 hover:underline focus-visible:rounded-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus"
                        >
                            Regístrate
                        </Link>
                    </p>
                </BaseCard>
            </motion.div>
        </section>
    </GuestLayout>
</template>
