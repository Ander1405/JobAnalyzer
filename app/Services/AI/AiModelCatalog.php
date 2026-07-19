<?php

declare(strict_types=1);

namespace App\Services\AI;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class AiModelCatalog
{
    /**
     * @var array<int, string>
     */
    private const CLAUDE_CLI_MODELS = [
        'default', 'best', 'opusplan',
        'sonnet', 'opus', 'haiku', 'fable',
        'claude-sonnet-5', 'claude-opus-4-8', 'claude-haiku-4-5-20251001', 'claude-fable-5',
    ];

    /**
     * @var array<int, string>
     */
    private const GEMINI_EXCLUDE_KEYWORDS = [
        'tts', 'image', 'robotics', 'computer-use', 'lyria', 'banana', 'antigravity', 'deep-research',
    ];

    /**
     * @return array<int, array{id: string, label: string}>
     */
    public function providers(): array
    {
        return [
            ['id' => 'claude_cli', 'label' => 'Claude CLI ($0, local session)'],
            ['id' => 'gemini', 'label' => 'Gemini'],
            ['id' => 'openrouter', 'label' => 'OpenRouter'],
        ];
    }

    /**
     * @return array<int, array{id: string, label: string, free: bool, promptPrice: float|null, completionPrice: float|null}>
     */
    public function modelsFor(string $provider): array
    {
        return match ($provider) {
            'claude_cli' => $this->claudeCliModels(),
            'gemini' => Cache::remember('ai_models:gemini', 600, fn () => $this->geminiModels()),
            'openrouter' => Cache::remember('ai_models:openrouter', 600, fn () => $this->openRouterModels()),
            default => throw new InvalidArgumentException("Unknown AI provider [{$provider}]."),
        };
    }

    /**
     * @return array<int, array{id: string, label: string, free: bool, promptPrice: float|null, completionPrice: float|null}>
     */
    private function claudeCliModels(): array
    {
        return collect(self::CLAUDE_CLI_MODELS)
            ->map(fn (string $id) => [
                'id' => $id,
                'label' => $id,
                'free' => true,
                'promptPrice' => null,
                'completionPrice' => null,
            ])
            ->all();
    }

    /**
     * @return array<int, array{id: string, label: string, free: bool, promptPrice: float|null, completionPrice: float|null}>
     */
    private function geminiModels(): array
    {
        $response = Http::get('https://generativelanguage.googleapis.com/v1beta/models', [
            'key' => config('jobhunter.gemini.api_key'),
        ]);

        $response->throw();

        /** @var array<int, array<string, mixed>> $models */
        $models = $response->json('models', []);

        return collect($models)
            ->filter(fn (array $model) => in_array('generateContent', $model['supportedGenerationMethods'] ?? [], true))
            ->filter(fn (array $model) => ! collect(self::GEMINI_EXCLUDE_KEYWORDS)->contains(
                fn (string $keyword) => str_contains(strtolower((string) $model['name']), $keyword)
            ))
            ->map(fn (array $model) => [
                'id' => str_replace('models/', '', (string) $model['name']),
                'label' => (string) ($model['displayName'] ?? $model['name']),
                'free' => true,
                'promptPrice' => null,
                'completionPrice' => null,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id: string, label: string, free: bool, promptPrice: float|null, completionPrice: float|null}>
     */
    private function openRouterModels(): array
    {
        $response = Http::get('https://openrouter.ai/api/v1/models');
        $response->throw();

        /** @var array<int, array<string, mixed>> $models */
        $models = $response->json('data', []);

        return collect($models)
            ->map(function (array $model) {
                $id = (string) $model['id'];
                $promptPrice = isset($model['pricing']['prompt']) ? (float) $model['pricing']['prompt'] : null;
                $completionPrice = isset($model['pricing']['completion']) ? (float) $model['pricing']['completion'] : null;

                return [
                    'id' => $id,
                    'label' => (string) ($model['name'] ?? $id),
                    'free' => str_ends_with($id, ':free') || ($promptPrice === 0.0 && $completionPrice === 0.0),
                    'promptPrice' => $promptPrice,
                    'completionPrice' => $completionPrice,
                ];
            })
            ->sortByDesc('free')
            ->values()
            ->all();
    }
}
