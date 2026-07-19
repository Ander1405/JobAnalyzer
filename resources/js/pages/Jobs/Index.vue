<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { onMounted, ref, watch } from 'vue';
import JobDetail from '@/components/JobDetail.vue';
import JobsTable from '@/components/JobsTable.vue';
import type { JobFilters } from '@/components/JobsTable.vue';
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

    try {
        const pending = jobs.value.filter((job) => job.status === 'fetched');

        for (const job of pending) {
            await fetch(`/api/jobs/${job.id}/analyze`, {
                method: 'POST',
                headers: { Accept: 'application/json' },
            });
        }

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
            <h1 class="mb-6 text-2xl font-semibold">JobHunter</h1>

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
