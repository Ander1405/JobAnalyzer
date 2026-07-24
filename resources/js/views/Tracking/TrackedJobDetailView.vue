<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import AppIcon from '@/components/AppIcon.vue';
import {
    BaseButton,
    BaseCard,
    BaseSelect,
    BaseSkeleton,
    BaseTag,
    BaseTextarea,
    CompanyLogo,
    EmptyState,
    MatchScore,
} from '@/components/ui';
import { useToast } from '@/lib/toast';
import {
    COMMENT_TYPE_LABELS,
    MANUAL_COMMENT_TYPES,
    TRACKED_JOB_PRIORITY_LABELS,
    TRACKED_JOB_PRIORITIES,
    TRACKED_JOB_STATUS_LABELS,
    TRACKED_JOB_STATUSES,
} from '@/types/tracking';
import type {
    CommentType,
    TrackedJob,
    TrackedJobPriority,
    TrackedJobStatus,
} from '@/types/tracking';

type TagTone = 'neutral' | 'primary' | 'success' | 'warning' | 'error' | 'info';

const route = useRoute();
const router = useRouter();
const toast = useToast();

const trackedJob = ref<TrackedJob | null>(null);
const loading = ref(true);
const saving = ref(false);
const newCommentBody = ref('');
const newCommentType = ref<CommentType>('nota');
const submittingComment = ref(false);
const statusOptions = TRACKED_JOB_STATUSES.map((status) => ({
    value: status,
    label: TRACKED_JOB_STATUS_LABELS[status],
}));
const priorityOptions = [
    { value: '', label: 'Sin definir' },
    ...TRACKED_JOB_PRIORITIES.map((priority) => ({
        value: priority,
        label: TRACKED_JOB_PRIORITY_LABELS[priority],
    })),
];
const commentTypeOptions = MANUAL_COMMENT_TYPES.map((type) => ({
    value: type,
    label: COMMENT_TYPE_LABELS[type],
}));
const commentTypeModel = computed<string | number | null>({
    get: () => newCommentType.value,
    set: (value) => {
        if (typeof value === 'string') {
            newCommentType.value = value as CommentType;
        }
    },
});
const dateFormatter = new Intl.DateTimeFormat('es', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
});
const dateTimeFormatter = new Intl.DateTimeFormat('es', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
});

async function loadTrackedJob(id: string | string[]) {
    loading.value = true;

    try {
        const response = await fetch(`/api/tracking/${id}`, {
            headers: { Accept: 'application/json' },
        });
        trackedJob.value = await response.json();
    } catch {
        toast.error('No se pudo cargar la vacante.');
    } finally {
        loading.value = false;
    }
}

watch(
    () => route.params.id,
    (id) => {
        if (id) {
            loadTrackedJob(id);
        }
    },
    { immediate: true },
);

const cvVariant = computed(
    () => trackedJob.value?.job?.latest_cv_variant ?? null,
);
const cvPdfUrl = computed(() =>
    trackedJob.value?.job
        ? `/api/jobs/${trackedJob.value.job.id}/cv/pdf`
        : null,
);
const cvPdfDownloadUrl = computed(() =>
    cvPdfUrl.value ? `${cvPdfUrl.value}?download=1` : null,
);

function openApplyUrl() {
    const job = trackedJob.value?.job;

    if (!job) {
        return;
    }

    window.open(job.apply_url ?? job.url, '_blank', 'noopener');
}

async function patchTrackedJob(payload: Record<string, unknown>) {
    if (!trackedJob.value) {
        return;
    }

    saving.value = true;

    try {
        const response = await fetch(`/api/tracking/${trackedJob.value.id}`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
            },
            body: JSON.stringify(payload),
        });
        trackedJob.value = await response.json();
    } catch {
        toast.error('No se pudieron guardar los cambios.');
    } finally {
        saving.value = false;
    }
}

function updateStatus(event: Event) {
    patchTrackedJob({ status: (event.target as HTMLSelectElement).value });
}

