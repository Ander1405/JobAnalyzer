<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import AiProviderSelector from '@/components/AiProviderSelector.vue';
import AppIcon from '@/components/AppIcon.vue';
import JobCard from '@/components/JobCard.vue';
import JobsTable from '@/components/JobsTable.vue';
import MarketplaceFilters from '@/components/MarketplaceFilters.vue';
import {
    BaseButton,
    BaseCard,
    BaseSkeleton,
    BaseTag,
    EmptyState,
} from '@/components/ui';
import { usePersistedRef } from '@/lib/persisted';
import { useToast } from '@/lib/toast';
import { formatCost, formatDuration } from '@/lib/utils';
import type { Job, PaginatedJobs, PaginationMeta } from '@/types/job';
import { defaultMarketplaceFilters } from '@/types/marketplace';
import type { MarketplaceFilters as MarketplaceFiltersType } from '@/types/marketplace';

const router = useRouter();
const page = usePage();
const toast = useToast();
const threshold = page.props.matchScoreAlertThreshold;

const PER_PAGE = 20;

const jobs = ref<Job[]>([]);
const sources = ref<string[]>([]);
const loading = ref(false);
const fetching = ref(false);
const analyzing = ref(false);
const currentPage = ref(1);
const meta = ref<PaginationMeta | null>(null);
const viewMode = usePersistedRef<'grid' | 'table'>(
    'marketplace:viewMode',
    'table',
);

const filters = usePersistedRef<MarketplaceFiltersType>('marketplace:filters', {
    ...defaultMarketplaceFilters(),
    minMatch: page.props.minMatchToPublish,
});

const pendingAnalysis = computed(() => meta.value?.pending_analysis ?? 0);

const selectedIds = ref<Set<number>>(new Set());
const trackingIds = ref<Set<number>>(new Set());
const bulkTracking = ref(false);

type AnalysisSummary = {
    count: number;
    durationMs: number;
    inputTokens: number;
    outputTokens: number;
    costUsd: number;
    hasUnknownCost: boolean;
};

const analysisSummary = ref<AnalysisSummary | null>(null);

type FetchProgress = { found: number; pendingAnalysis: number };
type AnalysisProgress = { total: number; done: number; failed: number };

const fetchProgress = ref<FetchProgress | null>(null);
const analysisProgress = ref<AnalysisProgress | null>(null);

let debounceTimer: ReturnType<typeof setTimeout> | undefined;

watch(
    filters,
    () => {
        currentPage.value = 1;
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(loadJobs, 300);
    },
    { deep: true },
);

onMounted(() => {
    loadJobs();
    loadSources();
});

function buildParams(
    overrides: Record<string, string | null> = {},
): URLSearchParams {
    const params = new URLSearchParams();

    if (filters.value.source) {
        params.set('source', filters.value.source);
    }

    if (filters.value.workMode) {
        params.set('work_mode', filters.value.workMode);
    }

    if (filters.value.seniority) {
        params.set('seniority', filters.value.seniority);
    }

    if (filters.value.language) {
        params.set('language', filters.value.language);
    }

    if (filters.value.minMatch > 0) {
        params.set('min_match', String(filters.value.minMatch));
    }

    if (filters.value.search) {
        params.set('search', filters.value.search);
    }

    if (filters.value.hasSalaryOnly) {
        params.set('has_salary_only', '1');
    }

    if (filters.value.hideTracked) {
        params.set('hide_tracked', '1');
    }

    params.set('sort', filters.value.sort);

    for (const [key, value] of Object.entries(overrides)) {
        if (value === null) {
            params.delete(key);
        } else {
            params.set(key, value);
        }
    }

    return params;
}

