<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { motion } from 'motion-v';
import AppIcon from '@/components/AppIcon.vue';
import { BaseButton, BaseCard, BaseInput } from '@/components/ui';
import GuestLayout from '@/layouts/GuestLayout.vue';

// Misma entrada sutil al montar que Login.vue, para coherencia entre ambos
// formularios de auth. Ver nota allí sobre por qué motion.div es seguro aquí.
const cardTransition = { duration: 0.45, ease: [0.16, 1, 0.3, 1] as const };

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

function submit(): void {
    form.post('/register', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
}
</script>

<template>
    <GuestLayout>
        <Head title="Crear cuenta" />

        <section
            class="mx-auto grid w-full max-w-6xl gap-10 px-4 py-12 sm:px-6 sm:py-16 lg:grid-cols-[minmax(0,1fr)_29rem] lg:items-center lg:px-8 lg:py-20"
        >
            <motion.div
                :initial="{ opacity: 0, y: 10 }"
                :animate="{ opacity: 1, y: 0 }"
                :transition="cardTransition"
                class="lg:order-2"
            >
                <BaseCard as="div" variant="raised" class="p-6 sm:p-8">
                    <div class="mb-7">
                        <p class="text-sm font-semibold text-primary">
                            Cuenta nueva
                        </p>
                        <h1
                            class="mt-2 text-step-h3 font-semibold tracking-[-0.03em]"
                        >
                            Crea tu acceso a JobHunter
                        </h1>
                        <p class="mt-2 text-sm leading-6 text-ink-muted">
                            Usa un email al que tengas acceso.
                        </p>
                    </div>

                    <form class="grid gap-5" @submit.prevent="submit">
                        <BaseInput
                            v-model="form.name"
                            label="Nombre"
                            autocomplete="name"
                            required
                            :error="form.errors.name"
                        />

                        <BaseInput
                            v-model="form.email"
                            label="Email"
                            type="email"
                            autocomplete="email"
                            required
                            :error="form.errors.email"
                        />

                        <BaseInput
                            v-model="form.password"
                            label="Contraseña"
                            type="password"
                            autocomplete="new-password"
                            required
                            hint="Usa 12 o más caracteres con mayúscula, minúscula, número y símbolo."
                            :error="form.errors.password"
                        />

                        <BaseInput
                            v-model="form.password_confirmation"
                            label="Confirmar contraseña"
                            type="password"
                            autocomplete="new-password"
                            required
                            :error="form.errors.password_confirmation"
                        />

                        <BaseButton
                            type="submit"
                            size="lg"
                            class="w-full"
                            :loading="form.processing"
                            loading-label="Creando cuenta"
                        >
                            Crear cuenta
                        </BaseButton>
                    </form>

                    <p class="mt-6 text-center text-sm text-ink-muted">
                        ¿Ya tienes cuenta?
                        <Link
                            href="/login"
                            class="font-semibold text-primary underline-offset-4 hover:underline focus-visible:rounded-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus"
                        >
                            Inicia sesión
                        </Link>
                    </p>
                </BaseCard>
            </motion.div>

            <div class="max-w-xl lg:order-1">
                <div
                    class="mb-7 inline-flex items-center gap-2 rounded-control bg-primary-subtle px-3 py-2 font-data text-step-eyebrow font-semibold tracking-[0.12em] text-primary uppercase"
                >
                    <AppIcon name="brand" class="h-4 w-4" />
                    Empieza con tu perfil real
                </div>
                <h2
                    class="max-w-lg text-step-h2 leading-[1.04] font-semibold tracking-[-0.04em] text-balance"
                >
                    Convierte cada vacante en una decisión informada.
                </h2>
                <p class="mt-5 max-w-lg text-step-lead text-ink-muted">
                    Crea tu cuenta para encontrar oportunidades afines, medir tu
                    compatibilidad y preparar una versión de CV para cada
                    postulación.
                </p>

                <ol class="mt-9 grid max-w-lg gap-4">
                    <li class="flex gap-4 border-b border-line pb-4">
                        <span
                            class="grid h-8 w-8 shrink-0 place-items-center rounded-control bg-primary-subtle font-data text-sm font-semibold text-primary"
                            >1</span
                        >
                        <div>
                            <p class="font-semibold">Completa tu perfil y CV</p>
                            <p class="mt-1 text-sm leading-6 text-ink-muted">
                                JobHunter usa tu experiencia como referencia.
                            </p>
                        </div>
                    </li>
                    <li class="flex gap-4 border-b border-line pb-4">
                        <span
                            class="grid h-8 w-8 shrink-0 place-items-center rounded-control bg-primary-subtle font-data text-sm font-semibold text-primary"
                            >2</span
                        >
                        <div>
                            <p class="font-semibold">Prioriza por match</p>
                            <p class="mt-1 text-sm leading-6 text-ink-muted">
                                Identifica dónde encaja mejor tu experiencia.
                            </p>
                        </div>
                    </li>
                    <li class="flex gap-4">
                        <span
                            class="grid h-8 w-8 shrink-0 place-items-center rounded-control bg-primary-subtle font-data text-sm font-semibold text-primary"
                            >3</span
                        >
                        <div>
                            <p class="font-semibold">
                                Adapta y haz seguimiento
                            </p>
                            <p class="mt-1 text-sm leading-6 text-ink-muted">
                                Conserva cada variante y el estado de la
                                postulación.
                            </p>
                        </div>
                    </li>
                </ol>
            </div>
        </section>
    </GuestLayout>
</template>
