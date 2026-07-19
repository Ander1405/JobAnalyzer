<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\Job;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiProvider implements AIProvider
{
    private const ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/models';

    public function analyze(string $perfilMd, Job $job): array
    {
        $model = (string) config('jobhunter.gemini.model');
        $apiKey = (string) config('jobhunter.gemini.api_key');

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

        return JobAnalyzer::parseAiResponse($text);
    }
}
