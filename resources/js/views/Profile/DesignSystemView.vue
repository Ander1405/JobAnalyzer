<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
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
                    ['score', 'Score'],
                    ['actions', 'Acciones'],
                    ['forms', 'Formularios'],
                    ['surfaces', 'Superficies'],
                    ['feedback', 'Feedback'],
                    ['navigation', 'Navegación'],
                    ['view-states', 'Estados de vista'],
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
