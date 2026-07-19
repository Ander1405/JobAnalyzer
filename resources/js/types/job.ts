export type JobStatus = 'fetched' | 'analyzed' | 'published' | 'failed';

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
    ingles_requerido: string;
    alerta_ingles: boolean;
    red_flags: string[];
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
    created_at: string;
    updated_at: string;
};

export const APPLICATION_STATUSES: ApplicationStatus[] = [
    'Nueva',
    'CV adaptado',
    'Aplicada',
    'Entrevista',
    'Cerrada',
];
