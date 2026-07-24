<!--
THESIS: La búsqueda se presenta como una mesa de decisiones, no como otro listado de empleos.
OWN-WORLD: Superficies pizarra, cobalto de acción, divisores finos y datos de compatibilidad instrumentales.
STORY: El visitante descubre una vacante, entiende el encaje, adapta su CV y mantiene el seguimiento.
FIRST VIEWPORT: La propuesta y las acciones ocupan la izquierda; a la derecha, una vacante atraviesa el flujo real de JobHunter.
FORM: Recorrido de señal a postulación, con una demostración operativa como prueba principal. Seed 37b1f434.
-->
<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { motion } from 'motion-v';
import AppIcon from '@/components/AppIcon.vue';
import { BaseButton, MatchScore } from '@/components/ui';
import GuestLayout from '@/layouts/GuestLayout.vue';

// Reveal en scroll (fade + translateY 14px, como .reveal del sistema visual).
// motion.* hereda reducedMotion="user" del MotionConfig global en app.ts, así
// que no necesita un check manual de prefers-reduced-motion aquí.
const revealInitial = { opacity: 0, y: 14 };
const revealInView = { opacity: 1, y: 0 };
const revealTransition = { duration: 0.6, ease: [0.16, 1, 0.3, 1] as const };
const revealViewport = { once: true, margin: '-80px' };
</script>

