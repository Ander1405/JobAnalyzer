<script setup lang="ts">
import { motion } from 'motion-v';
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import AppIcon from '@/components/AppIcon.vue';
import {
    BaseButton,
    BaseCard,
    BaseSkeleton,
    BaseTag,
    CompanyLogo,
    EmptyState,
    MatchScore,
} from '@/components/ui';
import { usePersistedRef } from '@/lib/persisted';
import { useToast } from '@/lib/toast';
import { cn } from '@/lib/utils';
import {
    TRACKED_JOB_PRIORITY_LABELS,
    TRACKED_JOB_STATUS_LABELS,
    TRACKED_JOB_STATUSES,
} from '@/types/tracking';
import type {
    TrackedJob,
    TrackedJobPriority,
    TrackedJobStatus,
} from '@/types/tracking';

type TagTone = 'neutral' | 'primary' | 'success' | 'warning' | 'error' | 'info';

const router = useRouter();
const toast = useToast();
const trackedJobs = ref<TrackedJob[]>([]);
const loading = ref(true);
const viewMode = usePersistedRef<'kanban' | 'list'>(
    'tracking:viewMode',
    'kanban',
);
const draggedId = ref<number | null>(null);
const movingId = ref<number | null>(null);
const activeDropStatus = ref<TrackedJobStatus | null>(null);
const dateFormatter = new Intl.DateTimeFormat('es', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
});

onMounted(loadTrackedJobs);

async function loadTrackedJobs() {
    loading.value = true;

    try {
        const response = await fetch('/api/tracking', {
            headers: { Accept: 'application/json' },
        });
        trackedJobs.value = await response.json();
    } catch {
        toast.error('No se pudieron cargar tus vacantes.');
    } finally {
        loading.value = false;
    }
}

function columnFor(status: TrackedJobStatus): TrackedJob[] {
    return trackedJobs.value.filter(
        (trackedJob) => trackedJob.status === status,
    );
}

function openDetail(trackedJob: TrackedJob) {
    router.push(`/tracking/${trackedJob.id}`);
}

function onDragStart(trackedJob: TrackedJob, event: DragEvent) {
    draggedId.value = trackedJob.id;
    event.dataTransfer?.setData('text/plain', String(trackedJob.id));

    if (event.dataTransfer) {
        event.dataTransfer.effectAllowed = 'move';
    }
}

function onDragEnd() {
    draggedId.value = null;
    activeDropStatus.value = null;
}

function onDrop(status: TrackedJobStatus) {
    const id = draggedId.value;
    draggedId.value = null;
    activeDropStatus.value = null;

    if (id === null) {
        return;
    }

    const trackedJob = trackedJobs.value.find(
        (candidate) => candidate.id === id,
    );

    if (!trackedJob) {
        return;
    }

    moveTo(trackedJob, status);
}

// Ruta única de cambio de estado: la llaman tanto el drop nativo (mejora
// progresiva para mouse) como los botones ◀ / "Siguiente estado ▶", que son
// la interacción primaria — HTML5 drag&drop no funciona en táctil ni teclado.
async function moveTo(trackedJob: TrackedJob, status: TrackedJobStatus) {
    if (trackedJob.status === status) {
        return;
    }

    const id = trackedJob.id;
    movingId.value = id;

    try {
        const response = await fetch(`/api/tracking/${id}`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
            },
            body: JSON.stringify({ status }),
        });
        const updated: TrackedJob = await response.json();
        const index = trackedJobs.value.findIndex(
            (candidate) => candidate.id === id,
        );

        if (index !== -1) {
            trackedJobs.value[index] = updated;
        }
    } catch {
        toast.error('No se pudo actualizar el estado.');
    } finally {
        movingId.value = null;
    }
}

function previousStatus(status: TrackedJobStatus): TrackedJobStatus | null {
    const index = TRACKED_JOB_STATUSES.indexOf(status);

    return index <= 0 ? null : TRACKED_JOB_STATUSES[index - 1];
}

