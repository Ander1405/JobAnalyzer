<script setup lang="ts">
import { cn } from '@/lib/utils';
import type {
    ApplicationStatus,
    Job,
    JobStatus,
    PaginationMeta,
} from '@/types/job';

export type JobFilters = {
    status: JobStatus | '';
    source: string;
    minMatch: number | null;
    search: string;
};

const filters = defineModel<JobFilters>('filters', { required: true });

const props = defineProps<{
    jobs: Job[];
    sources: string[];
    meta: PaginationMeta | null;
    loading: boolean;
    fetching: boolean;
    analyzing: boolean;
    threshold: number;
}>();

const emit = defineEmits<{
    select: [job: Job];
    'search-new': [];
    'analyze-pending': [];
    'page-change': [page: number];
}>();

function rangeStart(): number {
    if (!props.meta || props.meta.total === 0) {
        return 0;
    }

    return (props.meta.current_page - 1) * props.meta.per_page + 1;
}

function rangeEnd(): number {
    if (!props.meta) {
        return 0;
    }

    return Math.min(
        props.meta.current_page * props.meta.per_page,
        props.meta.total,
    );
}

const statusOptions: JobStatus[] = [
    'fetched',
    'analyzed',
    'published',
    'failed',
];

function matchScore(job: Job): number | null {
    return job.ai_analysis?.match_score ?? null;
}

function matchBadgeClass(score: number | null): string {
    if (score === null) {
        return 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400';
    }

    if (score >= 80) {
        return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300';
    }

    if (score >= 50) {
        return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300';
    }

    return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300';
}

