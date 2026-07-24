import type { Job } from '@/types/job';

export type TrackedJobStatus =
    'sin_aplicar' | 'aplique' | 'en_proceso' | 'rechazado' | 'oferta';

export type TrackedJobPriority = 'alta' | 'media' | 'baja';

export type CommentType =
    'nota' | 'cambio_estado' | 'entrevista' | 'seguimiento';

export type TrackedJobComment = {
    id: number;
    tracked_job_id: number;
    body: string;
    type: CommentType;
    created_at: string | null;
};

export type TrackedJob = {
    id: number;
    job_id: number;
    status: TrackedJobStatus;
    priority: TrackedJobPriority | null;
    applied_at: string | null;
    cv_version_used: string | null;
    next_action: string | null;
    next_action_date: string | null;
    created_at: string | null;
    updated_at: string | null;
    job?: Job;
    comments?: TrackedJobComment[];
    latest_comment?: TrackedJobComment | null;
};

export const TRACKED_JOB_STATUS_LABELS: Record<TrackedJobStatus, string> = {
    sin_aplicar: 'Sin aplicar',
    aplique: 'Apliqué',
    en_proceso: 'En proceso',
    rechazado: 'Rechazado',
    oferta: 'Oferta',
};

export const TRACKED_JOB_STATUSES: TrackedJobStatus[] = [
    'sin_aplicar',
    'aplique',
    'en_proceso',
    'rechazado',
    'oferta',
];

export const TRACKED_JOB_PRIORITY_LABELS: Record<TrackedJobPriority, string> = {
    alta: 'Alta',
    media: 'Media',
    baja: 'Baja',
};

export const TRACKED_JOB_PRIORITIES: TrackedJobPriority[] = [
    'alta',
    'media',
    'baja',
];

export const COMMENT_TYPE_LABELS: Record<CommentType, string> = {
    nota: 'Nota',
    cambio_estado: 'Cambio de estado',
    entrevista: 'Entrevista',
    seguimiento: 'Seguimiento',
};

export const MANUAL_COMMENT_TYPES: CommentType[] = [
    'nota',
    'entrevista',
    'seguimiento',
];
