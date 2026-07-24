<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\DTOs\AiUsage;
use App\DTOs\CvTailorResult;
use App\Models\Job;
use App\Models\Profile;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Rewrites only headline/summary/experience/skills to emphasize the parts of a
 * profile relevant to a specific job, based on tailoring items the user
 * explicitly approved. Nothing here ever writes to the Profile model — the
 * controller decides whether/how to persist the result as a new variant.
 */
class CvTailorer
{
    private const BASE_PROMPT = <<<'PROMPT'
Eres un editor experto de CVs para desarrolladores de software en Latinoamérica.
Recibirás el PERFIL actual de un candidato (headline, summary, experience, skills),
una VACANTE específica, y una lista de AJUSTES SELECCIONADOS que el candidato aprobó
aplicar a su CV para postularse a esa vacante.

Tu tarea es reescribir headline, summary, experience y skills aplicando ÚNICAMENTE
los ajustes seleccionados: reordenar, reformular o destacar información YA PRESENTE
en el PERFIL para que encaje mejor con la vacante. Está PROHIBIDO inventar
experiencia, tecnologías, logros, empresas o fechas que no estén ya en el PERFIL.

Responde ÚNICAMENTE con un objeto JSON válido, sin markdown, sin backticks, sin
texto adicional, con exactamente este esquema:

{
  "headline": "<headline reescrito>",
  "summary": "<summary reescrito>",
  "experience": ["<línea 1>", "..."],
  "skills": ["<skill 1>", "..."]
}

Reglas:
- Puedes reordenar experience/skills para priorizar lo relevante a la vacante.
- Puedes reformular headline/summary/experience para resaltar el encaje, siempre
  sobre hechos ya presentes en el PERFIL, nunca contenido nuevo.
- experience y skills deben representar la misma información que el PERFIL
  original (mismas empresas, cargos, tecnologías); no agregues ni quites hechos,
  solo reordena o reformula.
- Todo texto reescrito se rige por la VOZ HUMANA de abajo: headline y summary no
  pueden caer en muletillas de reclutamiento; cada reformulación se ancla en un
  hecho concreto del PERFIL relevante a esta vacante, no en adjetivos.
PROMPT;

    private ?string $lastError = null;

    private static function systemPrompt(): string
    {
        return self::BASE_PROMPT."\n\n".PromptCraft::humanVoice()."\n\n".PromptCraft::authenticityGuard();
    }

    public function __construct(private readonly AIProviderFactory $factory) {}

    /**
     * @param  array<int, string>  $selectedItems
     */
    public function tailor(Profile $profile, Job $job, array $selectedItems): CvTailorResult
    {
        $provider = $this->factory->make();

        $result = $this->attempt($provider, $profile, $job, $selectedItems)
            ?? $this->attempt($provider, $profile, $job, $selectedItems);

        if ($result === null) {
            throw new RuntimeException($this->lastError ?? 'AI CV tailoring failed.');
        }

        return $result;
    }

    /**
     * @param  array<int, string>  $selectedItems
     */
    private function attempt(AIProvider $provider, Profile $profile, Job $job, array $selectedItems): ?CvTailorResult
    {
        try {
            $completion = $provider->complete(self::systemPrompt(), $this->userPrompt($profile, $job, $selectedItems));

            return $this->parseResponse($completion->text, $completion->usage, $completion->model);
        } catch (Throwable $exception) {
            $this->lastError = $exception->getMessage();
            Log::warning("AI CV tailoring attempt failed for profile [{$profile->id}] / job [{$job->id}].", [
                'exception' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @param  array<int, string>  $selectedItems
     */
    private function userPrompt(Profile $profile, Job $job, array $selectedItems): string
    {
        $items = implode("\n", array_map(fn (string $item) => "- {$item}", $selectedItems));
        $experience = implode("\n", array_map(fn (string $line) => "- {$line}", $profile->experience ?? []));
        $skills = implode("\n", array_map(fn (string $line) => "- {$line}", $profile->skills ?? []));

        return "--- PERFIL ACTUAL ---\n"
            ."Headline: {$profile->headline}\n"
            ."Summary: {$profile->summary}\n"
            ."Experience:\n{$experience}\n"
            ."Skills:\n{$skills}\n\n"
            ."--- VACANTE ---\n{$job->title} en {$job->company}\n{$job->description}\n\n"
            ."--- AJUSTES SELECCIONADOS ---\n{$items}";
    }

    public function parseResponse(string $raw, AiUsage $usage, string $model): CvTailorResult
    {
        $clean = trim($raw);
        $clean = preg_replace('/^```(?:json)?\s*/i', '', $clean) ?? $clean;
        $clean = preg_replace('/\s*```$/', '', $clean) ?? $clean;
        $clean = trim($clean);

        $decoded = json_decode($clean, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('AI response must be a JSON object.');
        }

        foreach (['headline', 'summary'] as $key) {
            if (! isset($decoded[$key]) || ! is_string($decoded[$key])) {
                throw new RuntimeException("AI response is missing required string key [{$key}].");
            }
        }

        foreach (['experience', 'skills'] as $key) {
            if (! isset($decoded[$key]) || ! is_array($decoded[$key]) || ! array_all($decoded[$key], fn ($item) => is_string($item))) {
                throw new RuntimeException("AI response key [{$key}] must be an array of strings.");
            }
        }

        return new CvTailorResult(
            $decoded['headline'],
            $decoded['summary'],
            array_values($decoded['experience']),
            array_values($decoded['skills']),
            $usage,
            $model,
        );
    }
}
