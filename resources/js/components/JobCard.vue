<script setup lang="ts">
import { computed } from 'vue';
import AppIcon from '@/components/AppIcon.vue';
import {
    BaseButton,
    BaseCard,
    BaseTag,
    CompanyLogo,
    MatchScore,
} from '@/components/ui';
import { cn, formatRelativeTime } from '@/lib/utils';
import type { Job } from '@/types/job';

const props = defineProps<{
    job: Job;
    selected: boolean;
    tracking: boolean;
}>();

const emit = defineEmits<{
    open: [];
    'toggle-select': [];
    'quick-track': [];
}>();

const matchScore = computed(() => props.job.ai_analysis?.match_score ?? null);

const modalidad = computed(
    () =>
        props.job.work_mode ??
        props.job.ai_analysis?.modalidad_inferida ??
        null,
);

const salary = computed(
    () =>
        props.job.salary_raw ??
        props.job.ai_analysis?.salario_normalizado ??
        null,
);

const postedAgo = computed(() =>
    formatRelativeTime(props.job.posted_at ?? props.job.created_at),
);
</script>

<template>
    <BaseCard
        as="article"
        variant="interactive"
        :class="
            cn(
                'flex h-full flex-col gap-4',
                selected && 'border-primary ring-2 ring-primary-subtle',
            )
        "
    >
        <div class="flex items-start justify-between gap-3">
            <div class="flex items-start gap-3">
                <input
                    type="checkbox"
                    class="mt-3 h-4 w-4 accent-primary"
                    :checked="selected"
                    :aria-label="`Seleccionar ${job.title} para una acción masiva`"
                    @click.stop="emit('toggle-select')"
                />
                <CompanyLogo :company="job.company" :src="job.company_logo" />
            </div>

            <BaseButton
                size="icon"
                variant="quiet"
                :disabled="tracking || !!job.tracked_job"
                :loading="tracking"
                loading-label="Agregando a Mis vacantes"
                :aria-label="
                    job.tracked_job
                        ? `${job.title} ya está en Mis vacantes`
                        : `Agregar ${job.title} a Mis vacantes`
                "
                :title="
                    job.tracked_job
                        ? 'Ya está en mis vacantes'
                        : 'Agregar a mis vacantes'
                "
                @click.stop="emit('quick-track')"
            >
                <AppIcon name="tracking" class="h-5 w-5" />
            </BaseButton>
        </div>

        <button
            type="button"
            class="rounded-control text-left focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus"
            @click="emit('open')"
        >
            <h3 class="font-semibold tracking-[-0.02em] text-ink">
                {{ job.title }}
            </h3>
            <p class="mt-1 text-sm text-ink-muted">
                {{ job.company }}
            </p>
        </button>

        <div class="flex flex-wrap items-center gap-2">
            <MatchScore :score="matchScore" size="compact" />
            <BaseTag v-if="job.location">{{ job.location }}</BaseTag>
            <BaseTag v-if="modalidad">{{ modalidad }}</BaseTag>
        </div>

        <div
            class="mt-auto flex items-center justify-between gap-4 border-t border-line pt-3 text-xs text-ink-muted"
        >
            <span>{{ salary ?? 'Salario no especificado' }}</span>
            <span v-if="postedAgo" class="font-data tabular-nums">{{
                postedAgo
            }}</span>
        </div>
    </BaseCard>
</template>
