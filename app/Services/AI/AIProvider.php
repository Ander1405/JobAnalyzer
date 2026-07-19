<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\DTOs\AiAnalysisResult;
use App\DTOs\AiCompletionResult;
use App\Models\Job;

interface AIProvider
{
    public function analyze(string $perfilMd, Job $job): AiAnalysisResult;

    public function complete(string $systemPrompt, string $userPrompt): AiCompletionResult;
}