function updatePriority(event: Event) {
    const value = (event.target as HTMLSelectElement).value;
    patchTrackedJob({ priority: value || null });
}

function updateNextAction(event: Event) {
    const value = (event.target as HTMLInputElement).value;
    patchTrackedJob({ next_action: value || null });
}

function updateNextActionDate(event: Event) {
    const value = (event.target as HTMLInputElement).value;
    patchTrackedJob({ next_action_date: value || null });
}

function updateCvVersion(event: Event) {
    const value = (event.target as HTMLInputElement).value;
    patchTrackedJob({ cv_version_used: value || null });
}

async function submitComment() {
    if (!trackedJob.value || !newCommentBody.value.trim()) {
        return;
    }

    submittingComment.value = true;

    try {
        const response = await fetch(
            `/api/tracking/${trackedJob.value.id}/comments`,
            {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                },
                body: JSON.stringify({
                    body: newCommentBody.value,
                    type: newCommentType.value,
                }),
            },
        );
        const comment = await response.json();
        trackedJob.value.comments = [
            ...(trackedJob.value.comments ?? []),
            comment,
        ];
        newCommentBody.value = '';
        newCommentType.value = 'nota';
    } catch {
        toast.error('No se pudo agregar el comentario.');
    } finally {
        submittingComment.value = false;
    }
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

function commentTone(type: CommentType): TagTone {
    return {
        nota: 'neutral',
        cambio_estado: 'info',
        entrevista: 'primary',
        seguimiento: 'warning',
    }[type] as TagTone;
}

function formatDate(value: string | null): string {
    if (!value) {
        return '—';
    }

    const date = new Date(`${value.slice(0, 10)}T00:00:00`);

    return Number.isNaN(date.getTime()) ? value : dateFormatter.format(date);
}

function formatDateTime(value: string | null): string {
    if (!value) {
        return 'Sin fecha';
    }

    const date = new Date(value);

    return Number.isNaN(date.getTime())
        ? value
        : dateTimeFormatter.format(date);
}
</script>

