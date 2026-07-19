<?php

declare(strict_types=1);

namespace App\Services\AI;

use InvalidArgumentException;

class AIProviderFactory
{
    public function make(): AIProvider
    {
        $provider = (string) config('jobhunter.ai_provider');

        return match ($provider) {
            'claude_cli' => app(ClaudeCliProvider::class),
            'gemini' => app(GeminiProvider::class),
            'openrouter' => app(OpenRouterProvider::class),
            default => throw new InvalidArgumentException("Unknown AI_PROVIDER [{$provider}]."),
        };
    }
}
