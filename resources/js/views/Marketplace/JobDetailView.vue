<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import DiffViewer from '@/components/DiffViewer.vue';
import {
    BaseButton,
    BaseCard,
    BaseSelect,
    BaseSkeleton,
    BaseTag,
    CompanyLogo,
    MatchScore,
} from '@/components/ui';
import { useToast } from '@/lib/toast';
import { cn, formatCost, formatDuration } from '@/lib/utils';
import { APPLICATION_STATUSES } from '@/types/job';
import type { ApplicationStatus, Job } from '@/types/job';
import type { TailorPreview } from '@/types/profile';

const route = useRoute();
const router = useRouter();
const toast = useToast();

const job = ref<Job | null>(null);
const loading = ref(true);
const publishing = ref(false);
const publishError = ref<string | null>(null);
const updatingStatus = ref(false);
const tracking = ref(false);
const checkedTailoring = ref<boolean[]>([]);

const tailoring = ref(false);
const tailorError = ref<string | null>(null);
const tailorPreview = ref<TailorPreview | null>(null);
const savingVariant = ref(false);
const variantSaved = ref<string | null>(null);

const analysis = computed(() => job.value?.ai_analysis ?? null);

const selectedTailoringItems = computed(
    () =>
        analysis.value?.tailoring_cv?.filter(
            (_, index) => checkedTailoring.value[index],
        ) ?? [],
);
const applicationStatusOptions = APPLICATION_STATUSES.map((status) => ({
    value: status,
    label: status,
}));
const applicationStatus = computed<string | number | null>({
    get: () => job.value?.application_status ?? null,
    set: (status) => {
        if (typeof status === 'string') {
            void updateStatus(status as ApplicationStatus);
        }
    },
});

async function loadJob(id: string | string[]) {
    loading.value = true;

    try {
        const response = await fetch(`/api/jobs/${id}`, {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            throw new Error('Job request failed');
        }

        job.value = await response.json();
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
            loadJob(id);
        }
    },
    { immediate: true },
);

watch(
    () => job.value?.id,
    () => {
        publishError.value = null;
        checkedTailoring.value =
            job.value?.ai_analysis?.tailoring_cv?.map(() => false) ?? [];
    },
);

function openApplyUrl() {
    if (!job.value) {
        return;
    }

    window.open(job.value.apply_url ?? job.value.url, '_blank', 'noopener');
}

async function addToTracking() {
    if (!job.value) {
        return;
    }

    tracking.value = true;

    try {
        const response = await fetch(`/api/marketplace/${job.value.id}/track`, {
            method: 'POST',
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            throw new Error('Tracking request failed');
        }

        const trackedJob = await response.json();
        job.value = { ...job.value, tracked_job: trackedJob };
    } catch {
        toast.error('No se pudo agregar la vacante a Mis vacantes.');
    } finally {
        tracking.value = false;
    }
}

function sleep(ms: number): Promise<void> {
    return new Promise((resolve) => setTimeout(resolve, ms));
}

/**
 * El tailoring corre en un TailorProfile job encolado (igual que
 * AnalyzeJobListing) en vez de llamar a la IA dentro del request HTTP: con
 * claude_cli, macOS solo deja leer el Keychain a procesos lanzados desde una
 * terminal interactiva (el `queue:work` que el usuario corre él mismo), no a
 * PHP-FPM. Por eso hay que despachar y hacer polling del resultado.
 */
async function waitForTailoring(requestId: string): Promise<void> {
    const MAX_ROUNDS = 120;

    for (let round = 0; round < MAX_ROUNDS; round++) {
        const response = await fetch(`/api/profile/tailor/${requestId}`, {
            headers: { Accept: 'application/json' },
        });
        const data = await response.json();

        if (!response.ok) {
            tailorError.value = data.message ?? 'No se pudo adaptar el CV.';

            return;
        }

        if (data.status === 'completed') {
            tailorPreview.value = data;

            return;
        }

        if (data.status === 'failed') {
            tailorError.value = data.message ?? 'No se pudo adaptar el CV.';

            return;
        }

        await sleep(1500);
    }

    tailorError.value =
        'El tailoring está tardando demasiado. ¿Está corriendo `php artisan queue:work` en tu terminal?';
}

async function applyTailoring() {
    if (!job.value || selectedTailoringItems.value.length === 0) {
        return;
    }

    tailoring.value = true;
    tailorError.value = null;
    tailorPreview.value = null;
    variantSaved.value = null;

    try {
        const response = await fetch('/api/profile/tailor', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
            },
            body: JSON.stringify({
                job_id: job.value.id,
                items: selectedTailoringItems.value,
            }),
        });
        const data = await response.json();

        if (!response.ok) {
            tailorError.value = data.message ?? 'No se pudo adaptar el CV.';

            return;
        }

        await waitForTailoring(data.request_id);
    } catch {
        tailorError.value = 'No se pudo conectar con el servicio de IA.';
    } finally {
        tailoring.value = false;
    }
}

