<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\DTOs\AiAnalysisResult;
use App\DTOs\AiUsage;
use App\Models\Job;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenRouterProvider implements AIProvider
{
    private const ENDPOINT = 'https://openrouter.ai/api/v1/chat/completions';

    public function __construct(private readonly ?string $model = null) {}

    public function analyze(string $perfilMd, Job $job): AiAnalysisResult
    {
        $model = $this->model ?? config('jobhunter.openrouter.model');

        $startedAt = microtime(true);

        $response = Http::withToken((string) config('jobhunter.openrouter.api_key'))
            ->timeout(120)
            ->post(self::ENDPOINT, [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => JobAnalyzer::systemPrompt()],
                    ['role' => 'user', 'content' => JobAnalyzer::userPrompt($perfilMd, $job)],
                ],
            ]);

        $response->throw();

        $text = $response->json('choices.0.message.content');

        if (! is_string($text)) {
            throw new RuntimeException('Unexpected OpenRouter response format: missing message content.');
        }

        $analysis = JobAnalyzer::parseAiResponse($text);

        $usage = new AiUsage(
            durationMs: (int) round((microtime(true) - $startedAt) * 1000),
            inputTokens: $response->json('usage.prompt_tokens'),
            outputTokens: $response->json('usage.completion_tokens'),
            costUsd: $response->json('usage.cost') !== null ? (float) $response->json('usage.cost') : null,
        );

        return new AiAnalysisResult($analysis, $usage, (string) $model);
    }
}
