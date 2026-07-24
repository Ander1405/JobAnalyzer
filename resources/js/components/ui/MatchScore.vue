<script setup lang="ts">
import { animate as tweenNumber } from 'motion-v';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { getMatchScoreMeta, normalizeMatchScore } from '@/lib/designSystem';
import { cn } from '@/lib/utils';

type ScoreSize = 'compact' | 'card' | 'hero';
type PlaybackControls = { stop: () => void };

const props = withDefaults(
    defineProps<{
        score: number | null;
        size?: ScoreSize;
        animate?: boolean;
    }>(),
    {
        size: 'card',
        animate: true,
    },
);

const normalizedScore = computed(() => normalizeMatchScore(props.score));
const meta = computed(() => getMatchScoreMeta(props.score));
const accessibleLabel = computed(() =>
    normalizedScore.value === null
        ? 'Compatibilidad sin analizar'
        : `Compatibilidad ${normalizedScore.value} de 100, ${meta.value.label}`,
);
const dialSizeClass = computed(() =>
    props.size === 'hero' ? 'h-36 w-36' : 'h-24 w-24',
);
const numberSizeClass = computed(() =>
    props.size === 'hero' ? 'text-step-metric' : 'text-step-h3',
);

// El dial (card/hero) cuenta y dibuja el arco con motion-v; "compact" es texto
// plano y no lo necesita. displayCount es la fuente del número Y del arco para
// que ambos avancen siempre en sincronía.
const displayCount = ref(normalizedScore.value ?? 0);
const strokeDashoffset = computed(() => 100 - displayCount.value);
const displayValue = computed(() =>
    normalizedScore.value === null ? '--' : String(displayCount.value),
);

let playback: PlaybackControls | undefined;

function prefersReducedMotion(): boolean {
    return (
        typeof window !== 'undefined' &&
        window.matchMedia('(prefers-reduced-motion: reduce)').matches
    );
}

function runScoreAnimation(): void {
    playback?.stop();

    const target = normalizedScore.value;

    if (target === null) {
        displayCount.value = 0;

        return;
    }

    if (!props.animate || props.size === 'compact' || prefersReducedMotion()) {
        displayCount.value = target;

        return;
    }

    displayCount.value = 0;
    playback = tweenNumber(0, target, {
        duration: 1.1,
        ease: [0.16, 1, 0.3, 1],
        onUpdate(value) {
            displayCount.value = Math.round(value);
        },
    });
}

watch([normalizedScore, () => props.animate, () => props.size], runScoreAnimation, {
    immediate: true,
});
onBeforeUnmount(() => playback?.stop());
</script>

<template>
    <div
        v-if="size === 'compact'"
        :class="
            cn(
                'inline-grid min-w-20 grid-cols-[auto_1fr] items-center gap-x-2 rounded-control px-2.5 py-1.5',
                meta.surfaceClass,
                meta.textClass,
            )
        "
        :aria-label="accessibleLabel"
        role="img"
    >
        <span class="font-data text-lg leading-none font-semibold tabular-nums">
            {{ displayValue
            }}<small v-if="normalizedScore !== null" class="text-[0.6em]"
                >%</small
            >
        </span>
        <span class="text-[0.6875rem] leading-3 font-semibold">
            {{ meta.label }}
        </span>
    </div>

    <div
        v-else
        class="inline-grid justify-items-center gap-2"
        :aria-label="accessibleLabel"
        role="img"
    >
        <div :class="cn('relative', dialSizeClass)">
            <svg
                class="h-full w-full -rotate-90"
                viewBox="0 0 44 44"
                fill="none"
                aria-hidden="true"
            >
                <circle
                    cx="22"
                    cy="22"
                    r="17"
                    pathLength="100"
                    class="stroke-line"
                    stroke-width="2.5"
                />
                <circle
                    cx="22"
                    cy="22"
                    r="17"
                    pathLength="100"
                    class="stroke-line-strong"
                    stroke-width="0.75"
                    stroke-dasharray="1 24"
                    stroke-linecap="round"
                />
                <circle
                    v-if="normalizedScore !== null"
                    cx="22"
                    cy="22"
                    r="17"
                    pathLength="100"
                    fill="none"
                    :class="meta.strokeClass"
                    stroke-width="3.5"
                    stroke-linecap="round"
                    stroke-dasharray="100"
                    :stroke-dashoffset="strokeDashoffset"
                />
            </svg>
            <div
                :class="
                    cn(
                        'absolute inset-0 grid place-content-center text-center',
                        meta.textClass,
                    )
                "
            >
                <span
                    :class="
                        cn(
                            'font-data leading-none font-semibold tracking-[-0.06em] tabular-nums',
                            numberSizeClass,
                        )
                    "
                >
                    {{ displayValue }}
                </span>
                <span
                    v-if="normalizedScore !== null"
                    class="mt-0.5 text-[0.625rem] font-semibold tracking-[0.08em] uppercase"
                >
                    de 100
                </span>
            </div>
        </div>
        <span :class="cn('text-xs font-semibold', meta.textClass)">
            {{ meta.label }}
        </span>
    </div>
</template>
