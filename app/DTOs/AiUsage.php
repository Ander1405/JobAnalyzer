<?php

declare(strict_types=1);

namespace App\DTOs;

readonly class AiUsage
{
    public function __construct(
        public int $durationMs,
        public ?int $inputTokens = null,
        public ?int $outputTokens = null,
        public ?float $costUsd = null,
    ) {}
}