async function confirmTailoring() {
    if (!tailorPreview.value) {
        return;
    }

    savingVariant.value = true;

    try {
        const response = await fetch('/api/profile/tailor/confirm', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
            },
            body: JSON.stringify({
                job_id: tailorPreview.value.job_id,
                overrides: tailorPreview.value.overrides,
            }),
        });
        const data = await response.json();

        if (response.ok) {
            variantSaved.value = data.slug;
            tailorPreview.value = null;
        } else {
            tailorError.value =
                data.message ?? 'No se pudo guardar la variante.';
        }
    } catch {
        tailorError.value = 'No se pudo conectar con el servidor.';
    } finally {
        savingVariant.value = false;
    }
}

function discardTailoring() {
    tailorPreview.value = null;
    tailorError.value = null;
}

async function publish() {
    if (!job.value) {
        return;
    }

    publishing.value = true;
    publishError.value = null;

    try {
        const response = await fetch(`/api/jobs/${job.value.id}/publish`, {
            method: 'POST',
            headers: { Accept: 'application/json' },
        });

        const data = await response.json();

        if (!response.ok) {
            publishError.value =
                data.message ?? 'No se pudo publicar en Notion.';

            return;
        }

        const updated = data as Job;

        if (updated.status === 'failed') {
            publishError.value =
                updated.error_message ?? 'No se pudo publicar en Notion.';
        }

        job.value = updated;
    } catch {
        publishError.value = 'No se pudo conectar con Notion.';
    } finally {
        publishing.value = false;
    }
}

async function updateStatus(applicationStatus: ApplicationStatus) {
    if (!job.value) {
        return;
    }

    updatingStatus.value = true;

    try {
        const response = await fetch(`/api/jobs/${job.value.id}`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
            },
            body: JSON.stringify({ application_status: applicationStatus }),
        });

        if (!response.ok) {
            throw new Error('Status update failed');
        }

        job.value = await response.json();
    } catch {
        toast.error('No se pudo actualizar el estado local.');
    } finally {
        updatingStatus.value = false;
    }
}
</script>