function nextStatus(status: TrackedJobStatus): TrackedJobStatus | null {
    const index = TRACKED_JOB_STATUSES.indexOf(status);

    return index === -1 || index === TRACKED_JOB_STATUSES.length - 1
        ? null
        : TRACKED_JOB_STATUSES[index + 1];
}

function statusTone(status: TrackedJobStatus): TagTone {
    return {
        sin_aplicar: 'neutral',
        aplique: 'primary',
        en_proceso: 'info',
        rechazado: 'error',
        oferta: 'success',
    }[status] as TagTone;
}

function priorityTone(priority: TrackedJobPriority): TagTone {
    return {
        alta: 'error',
        media: 'warning',
        baja: 'neutral',
    }[priority] as TagTone;
}

// La tabla vive dentro de un BaseCard, que ya declara @container/card. Bajo
// ese contenedor angosto (no el viewport) cada <td> pasa de columna a fila
// "etiqueta: valor" usando su atributo data-label, igual que el patrón
// .jtable del mockup — sin scroll horizontal ni perder columnas.
const responsiveCell =
    '@max-[620px]/card:flex @max-[620px]/card:max-w-none @max-[620px]/card:items-start @max-[620px]/card:justify-between @max-[620px]/card:gap-4 @max-[620px]/card:border-0 @max-[620px]/card:px-3 @max-[620px]/card:py-1.5 @max-[620px]/card:before:mt-0.5 @max-[620px]/card:before:shrink-0 @max-[620px]/card:before:text-[0.6875rem] @max-[620px]/card:before:font-semibold @max-[620px]/card:before:tracking-wide @max-[620px]/card:before:text-ink-subtle @max-[620px]/card:before:uppercase @max-[620px]/card:before:content-[attr(data-label)]';

function formatDate(value: string | null): string {
    if (!value) {
        return '—';
    }

    const date = new Date(`${value.slice(0, 10)}T00:00:00`);

    return Number.isNaN(date.getTime()) ? value : dateFormatter.format(date);
}

function isOverdue(trackedJob: TrackedJob): boolean {
    const today = new Date().toISOString().slice(0, 10);

    return (
        trackedJob.next_action_date !== null &&
        trackedJob.next_action_date < today &&
        trackedJob.status !== 'rechazado' &&
        trackedJob.status !== 'oferta'
    );
}

const totalCount = computed(() => trackedJobs.value.length);

const appliedCount = computed(
    () =>
        trackedJobs.value.filter((trackedJob) => trackedJob.applied_at !== null)
            .length,
);

const inProcessCount = computed(
    () =>
        trackedJobs.value.filter(
            (trackedJob) => trackedJob.status === 'en_proceso',
        ).length,
);

const responseRate = computed(() => {
    if (appliedCount.value === 0) {
        return null;
    }

    return Math.round((inProcessCount.value / appliedCount.value) * 100);
});

const overdueCount = computed(() => trackedJobs.value.filter(isOverdue).length);
</script>

