<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\DTOs\ProfileReviewResult;
use App\Models\Profile;
use App\Services\Profile\ProfileVariantService;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Compares a profile's original CV text against its deterministically-parsed
 * fields and proposes discrete, user-approvable suggestions. Nothing here ever
 * writes to the Profile model on its own — {@see review()} only returns
 * suggestions, and {@see applySuggestions()} deterministically applies only the
 * subset the user approved, so the AI can never reintroduce rejected content or
 * hallucinate during application.
 */
class ProfileReviewer
{
    private const BASE_PROMPT = <<<'PROMPT'
Eres un editor experto de CVs para desarrolladores de software en Latinoamérica.
Recibirás el CV ORIGINAL (texto crudo extraído del archivo) y el PERFIL PARSEADO
(Markdown generado automáticamente por un parser determinista basado en reglas).

Tu tarea es comparar ambos y proponer sugerencias puntuales, cada una aprobable
por separado por el usuario. Responde ÚNICAMENTE con un objeto JSON válido, sin
markdown, sin backticks, sin texto adicional, con exactamente este esquema:

{
  "suggestions": [
    {
      "category": "<correction|improvement>",
      "field": "<headline|summary|english_level|experience|skills|education|certifications|languages>",
      "action": "<replace|add|remove>",
      "index": <entero o null>,
      "current": "<valor actual o null>",
      "suggested": "<valor propuesto, o null solo si action=remove>",
      "rationale": "<1 frase explicando por qué>"
    }
  ]
}

Reglas:
- category="correction": el PERFIL PARSEADO no refleja fielmente el CV ORIGINAL
  (contenido omitido, cortado, mal ubicado o mal transcrito por el parser).
- category="improvement": el contenido está bien parseado pero se puede redactar
  mejor o cuantificar más el impacto; nunca inventes experiencia, títulos,
  empresas o fechas que no estén en el CV ORIGINAL.
- "field": para "experience", "skills", "education", "certifications" y
  "languages" (idiomas declarados, no el nivel de inglés), cada elemento es una
  línea del array parseado; usa "index" (posición 0-based) para "replace"/"remove"
  y "index": null para "add" (se agrega al final). Para "headline", "summary" y
  "english_level" (nivel CEFR exacto: A1|A2|B1|B2|C1|C2, nunca uses otra escala),
  "index" siempre es null y "action" siempre es "replace".
- Si no encuentras nada que corregir o mejorar, responde con "suggestions": [].
- En las sugerencias category="improvement", el texto de "suggested" se rige por la
  VOZ HUMANA de abajo: nada de muletillas, cada reformulación anclada en un hecho ya
  presente en el CV ORIGINAL. Las de category="correction" solo restauran fielmente
  lo que el parser omitió o transcribió mal, sin aplicar esta voz.
PROMPT;

    private static function systemPrompt(): string
    {
        return self::BASE_PROMPT."\n\n".PromptCraft::humanVoice();
    }

    /**
     * @var array<int, string>
     */
    private const SCALAR_FIELDS = ['headline', 'summary', 'english_level'];

    /**
     * @var array<int, string>
     */
    private const ARRAY_FIELDS = ['experience', 'skills', 'education', 'certifications', 'languages'];

    /**
     * @var array<int, string>
     */
    private const CATEGORIES = ['correction', 'improvement'];

    /**
     * @var array<int, string>
     */
    private const ACTIONS = ['replace', 'add', 'remove'];