<template>
    <div class="mx-auto max-w-6xl">
        <BaseButton
            variant="quiet"
            class="mb-4"
            @click="router.push('/marketplace')"
        >
            Volver al Marketplace
        </BaseButton>

        <div v-if="loading" class="grid grid-cols-1 gap-6 lg:grid-cols-5">
            <div class="flex flex-col gap-4 lg:col-span-3">
                <div class="flex items-center gap-4">
                    <BaseSkeleton class="h-12 w-12" />
                    <div class="flex-1">
                        <BaseSkeleton shape="text" class="mb-2 h-5 w-2/3" />
                        <BaseSkeleton shape="text" class="w-1/3" />
                    </div>
                </div>
                <BaseSkeleton class="h-24 w-full" />
                <BaseSkeleton shape="text" class="w-full" />
                <BaseSkeleton shape="text" class="w-5/6" />
                <BaseSkeleton shape="text" class="w-2/3" />
            </div>
            <div class="lg:col-span-2">
                <BaseSkeleton class="h-64 w-full" />
            </div>
        </div>

        <div v-else-if="job" class="grid grid-cols-1 gap-6 lg:grid-cols-5">
            <div class="lg:col-span-3">
                <header
                    class="mb-5 flex items-start gap-4 border-b border-line pb-5"
                >
                    <CompanyLogo
                        :company="job.company"
                        :src="job.company_logo"
                        size="lg"
                    />
                    <div>
                        <h1
                            class="text-2xl font-semibold tracking-[-0.03em] text-ink"
                        >
                            {{ job.title }}
                        </h1>
                        <p class="mt-1 text-sm text-ink-muted">
                            {{ job.company }} · {{ job.source }}
                        </p>
                    </div>
                </header>

                <div class="mb-6 flex flex-wrap gap-2">
                    <BaseTag v-if="job.location">{{ job.location }}</BaseTag>
                    <BaseTag v-if="job.work_mode || job.seniority">
                        {{
                            [job.work_mode, job.seniority]
                                .filter(Boolean)
                                .join(' · ')
                        }}
                    </BaseTag>
                    <BaseTag v-if="job.employment_type">
                        {{ job.employment_type }}
                    </BaseTag>
                </div>

                <BaseCard
                    v-if="analysis?.resumen_ejecutivo"
                    variant="subtle"
                    class="mb-8"
                >
                    <h2 class="mb-2 text-sm font-semibold text-ink">
                        Resumen ejecutivo
                    </h2>
                    <p class="text-sm leading-6 text-ink-muted">
                        {{ analysis.resumen_ejecutivo }}
                    </p>
                </BaseCard>

                <section class="mb-8">
                    <h2 class="mb-3 text-lg font-semibold text-ink">
                        Descripción
                    </h2>
                    <p
                        class="max-w-[72ch] text-sm leading-6 whitespace-pre-line text-ink-muted"
                    >
                        {{ job.description }}
                    </p>
                </section>

                <section v-if="job.required_skills?.length" class="mb-8">
                    <h2 class="mb-3 text-lg font-semibold text-ink">Skills</h2>
                    <div class="flex flex-wrap gap-2">
                        <BaseTag
                            v-for="(skill, index) in job.required_skills"
                            :key="index"
                            tone="primary"
                        >
                            {{ skill }}
                        </BaseTag>
                    </div>
                </section>

                <section v-if="job.benefits?.length" class="mb-8">
                    <h2 class="mb-3 text-lg font-semibold text-ink">
                        Beneficios
                    </h2>
                    <ul class="grid gap-2 text-sm leading-6 text-ink-muted">
                        <li
                            v-for="(benefit, index) in job.benefits"
                            :key="index"
                            class="flex gap-2 before:mt-2.5 before:h-1 before:w-1 before:shrink-0 before:rounded-full before:bg-primary"
                        >
                            {{ benefit }}
                        </li>
                    </ul>
                </section>

                <template v-if="analysis">
                    <section class="mb-8 border-t border-line pt-7">
                        <h2 class="mb-3 text-lg font-semibold text-ink">
                            Tips para postular
                        </h2>
                        <ul class="grid gap-2 text-sm leading-6 text-ink-muted">
                            <li
                                v-for="(
                                    tip, index
                                ) in analysis.tips_postulacion"
                                :key="index"
                                class="flex gap-2 before:mt-2.5 before:h-1 before:w-1 before:shrink-0 before:rounded-full before:bg-primary"
                            >
                                {{ tip }}
                            </li>
                        </ul>
                    </section>

                    <section class="mb-8 border-t border-line pt-7">
                        <h2 class="mb-1 text-lg font-semibold text-ink">
                            Adaptar el CV
                        </h2>
                        <p class="mb-4 text-sm leading-6 text-ink-muted">
                            Selecciona ajustes concretos. Primero verás una
                            comparación y nada se guardará sin tu confirmación.
                        </p>
                        <ul class="grid gap-2 text-sm">
                            <li
                                v-for="(
                                    adjustment, index
                                ) in analysis.tailoring_cv"
                                :key="index"
                                class="flex items-start gap-3 rounded-control border border-line bg-surface px-3 py-3"
                            >
                                <input
                                    :id="`tailoring-${index}`"
                                    v-model="checkedTailoring[index]"
                                    type="checkbox"
                                    class="mt-1 h-4 w-4 accent-primary"
                                />
                                <label
                                    :for="`tailoring-${index}`"
                                    :class="
                                        cn(
                                            'leading-6 text-ink-muted',
                                            checkedTailoring[index] &&
                                                'text-ink-subtle line-through',
                                        )
                                    "
                                >
                                    {{ adjustment }}
                                </label>
                            </li>
                        </ul>

                        <div class="mt-4 flex flex-wrap items-center gap-3">
                            <BaseButton
                                size="sm"
                                :disabled="selectedTailoringItems.length === 0"
                                :loading="tailoring"
                                loading-label="Adaptando el CV"
                                @click="applyTailoring"
                            >
                                Preparar cambios seleccionados
                            </BaseButton>
                            <span
                                v-if="variantSaved"
                                class="text-xs font-medium text-success"
                                role="status"
                            >
                                Variante guardada: {{ variantSaved }}
                            </span>
                        </div>

                        <p
                            v-if="tailorError"
                            class="mt-3 rounded-control bg-error-surface px-3 py-2 text-xs font-medium text-error"
                            role="alert"
                        >
                            {{ tailorError }}
                        </p>

                        <p
                            v-else-if="tailoring"
                            class="mt-3 text-xs font-medium text-ink-muted"
                            role="status"
                            aria-live="polite"
                        >
                            Claude está preparando la comparación. Puedes
                            mantener esta vista abierta mientras trabaja el
                            worker local.
                        </p>

                        <div v-if="tailorPreview" class="mt-4">
                            <p class="mb-3 text-xs font-medium text-ink-muted">
                                Revisa la comparación. Nada se guarda hasta que
                                confirmes.
                            </p>
                            <DiffViewer
                                :before="tailorPreview.before_markdown"
                                :after="tailorPreview.after_markdown"
                            />
                            <div class="mt-3 flex flex-wrap gap-2">
                                <BaseButton
                                    size="sm"
                                    :loading="savingVariant"
                                    loading-label="Guardando una nueva variante"
                                    @click="confirmTailoring"
                                >
                                    Guardar como nueva variante
                                </BaseButton>
                                <BaseButton
                                    size="sm"
                                    variant="quiet"
                                    @click="discardTailoring"
                                >
                                    Descartar
                                </BaseButton>
                            </div>
                        </div>
                    </section>

                    <section
                        v-if="analysis.red_flags?.length"
                        class="mb-8 border-t border-line pt-7"
                    >
                        <h2 class="mb-3 text-lg font-semibold text-error">
                            Señales de alerta
                        </h2>
                        <ul class="grid gap-2 text-sm leading-6 text-error">
                            <li
                                v-for="(flag, index) in analysis.red_flags"
                                :key="index"
                                class="rounded-control bg-error-surface px-3 py-2"
                            >
                                {{ flag }}
                            </li>
                        </ul>
                    </section>
                </template>
                <p
                    v-else-if="job.status === 'analyzing'"
                    class="rounded-control bg-info-surface px-3 py-2 text-sm text-info"
                    role="status"
                >
                    Analizando con IA.
                </p>
                <p v-else class="text-sm text-ink-muted">
                    Esta vacante todavía no ha sido analizada.
                </p>
            </div>

            <aside class="lg:col-span-2">
                <BaseCard class="sticky top-6 flex flex-col gap-5">
                    <BaseButton size="lg" @click="openApplyUrl">
                        Abrir formulario de postulación
                    </BaseButton>

                    <BaseButton
                        variant="secondary"
                        :disabled="!!job.tracked_job"
                        :loading="tracking"
                        loading-label="Agregando a Mis vacantes"
                        @click="addToTracking"
                    >
                        {{
                            job.tracked_job
                                ? 'Ya está en Mis vacantes'
                                : 'Agregar a Mis vacantes'
                        }}
                    </BaseButton>

                    <div
                        v-if="analysis"
                        class="border-y border-line py-5 text-center"
                    >
                        <MatchScore :score="analysis.match_score" size="hero" />
                    </div>

                    <p
                        v-if="job.status === 'failed' && job.error_message"
                        class="rounded-control bg-error-surface p-3 text-sm leading-6 text-error"
                        role="alert"
                    >
                        {{ job.error_message }}
                    </p>

                    <dl v-if="analysis" class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <dt class="text-xs font-medium text-ink-subtle">
                                Idioma
                            </dt>
                            <dd class="mt-1 text-ink">{{ analysis.idioma }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-ink-subtle">
                                Tipo de contrato
                            </dt>
                            <dd class="mt-1 text-ink">
                                {{ analysis.tipo_contrato }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-ink-subtle">
                                Salario
                            </dt>
                            <dd class="mt-1 text-ink">
                                {{ analysis.salario_normalizado }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-ink-subtle">
                                Moneda
                            </dt>
                            <dd class="mt-1 text-ink">{{ analysis.moneda }}</dd>
                        </div>
                        <div class="col-span-2">
                            <dt class="text-xs font-medium text-ink-subtle">
                                Inglés requerido
                            </dt>
                            <dd class="mt-1 flex items-center gap-2 text-ink">
                                {{
                                    analysis.ingles_requerido ??
                                    'No especificado'
                                }}
                                <BaseTag
                                    v-if="analysis.alerta_ingles"
                                    tone="warning"
                                >
                                    Supera tu nivel declarado
                                </BaseTag>
                            </dd>
                        </div>
                    </dl>

                    <BaseSelect
                        v-model="applicationStatus"
                        label="Estado local"
                        :options="applicationStatusOptions"
                        :disabled="updatingStatus"
                    />

                    <div class="flex flex-col gap-2 border-t border-line pt-4">
                        <p
                            v-if="publishError"
                            class="text-sm text-error"
                            role="alert"
                        >
                            {{ publishError }}
                        </p>
                        <p
                            v-if="job.notion_page_id"
                            class="text-sm font-medium text-success"
                            role="status"
                        >
                            Publicado en Notion
                        </p>
                        <BaseButton
                            variant="secondary"
                            :disabled="!analysis"
                            :loading="publishing"
                            loading-label="Publicando en Notion"
                            @click="publish"
                        >
                            Publicar en Notion
                        </BaseButton>
                    </div>

                    <div
                        v-if="job.ai_duration_ms !== null"
                        class="grid gap-1 border-t border-line pt-4 font-data text-xs text-ink-subtle tabular-nums"
                    >
                        <span>
                            Proveedor: {{ job.ai_provider
                            }}<span v-if="job.ai_model">
                                · {{ job.ai_model }}</span
                            >
                        </span>
                        <span
                            >Duración:
                            {{ formatDuration(job.ai_duration_ms) }}</span
                        >
                        <span>Coste: {{ formatCost(job.ai_cost_usd) }}</span>
                    </div>
                </BaseCard>
            </aside>
        </div>
    </div>
</template>