<template>
    <div class="mx-auto max-w-7xl">
        <header class="mb-5 border-b border-line pb-5">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div class="min-w-0">
                    <p
                        class="mb-2 text-step-eyebrow font-semibold text-primary"
                    >
                        SEGUIMIENTO DE APLICACIONES
                    </p>
                    <h1
                        class="text-step-h1 font-semibold tracking-[-0.04em] text-balance text-ink"
                    >
                        Mis vacantes
                    </h1>
                    <p class="mt-1 max-w-2xl text-sm leading-6 text-ink-muted">
                        Mantén visibles el estado, la prioridad y la próxima
                        acción de cada oportunidad.
                    </p>
                </div>

                <div
                    class="flex rounded-control border border-line bg-surface p-1 shadow-card"
                    aria-label="Modo de visualización"
                    role="group"
                >
                    <BaseButton
                        size="sm"
                        :variant="viewMode === 'kanban' ? 'primary' : 'quiet'"
                        :aria-pressed="viewMode === 'kanban'"
                        @click="viewMode = 'kanban'"
                    >
                        <template #leading>
                            <AppIcon name="tracking" class="h-4 w-4" />
                        </template>
                        Kanban
                    </BaseButton>
                    <BaseButton
                        size="sm"
                        :variant="viewMode === 'list' ? 'primary' : 'quiet'"
                        :aria-pressed="viewMode === 'list'"
                        @click="viewMode = 'list'"
                    >
                        <template #leading>
                            <AppIcon name="menu" class="h-4 w-4" />
                        </template>
                        Lista
                    </BaseButton>
                </div>
            </div>
        </header>

        <BaseCard
            v-if="!loading && totalCount > 0"
            :padded="false"
            class="mb-5 overflow-hidden"
            aria-label="Resumen de seguimiento"
        >
            <dl class="grid grid-cols-2 sm:grid-cols-4">
                <div
                    class="border-r border-b border-line px-4 py-3 sm:border-b-0"
                >
                    <dt class="text-xs font-medium text-ink-subtle">Total</dt>
                    <dd
                        class="mt-1 font-data text-xl font-semibold text-ink tabular-nums"
                    >
                        {{ totalCount }}
                    </dd>
                </div>
                <div
                    class="border-b border-line px-4 py-3 sm:border-r sm:border-b-0"
                >
                    <dt class="text-xs font-medium text-ink-subtle">Apliqué</dt>
                    <dd
                        class="mt-1 font-data text-xl font-semibold text-ink tabular-nums"
                    >
                        {{ appliedCount }}
                    </dd>
                </div>
                <div class="border-r border-line px-4 py-3">
                    <dt class="text-xs font-medium text-ink-subtle">
                        Tasa de respuesta
                    </dt>
                    <dd
                        class="mt-1 font-data text-xl font-semibold text-ink tabular-nums"
                    >
                        {{ responseRate !== null ? `${responseRate}%` : '—' }}
                    </dd>
                </div>
                <div
                    :class="
                        cn('px-4 py-3', overdueCount > 0 && 'bg-error-surface')
                    "
                >
                    <dt
                        :class="
                            cn(
                                'text-xs font-medium text-ink-subtle',
                                overdueCount > 0 && 'text-error',
                            )
                        "
                    >
                        Acciones vencidas
                    </dt>
                    <dd
                        :class="
                            cn(
                                'mt-1 font-data text-xl font-semibold text-ink tabular-nums',
                                overdueCount > 0 && 'text-error',
                            )
                        "
                    >
                        {{ overdueCount }}
                    </dd>
                </div>
            </dl>
        </BaseCard>

        <p
            v-if="!loading && totalCount > 0"
            class="mb-2 text-right text-xs text-ink-subtle"
        >
            {{
                viewMode === 'kanban'
                    ? 'Desplaza horizontalmente para revisar las cinco etapas.'
                    : 'Desplaza la tabla para consultar todos los campos.'
            }}
        </p>

        <div
            v-if="loading"
            class="flex gap-4 overflow-x-auto overscroll-x-contain pb-3"
            aria-label="Cargando vacantes"
        >
            <BaseCard
                v-for="n in 5"
                :key="n"
                variant="subtle"
                class="flex w-72 shrink-0 flex-col gap-3"
            >
                <BaseSkeleton shape="text" class="w-2/3" />
                <BaseSkeleton class="h-32 w-full" />
                <BaseSkeleton class="h-32 w-full" />
            </BaseCard>
        </div>

        <EmptyState
            v-else-if="totalCount === 0"
            title="Todavía no hay vacantes en seguimiento"
            description="Agrega una oportunidad desde el Marketplace para organizar su postulación y próximos pasos."
        >
            <template #icon>
                <AppIcon name="tracking" class="h-6 w-6" />
            </template>
            <template #action>
                <BaseButton @click="router.push('/marketplace')">
                    Ir al Marketplace
                </BaseButton>
            </template>
        </EmptyState>

        <section
            v-else-if="viewMode === 'kanban'"
            class="flex snap-x snap-mandatory gap-4 overflow-x-auto overscroll-x-contain pb-3"
            aria-label="Tablero de seguimiento por estado"
        >
            <p id="tracking-drag-instructions" class="sr-only">
                Arrastra una vacante a otra columna para cambiar su estado, o
                usa los botones ◀ y de siguiente estado en cada tarjeta.
            </p>

            <BaseCard
                v-for="status in TRACKED_JOB_STATUSES"
                :key="status"
                as="section"
                variant="subtle"
                :padded="false"
                :class="
                    cn(
                        'flex min-h-80 w-72 shrink-0 snap-start flex-col p-3 transition-[border-color,box-shadow] duration-150',
                        activeDropStatus === status &&
                            'border-primary shadow-raised ring-2 ring-primary/20',
                    )
                "
                :aria-label="`${TRACKED_JOB_STATUS_LABELS[status]}, ${columnFor(status).length} vacantes`"
                @dragenter.prevent="activeDropStatus = status"
                @dragover.prevent="activeDropStatus = status"
                @dragleave.self="activeDropStatus = null"
                @drop="onDrop(status)"
            >
                <div class="mb-3 flex items-center justify-between gap-3">
                    <h2 class="text-sm font-semibold text-ink">
                        {{ TRACKED_JOB_STATUS_LABELS[status] }}
                    </h2>
                    <span
                        class="font-data text-xs font-semibold text-ink-subtle tabular-nums"
                    >
                        {{ columnFor(status).length }}
                    </span>
                </div>

                <!-- layout en el contenedor de la lista (no en la tarjeta
                     arrastrable): motion-v anima el reflow de la columna
                     cuando una tarjeta entra o sale sin envolver el nodo que
                     lleva draggable, que necesita seguir siendo un elemento
                     nativo para el drag&drop HTML5. -->
                <motion.div layout class="flex flex-col gap-3">
                    <article
                        v-for="trackedJob in columnFor(status)"
                        :key="trackedJob.id"
                        :class="
                            cn(
                                'w-full cursor-grab rounded-card border border-line bg-surface shadow-card transition-[border-color,box-shadow,opacity] duration-150 hover:border-primary hover:shadow-raised active:cursor-grabbing',
                                draggedId === trackedJob.id &&
                                    'border-primary opacity-60',
                                movingId === trackedJob.id && 'opacity-50',
                            )
                        "
                        draggable="true"
                        @dragstart="onDragStart(trackedJob, $event)"
                        @dragend="onDragEnd"
                    >
                        <button
                            type="button"
                            class="w-full p-3 text-left focus-visible:rounded-t-card focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus"
                            :aria-describedby="'tracking-drag-instructions'"
                            :aria-label="`${trackedJob.job?.title ?? 'Vacante'} en ${trackedJob.job?.company ?? 'empresa sin identificar'}`"
                            @click="openDetail(trackedJob)"
                        >
                            <div class="flex min-w-0 items-start gap-3">
                                <CompanyLogo
                                    :company="
                                        trackedJob.job?.company ?? 'Empresa'
                                    "
                                    :src="trackedJob.job?.company_logo"
                                    size="sm"
                                />
                                <div class="min-w-0 flex-1">
                                    <p
                                        class="line-clamp-2 text-sm font-semibold text-ink"
                                    >
                                        {{ trackedJob.job?.title ?? '—' }}
                                    </p>
                                    <p
                                        class="mt-0.5 truncate text-xs text-ink-muted"
                                    >
                                        {{ trackedJob.job?.company ?? '—' }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <BaseTag
                                    v-if="trackedJob.priority"
                                    :tone="priorityTone(trackedJob.priority)"
                                >
                                    Prioridad
                                    {{
                                        TRACKED_JOB_PRIORITY_LABELS[
                                            trackedJob.priority
                                        ]
                                    }}
                                </BaseTag>
                                <MatchScore
                                    v-if="trackedJob.job?.ai_analysis"
                                    :score="
                                        trackedJob.job.ai_analysis.match_score
                                    "
                                    size="compact"
                                    :animate="false"
                                />
                            </div>

                            <div
                                v-if="trackedJob.next_action"
                                class="mt-3 border-t border-line pt-3"
                            >
                                <p
                                    class="text-[0.6875rem] font-medium text-ink-subtle"
                                >
                                    Próxima acción
                                </p>
                                <p
                                    class="mt-1 line-clamp-2 text-xs leading-5 text-ink"
                                >
                                    {{ trackedJob.next_action }}
                                </p>
                                <p
                                    v-if="trackedJob.next_action_date"
                                    :class="
                                        cn(
                                            'mt-1 font-data text-[0.6875rem] text-ink-muted tabular-nums',
                                            isOverdue(trackedJob) &&
                                                'font-semibold text-error',
                                        )
                                    "
                                >
                                    {{
                                        formatDate(trackedJob.next_action_date)
                                    }}
                                    <span v-if="isOverdue(trackedJob)"
                                        >· Vencida</span
                                    >
                                </p>
                            </div>
                        </button>

                        <!-- Interacción primaria: mueve la vacante de estado
                             sin depender de mouse ni de drag&drop. Siempre
                             visibles (no solo al hover) y operables por
                             teclado. -->
                        <div
                            class="flex items-center gap-2 border-t border-line p-3 pt-2.5"
                        >
                            <BaseButton
                                v-if="previousStatus(trackedJob.status)"
                                size="sm"
                                variant="quiet"
                                :disabled="movingId === trackedJob.id"
                                :aria-label="`Mover a ${TRACKED_JOB_STATUS_LABELS[previousStatus(trackedJob.status)!]}`"
                                @click="
                                    moveTo(
                                        trackedJob,
                                        previousStatus(trackedJob.status)!,
                                    )
                                "
                            >
                                ◀
                            </BaseButton>
                            <BaseButton
                                v-if="nextStatus(trackedJob.status)"
                                size="sm"
                                variant="secondary"
                                class="flex-1"
                                :disabled="movingId === trackedJob.id"
                                :aria-label="`Mover a ${TRACKED_JOB_STATUS_LABELS[nextStatus(trackedJob.status)!]}`"
                                @click="
                                    moveTo(
                                        trackedJob,
                                        nextStatus(trackedJob.status)!,
                                    )
                                "
                            >
                                {{
                                    TRACKED_JOB_STATUS_LABELS[
                                        nextStatus(trackedJob.status)!
                                    ]
                                }}
                                ▶
                            </BaseButton>
                        </div>
                    </article>
                </motion.div>
            </BaseCard>
        </section>

        <!-- BaseCard ya declara @container/card en su nodo raíz (ver
             BaseCard.vue): la tabla colapsa en tarjetas legibles bajo un
             ancho de SU contenedor, no del viewport, igual que .jtable en el
             mockup — sin overflow-x-auto obligatorio en pantallas angostas. -->
        <BaseCard v-else :padded="false" class="overflow-hidden">
            <div class="overflow-x-auto overscroll-x-contain">
                <table
                    class="w-full min-w-[960px] text-left text-sm @max-[620px]/card:min-w-0"
                >
                    <thead
                        class="border-b border-line bg-surface-subtle text-ink-muted @max-[620px]/card:hidden"
                    >
                        <tr>
                            <th class="px-4 py-3 font-semibold">Empresa</th>
                            <th class="px-4 py-3 font-semibold">Cargo</th>
                            <th class="px-4 py-3 font-semibold">Estado</th>
                            <th class="px-4 py-3 font-semibold">Prioridad</th>
                            <th class="px-4 py-3 font-semibold">Postulación</th>
                            <th class="px-4 py-3 font-semibold">
                                Próxima acción
                            </th>
                            <th class="px-4 py-3 font-semibold">
                                Último comentario
                            </th>
                        </tr>
                    </thead>
                    <tbody
                        class="divide-y divide-line @max-[620px]/card:block @max-[620px]/card:divide-y-0"
                    >
                        <tr
                            v-for="trackedJob in trackedJobs"
                            :key="trackedJob.id"
                            class="cursor-pointer bg-surface text-ink transition-[background-color] duration-150 hover:bg-surface-subtle @max-[620px]/card:mb-3 @max-[620px]/card:block @max-[620px]/card:rounded-card @max-[620px]/card:border @max-[620px]/card:border-line @max-[620px]/card:py-1 @max-[620px]/card:shadow-card @max-[620px]/card:last:mb-0"
                            @click="openDetail(trackedJob)"
                        >
                            <td
                                data-label="Empresa"
                                :class="cn('px-4 py-3', responsiveCell)"
                            >
                                <div class="flex min-w-0 items-center gap-3">
                                    <CompanyLogo
                                        :company="
                                            trackedJob.job?.company ?? 'Empresa'
                                        "
                                        :src="trackedJob.job?.company_logo"
                                        size="sm"
                                    />
                                    <span
                                        class="max-w-40 truncate font-medium @max-[620px]/card:max-w-none"
                                    >
                                        {{ trackedJob.job?.company ?? '—' }}
                                    </span>
                                </div>
                            </td>
                            <td
                                data-label="Cargo"
                                :class="
                                    cn('max-w-64 px-4 py-3', responsiveCell)
                                "
                            >
                                <button
                                    type="button"
                                    class="line-clamp-2 text-left font-semibold text-ink hover:text-primary focus-visible:rounded-control focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus"
                                    :aria-label="`Abrir ${trackedJob.job?.title ?? 'vacante'} en ${trackedJob.job?.company ?? 'empresa sin identificar'}`"
                                    @click.stop="openDetail(trackedJob)"
                                >
                                    {{ trackedJob.job?.title ?? '—' }}
                                </button>
                            </td>
                            <td
                                data-label="Estado"
                                :class="cn('px-4 py-3', responsiveCell)"
                            >
                                <BaseTag :tone="statusTone(trackedJob.status)">
                                    {{
                                        TRACKED_JOB_STATUS_LABELS[
                                            trackedJob.status
                                        ]
                                    }}
                                </BaseTag>
                            </td>
                            <td
                                data-label="Prioridad"
                                :class="cn('px-4 py-3', responsiveCell)"
                            >
                                <BaseTag
                                    v-if="trackedJob.priority"
                                    :tone="priorityTone(trackedJob.priority)"
                                >
                                    {{
                                        TRACKED_JOB_PRIORITY_LABELS[
                                            trackedJob.priority
                                        ]
                                    }}
                                </BaseTag>
                                <span v-else class="text-ink-subtle">—</span>
                            </td>
                            <td
                                data-label="Postulación"
                                :class="
                                    cn(
                                        'px-4 py-3 font-data text-xs text-ink-muted tabular-nums',
                                        responsiveCell,
                                    )
                                "
                            >
                                {{ formatDate(trackedJob.applied_at) }}
                            </td>
                            <td
                                data-label="Próxima acción"
                                :class="
                                    cn('max-w-56 px-4 py-3', responsiveCell)
                                "
                            >
                                <p
                                    class="truncate text-ink @max-[620px]/card:max-w-none @max-[620px]/card:text-right"
                                >
                                    {{ trackedJob.next_action ?? '—' }}
                                </p>
                                <p
                                    v-if="trackedJob.next_action_date"
                                    :class="
                                        cn(
                                            'mt-1 font-data text-xs text-ink-muted tabular-nums @max-[620px]/card:text-right',
                                            isOverdue(trackedJob) &&
                                                'font-semibold text-error',
                                        )
                                    "
                                >
                                    {{
                                        formatDate(trackedJob.next_action_date)
                                    }}
                                </p>
                            </td>
                            <td
                                data-label="Último comentario"
                                :class="
                                    cn(
                                        'max-w-64 px-4 py-3 text-ink-muted',
                                        responsiveCell,
                                    )
                                "
                            >
                                <span
                                    class="block truncate @max-[620px]/card:max-w-none @max-[620px]/card:text-right"
                                >
                                    {{ trackedJob.latest_comment?.body ?? '—' }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </BaseCard>

        <p class="sr-only" role="status" aria-live="polite">
            <template v-if="movingId !== null">
                Actualizando el estado de la vacante.
            </template>
        </p>
    </div>
</template>
