<script setup lang="ts">
import { computed } from 'vue';
import { BaseButton, BaseInput, BaseSelect } from '@/components/ui';
import {
    LANGUAGE_OPTIONS,
    SENIORITY_OPTIONS,
    WORK_MODE_OPTIONS,
} from '@/types/marketplace';
import type { MarketplaceFilters } from '@/types/marketplace';

const filters = defineModel<MarketplaceFilters>('filters', { required: true });
const viewMode = defineModel<'grid' | 'table'>('viewMode', { required: true });

const props = defineProps<{
    sources: string[];
    fetching: boolean;
    analyzing: boolean;
    pendingAnalysis: number;
}>();

const emit = defineEmits<{
    'search-new': [];
    'analyze-pending': [];
}>();

const sourceOptions = computed(() => [
    { value: '', label: 'Todas' },
    ...props.sources.map((source) => ({ value: source, label: source })),
]);
const workModeOptions = [
    { value: '', label: 'Todas' },
    ...WORK_MODE_OPTIONS.map((mode) => ({ value: mode, label: mode })),
];
const seniorityOptions = [
    { value: '', label: 'Todos' },
    ...SENIORITY_OPTIONS.map((level) => ({ value: level, label: level })),
];
const languageOptions = [
    { value: '', label: 'Todos' },
    ...LANGUAGE_OPTIONS.map((language) => ({
        value: language,
        label: language,
    })),
];
const sortOptions = [
    { value: 'match', label: 'Match (mayor primero)' },
    { value: 'recent', label: 'Más recientes' },
    { value: 'salary', label: 'Salario' },
];
</script>

<template>
    <section class="grid gap-5 border-y border-line py-5">
        <div
            class="grid items-end gap-4 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-[minmax(13rem,1.35fr)_repeat(4,minmax(8rem,1fr))]"
        >
            <BaseInput
                v-model="filters.search"
                label="Buscar"
                placeholder="Empresa o cargo"
            />
            <BaseSelect
                v-model="filters.source"
                label="Fuente"
                :options="sourceOptions"
            />
            <BaseSelect
                v-model="filters.workMode"
                label="Modalidad"
                :options="workModeOptions"
            />
            <BaseSelect
                v-model="filters.seniority"
                label="Seniority"
                :options="seniorityOptions"
            />
            <BaseSelect
                v-model="filters.language"
                label="Idioma"
                :options="languageOptions"
            />
        </div>

        <div
            class="flex flex-col justify-between gap-4 xl:flex-row xl:items-end"
        >
            <div class="flex flex-wrap items-end gap-4">
                <label
                    class="grid min-w-44 gap-1.5 text-sm font-semibold text-ink"
                >
                    <span class="flex items-center justify-between gap-3">
                        Match mínimo
                        <span
                            class="font-data text-xs text-primary tabular-nums"
                        >
                            {{ filters.minMatch }}%
                        </span>
                    </span>
                    <input
                        v-model.number="filters.minMatch"
                        type="range"
                        min="0"
                        max="100"
                        step="5"
                        class="h-11 w-full accent-primary"
                    />
                </label>

                <BaseSelect
                    v-model="filters.sort"
                    class="min-w-52"
                    label="Orden"
                    :options="sortOptions"
                />

                <label
                    class="flex min-h-11 items-center gap-2 rounded-control px-2 text-sm font-medium text-ink-muted hover:text-ink"
                >
                    <input
                        v-model="filters.hasSalaryOnly"
                        type="checkbox"
                        class="h-4 w-4 accent-primary"
                    />
                    Solo con salario
                </label>

                <label
                    class="flex min-h-11 items-center gap-2 rounded-control px-2 text-sm font-medium text-ink-muted hover:text-ink"
                >
                    <input
                        v-model="filters.hideTracked"
                        type="checkbox"
                        class="h-4 w-4 accent-primary"
                    />
                    Ocultar seleccionadas
                </label>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <div
                    class="flex rounded-control border border-line-strong bg-surface p-1 shadow-card"
                    aria-label="Modo de visualización"
                    role="group"
                >
                    <BaseButton
                        size="sm"
                        :variant="viewMode === 'grid' ? 'primary' : 'quiet'"
                        :aria-pressed="viewMode === 'grid'"
                        @click="viewMode = 'grid'"
                    >
                        Tarjetas
                    </BaseButton>
                    <BaseButton
                        size="sm"
                        :variant="viewMode === 'table' ? 'primary' : 'quiet'"
                        :aria-pressed="viewMode === 'table'"
                        @click="viewMode = 'table'"
                    >
                        Tabla
                    </BaseButton>
                </div>

                <BaseButton
                    :loading="fetching"
                    loading-label="Buscando nuevas vacantes"
                    @click="emit('search-new')"
                >
                    Buscar nuevas
                </BaseButton>
                <BaseButton
                    variant="secondary"
                    :loading="analyzing"
                    loading-label="Analizando vacantes pendientes"
                    :disabled="pendingAnalysis === 0"
                    @click="emit('analyze-pending')"
                >
                    Analizar pendientes<span v-if="pendingAnalysis > 0">
                        ({{ pendingAnalysis }})</span
                    >
                </BaseButton>
            </div>
        </div>
    </section>
</template>
