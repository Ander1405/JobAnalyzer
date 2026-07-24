<script setup lang="ts">
import {
    BaseButton,
    BaseSkeleton,
    BaseTag,
    EmptyState,
    MatchScore,
} from '@/components/ui';
import { cn } from '@/lib/utils';
import type { ApplicationStatus, Job, PaginationMeta } from '@/types/job';

const props = defineProps<{
    jobs: Job[];
    meta: PaginationMeta | null;
    loading: boolean;
    fetching: boolean;
    threshold: number;
    selectedIds: number[];
}>();

const emit = defineEmits<{
    select: [job: Job];
    'page-change': [page: number];
    'toggle-select': [job: Job];
    'search-new': [];
    'reset-filters': [];
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

function matchScore(job: Job): number | null {
    return job.ai_analysis?.match_score ?? null;
}

function statusTone(
    status: ApplicationStatus,
): 'neutral' | 'primary' | 'success' | 'warning' | 'info' {
    const tones: Record<
        ApplicationStatus,
        'neutral' | 'primary' | 'success' | 'warning' | 'info'
    > = {
        Nueva: 'info',
        'CV adaptado': 'primary',
        Aplicada: 'warning',
        Entrevista: 'success',
        Cerrada: 'neutral',
    };

    return tones[status];
}
</script>

<template>
    <div class="flex flex-col gap-4">
        <!-- @container/table: bajo un contenedor angosto (sidebar, tablet en
             columna) la fila colapsa en tarjeta con etiquetas (td::before via
             data-label) — mismas 8 columnas, sin perder ninguna y sin
             depender de scroll lateral como única salida. Ver mockup
             "05 · Tablas". -->
        <div
            class="@container/table rounded-card border border-line bg-surface shadow-card"
        >
            <table class="w-full text-left text-sm @max-md/table:block">
                <thead
                    class="bg-surface-inverse text-ink-inverse @max-md/table:hidden"
                >
                    <tr>
                        <th class="w-8 px-4 py-3"></th>
                        <th class="px-4 py-3 text-xs font-semibold">Empresa</th>
                        <th class="px-4 py-3 text-xs font-semibold">Cargo</th>
                        <th class="px-4 py-3 text-xs font-semibold">Fuente</th>
                        <th class="px-4 py-3 text-xs font-semibold">Match %</th>
                        <th class="px-4 py-3 text-xs font-semibold">Salario</th>
                        <th class="px-4 py-3 text-xs font-semibold">Idioma</th>
                        <th class="px-4 py-3 text-xs font-semibold">Estado</th>
                    </tr>
                </thead>
                <tbody
                    class="divide-y divide-line @max-md/table:block @max-md/table:divide-y-0"
                >
                    <template v-if="loading">
                        <tr
                            v-for="n in 5"
                            :key="n"
                            class="@max-md/table:mb-3 @max-md/table:block @max-md/table:rounded-card @max-md/table:border @max-md/table:border-line @max-md/table:p-3 @max-md/table:shadow-card @max-md/table:last:mb-0"
                        >
                            <td
                                v-for="col in 8"
                                :key="col"
                                class="px-4 py-3 @max-md/table:block @max-md/table:px-0 @max-md/table:py-1.5"
                            >
                                <BaseSkeleton shape="text" class="w-full" />
                            </td>
                        </tr>
                    </template>
                    <tr v-else-if="jobs.length === 0">
                        <td colspan="8" class="px-4 py-8 text-center">
                            <EmptyState
                                compact
                                title="No hay vacantes con estos filtros"
                                description="Limpia los criterios activos o busca nuevas ofertas para ampliar los resultados."
                            >
                                <template #action>
                                    <BaseButton
                                        variant="secondary"
                                        @click="emit('reset-filters')"
                                    >
                                        Limpiar filtros
                                    </BaseButton>
                                    <BaseButton
                                        :loading="fetching"
                                        loading-label="Buscando nuevas vacantes"
                                        @click="emit('search-new')"
                                    >
                                        Buscar nuevas ofertas
                                    </BaseButton>
                                </template>
                            </EmptyState>
                        </td>
                    </tr>
                    <tr
                        v-for="job in jobs"
                        v-else
                        :key="job.id"
                        :class="
                            cn(
                                'transition-colors duration-150 hover:bg-surface-subtle',
                                matchScore(job) !== null &&
                                    matchScore(job)! >= threshold &&
                                    'bg-success-surface/45',
                                '@max-md/table:mb-3 @max-md/table:block @max-md/table:rounded-card @max-md/table:border @max-md/table:border-line @max-md/table:p-3 @max-md/table:shadow-card @max-md/table:last:mb-0',
                            )
                        "
                        @click="emit('select', job)"
                    >
                        <td
                            class="px-4 py-2 @max-md/table:flex @max-md/table:items-center @max-md/table:justify-between @max-md/table:px-0 @max-md/table:py-1.5"
                            data-label="Seleccionar"
                            @click.stop
                        >
                            <input
                                type="checkbox"
                                :checked="selectedIds.includes(job.id)"
                                :aria-label="`Seleccionar ${job.title} para una acción masiva`"
                                class="h-4 w-4 accent-primary"
                                @click="emit('toggle-select', job)"
                            />
                        </td>
                        <td
                            class="px-4 py-3 font-semibold text-ink @max-md/table:flex @max-md/table:items-center @max-md/table:justify-between @max-md/table:px-0 @max-md/table:py-1.5 @max-md/table:before:text-xs @max-md/table:before:font-semibold @max-md/table:before:tracking-wide @max-md/table:before:text-ink-subtle @max-md/table:before:uppercase @max-md/table:before:content-[attr(data-label)]"
                            data-label="Empresa"
                        >
                            {{ job.company || '—' }}
                        </td>
                        <td
                            class="px-4 py-3 font-semibold text-ink @max-md/table:flex @max-md/table:items-center @max-md/table:justify-between @max-md/table:px-0 @max-md/table:py-1.5 @max-md/table:before:text-xs @max-md/table:before:font-semibold @max-md/table:before:tracking-wide @max-md/table:before:text-ink-subtle @max-md/table:before:uppercase @max-md/table:before:content-[attr(data-label)]"
                            data-label="Cargo"
                        >
                            <button
                                type="button"
                                class="rounded-control text-left hover:text-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus"
                                @click.stop="emit('select', job)"
                            >
                                {{ job.title }}
                            </button>
                        </td>
                        <td
                            class="px-4 py-3 text-ink-muted @max-md/table:flex @max-md/table:items-center @max-md/table:justify-between @max-md/table:px-0 @max-md/table:py-1.5 @max-md/table:before:text-xs @max-md/table:before:font-semibold @max-md/table:before:tracking-wide @max-md/table:before:text-ink-subtle @max-md/table:before:uppercase @max-md/table:before:content-[attr(data-label)]"
                            data-label="Fuente"
                        >
                            {{ job.source }}
                        </td>
                        <td
                            class="px-4 py-3 @max-md/table:flex @max-md/table:items-center @max-md/table:justify-between @max-md/table:px-0 @max-md/table:py-1.5 @max-md/table:before:text-xs @max-md/table:before:font-semibold @max-md/table:before:tracking-wide @max-md/table:before:text-ink-subtle @max-md/table:before:uppercase @max-md/table:before:content-[attr(data-label)]"
                            data-label="Match %"
                        >
                            <MatchScore
                                :score="matchScore(job)"
                                size="compact"
                                :animate="false"
                            />
                        </td>
                        <td
                            class="px-4 py-3 text-ink-muted @max-md/table:flex @max-md/table:items-center @max-md/table:justify-between @max-md/table:px-0 @max-md/table:py-1.5 @max-md/table:before:text-xs @max-md/table:before:font-semibold @max-md/table:before:tracking-wide @max-md/table:before:text-ink-subtle @max-md/table:before:uppercase @max-md/table:before:content-[attr(data-label)]"
                            data-label="Salario"
                        >
                            {{
                                job.ai_analysis?.salario_normalizado ??
                                job.salary_raw ??
                                'No especificado'
                            }}
                        </td>
                        <td
                            class="px-4 py-3 text-ink-muted @max-md/table:flex @max-md/table:items-center @max-md/table:justify-between @max-md/table:px-0 @max-md/table:py-1.5 @max-md/table:before:text-xs @max-md/table:before:font-semibold @max-md/table:before:tracking-wide @max-md/table:before:text-ink-subtle @max-md/table:before:uppercase @max-md/table:before:content-[attr(data-label)]"
                            data-label="Idioma"
                        >
                            {{ job.ai_analysis?.idioma ?? '—' }}
                        </td>
                        <td
                            class="px-4 py-3 @max-md/table:flex @max-md/table:items-center @max-md/table:justify-between @max-md/table:px-0 @max-md/table:py-1.5 @max-md/table:before:text-xs @max-md/table:before:font-semibold @max-md/table:before:tracking-wide @max-md/table:before:text-ink-subtle @max-md/table:before:uppercase @max-md/table:before:content-[attr(data-label)]"
                            data-label="Estado"
                        >
                            <BaseTag :tone="statusTone(job.application_status)">
                                {{ job.application_status }}
                            </BaseTag>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div
            v-if="meta && meta.total > 0"
            class="flex flex-wrap items-center justify-between gap-3 text-sm text-ink-muted"
        >
            <span>
                Mostrando {{ rangeStart() }}–{{ rangeEnd() }} de
                {{ meta.total }}
            </span>
            <div class="flex items-center gap-2">
                <BaseButton
                    size="sm"
                    variant="secondary"
                    :disabled="meta.current_page <= 1"
                    @click="emit('page-change', meta.current_page - 1)"
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
                    @click="emit('page-change', meta.current_page + 1)"
                >
                    Siguiente
                </BaseButton>
            </div>
        </div>
    </div>
</template>
