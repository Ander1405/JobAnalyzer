<?php

declare(strict_types=1);

namespace App\DTOs;

readonly class AiCompletionResult
{
    public function __construct(
        public string $text,
        public AiUsage $usage,
        public string $model,
    ) {}
}
