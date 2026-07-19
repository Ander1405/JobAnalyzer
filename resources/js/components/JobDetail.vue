<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { cn, formatCost, formatDuration } from '@/lib/utils';
import { APPLICATION_STATUSES } from '@/types/job';
import type { Job } from '@/types/job';

const props = defineProps<{
    job: Job | null;
    open: boolean;
}>();

const emit = defineEmits<{
    close: [];
    updated: [job: Job];
}>();

const publishing = ref(false);
const publishError = ref<string | null>(null);
const updatingStatus = ref(false);
const checkedTailoring = ref<boolean[]>([]);

watch(
    () => props.job?.id,
    () => {
        publishError.value = null;
        checkedTailoring.value =
            props.job?.ai_analysis?.tailoring_cv.map(() => false) ?? [];
    },
    { immediate: true },
);

const analysis = computed(() => props.job?.ai_analysis ?? null);

async function publish() {
    if (!props.job) {
        return;
    }

    publishing.value = true;
    publishError.value = null;

    try {
        const response = await fetch(`/api/jobs/${props.job.id}/publish`, {
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

        emit('updated', updated);
    } catch {
        publishError.value = 'No se pudo conectar con Notion.';
    } finally {
        publishing.value = false;
    }
}

async function updateStatus(event: Event) {
    if (!props.job) {
        return;
    }

    const applicationStatus = (event.target as HTMLSelectElement).value;
    updatingStatus.value = true;

    try {
        const response = await fetch(`/api/jobs/${props.job.id}`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
            },
            body: JSON.stringify({ application_status: applicationStatus }),
        });

        const updated: Job = await response.json();
        emit('updated', updated);
    } finally {
        updatingStatus.value = false;
    }
}
</script>

