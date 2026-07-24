<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { AnimatePresence, motion } from 'motion-v';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import AppIcon from '@/components/AppIcon.vue';
import {
    BaseAvatar,
    BaseButton,
    BaseCard,
    BaseDrawer,
    BaseDropdown,
    BaseInput,
    BaseModal,
    BaseSelect,
    BaseSkeleton,
    BaseTabs,
    BaseTag,
    BaseTextarea,
    BaseToast,
    BaseTooltip,
    CompanyLogo,
    EmptyState,
    MatchScore,
} from '@/components/ui';
import type { DropdownActionItem, TabsItem } from '@/components/ui';
import {
    getStoredThemePreference,
    setThemePreference,
    THEME_CHANGE_EVENT,
} from '@/lib/theme';
import type { ThemePreference } from '@/lib/theme';

type ViewState = 'loading' | 'empty' | 'content' | 'error';

const themePreference = ref<ThemePreference>('system');
const role = ref('Frontend Engineer');
const source = ref<string | number | null>('jsearch');
const notes = ref('Priorizar experiencia con Vue 3 y accesibilidad.');
const activeTab = ref('signals');
const viewState = ref<ViewState>('loading');
const modalOpen = ref(false);
const drawerOpen = ref(false);
const toastVisible = ref(true);
const errorToastVisible = ref(true);
const infoToastVisible = ref(true);
const dropdownMessage = ref('Ninguna acción seleccionada.');

// :key fuerza el remount de MatchScore, que dispara de nuevo su animación de
// conteo (motion-v) en el watch({ immediate: true }) — así "Repetir animación"
// no necesita duplicar la lógica de animate() que ya vive en el componente.
const motionReplayKey = ref(0);
const revealPanelOpen = ref(false);

const themeOptions: Array<{ value: ThemePreference; label: string }> = [
    { value: 'light', label: 'Claro' },
    { value: 'dark', label: 'Oscuro' },
    { value: 'system', label: 'Sistema' },
];

const sourceOptions = [
    { value: 'jsearch', label: 'JSearch' },
    { value: 'infojobs', label: 'InfoJobs' },
    { value: 'larajobs', label: 'LaraJobs RSS' },
];

const showcaseTabs: TabsItem[] = [
    { value: 'signals', label: 'Señales' },
    { value: 'evidence', label: 'Evidencia' },
    { value: 'history', label: 'Historial' },
    { value: 'disabled', label: 'No disponible', disabled: true },
];

const stateTabs: TabsItem[] = [
    { value: 'loading', label: 'Cargando' },
    { value: 'empty', label: 'Vacío' },
    { value: 'content', label: 'Con datos' },
    { value: 'error', label: 'Error' },
];

const dropdownItems: DropdownActionItem[] = [
    { value: 'duplicate', label: 'Clonar variante' },
    { value: 'archive', label: 'Archivar muestra' },
    { value: 'delete', label: 'Eliminar muestra', destructive: true },
];

const palette = [
    {
        name: 'Canvas',
        light: '#F2F5FB',
        dark: '#08101F',
        className: 'bg-canvas',
    },
    {
        name: 'Superficie',
        light: '#FFFFFF',
        dark: '#0E182B',
        className: 'bg-surface',
    },
    {
        name: 'Primario',
        light: '#1749E9',
        dark: '#7898FF',
        className: 'bg-primary',
    },
    {
        name: 'Secundario',
        light: '#44546F',
        dark: '#B6C4D8',
        className: 'bg-secondary',
    },
    {
        name: 'Borde',
        light: '#CED6E4',
        dark: '#2B3A57',
        className: 'bg-line',
    },
];

const scorePalette = [
    {
        label: 'Excelente',
        range: '85–100',
        className: 'bg-score-excellent text-score-excellent-surface',
    },
    {
        label: 'Muy bueno',
        range: '75–84',
        className: 'bg-score-very-good text-score-very-good-surface',
    },
    {
        label: 'Aceptable',
        range: '60–74',
        className: 'bg-score-acceptable text-score-acceptable-surface',
    },
    {
        label: 'Bajo',
        range: '0–59',
        className: 'bg-score-low text-score-low-surface',
    },
];