async function loadJobs() {
    loading.value = true;

    const params = buildParams({
        page: String(currentPage.value),
        per_page: String(PER_PAGE),
    });

    try {
        const response = await fetch(`/api/marketplace?${params.toString()}`, {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            throw new Error('Marketplace request failed');
        }

        const paginated: PaginatedJobs = await response.json();
        jobs.value = paginated.data;
        meta.value = paginated.meta;
        currentPage.value = paginated.meta.current_page;
    } catch {
        toast.error('No se pudieron cargar las vacantes.');
    } finally {
        loading.value = false;
    }
}

async function loadSources() {
    try {
        const response = await fetch('/api/jobs/sources', {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            throw new Error('Sources request failed');
        }

        sources.value = await response.json();
    } catch {
        toast.error('No se pudieron cargar las fuentes.');
    }
}

function goToPage(nextPage: number) {
    currentPage.value = nextPage;
    loadJobs();
}

function resetFilters() {
    filters.value = {
        ...defaultMarketplaceFilters(),
        minMatch: page.props.minMatchToPublish,
    };
}

async function searchNew() {
    fetching.value = true;
    fetchProgress.value = {
        found: meta.value?.total ?? 0,
        pendingAnalysis: meta.value?.pending_analysis ?? 0,
    };

    try {
        const response = await fetch('/api/jobs/fetch', {
            method: 'POST',
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            throw new Error('fetch failed');
        }

        await waitForFetch();
    } catch {
        toast.error('No se pudieron buscar nuevas vacantes.');
    } finally {
        fetching.value = false;
        fetchProgress.value = null;
    }
}

/**
 * El fetch corre en la cola, así que la vista no tiene un "listo" que esperar:
 * refresca durante un rato y se detiene cuando el conteo deja de moverse. Cada
 * ronda actualiza fetchProgress para que la UI muestre cuántas van apareciendo
 * en vez de un spinner ciego.
 */
async function waitForFetch() {
    const POLLS = 20;
    const INTERVAL_MS = 3000;

    let previousTotal = -1;
    let stableRounds = 0;

    for (let i = 0; i < POLLS; i++) {
        await sleep(INTERVAL_MS);
        await Promise.all([loadJobs(), loadSources()]);

        const found = meta.value?.total ?? 0;
        const pendingAnalysisCount = meta.value?.pending_analysis ?? 0;
        fetchProgress.value = { found, pendingAnalysis: pendingAnalysisCount };

        const total = found + pendingAnalysisCount;

        stableRounds = total === previousTotal ? stableRounds + 1 : 0;
        previousTotal = total;

        if (stableRounds >= 2 && i > 2) {
            return;
        }
    }
}

function sleep(ms: number): Promise<void> {
    return new Promise((resolve) => setTimeout(resolve, ms));
}

async function fetchJob(id: number): Promise<Job | null> {
    const response = await fetch(`/api/jobs/${id}`, {
        headers: { Accept: 'application/json' },
    });

    return response.ok ? response.json() : null;
}

/**
 * Se indexa por el id pedido, no por el del cuerpo de la respuesta: una vacante
 * que desaparece devuelve 404 sin id, y con eso el Map nunca se completaba y el
 * sondeo quedaba girando para siempre. El límite de rondas cubre el caso de un
 * worker caído, donde la vacante se queda en "analyzing". Cada ronda actualiza
 * analysisProgress para que la UI muestre cuántas van, no solo un spinner.
 */
async function waitForAnalysis(ids: number[]): Promise<Job[]> {
    const MAX_ROUNDS = 240;
    const resolved = new Map<number, Job | null>();

    for (let round = 0; round < MAX_ROUNDS; round++) {
        if (resolved.size >= ids.length) {
            break;
        }

        await sleep(1500);

        const pendingIds = ids.filter((id) => !resolved.has(id));
        const jobs = await Promise.all(pendingIds.map(fetchJob));

        pendingIds.forEach((id, index) => {
            const job = jobs[index];

            if (job === null || job.status !== 'analyzing') {
                resolved.set(id, job);
            }
        });

        const failed = Array.from(resolved.values()).filter(
            (job) => job?.status === 'failed',
        ).length;

        analysisProgress.value = {
            total: ids.length,
            done: resolved.size,
            failed,
        };

        await loadJobs();
    }

    return ids
        .map((id) => resolved.get(id))
        .filter((job): job is Job => job != null);
}

async function analyzePending() {
    analyzing.value = true;
    analysisSummary.value = null;

    try {
        const response = await fetch('/api/marketplace/analyze-pending', {
            method: 'POST',
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            throw new Error('Analysis dispatch failed');
        }

        const { dispatched } = (await response.json()) as {
            dispatched: number[];
        };

        if (dispatched.length === 0) {
            return;
        }

        analysisProgress.value = {
            total: dispatched.length,
            done: 0,
            failed: 0,
        };

        await loadJobs();

        const analyzed = await waitForAnalysis(dispatched);

        const summary: AnalysisSummary = {
            count: 0,
            durationMs: 0,
            inputTokens: 0,
            outputTokens: 0,
            costUsd: 0,
            hasUnknownCost: false,
        };

        for (const job of analyzed) {
            if (job.status !== 'analyzed') {
                continue;
            }

            summary.count++;
            summary.durationMs += job.ai_duration_ms ?? 0;
            summary.inputTokens += job.ai_input_tokens ?? 0;
            summary.outputTokens += job.ai_output_tokens ?? 0;

            if (job.ai_cost_usd === null) {
                summary.hasUnknownCost = true;
            } else {
                summary.costUsd += job.ai_cost_usd;
            }
        }

        // Un análisis que falla deja la vacante sin score y fuera del filtro: sin
        // este aviso el usuario solo ve una lista vacía y nada que lo explique.
        const failed = analyzed.filter((job) => job.status === 'failed');

        if (failed.length > 0) {
            toast.error(
                `${failed.length} vacante(s) no se pudieron analizar: ${failed[0].error_message ?? 'error del proveedor de IA'}`,
            );
        }

        analysisSummary.value = summary.count > 0 ? summary : null;

        await loadJobs();
    } catch {
        toast.error(
            'No se pudo completar el análisis de las vacantes pendientes.',
        );
    } finally {
        analyzing.value = false;
        analysisProgress.value = null;
    }
}

function openJob(job: Job) {
    router.push(`/marketplace/${job.id}`);
}

function toggleSelect(job: Job) {
    if (selectedIds.value.has(job.id)) {
        selectedIds.value.delete(job.id);
    } else {
        selectedIds.value.add(job.id);
    }
}

function clearSelection() {
    selectedIds.value.clear();
}

async function quickTrack(job: Job) {
    if (job.tracked_job || trackingIds.value.has(job.id)) {
        return;
    }

    trackingIds.value.add(job.id);

    try {
        const response = await fetch(`/api/marketplace/${job.id}/track`, {
            method: 'POST',
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            throw new Error('Tracking request failed');
        }

        const trackedJob = await response.json();
        const target = jobs.value.find((candidate) => candidate.id === job.id);

        if (target) {
            target.tracked_job = trackedJob;
        }
    } catch {
        toast.error('No se pudo agregar la vacante a Mis vacantes.');
    } finally {
        trackingIds.value.delete(job.id);
    }
}

async function bulkTrack() {
    if (selectedIds.value.size === 0) {
        return;
    }

    bulkTracking.value = true;

    try {
        const response = await fetch('/api/marketplace/track-bulk', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
            },
            body: JSON.stringify({ job_ids: Array.from(selectedIds.value) }),
        });

        if (!response.ok) {
            throw new Error('Bulk tracking request failed');
        }

        clearSelection();
        await loadJobs();
    } catch {
        toast.error('No se pudieron agregar las vacantes seleccionadas.');
    } finally {
        bulkTracking.value = false;
    }
}
</script>

<template>
    <div class="mx-auto max-w-7xl">
        <header class="mb-7 border-b border-line pb-5">
            <p class="mb-2 text-xs font-semibold text-primary">
                PANEL DE DECISIÓN
            </p>
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1
                        class="text-3xl font-semibold tracking-[-0.04em] text-ink"
                    >
                        Marketplace
                    </h1>
                    <p class="mt-1 text-sm text-ink-muted">
                        {{
                            meta
                                ? `${meta.total} vacantes disponibles para revisar y priorizar.`
                                : 'Revisa el encaje de cada vacante antes de decidir.'
                        }}
                    </p>
                </div>
                <BaseTag tone="primary">Señales de tu perfil activas</BaseTag>
            </div>
        </header>

        <AiProviderSelector class="mb-5" />

        <BaseCard
            v-if="fetchProgress"
            variant="subtle"
            class="mb-5 text-sm text-ink"
            role="status"
            aria-live="polite"
        >
            <p class="font-medium">
                Buscando vacantes en segundo plano ·
                {{ fetchProgress.found }} encontrada{{
                    fetchProgress.found === 1 ? '' : 's'
                }}
                hasta ahora
                <template v-if="fetchProgress.pendingAnalysis > 0">
                    · {{ fetchProgress.pendingAnalysis }} pendiente{{
                        fetchProgress.pendingAnalysis === 1 ? '' : 's'
                    }}
                    de analizar
                </template>
            </p>
        </BaseCard>

        <BaseCard
            v-if="analysisProgress"
            variant="subtle"
            class="mb-5 flex flex-col gap-3 text-sm text-ink"
            role="status"
            aria-live="polite"
        >
            <div class="flex items-center justify-between gap-4">
                <span>
                    Analizando {{ analysisProgress.done }}/{{
                        analysisProgress.total
                    }}
                    <template v-if="analysisProgress.failed > 0">
                        · {{ analysisProgress.failed }} fallida{{
                            analysisProgress.failed === 1 ? '' : 's'
                        }}
                    </template>
                </span>
                <span class="font-data font-semibold tabular-nums"
                    >{{
                        Math.round(
                            (analysisProgress.done / analysisProgress.total) *
                                100,
                        )
                    }}%</span
                >
            </div>
            <div class="h-1.5 w-full overflow-hidden rounded-full bg-line">
                <div
                    class="h-full rounded-full bg-primary transition-[width] duration-300 ease-signal-out"
                    :style="{
                        width: `${(analysisProgress.done / analysisProgress.total) * 100}%`,
                    }"
                />
            </div>
        </BaseCard>

        <BaseCard
            v-if="analysisSummary"
            class="mb-5 flex items-center justify-between gap-4 border-success/20 bg-success-surface text-sm text-success"
            role="status"
            aria-live="polite"
        >
            <span>
                Análisis completado · {{ analysisSummary.count }} vacante{{
                    analysisSummary.count === 1 ? '' : 's'
                }}
                · {{ formatDuration(analysisSummary.durationMs) }} ·
                {{ analysisSummary.inputTokens + analysisSummary.outputTokens }}
                tokens ·
                {{
                    analysisSummary.hasUnknownCost
                        ? `${formatCost(analysisSummary.costUsd)}+`
                        : formatCost(analysisSummary.costUsd)
                }}
            </span>
            <BaseButton
                variant="quiet"
                size="icon"
                aria-label="Cerrar resumen del análisis"
                @click="analysisSummary = null"
            >
                <AppIcon name="close" class="h-4 w-4" />
            </BaseButton>
        </BaseCard>

        <MarketplaceFilters
            v-model:filters="filters"
            v-model:view-mode="viewMode"
            :sources="sources"
            :fetching="fetching"
            :analyzing="analyzing"
            :pending-analysis="pendingAnalysis"
            class="mb-4"
            @search-new="searchNew"
            @analyze-pending="analyzePending"
        />

        <BaseCard
            v-if="selectedIds.size > 0"
            variant="subtle"
            class="mb-4 flex items-center justify-between gap-4 text-sm text-ink"
        >
            <span
                >{{ selectedIds.size }} vacante{{
                    selectedIds.size === 1 ? '' : 's'
                }}
                seleccionada{{ selectedIds.size === 1 ? '' : 's' }}</span
            >
            <div class="flex gap-2">
                <BaseButton variant="quiet" size="sm" @click="clearSelection">
                    Limpiar
                </BaseButton>
                <BaseButton
                    size="sm"
                    :loading="bulkTracking"
                    loading-label="Agregando vacantes seleccionadas"
                    @click="bulkTrack"
                >
                    Agregar a Mis vacantes
                </BaseButton>
            </div>
        </BaseCard>

        <div v-if="viewMode === 'grid'" class="flex flex-col gap-4">
            <div
                v-if="loading"
                class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3"
            >
                <div
                    v-for="n in 6"
                    :key="n"
                    class="flex flex-col gap-3 rounded-card border border-line bg-surface p-4 shadow-card"
                >
                    <div class="flex items-center gap-3">
                        <BaseSkeleton shape="circle" class="h-10 w-10" />
                        <div class="flex-1">
                            <BaseSkeleton shape="text" class="mb-2 w-3/4" />
                            <BaseSkeleton shape="text" class="w-1/2" />
                        </div>
                    </div>
                    <BaseSkeleton shape="text" class="w-full" />
                    <BaseSkeleton shape="text" class="w-2/3" />
                </div>
            </div>
            <EmptyState
                v-else-if="jobs.length === 0"
                title="No hay vacantes con estos filtros"
                description="Limpia los criterios activos o busca nuevas ofertas para ampliar los resultados."
            >
                <template #action>
                    <BaseButton variant="secondary" @click="resetFilters">
                        Limpiar filtros
                    </BaseButton>
                    <BaseButton
                        :loading="fetching"
                        loading-label="Buscando nuevas vacantes"
                        @click="searchNew"
                    >
                        Buscar nuevas ofertas
                    </BaseButton>
                </template>
            </EmptyState>
            <div
                v-else
                class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3"
            >
                <JobCard
                    v-for="job in jobs"
                    :key="job.id"
                    :job="job"
                    :selected="selectedIds.has(job.id)"
                    :tracking="trackingIds.has(job.id)"
                    @open="openJob(job)"
                    @toggle-select="toggleSelect(job)"
                    @quick-track="quickTrack(job)"
                />
            </div>

            <div
                v-if="meta && meta.total > 0"
                class="flex flex-wrap items-center justify-between gap-3 text-sm text-ink-muted"
            >
                <span>
                    Mostrando
                    {{ (meta.current_page - 1) * meta.per_page + 1 }}–{{
                        Math.min(meta.current_page * meta.per_page, meta.total)
                    }}
                    de {{ meta.total }}
                </span>
                <div class="flex items-center gap-2">
                    <BaseButton
                        size="sm"
                        variant="secondary"
                        :disabled="meta.current_page <= 1"
                        @click="goToPage(meta.current_page - 1)"
                    >
                        Anterior
                    </BaseButton>
                    <span class="font-data text-xs tabular-nums">
                        Página {{ meta.current_page }} de {{ meta.last_page }}
                    </span>
                    <BaseButton
                        size="sm"
                        variant="secondary"
                        :disabled="meta.current_page >= meta.last_page"
                        @click="goToPage(meta.current_page + 1)"
                    >
                        Siguiente
                    </BaseButton>
                </div>
            </div>
        </div>

        <JobsTable
            v-else
            :jobs="jobs"
            :meta="meta"
            :loading="loading"
            :fetching="fetching"
            :threshold="threshold"
            :selected-ids="Array.from(selectedIds)"
            @select="openJob"
            @page-change="goToPage"
            @toggle-select="toggleSelect"
            @search-new="searchNew"
            @reset-filters="resetFilters"
        />
    </div>
</template>