<template>
    <div v-if="open && job" class="fixed inset-0 z-50 flex justify-end">
        <div class="absolute inset-0 bg-black/30" @click="emit('close')" />

        <aside
            class="relative flex h-full w-full max-w-lg flex-col overflow-y-auto bg-white p-6 shadow-xl dark:bg-gray-950"
        >
            <div class="mb-4 flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold">{{ job.title }}</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ job.company }} · {{ job.source }}
                    </p>
                </div>
                <button
                    type="button"
                    class="text-gray-400 hover:text-gray-700 dark:hover:text-gray-200"
                    @click="emit('close')"
                >
                    ✕
                </button>
            </div>

            <div class="mb-4 flex flex-wrap items-center gap-2">
                <label class="text-sm text-gray-600 dark:text-gray-400"
                    >Estado local:</label
                >
                <select
                    :value="job.application_status"
                    :disabled="updatingStatus"
                    class="rounded-md border border-gray-300 bg-white px-2 py-1 text-sm dark:border-gray-700 dark:bg-gray-900"
                    @change="updateStatus"
                >
                    <option
                        v-for="status in APPLICATION_STATUSES"
                        :key="status"
                        :value="status"
                    >
                        {{ status }}
                    </option>
                </select>
            </div>

            <div
                v-if="job.status === 'failed' && job.error_message"
                class="mb-4 rounded-md bg-red-50 p-3 text-sm text-red-700 dark:bg-red-950 dark:text-red-300"
            >
                {{ job.error_message }}
            </div>

            <template v-if="analysis">
                <section
                    class="mb-4 rounded-md bg-gray-50 p-4 dark:bg-gray-900"
                >
                    <p class="mb-1 text-sm font-semibold">
                        🎯 {{ analysis.match_score }}% match
                    </p>
                    <p class="text-sm text-gray-700 dark:text-gray-300">
                        {{ analysis.diagnostico }}
                    </p>
                </section>

                <section
                    v-if="job.ai_duration_ms !== null"
                    class="mb-4 flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500 dark:text-gray-400"
                >
                    <span
                        >🤖 {{ job.ai_provider
                        }}<span v-if="job.ai_model">
                            · {{ job.ai_model }}</span
                        ></span
                    >
                    <span>⏱ {{ formatDuration(job.ai_duration_ms) }}</span>
                    <span
                        v-if="
                            job.ai_input_tokens !== null ||
                            job.ai_output_tokens !== null
                        "
                    >
                        🔢 {{ job.ai_input_tokens ?? '—' }} in /
                        {{ job.ai_output_tokens ?? '—' }} out
                    </span>
                    <span>💰 {{ formatCost(job.ai_cost_usd) }}</span>
                </section>

                <section class="mb-4">
                    <h3 class="mb-2 text-sm font-semibold">
                        💡 Tips para postular
                    </h3>
                    <ul
                        class="list-inside list-disc space-y-1 text-sm text-gray-700 dark:text-gray-300"
                    >
                        <li
                            v-for="(tip, index) in analysis.tips_postulacion"
                            :key="index"
                        >
                            {{ tip }}
                        </li>
                    </ul>
                </section>

                <section class="mb-4">
                    <h3 class="mb-2 text-sm font-semibold">
                        ✂️ Tailoring del CV
                    </h3>
                    <ul class="space-y-2 text-sm">
                        <li
                            v-for="(adjustment, index) in analysis.tailoring_cv"
                            :key="index"
                            class="flex items-start gap-2"
                        >
                            <input
                                :id="`tailoring-${index}`"
                                v-model="checkedTailoring[index]"
                                type="checkbox"
                                class="mt-1"
                            />
                            <label
                                :for="`tailoring-${index}`"
                                :class="
                                    cn(
                                        'text-gray-700 dark:text-gray-300',
                                        checkedTailoring[index] &&
                                            'text-gray-400 line-through dark:text-gray-600',
                                    )
                                "
                            >
                                {{ adjustment }}
                            </label>
                        </li>
                    </ul>
                </section>

                <dl class="mb-4 grid grid-cols-2 gap-2 text-sm">
                    <div>
                        <dt class="text-gray-500">Idioma</dt>
                        <dd>{{ analysis.idioma }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Tipo de contrato</dt>
                        <dd>{{ analysis.tipo_contrato }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Salario</dt>
                        <dd>{{ analysis.salario_normalizado }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Moneda</dt>
                        <dd>{{ analysis.moneda }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Inglés requerido</dt>
                        <dd>
                            {{ analysis.ingles_requerido }}
                            <span
                                v-if="analysis.alerta_ingles"
                                title="Supera tu nivel declarado"
                                >⚠️</span
                            >
                        </dd>
                    </div>
                </dl>

                <section v-if="analysis.red_flags.length" class="mb-4">
                    <h3 class="mb-2 text-sm font-semibold">🚩 Red flags</h3>
                    <ul
                        class="list-inside list-disc space-y-1 text-sm text-red-700 dark:text-red-400"
                    >
                        <li
                            v-for="(flag, index) in analysis.red_flags"
                            :key="index"
                        >
                            {{ flag }}
                        </li>
                    </ul>
                </section>
            </template>
            <p v-else class="mb-4 text-sm text-gray-500 dark:text-gray-400">
                Esta vacante todavía no ha sido analizada.
            </p>

            <section class="mb-4">
                <h3 class="mb-2 text-sm font-semibold">📄 Descripción</h3>
                <p
                    class="text-sm whitespace-pre-line text-gray-700 dark:text-gray-300"
                >
                    {{ job.description }}
                </p>
            </section>

            <div
                class="mt-auto flex flex-col gap-2 border-t border-gray-200 pt-4 dark:border-gray-800"
            >
                <p
                    v-if="publishError"
                    class="text-sm text-red-600 dark:text-red-400"
                >
                    {{ publishError }}
                </p>
                <p
                    v-if="job.notion_page_id"
                    class="text-sm text-green-600 dark:text-green-400"
                >
                    Publicado en Notion ✓
                </p>
                <button
                    type="button"
                    :disabled="publishing || !analysis"
                    class="rounded-md bg-[#1b1b18] px-4 py-2 text-sm font-medium text-white hover:bg-black disabled:opacity-50 dark:bg-white dark:text-[#1b1b18] dark:hover:bg-gray-200"
                    @click="publish"
                >
                    {{ publishing ? 'Publicando…' : 'Publicar en Notion' }}
                </button>
            </div>
        </aside>
    </div>
</template>
