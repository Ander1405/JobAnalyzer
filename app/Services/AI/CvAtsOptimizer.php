<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\DTOs\AiUsage;
use App\DTOs\CvAtsResult;
use App\Models\Profile;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Scores a profile's Markdown CV against standard ATS-compatibility criteria and
 * proposes a reformatted rewrite. Never writes to the Profile model — the
 * controller decides whether/how to persist the rewrite as a new variant.
 */
class CvAtsOptimizer
{
    private const SYSTEM_PROMPT = <<<'PROMPT'
Eres un experto en optimización de CVs para sistemas ATS (Applicant Tracking Systems).
Recibirás el CV completo de un candidato en Markdown. Evalúalo contra criterios
estándar de compatibilidad ATS: una sola columna, encabezados estándar y claros
(Experiencia, Educación, Skills, etc.), sin tablas ni imágenes, fechas consistentes,
presencia de keywords técnicas relevantes para el perfil, uso de verbos de acción,
y logros cuantificados cuando sea posible.

Responde ÚNICAMENTE con un objeto JSON válido, sin markdown, sin backticks, sin
texto adicional, con exactamente este esquema:

{
  "ats_score": <entero de 0 a 100>,
  "problemas": ["<problema priorizado 1>", "..."],
  "keywords_faltantes": ["<keyword técnica 1>", "..."],
  "recomendaciones_formato": ["<recomendación de formato 1>", "..."],
  "version_optimizada_md": "<CV completo reescrito en Markdown>"
}

Reglas:
- version_optimizada_md debe conservar EXACTAMENTE la misma información factual
  (empresas, cargos, fechas, tecnologías, logros) que el CV original; solo puedes
  reformular, reordenar o mejorar el formato de lo que ya está — está PROHIBIDO
  inventar experiencia, tecnologías o logros que no estén ya en el CV original.
- Si el CV original no tiene logros cuantificados, sugiérelo en "problemas", pero
  no inventes cifras en version_optimizada_md.
- Ningún CV es infalible ante un sistema ATS: sé honesto y específico en
  "problemas" en vez de prometer resultados garantizados.
PROMPT;

    private ?string $lastError = null;

    public function __construct(private readonly AIProviderFactory $factory) {}

    public function analyze(Profile $profile): CvAtsResult
    {
        $provider = $this->factory->make();

        $result = $this->attempt($provider, $profile)
            ?? $this->attempt($provider, $profile);

        if ($result === null) {
            throw new RuntimeException($this->lastError ?? 'AI ATS analysis failed.');
        }

        return $result;
    }

    private function attempt(AIProvider $provider, Profile $profile): ?CvAtsResult
    {
        try {
            $completion = $provider->complete(self::SYSTEM_PROMPT, $profile->raw_md);

            return $this->parseResponse($completion->text, $completion->usage, $completion->model);
        } catch (Throwable $exception) {
            $this->lastError = $exception->getMessage();
            Log::warning("AI ATS analysis attempt failed for profile [{$profile->id}].", [
                'exception' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    public function parseResponse(string $raw, AiUsage $usage, string $model): CvAtsResult
    {
        $clean = trim($raw);
        $clean = preg_replace('/^```(?:json)?\s*/i', '', $clean) ?? $clean;
        $clean = preg_replace('/\s*```$/', '', $clean) ?? $clean;
        $clean = trim($clean);

        $decoded = json_decode($clean, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('AI response must be a JSON object.');
        }

        if (! isset($decoded['ats_score']) || ! is_int($decoded['ats_score']) || $decoded['ats_score'] < 0 || $decoded['ats_score'] > 100) {
            throw new RuntimeException('AI response key [ats_score] must be an integer between 0 and 100.');
        }

        foreach (['problemas', 'keywords_faltantes', 'recomendaciones_formato'] as $key) {
            if (! isset($decoded[$key]) || ! is_array($decoded[$key]) || ! array_all($decoded[$key], fn ($item) => is_string($item))) {
                throw new RuntimeException("AI response key [{$key}] must be an array of strings.");
            }
        }

        if (! isset($decoded['version_optimizada_md']) || ! is_string($decoded['version_optimizada_md']) || trim($decoded['version_optimizada_md']) === '') {
            throw new RuntimeException('AI response key [version_optimizada_md] must be a non-empty string.');
        }

        return new CvAtsResult(
            $decoded['ats_score'],
            array_values($decoded['problemas']),
            array_values($decoded['keywords_faltantes']),
            array_values($decoded['recomendaciones_formato']),
            $decoded['version_optimizada_md'],
            $usage,
            $model,
        );
    }
}