    /**
     * @var array<int, string>
     */
    private const ENGLISH_LEVELS = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];

    private ?string $lastError = null;

    public function __construct(
        private readonly AIProviderFactory $factory,
        private readonly ProfileVariantService $variants,
    ) {}

    public function review(Profile $profile): ProfileReviewResult
    {
        if ($profile->source_text === null) {
            throw new RuntimeException(
                'This profile has no stored source text. Re-import the CV to enable AI review.',
            );
        }

        $provider = $this->factory->make();

        $result = $this->attempt($provider, $profile)
            ?? $this->attempt($provider, $profile);

        if ($result === null) {
            throw new RuntimeException($this->lastError ?? 'AI review failed.');
        }

        return $result;
    }

    /**
     * Deterministically applies only the approved suggestions and regenerates
     * `raw_md` (and `perfil.md` if active) via {@see ProfileVariantService::regenerateMarkdown()}.
     * No AI call happens here.
     *
     * @param  array<int, array{field: string, action: string, index: int|null, suggested: string|null}>  $approved
     */
    public function applySuggestions(Profile $profile, array $approved): Profile
    {
        $languages = $profile->languages ?? ['items' => [], 'english_level' => null];

        $arrayFields = [
            'experience' => $profile->experience ?? [],
            'skills' => $profile->skills ?? [],
            'education' => $profile->education ?? [],
            'certifications' => $profile->certifications ?? [],
            'languages' => $languages['items'],
        ];

        $scalarUpdates = [];

        $byField = [];
        foreach ($approved as $suggestion) {
            $byField[$suggestion['field']][] = $suggestion;
        }

        foreach ($byField as $field => $suggestions) {
            if ($field === 'headline' || $field === 'summary') {
                $scalarUpdates[$field] = end($suggestions)['suggested'];

                continue;
            }

            if ($field === 'english_level') {
                $languages['english_level'] = end($suggestions)['suggested'];

                continue;
            }

            if (array_key_exists($field, $arrayFields)) {
                $arrayFields[$field] = $this->applyArraySuggestions($arrayFields[$field], $suggestions);
            }
        }

        $languages['items'] = $arrayFields['languages'];
        unset($arrayFields['languages']);

        $profile->update([...$scalarUpdates, ...$arrayFields, 'languages' => $languages]);

        return $this->variants->regenerateMarkdown($profile);
    }

    private function attempt(AIProvider $provider, Profile $profile): ?ProfileReviewResult
    {
        try {
            $completion = $provider->complete(self::systemPrompt(), $this->userPrompt($profile));

            return new ProfileReviewResult(
                $this->parseReviewResponse($completion->text),
                $completion->usage,
                $completion->model,
            );
        } catch (Throwable $exception) {
            $this->lastError = $exception->getMessage();
            Log::warning("AI profile review attempt failed for profile [{$profile->id}].", [
                'exception' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    private function userPrompt(Profile $profile): string
    {
        return "--- CV ORIGINAL ---\n{$profile->source_text}\n\n"
            ."--- PERFIL PARSEADO ---\n{$profile->raw_md}";
    }

    /**
     * @return array<int, array{
     *     id: string,
     *     category: string,
     *     field: string,
     *     action: string,
     *     index: int|null,
     *     current: string|null,
     *     suggested: string|null,
     *     rationale: string,
     * }>
     */
    public function parseReviewResponse(string $raw): array
    {
        $clean = trim($raw);
        $clean = preg_replace('/^```(?:json)?\s*/i', '', $clean) ?? $clean;
        $clean = preg_replace('/\s*```$/', '', $clean) ?? $clean;
        $clean = trim($clean);

        $decoded = json_decode($clean, true);

        if (! is_array($decoded) || ! isset($decoded['suggestions']) || ! is_array($decoded['suggestions'])) {
            throw new RuntimeException('AI response must be a JSON object with a "suggestions" array.');
        }

        $suggestions = [];

        foreach (array_values($decoded['suggestions']) as $i => $item) {
            $suggestions[] = $this->validateSuggestion($item, $i);
        }

        return $suggestions;
    }

    /**
     * @return array{
     *     id: string,
     *     category: string,
     *     field: string,
     *     action: string,
     *     index: int|null,
     *     current: string|null,
     *     suggested: string|null,
     *     rationale: string,
     * }
     */
    private function validateSuggestion(mixed $item, int $i): array
    {
        if (! is_array($item)) {
            throw new RuntimeException("Suggestion [{$i}] must be an object.");
        }

        foreach (['category', 'field', 'action', 'rationale'] as $key) {
            if (! isset($item[$key]) || ! is_string($item[$key])) {
                throw new RuntimeException("Suggestion [{$i}] is missing required string key [{$key}].");
            }
        }

        if (! in_array($item['category'], self::CATEGORIES, true)) {
            throw new RuntimeException("Suggestion [{$i}] has an invalid category [{$item['category']}].");
        }

        $isArrayField = in_array($item['field'], self::ARRAY_FIELDS, true);

        if (! $isArrayField && ! in_array($item['field'], self::SCALAR_FIELDS, true)) {
            throw new RuntimeException("Suggestion [{$i}] has an invalid field [{$item['field']}].");
        }

        if (! in_array($item['action'], self::ACTIONS, true)) {
            throw new RuntimeException("Suggestion [{$i}] has an invalid action [{$item['action']}].");
        }

        $index = $item['index'] ?? null;

        if ($index !== null && ! is_int($index)) {
            throw new RuntimeException("Suggestion [{$i}] key [index] must be an integer or null.");
        }

        if (! $isArrayField && $index !== null) {
            throw new RuntimeException("Suggestion [{$i}] targets scalar field [{$item['field']}] but supplies an index.");
        }

        if ($isArrayField && $item['action'] !== 'add' && $index === null) {
            throw new RuntimeException("Suggestion [{$i}] action [{$item['action']}] on array field [{$item['field']}] requires an index.");
        }

        $current = $item['current'] ?? null;

        if ($current !== null && ! is_string($current)) {
            throw new RuntimeException("Suggestion [{$i}] key [current] must be a string or null.");
        }

        $suggested = $item['suggested'] ?? null;

        if ($item['action'] !== 'remove' && ! is_string($suggested)) {
            throw new RuntimeException("Suggestion [{$i}] action [{$item['action']}] requires a string [suggested] value.");
        }

        if ($suggested !== null && ! is_string($suggested)) {
            throw new RuntimeException("Suggestion [{$i}] key [suggested] must be a string or null.");
        }

        if ($item['field'] === 'english_level' && $suggested !== null && ! in_array($suggested, self::ENGLISH_LEVELS, true)) {
            throw new RuntimeException("Suggestion [{$i}] key [suggested] for field [english_level] must be a CEFR code (A1-C2).");
        }

        return [
            'id' => 'sugg-'.($i + 1),
            'category' => $item['category'],
            'field' => $item['field'],
            'action' => $item['action'],
            'index' => $index,
            'current' => $current,
            'suggested' => $suggested,
            'rationale' => $item['rationale'],
        ];
    }

    /**
     * @param  array<int, string>  $items
     * @param  array<int, array{action: string, index: int|null, suggested: string|null}>  $suggestions
     * @return array<int, string>
     */
    private function applyArraySuggestions(array $items, array $suggestions): array
    {
        foreach ($suggestions as $suggestion) {
            if ($suggestion['action'] === 'replace' && array_key_exists($suggestion['index'], $items)) {
                $items[$suggestion['index']] = $suggestion['suggested'];
            }
        }

        $removeIndexes = array_map(
            fn (array $s) => $s['index'],
            array_filter($suggestions, fn (array $s) => $s['action'] === 'remove'),
        );
        rsort($removeIndexes);

        foreach ($removeIndexes as $index) {
            if (array_key_exists($index, $items)) {
                array_splice($items, $index, 1);
            }
        }

        foreach ($suggestions as $suggestion) {
            if ($suggestion['action'] === 'add') {
                $items[] = $suggestion['suggested'];
            }
        }

        return array_values($items);
    }
}
