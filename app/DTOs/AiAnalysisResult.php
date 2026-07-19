<?php

declare(strict_types=1);

namespace App\DTOs;

readonly class AiAnalysisResult
{
    /**
     * @param  array<string, mixed>  $analysis
     */
    public function __construct(
        public array $analysis,
        public AiUsage $usage,
        public string $model,
    ) {}
}