// Cada fila renderiza su propia utilidad text-step-*: son los mismos tokens
// --step-* (clamp()) espejados en app.css para que Tailwind los genere.
const typeScale = [
    {
        tag: 'Metric',
        token: '--step-metric',
        textClass: 'font-data text-step-metric font-semibold tracking-tight',
        sample: '$128k',
    },
    {
        tag: 'H1',
        token: '--step-h1',
        textClass: 'text-step-h1 font-semibold tracking-tight',
        sample: 'Encuentra tu match',
    },
    {
        tag: 'H2',
        token: '--step-h2',
        textClass: 'text-step-h2 font-semibold tracking-tight',
        sample: 'Vacantes recomendadas',
    },
    {
        tag: 'H3',
        token: '--step-h3',
        textClass: 'text-step-h3 font-semibold',
        sample: 'Resumen del perfil',
    },
    {
        tag: 'Lead',
        token: '--step-lead',
        textClass: 'text-step-lead text-ink-muted',
        sample: 'Ordenadas por afinidad con tu perfil.',
    },
    {
        tag: 'Body',
        token: '--step-body',
        textClass: 'text-step-body',
        sample: 'El cuerpo base se mantiene legible de 320px a 4K sin tocar una media query.',
    },
    {
        tag: 'Eyebrow',
        token: '--step-eyebrow',
        textClass:
            'text-step-eyebrow font-semibold tracking-[0.08em] text-ink-subtle uppercase',
        sample: 'Filtros activos',
    },
];

onMounted(() => {
    themePreference.value = getStoredThemePreference();
    window.addEventListener(THEME_CHANGE_EVENT, syncThemePreference);
});

onBeforeUnmount(() => {
    window.removeEventListener(THEME_CHANGE_EVENT, syncThemePreference);
});

function changeTheme(preference: ThemePreference): void {
    themePreference.value = preference;
    setThemePreference(preference);
}

function syncThemePreference(event: Event): void {
    themePreference.value = (event as CustomEvent<ThemePreference>).detail;
}

function onDropdownSelect(item: DropdownActionItem): void {
    dropdownMessage.value = `Acción seleccionada: ${item.label}.`;
}
</script>

