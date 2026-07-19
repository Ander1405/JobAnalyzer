<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\DTOs\AiAnalysisResult;
use App\Models\Job;

interface AIProvider
{
    public function analyze(string $perfilMd, Job $job): AiAnalysisResult;
}
