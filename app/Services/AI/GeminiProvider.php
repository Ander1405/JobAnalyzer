<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\DTOs\AiAnalysisResult;
use App\DTOs\AiUsage;
use App\Models\Job;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiProvider implements AIProvider
{
    private const ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/models';

    public function __construct(private readonly ?string $model = null) {}

    public function analyze(string $perfilMd, Job $job): AiAnalysisResult
    {
        $model = $this->model ?? (string) config('jobhunter.gemini.model');
        $apiKey = (string) config('jobhunter.gemini.api_key');

        $startedAt = microtime(true);

        $response = Http::timeout(120)
            ->post(self::ENDPOINT."/{$model}:generateContent?key={$apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => JobAnalyzer::buildPrompt($perfilMd, $job)],
                        ],
                    ],
                ],
            ]);

        $response->throw();

        $text = $response->json('candidates.0.content.parts.0.text');

        if (! is_string($text)) {
            throw new RuntimeException('Unexpected Gemini response format: missing candidate text.');
        }

        $analysis = JobAnalyzer::parseAiResponse($text);

        $usage = new AiUsage(
            durationMs: (int) round((microtime(true) - $startedAt) * 1000),
            inputTokens: $response->json('usageMetadata.promptTokenCount'),
            outputTokens: $response->json('usageMetadata.candidatesTokenCount'),
            costUsd: null,
        );

        return new AiAnalysisResult($analysis, $usage, $model);
    }
}