<template>
    <div class="mx-auto grid w-full max-w-7xl min-w-0 gap-12 pb-16">
        <Head title="Sistema de diseño" />

        <header
            class="grid items-end gap-8 border-b border-line pb-8 lg:grid-cols-[minmax(0,1fr)_auto]"
        >
            <div class="grid max-w-3xl gap-4">
                <div class="flex flex-wrap items-center gap-2">
                    <BaseTag tone="primary">Signal Desk</BaseTag>
                    <BaseTag>Bloque 0</BaseTag>
                    <span class="font-data text-xs text-ink-subtle"
                        >v4 / calibración</span
                    >
                </div>
                <div class="grid gap-3">
                    <h1
                        class="max-w-2xl text-3xl leading-tight font-semibold tracking-[-0.04em] text-balance text-ink sm:text-4xl"
                    >
                        Sistema de diseño de JobHunter
                    </h1>
                    <p
                        class="max-w-2xl text-base leading-7 text-pretty text-ink-muted"
                    >
                        Una mesa operativa donde la compatibilidad, la evidencia
                        y la próxima acción aparecen antes que la decoración.
                    </p>
                </div>
                <div
                    class="flex flex-wrap gap-2"
                    role="group"
                    aria-label="Preferencia de color"
                >
                    <BaseButton
                        v-for="option in themeOptions"
                        :key="option.value"
                        size="sm"
                        :variant="
                            themePreference === option.value
                                ? 'primary'
                                : 'secondary'
                        "
                        :aria-pressed="themePreference === option.value"
                        @click="changeTheme(option.value)"
                    >
                        {{ option.label }}
                    </BaseButton>
                </div>
            </div>

            <div
                class="flex items-center gap-5 border-l border-line pl-6 max-lg:border-t max-lg:border-l-0 max-lg:pt-6 max-lg:pl-0"
            >
                <MatchScore :score="91" size="hero" />
                <div class="grid max-w-44 gap-1">
                    <span
                        class="font-data text-xs font-semibold tracking-[0.08em] text-ink-subtle uppercase"
                        >Señal principal</span
                    >
                    <p class="text-sm leading-5 text-ink-muted">
                        Número, rango y color siempre viajan juntos.
                    </p>
                </div>
            </div>
        </header>

        <nav
            class="sticky top-0 z-30 -mx-4 flex gap-1 overflow-x-auto border-y border-line bg-canvas px-4 py-2 sm:mx-0 sm:rounded-control sm:border sm:px-2"
            aria-label="Secciones del sistema de diseño"
        >
            <a
                v-for="item in [
                    ['tokens', 'Tokens'],
                    ['type-scale', 'Tipografía'],
                    ['score', 'Score'],
                    ['actions', 'Acciones'],
                    ['forms', 'Formularios'],
                    ['surfaces', 'Superficies'],
                    ['container-demo', 'Container queries'],
                    ['feedback', 'Feedback'],
                    ['navigation', 'Navegación'],
                    ['view-states', 'Estados de vista'],
                    ['motion', 'Motion'],
                ]"
                :key="item[0]"
                :href="`#${item[0]}`"
                class="flex min-h-11 shrink-0 items-center rounded-control px-3 text-sm font-semibold text-ink-muted transition-colors duration-150 hover:bg-surface-subtle hover:text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus"
            >
                {{ item[1] }}
            </a>
        </nav>

        <section id="tokens" class="grid gap-6">
            <div class="grid max-w-2xl gap-2">
                <h2 class="text-2xl font-semibold tracking-[-0.03em] text-ink">
                    Tokens fundamentales
                </h2>
                <p class="text-sm leading-6 text-ink-muted">
                    Los componentes consumen nombres semánticos. Los valores
                    crudos quedan aislados en la capa de tokens.
                </p>
            </div>

            <div
                class="grid overflow-hidden rounded-panel border border-line bg-surface sm:grid-cols-2 lg:grid-cols-5"
            >
                <div
                    v-for="token in palette"
                    :key="token.name"
                    class="grid min-w-0 gap-4 border-line p-4 not-last:border-b sm:nth-[2n+1]:border-r sm:nth-last-[-n+2]:border-b-0 lg:not-last:border-r lg:not-last:border-b-0"
                >
                    <div
                        :class="[
                            token.className,
                            'h-16 rounded-control border border-line shadow-card',
                        ]"
                    />
                    <div class="grid gap-1">
                        <strong class="text-sm font-semibold text-ink">{{
                            token.name
                        }}</strong>
                        <span
                            class="font-data text-[0.6875rem] leading-4 text-ink-subtle tabular-nums"
                            >{{ token.light }} / {{ token.dark }}</span
                        >
                    </div>
                </div>
            </div>

            <div
                class="grid gap-8 border-t border-line pt-6 md:grid-cols-[minmax(0,1fr)_minmax(18rem,0.7fr)]"
            >
                <div class="grid gap-4">
                    <p
                        class="text-3xl leading-tight font-semibold tracking-[-0.04em] text-balance text-ink"
                    >
                        La jerarquía nace del ritmo, no del ruido.
                    </p>
                    <p class="max-w-[70ch] text-base leading-7 text-ink-muted">
                        Instrument Sans sostiene la interfaz en pesos 400, 500 y
                        600. El ancho de lectura se limita; los datos usan una
                        voz monoespaciada solo cuando comparar cifras importa.
                    </p>
                </div>
                <dl
                    class="grid grid-cols-2 gap-x-6 gap-y-4 border-l border-line pl-6"
                >
                    <div>
                        <dt class="text-xs font-semibold text-ink-subtle">
                            Espacio base
                        </dt>
                        <dd
                            class="mt-1 font-data text-lg font-semibold text-ink tabular-nums"
                        >
                            4 px
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold text-ink-subtle">
                            Control
                        </dt>
                        <dd
                            class="mt-1 font-data text-lg font-semibold text-ink tabular-nums"
                        >
                            10 px
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold text-ink-subtle">
                            Tarjeta
                        </dt>
                        <dd
                            class="mt-1 font-data text-lg font-semibold text-ink tabular-nums"
                        >
                            14 px
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold text-ink-subtle">
                            Panel
                        </dt>
                        <dd
                            class="mt-1 font-data text-lg font-semibold text-ink tabular-nums"
                        >
                            18 px
                        </dd>
                    </div>
                </dl>
            </div>
        </section>

        <section id="type-scale" class="grid gap-6 border-t border-line pt-10">
            <div class="grid max-w-2xl gap-2">
                <h2 class="text-2xl font-semibold tracking-[-0.03em] text-ink">
                    Escala tipográfica fluida
                </h2>
                <p class="text-sm leading-6 text-ink-muted">
                    Cada paso usa
                    <code class="font-data text-xs text-primary">clamp()</code>
                    para interpolar tamaño entre el viewport más chico y el
                    más grande, sin saltos de breakpoint. Son las utilidades
                    <code class="font-data text-xs text-primary"
                        >text-step-*</code
                    >, espejo de los tokens
                    <code class="font-data text-xs text-primary">--step-*</code>
                    en <code class="font-data text-xs text-primary"
                        >app.css</code
                    >. <b>Redimensiona la ventana</b> y observa cómo cada
                    línea cambia de tamaño de forma continua, no a saltos.
                </p>
            </div>

            <div
                class="grid divide-y divide-line overflow-hidden rounded-panel border border-line bg-surface"
            >
                <div
                    v-for="step in typeScale"
                    :key="step.token"
                    class="grid grid-cols-[minmax(5.5rem,auto)_1fr] items-baseline gap-4 px-4 py-4 sm:px-5"
                >
                    <span class="grid gap-0.5">
                        <span class="text-xs font-semibold text-ink-subtle">
                            {{ step.tag }}
                        </span>
                        <code class="font-data text-[0.6875rem] text-primary">
                            {{ step.token }}
                        </code>
                    </span>
                    <span :class="[step.textClass, 'min-w-0 truncate']">
                        {{ step.sample }}
                    </span>
                </div>
            </div>
        </section>

        <section id="score" class="grid gap-6 border-t border-line pt-10">
            <div class="grid max-w-2xl gap-2">
                <h2 class="text-2xl font-semibold tracking-[-0.03em] text-ink">
                    Match score
                </h2>
                <p class="text-sm leading-6 text-ink-muted">
                    El dial aparece en escalas de detalle y tarjeta; la versión
                    compacta mantiene la misma semántica en filas densas.
                </p>
            </div>

            <div
                class="grid min-w-0 items-center gap-8 rounded-panel border border-line bg-surface px-5 py-8 shadow-card md:px-8 lg:grid-cols-[auto_1fr]"
            >
                <div class="flex flex-wrap items-end gap-8">
                    <MatchScore :score="91" size="hero" />
                    <MatchScore :score="79" size="card" />
                    <MatchScore :score="68" size="compact" />
                    <MatchScore :score="42" size="compact" />
                    <MatchScore :score="null" size="compact" />
                </div>
                <div class="grid min-w-0 grid-cols-2 gap-2 sm:grid-cols-4">
                    <div
                        v-for="score in scorePalette"
                        :key="score.label"
                        :class="[
                            score.className,
                            'grid min-h-24 content-between rounded-card p-3',
                        ]"
                    >
                        <span class="text-xs font-semibold">{{
                            score.label
                        }}</span>
                        <span
                            class="font-data text-xl font-semibold tabular-nums"
                            >{{ score.range }}</span
                        >
                    </div>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                <BaseTag tone="score-excellent">Excelente</BaseTag>
                <BaseTag tone="score-very-good">Muy bueno</BaseTag>
                <BaseTag tone="score-acceptable">Aceptable</BaseTag>
                <BaseTag tone="score-low">Bajo</BaseTag>
            </div>
        </section>

        <section id="actions" class="grid gap-6 border-t border-line pt-10">
            <div class="grid max-w-2xl gap-2">
                <h2 class="text-2xl font-semibold tracking-[-0.03em] text-ink">
                    Acciones
                </h2>
                <p class="text-sm leading-6 text-ink-muted">
                    Cada acción conserva nombre específico, respuesta visible y
                    un área táctil suficiente. Usa Tab para comprobar el foco.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <BaseButton>Analizar pendientes</BaseButton>
                <BaseButton size="lg">Buscar nuevas ofertas</BaseButton>
                <BaseButton variant="secondary">Guardar cambios</BaseButton>
                <BaseButton variant="quiet">Cancelar</BaseButton>
                <BaseButton variant="danger">Eliminar variante</BaseButton>
                <BaseButton loading loading-label="Analizando vacantes">
                    Analizando…
                </BaseButton>
                <BaseButton disabled>Deshabilitado</BaseButton>
                <BaseTooltip text="Recalcula el match score">
                    <template #default="{ describedby }">
                        <BaseButton
                            size="icon"
                            variant="secondary"
                            aria-label="Recalcular compatibilidad"
                            :aria-describedby="describedby"
                        >
                            <AppIcon name="brand" class="h-5 w-5" />
                        </BaseButton>
                    </template>
                </BaseTooltip>
            </div>

            <div class="flex flex-wrap items-start gap-3">
                <BaseButton variant="secondary" @click="drawerOpen = true">
                    Abrir drawer
                </BaseButton>
                <BaseButton variant="danger" @click="modalOpen = true">
                    Abrir confirmación
                </BaseButton>
                <BaseDropdown
                    :items="dropdownItems"
                    trigger-label="Abrir acciones de la muestra"
                    @select="onDropdownSelect"
                />
                <p
                    class="min-h-11 py-3 text-sm text-ink-muted"
                    aria-live="polite"
                >
                    {{ dropdownMessage }}
                </p>
            </div>
        </section>

        <section id="forms" class="grid gap-6 border-t border-line pt-10">
            <div class="grid max-w-2xl gap-2">
                <h2 class="text-2xl font-semibold tracking-[-0.03em] text-ink">
                    Formularios
                </h2>
                <p class="text-sm leading-6 text-ink-muted">
                    Labels persistentes, ayuda asociada y errores que explican
                    cómo continuar.
                </p>
            </div>

            <div class="grid gap-5 md:grid-cols-2">
                <BaseInput
                    v-model="role"
                    name="role"
                    label="Cargo objetivo"
                    placeholder="Ej.: Frontend Engineer…"
                    autocomplete="organization-title"
                    hint="Se usa para priorizar ofertas relevantes."
                />
                <BaseSelect
                    v-model="source"
                    name="source"
                    label="Fuente principal"
                    :options="sourceOptions"
                />
                <BaseSelect
                    name="disabled-source"
                    label="Fuente bloqueada"
                    model-value="jsearch"
                    :options="sourceOptions"
                    disabled
                    hint="Disponible cuando finalice el análisis actual."
                />
                <BaseSelect
                    name="invalid-source"
                    label="Fuente requerida"
                    :model-value="null"
                    :options="sourceOptions"
                    error="Selecciona una fuente para continuar."
                />
                <BaseInput
                    name="email-example"
                    label="Correo de contacto"
                    type="email"
                    model-value="correo-invalido"
                    autocomplete="email"
                    spellcheck="false"
                    error="Introduce una dirección válida, por ejemplo nombre@dominio.com."
                />
                <BaseInput
                    name="disabled-example"
                    label="Perfil base"
                    model-value="Perfil principal"
                    disabled
                    hint="La IA nunca sobrescribe esta versión."
                />
                <BaseTextarea
                    v-model="notes"
                    name="notes"
                    label="Criterios de revisión"
                    placeholder="Ej.: Destacar experiencia en accesibilidad…"
                    autocomplete="off"
                    hint="Las recomendaciones usan solo información ya presente en tu CV."
                />
                <BaseTextarea
                    name="notes-error"
                    label="Motivo del cambio"
                    model-value=""
                    :rows="4"
                    error="Describe por qué crearás esta variante antes de continuar."
                />
            </div>
        </section>

        <section id="surfaces" class="grid gap-6 border-t border-line pt-10">
            <div class="grid max-w-2xl gap-2">
                <h2 class="text-2xl font-semibold tracking-[-0.03em] text-ink">
                    Superficies e identidad
                </h2>
                <p class="text-sm leading-6 text-ink-muted">
                    Las superficies ordenan objetos reales. Evitan anidar
                    tarjetas solo para fabricar espacio.
                </p>
            </div>

            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <BaseCard>
                    <div class="grid gap-3">
                        <BaseTag>Default</BaseTag>
                        <h3 class="font-semibold text-ink">Vacante evaluada</h3>
                        <p class="text-sm leading-5 text-ink-muted">
                            Contenido estable sobre el canvas.
                        </p>
                    </div>
                </BaseCard>
                <BaseCard variant="raised">
                    <div class="grid gap-3">
                        <BaseTag tone="info">Raised</BaseTag>
                        <h3 class="font-semibold text-ink">Panel temporal</h3>
                        <p class="text-sm leading-5 text-ink-muted">
                            Elevación reservada para capas activas.
                        </p>
                    </div>
                </BaseCard>
                <BaseCard variant="subtle">
                    <div class="grid gap-3">
                        <BaseTag tone="warning">Subtle</BaseTag>
                        <h3 class="font-semibold text-ink">
                            Contexto secundario
                        </h3>
                        <p class="text-sm leading-5 text-ink-muted">
                            Agrupa evidencia sin competir con la acción.
                        </p>
                    </div>
                </BaseCard>
                <BaseCard variant="interactive">
                    <div class="grid gap-3">
                        <BaseTag tone="success">Interactive</BaseTag>
                        <h3 class="font-semibold text-ink">Acceso navegable</h3>
                        <p class="text-sm leading-5 text-ink-muted">
                            El foco interior activa el estado del contenedor.
                        </p>
                        <a
                            href="#feedback"
                            class="text-sm font-semibold text-primary underline decoration-primary/30 underline-offset-4 hover:decoration-primary focus-visible:rounded-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus"
                            >Ver feedback</a
                        >
                    </div>
                </BaseCard>
            </div>

            <div
                class="flex flex-wrap items-center gap-5 border-y border-line py-5"
            >
                <CompanyLogo company="Laravel" src="/favicon.svg" size="lg" />
                <CompanyLogo
                    company="Acme Labs"
                    src="data:image/png;base64,invalid"
                    size="lg"
                />
                <CompanyLogo company="Nómada Digital" size="md" />
                <BaseAvatar name="Elena Torres" size="lg" />
                <BaseAvatar name="Martín Ruiz" />
                <BaseAvatar name="A" size="sm" />
                <div class="flex flex-wrap gap-2">
                    <BaseTag>Remoto</BaseTag>
                    <BaseTag tone="primary">Vue 3</BaseTag>
                    <BaseTag tone="success">Activo</BaseTag>
                    <BaseTag tone="warning">Revisar</BaseTag>
                    <BaseTag tone="error">Vencido</BaseTag>
                    <BaseTag tone="info">Información</BaseTag>
                </div>
            </div>
            <BaseCard variant="subtle">
                <div class="grid max-w-3xl gap-2">
                    <BaseTag>Contenido largo</BaseTag>
                    <h3 class="font-semibold break-words text-ink">
                        Especialista en plataformas de experiencia digital y
                        automatización de procesos de selección internacionales
                    </h3>
                    <p class="text-sm leading-6 break-all text-ink-muted">
                        Esta muestra comprueba textos extensos y cadenas sin
                        espacios:
                        compatibilidadfrontendvueaccesibilidadarquitecturadeinterfaces.
                    </p>
                </div>
            </BaseCard>
        </section>

        <section id="container-demo" class="grid gap-6 border-t border-line pt-10">
            <div class="grid max-w-2xl gap-2">
                <h2 class="text-2xl font-semibold tracking-[-0.03em] text-ink">
                    La tarjeta se adapta a su espacio, no al viewport
                </h2>
                <p class="text-sm leading-6 text-ink-muted">
                    Con <code class="font-data text-xs text-primary"
                        >@container</code
                    >
                    la misma tarjeta se apila en una columna angosta y pasa a
                    fila cuando su propio contenedor crece, aunque el
                    viewport no cambie. Así funciona en el sidebar, un panel
                    o una lista sin duplicar el componente.
                </p>
            </div>

            <div
                class="@container/jobcard w-80 max-w-full resize-x overflow-auto rounded-panel border border-dashed border-line-strong bg-surface-subtle p-4"
                style="min-width: 14rem; max-width: 34rem"
            >
                <p
                    class="mb-3 flex items-center gap-1.5 font-data text-xs text-ink-subtle"
                >
                    <AppIcon name="expand" class="h-3.5 w-3.5" />
                    Arrastra la esquina inferior derecha para redimensionar el
                    contenedor
                </p>
                <article
                    class="grid gap-3 rounded-card border border-line bg-surface p-4 shadow-card @sm/jobcard:grid-cols-[1fr_auto] @sm/jobcard:items-center"
                >
                    <div class="min-w-0">
                        <p class="font-semibold text-ink">
                            Senior Product Designer
                        </p>
                        <p class="text-sm text-ink-muted">
                            Nubank · Remoto · LatAm
                        </p>
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            <BaseTag>Figma</BaseTag>
                            <BaseTag>Design systems</BaseTag>
                            <BaseTag>B2C</BaseTag>
                        </div>
                    </div>
                    <div
                        class="flex items-center justify-between gap-3 @sm/jobcard:flex-col @sm/jobcard:items-end @sm/jobcard:justify-center"
                    >
                        <MatchScore :score="92" size="compact" :animate="false" />
                        <BaseButton size="sm" variant="quiet">
                            Guardar
                        </BaseButton>
                    </div>
                </article>
            </div>
        </section>

        <section id="feedback" class="grid gap-6 border-t border-line pt-10">
            <div class="grid max-w-2xl gap-2">
                <h2 class="text-2xl font-semibold tracking-[-0.03em] text-ink">
                    Feedback y carga
                </h2>
                <p class="text-sm leading-6 text-ink-muted">
                    El feedback confirma qué ocurrió; la carga conserva la forma
                    del contenido que llegará.
                </p>
            </div>

            <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_24rem]">
                <div
                    class="grid gap-4 rounded-panel border border-line bg-surface p-5"
                    role="status"
                    aria-label="Ejemplo de contenido cargando"
                >
                    <span class="sr-only">Cargando ejemplo…</span>
                    <div class="flex items-center gap-4">
                        <BaseSkeleton shape="circle" class="h-12 w-12" />
                        <div class="grid flex-1 gap-2">
                            <BaseSkeleton shape="text" class="w-2/3" />
                            <BaseSkeleton shape="text" class="w-1/3" />
                        </div>
                    </div>
                    <BaseSkeleton class="h-24" />
                    <div class="grid grid-cols-3 gap-3">
                        <BaseSkeleton class="h-9" />
                        <BaseSkeleton class="h-9" />
                        <BaseSkeleton class="h-9" />
                    </div>
                </div>

                <div class="grid content-start gap-3">
                    <BaseToast
                        v-if="toastVisible"
                        type="success"
                        title="Variante creada"
                        message="El perfil base no se modificó."
                        @dismiss="toastVisible = false"
                    />
                    <BaseToast
                        v-if="errorToastVisible"
                        type="error"
                        title="No se pudo guardar"
                        message="Revisa la conexión y vuelve a intentarlo."
                        @dismiss="errorToastVisible = false"
                    />
                    <BaseToast
                        v-if="infoToastVisible"
                        type="info"
                        title="Análisis en curso"
                        message="12 de 20 vacantes procesadas."
                        @dismiss="infoToastVisible = false"
                    />
                    <BaseButton
                        v-if="!toastVisible"
                        variant="secondary"
                        size="sm"
                        @click="toastVisible = true"
                    >
                        Restaurar toast
                    </BaseButton>
                </div>
            </div>
        </section>

        <section id="navigation" class="grid gap-6 border-t border-line pt-10">
            <div class="grid max-w-2xl gap-2">
                <h2 class="text-2xl font-semibold tracking-[-0.03em] text-ink">
                    Navegación local
                </h2>
                <p class="text-sm leading-6 text-ink-muted">
                    Flechas, Inicio y Fin recorren las pestañas. Escape cierra
                    los menús y devuelve el foco al disparador.
                </p>
            </div>

            <BaseTabs v-model="activeTab" :items="showcaseTabs">
                <template #default="{ item }">
                    <div class="grid gap-2 py-5">
                        <h3 class="text-lg font-semibold text-ink">
                            {{ item.label }} del análisis
                        </h3>
                        <p
                            class="max-w-[70ch] text-sm leading-6 text-ink-muted"
                        >
                            Este panel demuestra la relación semántica entre la
                            pestaña activa y su contenido, sin ocultar el foco
                            del teclado.
                        </p>
                    </div>
                </template>
            </BaseTabs>
        </section>

        <section id="view-states" class="grid gap-6 border-t border-line pt-10">
            <div class="grid max-w-2xl gap-2">
                <h2 class="text-2xl font-semibold tracking-[-0.03em] text-ink">
                    Estados de vista
                </h2>
                <p class="text-sm leading-6 text-ink-muted">
                    Cargar, orientar, informar y recuperar son estados
                    diseñados, no excepciones.
                </p>
            </div>

            <BaseTabs
                v-model="viewState"
                :items="stateTabs"
                label="Estado de la vista de ejemplo"
            >
                <div class="pt-5">
                    <div
                        v-if="viewState === 'loading'"
                        class="grid gap-4 rounded-panel border border-line bg-surface p-5"
                        role="status"
                    >
                        <span class="sr-only">Cargando vacantes…</span>
                        <BaseSkeleton shape="text" class="w-48" />
                        <BaseSkeleton class="h-28" />
                        <BaseSkeleton shape="text" class="w-2/3" />
                    </div>

                    <EmptyState
                        v-else-if="viewState === 'empty'"
                        title="Aún no hay vacantes analizadas"
                        description="Importa ofertas y ejecuta el análisis para comparar cada oportunidad con tu CV."
                    >
                        <template #icon>
                            <AppIcon name="marketplace" class="h-6 w-6" />
                        </template>
                        <template #action>
                            <BaseButton>Buscar nuevas ofertas</BaseButton>
                        </template>
                    </EmptyState>

                    <BaseCard
                        v-else-if="viewState === 'content'"
                        class="max-w-2xl"
                    >
                        <div class="flex items-start gap-4">
                            <CompanyLogo company="Acme Labs" size="lg" />
                            <div class="min-w-0 flex-1">
                                <BaseTag tone="success">Con datos</BaseTag>
                                <h3
                                    class="mt-3 truncate text-lg font-semibold text-ink"
                                >
                                    Senior Frontend Engineer
                                </h3>
                                <p class="mt-1 text-sm text-ink-muted">
                                    Acme Labs · Remoto
                                </p>
                            </div>
                            <MatchScore :score="88" size="compact" />
                        </div>
                    </BaseCard>

                    <BaseCard
                        v-else
                        class="max-w-2xl border-error bg-error-surface"
                    >
                        <div class="grid gap-3" role="alert">
                            <BaseTag tone="error">Error recuperable</BaseTag>
                            <div class="grid gap-1">
                                <h3 class="font-semibold text-ink">
                                    No se pudieron cargar las vacantes
                                </h3>
                                <p class="text-sm leading-5 text-ink-muted">
                                    Revisa la conexión y vuelve a intentarlo.
                                    Tus filtros siguen guardados.
                                </p>
                            </div>
                            <BaseButton
                                variant="secondary"
                                class="justify-self-start"
                            >
                                Reintentar carga
                            </BaseButton>
                        </div>
                    </BaseCard>
                </div>
            </BaseTabs>
        </section>

        <section id="motion" class="grid gap-6 border-t border-line pt-10">
            <div class="grid max-w-2xl gap-2">
                <h2 class="text-2xl font-semibold tracking-[-0.03em] text-ink">
                    Movimiento con intención
                </h2>
                <p class="text-sm leading-6 text-ink-muted">
                    Toda la app está envuelta en
                    <code class="font-data text-xs text-primary"
                        >&lt;MotionConfig reducedMotion="user"&gt;</code
                    >
                    (<code class="font-data text-xs text-primary"
                        >resources/js/app.ts</code
                    >): cualquier
                    <code class="font-data text-xs text-primary">motion.*</code>
                    o
                    <code class="font-data text-xs text-primary"
                        >AnimatePresence</code
                    >
                    hereda el respeto por
                    <code class="font-data text-xs text-primary"
                        >prefers-reduced-motion</code
                    >
                    sin código adicional por componente.
                </p>
            </div>

            <div class="grid gap-5 lg:grid-cols-3">
                <div class="grid content-start gap-3 rounded-panel border border-line bg-surface p-5">
                    <h3 class="text-sm font-semibold text-ink">
                        Score animado
                    </h3>
                    <p class="text-xs leading-5 text-ink-muted">
                        <code class="font-data text-[0.6875rem] text-primary"
                            >MatchScore</code
                        >
                        dibuja el arco y cuenta hasta el valor con
                        <code class="font-data text-[0.6875rem] text-primary"
                            >animate()</code
                        >
                        de motion-v: confirma que el número se calculó, no
                        que se puso.
                    </p>
                    <div class="mt-2 flex items-center gap-4">
                        <MatchScore
                            :key="motionReplayKey"
                            :score="87"
                            size="card"
                        />
                        <BaseButton
                            size="sm"
                            variant="secondary"
                            @click="motionReplayKey++"
                        >
                            Repetir animación
                        </BaseButton>
                    </div>
                </div>

                <div class="grid content-start gap-3 rounded-panel border border-line bg-surface p-5">
                    <h3 class="text-sm font-semibold text-ink">
                        Gesto hover / press
                    </h3>
                    <p class="text-xs leading-5 text-ink-muted">
                        <code class="font-data text-[0.6875rem] text-primary"
                            >motion.div</code
                        >
                        con
                        <code class="font-data text-[0.6875rem] text-primary"
                            >whileHover</code
                        >
                        /
                        <code class="font-data text-[0.6875rem] text-primary"
                            >whilePress</code
                        >: un gesto declarativo, sin listeners manuales.
                    </p>
                    <motion.div
                        class="mt-2 grid h-20 cursor-pointer place-content-center rounded-card border border-line bg-primary-subtle text-sm font-semibold text-primary select-none"
                        :while-hover="{ scale: 1.03 }"
                        :while-press="{ scale: 0.97 }"
                        :transition="{ duration: 0.15, ease: [0.16, 1, 0.3, 1] }"
                        tabindex="0"
                    >
                        Prueba el gesto
                    </motion.div>
                </div>

                <div class="grid content-start gap-3 rounded-panel border border-line bg-surface p-5">
                    <h3 class="text-sm font-semibold text-ink">
                        Entrada y salida
                    </h3>
                    <p class="text-xs leading-5 text-ink-muted">
                        <code class="font-data text-[0.6875rem] text-primary"
                            >AnimatePresence</code
                        >
                        anima también la salida — el mismo patrón que usa el
                        toast global en
                        <code class="font-data text-[0.6875rem] text-primary"
                            >ToastContainer.vue</code
                        >.
                    </p>
                    <BaseButton
                        size="sm"
                        variant="secondary"
                        class="mt-2 justify-self-start"
                        :aria-expanded="revealPanelOpen"
                        aria-controls="motion-reveal-panel"
                        @click="revealPanelOpen = !revealPanelOpen"
                    >
                        {{ revealPanelOpen ? 'Ocultar panel' : 'Mostrar panel' }}
                    </BaseButton>
                    <AnimatePresence>
                        <motion.div
                            v-if="revealPanelOpen"
                            id="motion-reveal-panel"
                            :initial="{ opacity: 0, y: 10, scale: 0.98 }"
                            :animate="{ opacity: 1, y: 0, scale: 1 }"
                            :exit="{ opacity: 0, scale: 0.98 }"
                            :transition="{
                                duration: 0.2,
                                ease: [0.16, 1, 0.3, 1],
                            }"
                            class="rounded-control border border-line bg-surface-subtle px-3 py-2 text-xs text-ink-muted"
                        >
                            Este panel entra y sale con motion-v.
                        </motion.div>
                    </AnimatePresence>
                </div>
            </div>
        </section>

        <BaseDrawer
            :open="drawerOpen"
            title="Configurar análisis"
            description="Ajusta el contexto sin abandonar la vista actual."
            @close="drawerOpen = false"
        >
            <div class="grid gap-5">
                <BaseSelect
                    v-model="source"
                    label="Fuente"
                    name="drawer-source"
                    :options="sourceOptions"
                />
                <BaseTextarea
                    v-model="notes"
                    label="Criterios"
                    name="drawer-notes"
                    autocomplete="off"
                />
            </div>
            <template #footer>
                <BaseButton variant="quiet" @click="drawerOpen = false">
                    Cancelar
                </BaseButton>
                <BaseButton @click="drawerOpen = false"
                    >Guardar ajustes</BaseButton
                >
            </template>
        </BaseDrawer>

        <BaseModal
            :open="modalOpen"
            title="¿Eliminar esta muestra?"
            description="Esta confirmación demuestra una decisión breve y destructiva."
            @close="modalOpen = false"
        >
            <p class="text-sm leading-6 text-ink-muted">
                La acción de demostración no elimina datos reales.
            </p>
            <template #footer>
                <BaseButton variant="quiet" @click="modalOpen = false">
                    Conservar muestra
                </BaseButton>
                <BaseButton variant="danger" @click="modalOpen = false">
                    Eliminar muestra
                </BaseButton>
            </template>
        </BaseModal>
    </div>
</template>
