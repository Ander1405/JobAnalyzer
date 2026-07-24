export type JobStatus =
    'fetched' | 'analyzing' | 'analyzed' | 'published' | 'failed' | 'discarded';

export type ApplicationStatus =
    'Nueva' | 'CV adaptado' | 'Aplicada' | 'Entrevista' | 'Cerrada';

export type AiAnalysis = {
    match_score: number;
    diagnostico: string;
    tips_postulacion: string[];
    tailoring_cv: string[];
    idioma: string;
    tipo_contrato: string;
    salario_normalizado: string;
    moneda: string;
    // Absent on jobs analyzed before this schema addition.
    ingles_requerido?: string;
    alerta_ingles?: boolean;
    red_flags?: string[];
    seniority_inferido?: string;
    modalidad_inferida?: string;
    skills_requeridos?: string[];
    resumen_ejecutivo?: string;
};

export type TrackedJobSummary = {
    id: number;
    status: string;
};

export type CvVariantSummary = {
    id: number;
    job_id: number;
    slug: string;
    label: string;
    updated_at: string;
};

export type Job = {
    id: number;
    hash: string;
    source: string;
    company: string;
    title: string;
    description: string;
    url: string;
    contract_type: string | null;
    salary_raw: string | null;
    language: string | null;
    apply_url: string | null;
    location: string | null;
    is_remote: boolean | null;
    work_mode: string | null;
    seniority: string | null;
    employment_type: string | null;
    posted_at: string | null;
    expires_at: string | null;
    company_logo: string | null;
    company_website: string | null;
    benefits: string[] | null;
    required_skills: string[] | null;
    applicants_count: number | null;
    status: JobStatus;
    application_status: ApplicationStatus;
    ai_provider: string | null;
    ai_analysis: AiAnalysis | null;
    ai_model: string | null;
    ai_duration_ms: number | null;
    ai_input_tokens: number | null;
    ai_output_tokens: number | null;
    ai_cost_usd: number | null;
    notion_page_id: string | null;
    error_message: string | null;
    tracked_job?: TrackedJobSummary | null;
    latest_cv_variant?: CvVariantSummary | null;
    created_at: string;
    updated_at: string;
};

export type PaginationMeta = {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
    // Only the marketplace endpoint reports it: offers fetched but not yet scored,
    // which the min_match filter hides.
    pending_analysis?: number;
};

export type PaginatedJobs = {
    data: Job[];
    meta: PaginationMeta;
};

export const APPLICATION_STATUSES: ApplicationStatus[] = [
    'Nueva',
    'CV adaptado',
    'Aplicada',
    'Entrevista',
    'Cerrada',
];
