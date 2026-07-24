export type MatchScoreTier =
    'excellent' | 'very-good' | 'acceptable' | 'low' | 'unscored';

export type MatchScoreMeta = {
    tier: MatchScoreTier;
    label: string;
    textClass: string;
    surfaceClass: string;
    strokeClass: string;
};

const MATCH_SCORE_META: Record<MatchScoreTier, MatchScoreMeta> = {
    excellent: {
        tier: 'excellent',
        label: 'Excelente',
        textClass: 'text-score-excellent',
        surfaceClass: 'bg-score-excellent-surface',
        strokeClass: 'stroke-score-excellent',
    },
    'very-good': {
        tier: 'very-good',
        label: 'Muy bueno',
        textClass: 'text-score-very-good',
        surfaceClass: 'bg-score-very-good-surface',
        strokeClass: 'stroke-score-very-good',
    },
    acceptable: {
        tier: 'acceptable',
        label: 'Aceptable',
        textClass: 'text-score-acceptable',
        surfaceClass: 'bg-score-acceptable-surface',
        strokeClass: 'stroke-score-acceptable',
    },
    low: {
        tier: 'low',
        label: 'Bajo',
        textClass: 'text-score-low',
        surfaceClass: 'bg-score-low-surface',
        strokeClass: 'stroke-score-low',
    },
    unscored: {
        tier: 'unscored',
        label: 'Sin analizar',
        textClass: 'text-ink-subtle',
        surfaceClass: 'bg-surface-subtle',
        strokeClass: 'stroke-line-strong',
    },
};

export function normalizeMatchScore(score: number | null): number | null {
    if (score === null || !Number.isFinite(score)) {
        return null;
    }

    return Math.min(100, Math.max(0, Math.round(score)));
}

export function getMatchScoreMeta(score: number | null): MatchScoreMeta {
    const normalizedScore = normalizeMatchScore(score);

    if (normalizedScore === null) {
        return MATCH_SCORE_META.unscored;
    }

    if (normalizedScore >= 85) {
        return MATCH_SCORE_META.excellent;
    }

    if (normalizedScore >= 75) {
        return MATCH_SCORE_META['very-good'];
    }

    if (normalizedScore >= 60) {
        return MATCH_SCORE_META.acceptable;
    }

    return MATCH_SCORE_META.low;
}
