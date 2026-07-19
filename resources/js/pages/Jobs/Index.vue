<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { onMounted, ref, watch } from 'vue';
import AiProviderSelector from '@/components/AiProviderSelector.vue';
import JobDetail from '@/components/JobDetail.vue';
import JobsTable from '@/components/JobsTable.vue';
import type { JobFilters } from '@/components/JobsTable.vue';
import ProfileUpload from '@/components/ProfileUpload.vue';
import { formatCost, formatDuration } from '@/lib/utils';
import type { Job } from '@/types/job';

const page = usePage();
const threshold = page.props.matchScoreAlertThreshold;

const jobs = ref<Job[]>([]);
const loading = ref(false);
const fetching = ref(false);
const analyzing = ref(false);

const filters = ref<JobFilters>({
    status: '',
    source: '',
    minMatch: null,
    search: '',
});

const selectedJob = ref<Job | null>(null);
const detailOpen = ref(false);

type AnalysisSummary = {
    count: number;
    durationMs: number;
    inputTokens: number;
    outputTokens: number;
    costUsd: number;
    hasUnknownCost: boolean;
};

const analysisSummary = ref<AnalysisSummary | null>(null);

let debounceTimer: ReturnType<typeof setTimeout> | undefined;

watch(
    filters,
    () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(loadJobs, 300);
    },
    { deep: true },
);

onMounted(loadJobs);

async function loadJobs() {
    loading.value = true;

    const params = new URLSearchParams();

    if (filters.value.status) {
        params.set('status', filters.value.status);
    }

    if (filters.value.source) {
        params.set('source', filters.value.source);
    }

    if (filters.value.minMatch !== null) {
        params.set('min_match', String(filters.value.minMatch));
    }

    if (filters.value.search) {
        params.set('search', filters.value.search);
    }

    try {
        const response = await fetch(`/api/jobs?${params.toString()}`, {
            headers: { Accept: 'application/json' },
        });
        jobs.value = await response.json();
    } finally {
        loading.value = false;
    }
}

async function searchNew() {
    fetching.value = true;

    try {
        await fetch('/api/jobs/fetch', {
            method: 'POST',
            headers: { Accept: 'application/json' },
        });
        await loadJobs();
    } finally {
        fetching.value = false;
    }
}

async function analyzePending() {
    analyzing.value = true;
    analysisSummary.value = null;

    const summary: AnalysisSummary = {
        count: 0,
        durationMs: 0,
        inputTokens: 0,
        outputTokens: 0,
        costUsd: 0,
        hasUnknownCost: false,
    };

    try {
        const pending = jobs.value.filter((job) => job.status === 'fetched');

        for (const job of pending) {
            const response = await fetch(`/api/jobs/${job.id}/analyze`, {
                method: 'POST',
                headers: { Accept: 'application/json' },
            });
            const analyzed: Job = await response.json();

            summary.count++;
            summary.durationMs += analyzed.ai_duration_ms ?? 0;
            summary.inputTokens += analyzed.ai_input_tokens ?? 0;
            summary.outputTokens += analyzed.ai_output_tokens ?? 0;

            if (analyzed.ai_cost_usd === null) {
                summary.hasUnknownCost = true;
            } else {
                summary.costUsd += analyzed.ai_cost_usd;
            }
        }

        analysisSummary.value = summary.count > 0 ? summary : null;

        await loadJobs();
    } finally {
        analyzing.value = false;
    }
}

function selectJob(job: Job) {
    selectedJob.value = job;
    detailOpen.value = true;
}

function onUpdated(updated: Job) {
    jobs.value = jobs.value.map((job) =>
        job.id === updated.id ? updated : job,
    );

    if (selectedJob.value?.id === updated.id) {
        selectedJob.value = updated;
    }
}
</script>

<template>
    <Head title="JobHunter" />

    <div
        class="min-h-screen bg-[#FDFDFC] p-6 text-[#1b1b18] lg:p-8 dark:bg-[#0a0a0a] dark:text-[#EDEDEC]"
    >
        <div class="mx-auto max-w-6xl">
            <h1 class="mb-4 text-2xl font-semibold">JobHunter</h1>

            <ProfileUpload class="mb-4" />

            <AiProviderSelector class="mb-6" />

            <div
                v-if="analysisSummary"
                class="mb-6 flex items-center justify-between gap-4 rounded-lg border border-green-200 bg-green-50 px-4 py-2 text-sm text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-300"
            >
                <span>
                    ✅ {{ analysisSummary.count }} analizada{{
                        analysisSummary.count === 1 ? '' : 's'
                    }}
                    · {{ formatDuration(analysisSummary.durationMs) }} ·
                    {{
                        analysisSummary.inputTokens +
                        analysisSummary.outputTokens
                    }}
                    tokens ·
                    {{
                        analysisSummary.hasUnknownCost
                            ? `${formatCost(analysisSummary.costUsd)}+`
                            : formatCost(analysisSummary.costUsd)
                    }}
                </span>
                <button
                    type="button"
                    class="text-green-700 hover:text-green-900 dark:text-green-400"
                    @click="analysisSummary = null"
                >
                    ✕
                </button>
            </div>

            <JobsTable
                v-model:filters="filters"
                :jobs="jobs"
                :loading="loading"
                :fetching="fetching"
                :analyzing="analyzing"
                :threshold="threshold"
                @select="selectJob"
                @search-new="searchNew"
                @analyze-pending="analyzePending"
            />
        </div>

        <JobDetail
            :job="selectedJob"
            :open="detailOpen"
            @close="detailOpen = false"
            @updated="onUpdated"
        />
    </div>
</template>
