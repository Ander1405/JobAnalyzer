<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\Job;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenRouterProvider implements AIProvider
{
    private const ENDPOINT = 'https://openrouter.ai/api/v1/chat/completions';

    public function analyze(string $perfilMd, Job $job): array
    {
        $response = Http::withToken((string) config('jobhunter.openrouter.api_key'))
            ->timeout(120)
            ->post(self::ENDPOINT, [
                'model' => config('jobhunter.openrouter.model'),
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

        return JobAnalyzer::parseAiResponse($text);
    }
}
