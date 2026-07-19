<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\DTOs\AiAnalysisResult;
use App\Enums\JobStatus;
use App\Models\AiSetting;
use App\Models\Job;
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
  "moneda": "<COP|USD|EUR|No especificado>"
}

Reglas: sé específico, no genérico. Si la vacante exige inglés avanzado y el perfil es B1,
refléjalo honestamente en el score y en los tips. Normaliza salarios ambiguos con tu mejor criterio.
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
    ];

    /**
     * @var array<int, string>
     */
    private const ARRAY_KEYS = ['tips_postulacion', 'tailoring_cv'];

    private ?string $lastError = null;

    public function __construct(private readonly AIProviderFactory $factory) {}

    public function analyze(Job $job): void
    {
        $perfilMd = (string) file_get_contents(storage_path('app/perfil.md'));
        $provider = $this->factory->make();
        $providerName = AiSetting::current()->provider;

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

    public static function buildPrompt(string $perfilMd, Job $job): string
    {
        return self::systemPrompt()."\n\n".self::userPrompt($perfilMd, $job);
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

        foreach ([...self::STRING_KEYS, ...self::ARRAY_KEYS, 'match_score'] as $key) {
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

        return $decoded;
    }
}