<template>
    <GuestLayout>
        <Head title="Vacantes que encajan contigo" />

        <section class="overflow-hidden border-b border-line bg-surface">
            <div
                class="mx-auto grid w-full max-w-7xl gap-12 px-4 py-14 sm:px-6 sm:py-20 lg:grid-cols-[minmax(0,0.92fr)_minmax(32rem,1.08fr)] lg:items-center lg:gap-16 lg:px-8 lg:py-24"
            >
                <div class="max-w-2xl">
                    <p
                        class="mb-6 inline-flex items-center gap-2 rounded-control bg-primary-subtle px-3 py-2 font-data text-step-eyebrow font-semibold tracking-[0.12em] text-primary uppercase"
                    >
                        <span
                            class="h-2 w-2 rounded-full bg-primary"
                            aria-hidden="true"
                        />
                        Tu búsqueda laboral, con criterio
                    </p>
                    <h1
                        class="text-step-h1 leading-[0.98] font-semibold tracking-[-0.04em] text-balance"
                    >
                        Vacantes que
                        <span
                            class="bg-gradient-to-r from-primary to-score-excellent bg-clip-text text-transparent"
                            >encajan</span
                        >. Un CV que responde.
                    </h1>
                    <p class="mt-7 max-w-xl text-step-lead text-ink-muted">
                        JobHunter encuentra oportunidades afines a tu perfil,
                        explica el match y te ayuda a adaptar tu CV para cada
                        postulación.
                    </p>
                    <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                        <BaseButton
                            :as="Link"
                            href="/register"
                            size="lg"
                            class="sm:min-w-40"
                        >
                            Crear cuenta
                            <template #trailing>
                                <span aria-hidden="true">→</span>
                            </template>
                        </BaseButton>
                        <BaseButton
                            :as="Link"
                            href="/login"
                            variant="secondary"
                            size="lg"
                            class="sm:min-w-40"
                        >
                            Iniciar sesión
                        </BaseButton>
                    </div>
                    <p class="mt-5 text-sm leading-6 text-ink-subtle">
                        Del descubrimiento al seguimiento, sin perder versiones
                        ni contexto.
                    </p>
                </div>

                <div
                    class="dark relative overflow-hidden rounded-panel bg-signal-950 text-slate-100 shadow-raised"
                    aria-label="Ejemplo ilustrativo del análisis de una vacante"
                >
                    <div
                        class="flex items-center justify-between gap-4 border-b border-slate-800 px-5 py-4 sm:px-6"
                    >
                        <div class="flex items-center gap-2.5">
                            <span
                                class="h-2 w-2 rounded-full bg-score-excellent"
                                aria-hidden="true"
                            />
                            <span class="text-sm font-semibold"
                                >Análisis de oportunidad</span
                            >
                        </div>
                        <span class="font-data text-xs text-slate-400"
                            >Ejemplo ilustrativo</span
                        >
                    </div>

                    <div
                        class="grid justify-items-center gap-6 p-5 text-center sm:p-7"
                    >
                        <!-- SIGNATURE: el dial de match-score, pieza central del hero -->
                        <MatchScore :score="87" size="hero" />
                        <div>
                            <p class="text-sm font-medium text-signal-200">
                                Vacante encontrada
                            </p>
                            <h2
                                class="mt-2 text-step-h3 font-semibold tracking-[-0.03em] text-white"
                            >
                                Frontend Engineer
                            </h2>
                            <p class="mt-1 text-sm text-slate-400">
                                Remoto · Producto digital
                            </p>
                            <div
                                class="mt-5 flex flex-wrap justify-center gap-2"
                            >
                                <span
                                    class="rounded-control border border-slate-700 bg-slate-900 px-2.5 py-1.5 text-xs font-semibold text-slate-200"
                                    >Vue 3</span
                                >
                                <span
                                    class="rounded-control border border-slate-700 bg-slate-900 px-2.5 py-1.5 text-xs font-semibold text-slate-200"
                                    >TypeScript</span
                                >
                                <span
                                    class="rounded-control border border-slate-700 bg-slate-900 px-2.5 py-1.5 text-xs font-semibold text-slate-200"
                                    >Producto</span
                                >
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-slate-800 px-5 sm:px-7">
                        <div
                            class="grid grid-cols-[1.5rem_minmax(0,1fr)_auto] items-center gap-3 border-b border-slate-800 py-4"
                        >
                            <span
                                class="grid h-6 w-6 place-items-center rounded-md bg-score-excellent-surface font-data text-xs font-semibold text-score-excellent"
                                >✓</span
                            >
                            <div>
                                <p class="text-sm font-semibold text-white">
                                    Match explicado
                                </p>
                                <p class="mt-0.5 text-xs text-slate-400">
                                    Fortalezas y brechas visibles
                                </p>
                            </div>
                            <span
                                class="font-data text-xs font-semibold text-score-excellent"
                                >Listo</span
                            >
                        </div>
                        <div
                            class="grid grid-cols-[1.5rem_minmax(0,1fr)_auto] items-center gap-3 border-b border-slate-800 py-4"
                        >
                            <span
                                class="grid h-6 w-6 place-items-center rounded-md bg-score-very-good-surface font-data text-xs font-semibold text-score-very-good"
                                >02</span
                            >
                            <div>
                                <p class="text-sm font-semibold text-white">
                                    CV adaptado a la vacante
                                </p>
                                <p class="mt-0.5 text-xs text-slate-400">
                                    Nueva variante, original intacto
                                </p>
                            </div>
                            <span
                                class="font-data text-xs font-semibold text-score-very-good"
                                >Variante</span
                            >
                        </div>
                        <div
                            class="grid grid-cols-[1.5rem_minmax(0,1fr)_auto] items-center gap-3 py-4"
                        >
                            <span
                                class="grid h-6 w-6 place-items-center rounded-md bg-warning-surface font-data text-xs font-semibold text-warning"
                                >03</span
                            >
                            <div>
                                <p class="text-sm font-semibold text-white">
                                    Postulación en seguimiento
                                </p>
                                <p class="mt-0.5 text-xs text-slate-400">
                                    Estado, notas y próxima acción
                                </p>
                            </div>
                            <span
                                class="font-data text-xs font-semibold text-warning"
                                >En curso</span
                            >
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section
            id="funciones"
            class="mx-auto w-full max-w-7xl px-4 py-16 sm:px-6 sm:py-24 lg:px-8"
            aria-labelledby="features-title"
        >
            <div
                class="grid gap-6 border-b border-line pb-10 lg:grid-cols-[minmax(0,0.8fr)_minmax(24rem,1.2fr)] lg:items-end"
            >
                <h2
                    id="features-title"
                    class="max-w-xl text-step-h2 leading-tight font-semibold tracking-[-0.04em] text-balance"
                >
                    Una sola ruta para decidir, preparar y avanzar.
                </h2>
                <p class="max-w-2xl text-step-lead text-ink-muted lg:ml-auto">
                    JobHunter conecta las piezas que suelen quedar separadas:
                    descubrir vacantes, evaluar el encaje, adaptar el CV y
                    recordar qué sigue.
                </p>
            </div>

            <!-- @container/features: la grilla de tarjetas reflow según SU ancho,
                 no el del viewport — coherente con GuestLayout, que ya expone
                 @container/content en <main>. -->
            <div
                class="@container/features mt-10 grid gap-5 @4xl/features:grid-cols-12"
            >
                <motion.article
                    :initial="revealInitial"
                    :while-in-view="revealInView"
                    :viewport="revealViewport"
                    :transition="revealTransition"
                    class="flex flex-col overflow-hidden rounded-panel bg-signal-950 text-slate-100 shadow-raised @4xl/features:col-span-7 @4xl/features:row-span-2"
                >
                    <div class="p-6 sm:p-8">
                        <AppIcon
                            name="marketplace"
                            class="h-7 w-7 text-signal-200"
                        />
                        <h3
                            class="mt-8 text-step-h3 font-semibold tracking-[-0.03em] text-white"
                        >
                            Marketplace con match por IA
                        </h3>
                        <p
                            class="mt-3 max-w-xl text-base leading-7 text-slate-300"
                        >
                            Explora vacantes y usa la compatibilidad para
                            concentrarte en las oportunidades donde tu perfil
                            tiene más sentido.
                        </p>
                    </div>
                    <div class="border-t border-slate-800 bg-slate-900/60">
                        <div
                            class="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-4 border-b border-slate-800 px-6 py-5 sm:px-8"
                        >
                            <div>
                                <p class="font-semibold text-white">
                                    Product Engineer
                                </p>
                                <p class="mt-1 text-sm text-slate-400">
                                    Remoto · SaaS
                                </p>
                            </div>
                            <MatchScore
                                :score="91"
                                size="compact"
                                :animate="false"
                            />
                        </div>
                        <div
                            class="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-4 px-6 py-5 sm:px-8"
                        >
                            <div>
                                <p class="font-semibold text-white">
                                    Senior Frontend Developer
                                </p>
                                <p class="mt-1 text-sm text-slate-400">
                                    Híbrido · Fintech
                                </p>
                            </div>
                            <MatchScore
                                :score="84"
                                size="compact"
                                :animate="false"
                            />
                        </div>
                    </div>
                    <div class="flex-1 border-t border-slate-800 p-6 sm:p-8">
                        <p class="text-sm font-semibold text-signal-200">
                            Señales de encaje
                        </p>
                        <div class="mt-5 grid gap-4 sm:grid-cols-3">
                            <div>
                                <p
                                    class="font-data text-lg font-semibold text-score-excellent"
                                >
                                    Fuerte
                                </p>
                                <p class="mt-1 text-sm text-slate-400">
                                    Experiencia relevante
                                </p>
                            </div>
                            <div>
                                <p
                                    class="font-data text-lg font-semibold text-score-very-good"
                                >
                                    Alta
                                </p>
                                <p class="mt-1 text-sm text-slate-400">
                                    Afinidad de stack
                                </p>
                            </div>
                            <div>
                                <p
                                    class="font-data text-lg font-semibold text-warning"
                                >
                                    Revisar
                                </p>
                                <p class="mt-1 text-sm text-slate-400">
                                    Brechas detectadas
                                </p>
                            </div>
                        </div>
                    </div>
                </motion.article>

                <motion.article
                    :initial="revealInitial"
                    :while-in-view="revealInView"
                    :viewport="revealViewport"
                    :transition="revealTransition"
                    class="rounded-panel border border-line bg-surface p-6 shadow-card sm:p-8 @4xl/features:col-span-5"
                >
                    <div class="flex items-start justify-between gap-5">
                        <div>
                            <AppIcon
                                name="tracking"
                                class="h-7 w-7 text-primary"
                            />
                            <h3
                                class="mt-6 text-step-h3 font-semibold tracking-[-0.03em]"
                            >
                                Tracking sin hojas sueltas
                            </h3>
                        </div>
                        <span
                            class="rounded-control bg-success-surface px-2.5 py-1.5 text-xs font-semibold text-success"
                            >Próxima acción</span
                        >
                    </div>
                    <p class="mt-3 leading-7 text-ink-muted">
                        Guarda cada vacante en “Mis vacantes” y registra estado,
                        notas y seguimiento desde el primer contacto.
                    </p>
                    <div class="mt-7 border-t border-line pt-5">
                        <div class="flex items-center gap-3">
                            <span
                                class="h-3 w-3 rounded-full border-2 border-primary bg-surface"
                                aria-hidden="true"
                            />
                            <span class="text-sm font-semibold">Aplicada</span>
                            <span
                                class="ml-auto font-data text-xs text-ink-subtle"
                                >Hoy</span
                            >
                        </div>
                        <div class="mt-4 flex items-center gap-3">
                            <span
                                class="h-3 w-3 rounded-full border-2 border-line-strong bg-surface"
                                aria-hidden="true"
                            />
                            <span class="text-sm text-ink-muted"
                                >Seguimiento pendiente</span
                            >
                            <span
                                class="ml-auto font-data text-xs text-ink-subtle"
                                >+3 días</span
                            >
                        </div>
                    </div>
                </motion.article>

                <motion.article
                    :initial="revealInitial"
                    :while-in-view="revealInView"
                    :viewport="revealViewport"
                    :transition="revealTransition"
                    class="rounded-panel border border-line bg-primary-subtle p-6 sm:p-8 @4xl/features:col-span-5"
                >
                    <AppIcon name="profile" class="h-7 w-7 text-primary" />
                    <h3
                        class="mt-6 text-step-h3 font-semibold tracking-[-0.03em]"
                    >
                        Un CV base. Variantes con propósito.
                    </h3>
                    <p class="mt-3 leading-7 text-ink-muted">
                        Adapta el contenido a una vacante, revisa el encaje ATS
                        y conserva variantes sin sobrescribir tu CV original.
                    </p>
                    <div class="mt-7 grid gap-2 sm:grid-cols-3 lg:grid-cols-1">
                        <div
                            class="flex items-center justify-between gap-3 rounded-control bg-surface px-3 py-2.5 text-sm shadow-card"
                        >
                            <span class="font-semibold">CV original</span>
                            <span class="text-xs text-ink-subtle">Base</span>
                        </div>
                        <div
                            class="flex items-center justify-between gap-3 rounded-control bg-surface px-3 py-2.5 text-sm shadow-card"
                        >
                            <span class="font-semibold">Variante producto</span>
                            <span class="text-xs font-semibold text-primary"
                                >Tailored</span
                            >
                        </div>
                        <div
                            class="flex items-center justify-between gap-3 rounded-control bg-surface px-3 py-2.5 text-sm shadow-card"
                        >
                            <span class="font-semibold">Chequeo ATS</span>
                            <span class="text-xs font-semibold text-success"
                                >Revisado</span
                            >
                        </div>
                    </div>
                </motion.article>
            </div>
            <p class="mt-4 text-right text-xs text-ink-subtle">
                Datos mostrados con fines ilustrativos.
            </p>
        </section>

        <section class="border-y border-slate-800 bg-signal-950 text-white">
            <div
                class="mx-auto grid w-full max-w-7xl gap-8 px-4 py-14 sm:px-6 sm:py-16 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center lg:px-8"
            >
                <motion.div
                    :initial="revealInitial"
                    :while-in-view="revealInView"
                    :viewport="revealViewport"
                    :transition="revealTransition"
                >
                    <h2
                        class="max-w-2xl text-step-h2 leading-tight font-semibold tracking-[-0.04em] text-balance"
                    >
                        Tu próxima postulación puede empezar con más contexto.
                    </h2>
                    <p class="mt-4 max-w-2xl text-lg leading-8 text-slate-300">
                        Crea tu cuenta, completa tu perfil y convierte el ruido
                        del mercado en una lista de decisiones claras.
                    </p>
                </motion.div>
                <BaseButton
                    :as="Link"
                    href="/register"
                    size="lg"
                    class="w-full lg:w-auto lg:min-w-44"
                >
                    Crear mi cuenta
                    <template #trailing>
                        <span aria-hidden="true">→</span>
                    </template>
                </BaseButton>
            </div>
        </section>

        <template #footer>
            <footer class="bg-surface">
                <div
                    class="mx-auto flex w-full max-w-7xl flex-col gap-5 px-4 py-8 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8"
                >
                    <Link
                        href="/"
                        class="inline-flex w-fit items-center gap-2 rounded-control font-semibold tracking-[-0.03em] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-focus"
                    >
                        <AppIcon name="brand" class="h-5 w-5 text-primary" />
                        JobHunter
                    </Link>
                    <p class="text-sm text-ink-muted">
                        Encuentra el encaje. Adapta tu CV. Sigue avanzando.
                    </p>
                    <div class="flex items-center gap-4 text-sm">
                        <Link
                            href="/login"
                            class="rounded-sm font-semibold text-ink-muted hover:text-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus"
                            >Iniciar sesión</Link
                        >
                        <Link
                            href="/register"
                            class="rounded-sm font-semibold text-primary underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus"
                            >Crear cuenta</Link
                        >
                    </div>
                </div>
            </footer>
        </template>
    </GuestLayout>
</template>