function statusBadgeClass(status: ApplicationStatus): string {
    return {
        Nueva: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
        'CV adaptado':
            'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
        Aplicada:
            'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-300',
        Entrevista:
            'bg-teal-100 text-teal-800 dark:bg-teal-900 dark:text-teal-300',
        Cerrada:
            'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
    }[status];
}
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="flex flex-wrap items-end gap-3">
                <label class="flex flex-col gap-1 text-sm">
                    <span class="text-gray-600 dark:text-gray-400">Estado</span>
                    <select
                        v-model="filters.status"
                        class="rounded-md border border-gray-300 bg-white px-2 py-1.5 text-sm dark:border-gray-700 dark:bg-gray-900"
                    >
                        <option value="">Todos</option>
                        <option
                            v-for="status in statusOptions"
                            :key="status"
                            :value="status"
                        >
                            {{ status }}
                        </option>
                    </select>
                </label>

                <label class="flex flex-col gap-1 text-sm">
                    <span class="text-gray-600 dark:text-gray-400">Fuente</span>
                    <select
                        v-model="filters.source"
                        class="rounded-md border border-gray-300 bg-white px-2 py-1.5 text-sm dark:border-gray-700 dark:bg-gray-900"
                    >
                        <option value="">Todas</option>
                        <option
                            v-for="source in sources"
                            :key="source"
                            :value="source"
                        >
                            {{ source }}
                        </option>
                    </select>
                </label>

                <label class="flex flex-col gap-1 text-sm">
                    <span class="text-gray-600 dark:text-gray-400"
                        >Match % mínimo</span
                    >
                    <input
                        v-model.number="filters.minMatch"
                        type="number"
                        min="0"
                        max="100"
                        placeholder="0"
                        class="w-24 rounded-md border border-gray-300 bg-white px-2 py-1.5 text-sm dark:border-gray-700 dark:bg-gray-900"
                    />
                </label>

                <label class="flex flex-col gap-1 text-sm">
                    <span class="text-gray-600 dark:text-gray-400">Buscar</span>
                    <input
                        v-model="filters.search"
                        type="text"
                        placeholder="Empresa o cargo"
                        class="w-56 rounded-md border border-gray-300 bg-white px-2 py-1.5 text-sm dark:border-gray-700 dark:bg-gray-900"
                    />
                </label>
            </div>

            <div class="flex gap-2">
                <button
                    type="button"
                    :disabled="fetching"
                    class="rounded-md bg-[#1b1b18] px-4 py-2 text-sm font-medium text-white hover:bg-black disabled:opacity-50 dark:bg-white dark:text-[#1b1b18] dark:hover:bg-gray-200"
                    @click="emit('search-new')"
                >
                    {{ fetching ? 'Buscando…' : 'Buscar nuevas' }}
                </button>
                <button
                    type="button"
                    :disabled="analyzing"
                    class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium hover:bg-gray-50 disabled:opacity-50 dark:border-gray-700 dark:hover:bg-gray-800"
                    @click="emit('analyze-pending')"
                >
                    {{ analyzing ? 'Analizando…' : 'Analizar pendientes' }}
                </button>
            </div>
        </div>

        <div
            class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-800"
        >
            <table class="w-full min-w-[900px] text-left text-sm">
                <thead
                    class="bg-gray-50 text-gray-600 dark:bg-gray-900 dark:text-gray-400"
                >
                    <tr>
                        <th class="px-4 py-2 font-medium">Empresa</th>
                        <th class="px-4 py-2 font-medium">Cargo</th>
                        <th class="px-4 py-2 font-medium">Fuente</th>
                        <th class="px-4 py-2 font-medium">Match %</th>
                        <th class="px-4 py-2 font-medium">Salario</th>
                        <th class="px-4 py-2 font-medium">Idioma</th>
                        <th class="px-4 py-2 font-medium">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    <tr v-if="loading">
                        <td
                            colspan="7"
                            class="px-4 py-6 text-center text-gray-500"
                        >
                            Cargando vacantes…
                        </td>
                    </tr>
                    <tr v-else-if="jobs.length === 0">
                        <td
                            colspan="7"
                            class="px-4 py-6 text-center text-gray-500"
                        >
                            No hay vacantes que coincidan con los filtros.
                        </td>
                    </tr>
                    <tr
                        v-for="job in jobs"
                        v-else
                        :key="job.id"
                        :class="
                            cn(
                                'cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-900',
                                matchScore(job) !== null &&
                                    matchScore(job)! >= threshold &&
                                    'bg-green-50/60 dark:bg-green-950/30',
                            )
                        "
                        @click="emit('select', job)"
                    >
                        <td class="px-4 py-2">{{ job.company || '—' }}</td>
                        <td class="px-4 py-2">{{ job.title }}</td>
                        <td class="px-4 py-2 text-gray-500 dark:text-gray-400">
                            {{ job.source }}
                        </td>
                        <td class="px-4 py-2">
                            <span
                                :class="
                                    cn(
                                        'rounded-full px-2 py-0.5 text-xs font-semibold',
                                        matchBadgeClass(matchScore(job)),
                                    )
                                "
                            >
                                {{
                                    matchScore(job) !== null
                                        ? `${matchScore(job)}%`
                                        : '—'
                                }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-gray-500 dark:text-gray-400">
                            {{
                                job.ai_analysis?.salario_normalizado ??
                                job.salary_raw ??
                                'No especificado'
                            }}
                        </td>
                        <td class="px-4 py-2 text-gray-500 dark:text-gray-400">
                            {{ job.ai_analysis?.idioma ?? '—' }}
                        </td>
                        <td class="px-4 py-2">
                            <span
                                :class="
                                    cn(
                                        'rounded-full px-2 py-0.5 text-xs font-semibold',
                                        statusBadgeClass(
                                            job.application_status,
                                        ),
                                    )
                                "
                            >
                                {{ job.application_status }}
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div
            v-if="meta && meta.total > 0"
            class="flex flex-wrap items-center justify-between gap-3 text-sm text-gray-500 dark:text-gray-400"
        >
            <span>
                Mostrando {{ rangeStart() }}–{{ rangeEnd() }} de
                {{ meta.total }}
            </span>
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    :disabled="meta.current_page <= 1"
                    class="rounded-md border border-gray-300 px-3 py-1.5 text-sm font-medium hover:bg-gray-50 disabled:opacity-50 dark:border-gray-700 dark:hover:bg-gray-800"
                    @click="emit('page-change', meta.current_page - 1)"
                >
                    ← Anterior
                </button>
                <span
                    >Página {{ meta.current_page }} de
                    {{ meta.last_page }}</span
                >
                <button
                    type="button"
                    :disabled="meta.current_page >= meta.last_page"
                    class="rounded-md border border-gray-300 px-3 py-1.5 text-sm font-medium hover:bg-gray-50 disabled:opacity-50 dark:border-gray-700 dark:hover:bg-gray-800"
                    @click="emit('page-change', meta.current_page + 1)"
                >
                    Siguiente →
                </button>
            </div>
        </div>
    </div>
</template>
