<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\AiSetting;
use InvalidArgumentException;

class AIProviderFactory
{
    public function make(): AIProvider
    {
        $setting = AiSetting::current();

        return match ($setting->provider) {
            'claude_cli' => new ClaudeCliProvider($setting->model),
            'gemini' => new GeminiProvider($setting->model),
            'openrouter' => new OpenRouterProvider($setting->model),
            default => throw new InvalidArgumentException("Unknown AI provider [{$setting->provider}]."),
        };
    }
}