<template>
    <div class="mx-auto max-w-6xl">
        <BaseButton
            variant="quiet"
            class="mb-4"
            @click="router.push('/tracking')"
        >
            <template #leading>
                <AppIcon name="collapse" class="h-4 w-4" />
            </template>
            Volver a Mis vacantes
        </BaseButton>

        <div v-if="loading" class="flex flex-col gap-6">
            <div class="flex items-start gap-4 border-b border-line pb-6">
                <BaseSkeleton class="h-16 w-16 shrink-0" />
                <div class="flex-1">
                    <BaseSkeleton shape="text" class="mb-3 h-5 w-1/2" />
                    <BaseSkeleton shape="text" class="w-1/3" />
                </div>
                <BaseSkeleton class="hidden h-12 w-48 sm:block" />
            </div>
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-5">
                <div class="flex flex-col gap-4 lg:col-span-3">
                    <BaseSkeleton class="h-40 w-full" />
                    <BaseSkeleton class="h-64 w-full" />
                </div>
                <div class="flex flex-col gap-4 lg:col-span-2">
                    <BaseSkeleton class="h-56 w-full" />
                    <BaseSkeleton class="h-80 w-full" />
                </div>
            </div>
        </div>

        <template v-else-if="trackedJob">
            <header class="mb-6 border-b border-line pb-6">
                <div
                    class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between"
                >
                    <div class="flex min-w-0 items-start gap-4">
                        <CompanyLogo
                            :company="trackedJob.job?.company ?? 'Empresa'"
                            :src="trackedJob.job?.company_logo"
                            size="lg"
                        />
                        <div class="min-w-0">
                            <h1
                                class="text-2xl font-semibold tracking-[-0.03em] text-balance text-ink sm:text-3xl"
                            >
                                {{
                                    trackedJob.job?.title ??
                                    'Vacante sin título'
                                }}
                            </h1>
                            <p class="mt-1 text-sm text-ink-muted">
                                {{
                                    trackedJob.job?.company ??
                                    'Empresa sin identificar'
                                }}
                            </p>
                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <BaseTag :tone="statusTone(trackedJob.status)">
                                    {{
                                        TRACKED_JOB_STATUS_LABELS[
                                            trackedJob.status
                                        ]
                                    }}
                                </BaseTag>
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
                                <span
                                    v-if="trackedJob.applied_at"
                                    class="font-data text-xs text-ink-subtle tabular-nums"
                                >
                                    Postulación:
                                    {{ formatDate(trackedJob.applied_at) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <BaseButton size="lg" @click="openApplyUrl">
                        Abrir postulación
                        <template #trailing>
                            <AppIcon name="expand" class="h-4 w-4" />
                        </template>
                    </BaseButton>
                </div>
            </header>

            <div class="grid grid-cols-1 gap-7 lg:grid-cols-5">
                <div class="min-w-0 lg:col-span-3">
                    <BaseCard
                        v-if="trackedJob.job?.ai_analysis?.resumen_ejecutivo"
                        variant="subtle"
                        class="mb-7"
                    >
                        <div class="mb-2 flex items-center gap-2 text-primary">
                            <AppIcon name="ai-settings" class="h-4 w-4" />
                            <h2 class="text-sm font-semibold text-ink">
                                Lectura de encaje
                            </h2>
                        </div>
                        <p
                            class="max-w-[72ch] text-sm leading-6 text-ink-muted"
                        >
                            {{ trackedJob.job.ai_analysis.resumen_ejecutivo }}
                        </p>
                    </BaseCard>

                    <section aria-labelledby="cv-heading" class="mb-7">
                        <div class="mb-4">
                            <h2
                                id="cv-heading"
                                class="text-xl font-semibold tracking-[-0.02em] text-ink"
                            >
                                CV adaptado a esta vacante
                            </h2>
                            <p class="mt-1 text-sm leading-6 text-ink-muted">
                                Esta vacante conserva su propia versión del CV,
                                distinta de la de otras postulaciones.
                            </p>
                        </div>

                        <BaseCard v-if="cvVariant && cvPdfUrl">
                            <div
                                class="mb-3 flex flex-wrap items-center justify-between gap-3"
                            >
                                <p class="text-sm text-ink-muted">
                                    Versión:
                                    <span class="font-medium text-ink">{{
                                        cvVariant.label
                                    }}</span>
                                </p>
                                <BaseButton
                                    :href="cvPdfDownloadUrl ?? undefined"
                                    as="a"
                                    variant="secondary"
                                    size="sm"
                                >
                                    Descargar PDF
                                    <template #trailing>
                                        <AppIcon
                                            name="expand"
                                            class="h-4 w-4"
                                        />
                                    </template>
                                </BaseButton>
                            </div>
                            <iframe
                                :src="cvPdfUrl"
                                title="CV adaptado en PDF"
                                class="h-[720px] w-full rounded-control border border-line-strong"
                            />
                        </BaseCard>

                        <EmptyState
                            v-else
                            compact
                            title="Todavía no hay un CV confirmado para esta vacante"
                            description="Confirma el tailoring desde el detalle de la vacante en el Marketplace para generar su PDF."
                        >
                            <template #icon>
                                <AppIcon name="ai-settings" class="h-6 w-6" />
                            </template>
                        </EmptyState>
                    </section>

                    <section aria-labelledby="log-heading">
                        <div class="mb-4">
                            <h2
                                id="log-heading"
                                class="text-xl font-semibold tracking-[-0.02em] text-ink"
                            >
                                Bitácora
                            </h2>
                            <p class="mt-1 text-sm leading-6 text-ink-muted">
                                Registra notas, entrevistas y seguimientos en la
                                secuencia de la aplicación.
                            </p>
                        </div>

                        <BaseCard
                            as="form"
                            class="mb-6"
                            @submit.prevent="submitComment"
                        >
                            <BaseTextarea
                                v-model="newCommentBody"
                                name="comment"
                                label="Nueva entrada"
                                :rows="3"
                                autocomplete="off"
                                placeholder="Agrega una nota, entrevista o seguimiento…"
                            />
                            <div
                                class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between"
                            >
                                <div class="w-full sm:max-w-56">
                                    <BaseSelect
                                        v-model="commentTypeModel"
                                        name="comment_type"
                                        label="Tipo de entrada"
                                        :options="commentTypeOptions"
                                    />
                                </div>
                                <BaseButton
                                    type="submit"
                                    :disabled="!newCommentBody.trim()"
                                    :loading="submittingComment"
                                    loading-label="Guardando entrada en la bitácora"
                                >
                                    Agregar a la bitácora
                                </BaseButton>
                            </div>
                        </BaseCard>

                        <ol
                            v-if="trackedJob.comments?.length"
                            class="relative flex flex-col gap-5 before:absolute before:top-4 before:bottom-4 before:left-[0.9375rem] before:w-px before:bg-line"
                        >
                            <li
                                v-for="comment in trackedJob.comments"
                                :key="comment.id"
                                class="relative grid grid-cols-[2rem_minmax(0,1fr)] gap-3"
                            >
                                <span
                                    class="relative z-10 mt-1 flex h-8 w-8 items-center justify-center rounded-full border border-line bg-surface shadow-card"
                                    aria-hidden="true"
                                >
                                    <span
                                        class="h-2 w-2 rounded-full bg-primary"
                                    />
                                </span>
                                <div class="min-w-0 border-b border-line pb-5">
                                    <div
                                        class="flex flex-wrap items-center gap-2"
                                    >
                                        <BaseTag
                                            :tone="commentTone(comment.type)"
                                        >
                                            {{
                                                COMMENT_TYPE_LABELS[
                                                    comment.type
                                                ]
                                            }}
                                        </BaseTag>
                                        <time
                                            class="font-data text-xs text-ink-subtle tabular-nums"
                                            :datetime="
                                                comment.created_at ?? undefined
                                            "
                                        >
                                            {{
                                                formatDateTime(
                                                    comment.created_at,
                                                )
                                            }}
                                        </time>
                                    </div>
                                    <p
                                        class="mt-2 text-sm leading-6 break-words whitespace-pre-line text-ink"
                                    >
                                        {{ comment.body }}
                                    </p>
                                </div>
                            </li>
                        </ol>

                        <EmptyState
                            v-else
                            compact
                            title="La bitácora está vacía"
                            description="Agrega la primera nota para conservar el contexto de esta aplicación."
                        >
                            <template #icon>
                                <AppIcon name="tracking" class="h-6 w-6" />
                            </template>
                        </EmptyState>
                    </section>
                </div>

                <aside class="min-w-0 lg:col-span-2">
                    <div class="flex flex-col gap-5 lg:sticky lg:top-6">
                        <BaseCard
                            v-if="trackedJob.job?.ai_analysis"
                            class="flex flex-col items-center gap-4"
                        >
                            <div class="w-full border-b border-line pb-3">
                                <p class="text-xs font-semibold text-primary">
                                    INSTRUMENTO DE ENCAJE
                                </p>
                                <h2 class="mt-1 text-lg font-semibold text-ink">
                                    Compatibilidad con tu perfil
                                </h2>
                            </div>
                            <MatchScore
                                :score="trackedJob.job.ai_analysis.match_score"
                                size="hero"
                            />
                        </BaseCard>

                        <BaseCard
                            aria-labelledby="application-controls-heading"
                        >
                            <div
                                class="mb-4 flex items-start justify-between gap-3 border-b border-line pb-3"
                            >
                                <div>
                                    <h2
                                        id="application-controls-heading"
                                        class="text-lg font-semibold text-ink"
                                    >
                                        Control de aplicación
                                    </h2>
                                    <p class="mt-1 text-xs text-ink-muted">
                                        Los cambios se guardan al editar cada
                                        campo.
                                    </p>
                                </div>
                                <span
                                    class="min-h-5 text-xs font-medium text-primary"
                                    role="status"
                                    aria-live="polite"
                                >
                                    {{ saving ? 'Guardando…' : '' }}
                                </span>
                            </div>

                            <div class="grid gap-4">
                                <BaseSelect
                                    :model-value="trackedJob.status"
                                    name="status"
                                    label="Estado"
                                    :options="statusOptions"
                                    :disabled="saving"
                                    @change="updateStatus"
                                />

                                <BaseSelect
                                    :model-value="trackedJob.priority ?? ''"
                                    name="priority"
                                    label="Prioridad"
                                    :options="priorityOptions"
                                    :disabled="saving"
                                    @change="updatePriority"
                                />

                                <label class="grid gap-1.5" for="next-action">
                                    <span
                                        class="text-sm font-semibold text-ink"
                                    >
                                        Próxima acción
                                    </span>
                                    <input
                                        id="next-action"
                                        :value="trackedJob.next_action ?? ''"
                                        name="next_action"
                                        type="text"
                                        autocomplete="off"
                                        placeholder="Ej. Enviar seguimiento…"
                                        :disabled="saving"
                                        class="min-h-11 w-full rounded-control border border-line-strong bg-surface px-3 py-2.5 text-sm text-ink shadow-card transition-[border-color,box-shadow,background-color] duration-150 placeholder:text-ink-subtle hover:border-primary focus:border-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus disabled:cursor-not-allowed disabled:bg-surface-subtle disabled:text-ink-subtle"
                                        @change="updateNextAction"
                                    />
                                </label>

                                <label
                                    class="grid gap-1.5"
                                    for="next-action-date"
                                >
                                    <span
                                        class="text-sm font-semibold text-ink"
                                    >
                                        Fecha de próxima acción
                                    </span>
                                    <input
                                        id="next-action-date"
                                        :value="
                                            trackedJob.next_action_date ?? ''
                                        "
                                        name="next_action_date"
                                        type="date"
                                        autocomplete="off"
                                        :disabled="saving"
                                        class="min-h-11 w-full rounded-control border border-line-strong bg-surface px-3 py-2.5 font-data text-sm text-ink shadow-card transition-[border-color,box-shadow,background-color] duration-150 hover:border-primary focus:border-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus disabled:cursor-not-allowed disabled:bg-surface-subtle disabled:text-ink-subtle"
                                        @change="updateNextActionDate"
                                    />
                                </label>

                                <label class="grid gap-1.5" for="cv-version">
                                    <span
                                        class="text-sm font-semibold text-ink"
                                    >
                                        Versión de CV usada
                                    </span>
                                    <input
                                        id="cv-version"
                                        :value="
                                            trackedJob.cv_version_used ?? ''
                                        "
                                        name="cv_version_used"
                                        type="text"
                                        autocomplete="off"
                                        placeholder="Ej. backend-senior-v2…"
                                        :disabled="saving"
                                        class="min-h-11 w-full rounded-control border border-line-strong bg-surface px-3 py-2.5 text-sm text-ink shadow-card transition-[border-color,box-shadow,background-color] duration-150 placeholder:text-ink-subtle hover:border-primary focus:border-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus disabled:cursor-not-allowed disabled:bg-surface-subtle disabled:text-ink-subtle"
                                        @change="updateCvVersion"
                                    />
                                </label>
                            </div>
                        </BaseCard>
                    </div>
                </aside>
            </div>
        </template>

        <EmptyState
            v-else
            title="No se pudo mostrar esta vacante"
            description="Vuelve a Mis vacantes para elegir otra oportunidad."
        >
            <template #icon>
                <AppIcon name="tracking" class="h-6 w-6" />
            </template>
            <template #action>
                <BaseButton @click="router.push('/tracking')">
                    Volver a Mis vacantes
                </BaseButton>
            </template>
        </EmptyState>
    </div>
</template>
