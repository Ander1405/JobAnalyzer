<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\DTOs\AiAnalysisResult;
use App\Enums\JobStatus;
use App\Models\AiSetting;
use App\Models\Job;
use App\Models\Profile;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class JobAnalyzer
{
    private const SYSTEM_PROMPT = <<<'PROMPT'
Eres un asesor experto en carrera para desarrolladores de software en Latinoamérica.
Recibirás un PERFIL profesional y una VACANTE. Analiza la compatibilidad y responde
ÚNICAMENTE con un objeto JSON válido, sin markdown, sin backticks, sin texto adicional,
con exactamente este esquema:

{
  "match_score": <entero 0-100>,
  "diagnostico": "<2-4 frases: por qué encaja o no, con skills y experiencia concretas>",
  "tips_postulacion": ["<tip accionable 1>", "<tip 2>", "<tip 3>"],
  "tailoring_cv": ["<ajuste concreto al CV 1>", "<ajuste 2>", "<ajuste 3>"],
  "idioma": "<Español|Inglés|Ambos>",
  "tipo_contrato": "<Indefinido|Prestación de servicios|Freelance|Término fijo|No especificado>",
  "salario_normalizado": "<rango y moneda legibles, ej '4.000-6.000 USD/mes', o 'No especificado'>",
  "moneda": "<COP|USD|EUR|No especificado>",
  "ingles_requerido": "<None|Básico|Intermedio|Avanzado|No especificado>",
  "alerta_ingles": <true|false>,
  "red_flags": ["<señal de alerta 1 basada en el desajuste con el PERFIL>", "..."]
}

Reglas: sé específico, no genérico. Normaliza salarios ambiguos con tu mejor criterio.
Compara el inglés exigido por la vacante contra el nivel de inglés DECLARADO en la
sección "Idiomas" del PERFIL (no asumas un nivel que no esté escrito). Si la vacante
exige más de lo declarado, pon alerta_ingles=true y menciónalo en tips_postulacion;
si exige igual o menos, alerta_ingles=false. red_flags debe basarse en desajustes
concretos entre la VACANTE y el PERFIL real (stack que no coincide, seniority
desalineado, salario ausente, descripción vaga); no inventes red flags genéricas,
si no hay ninguna real devuelve un array vacío. Todo el análisis se basa
EXCLUSIVAMENTE en la información del PERFIL.
PROMPT;

    /**
     * @var array<int, string>
     */
    private const STRING_KEYS = [
        'diagnostico',
        'idioma',
        'tipo_contrato',
        'salario_normalizado',
        'moneda',
        'ingles_requerido',
    ];

    /**
     * @var array<int, string>
     */
    private const ARRAY_KEYS = ['tips_postulacion', 'tailoring_cv', 'red_flags'];

    private ?string $lastError = null;

    public function __construct(private readonly AIProviderFactory $factory) {}

    /**
     * Runs from a queued job (AnalyzeJobListing) as well as console commands, so any
     * exception here must resolve the job's status itself — a queue worker's own retry/
     * failed_jobs handling never touches this model, and would otherwise leave it stuck
     * at "analyzing" forever.
     */
    public function analyze(Job $job): void
    {
        try {
            $activeProfile = Profile::active();
            $perfilMd = $activeProfile !== null ? $activeProfile->raw_md : (string) file_get_contents(storage_path('app/perfil.md'));
            $provider = $this->factory->make();
            $providerName = AiSetting::current()->provider;
        } catch (Throwable $exception) {
            $job->update([
                'status' => JobStatus::Failed,
                'error_message' => $exception->getMessage(),
            ]);

            return;
        }

        $result = $this->attempt($provider, $perfilMd, $job)
            ?? $this->attempt($provider, $perfilMd, $job);

        if ($result === null) {
            $job->update([
                'status' => JobStatus::Failed,
                'error_message' => $this->lastError,
            ]);

            return;
        }

        $job->update([
            'ai_analysis' => $result->analysis,
            'ai_provider' => $providerName,
            'ai_model' => $result->model,
            'ai_duration_ms' => $result->usage->durationMs,
            'ai_input_tokens' => $result->usage->inputTokens,
            'ai_output_tokens' => $result->usage->outputTokens,
            'ai_cost_usd' => $result->usage->costUsd,
            'status' => JobStatus::Analyzed,
        ]);
    }

    private function attempt(AIProvider $provider, string $perfilMd, Job $job): ?AiAnalysisResult
    {
        try {
            return $provider->analyze($perfilMd, $job);
        } catch (Throwable $exception) {
            $this->lastError = $exception->getMessage();
            Log::warning("AI analysis attempt failed for job [{$job->id}].", [
                'exception' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    public static function systemPrompt(): string
    {
        return self::SYSTEM_PROMPT;
    }

    public static function userPrompt(string $perfilMd, Job $job): string
    {
        return $perfilMd."\n\n--- VACANTE ---\n"
            ."Título: {$job->title}\n"
            ."Empresa: {$job->company}\n"
            ."Descripción: {$job->description}\n"
            ."Tipo de contrato: {$job->contract_type}\n"
            ."Salario: {$job->salary_raw}";
    }

    /**
     * @return array<string, mixed>
     */
    public static function parseAiResponse(string $raw): array
    {
        $clean = trim($raw);
        $clean = preg_replace('/^```(?:json)?\s*/i', '', $clean) ?? $clean;
        $clean = preg_replace('/\s*```$/', '', $clean) ?? $clean;
        $clean = trim($clean);

        $decoded = json_decode($clean, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('AI response is not valid JSON.');
        }

        foreach ([...self::STRING_KEYS, ...self::ARRAY_KEYS, 'match_score', 'alerta_ingles'] as $key) {
            if (! array_key_exists($key, $decoded)) {
                throw new RuntimeException("AI response is missing required key [{$key}].");
            }
        }

        if (! is_numeric($decoded['match_score'])) {
            throw new RuntimeException('AI response key [match_score] must be numeric.');
        }

        $decoded['match_score'] = max(0, min(100, (int) round((float) $decoded['match_score'])));

        foreach (self::STRING_KEYS as $key) {
            if (! is_string($decoded[$key])) {
                throw new RuntimeException("AI response key [{$key}] must be a string.");
            }
        }

        foreach (self::ARRAY_KEYS as $key) {
            if (! is_array($decoded[$key])) {
                throw new RuntimeException("AI response key [{$key}] must be an array.");
            }
        }

        if (! is_bool($decoded['alerta_ingles'])) {
            throw new RuntimeException('AI response key [alerta_ingles] must be a boolean.');
        }

        return $decoded;
    }
}
